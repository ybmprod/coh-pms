<?php
declare(strict_types=1);

// CHAPTER 5.3.5 - Payment Recording and Verification
class PaymentService
{
    public function submit(int $bookingId, int $customerId, array $input): int
    {
        $method = trim((string) ($input['payment_method'] ?? ''));
        $reference = trim((string) ($input['transaction_reference'] ?? ''));
        if (!in_array($method, ['Cash', 'Bank Transfer', 'Mobile Money', 'Card'], true)) {
            throw new DomainException('Payment method is invalid.');
        }
        if ($reference === '' || mb_strlen($reference) > 100) {
            throw new DomainException('Transaction reference is required and must be no more than 100 characters.');
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        try {
            $booking = Booking::findByIdForUpdate($bookingId, $pdo);
            if (!$booking || (int) $booking['customer_id'] !== $customerId) {
                throw new DomainException('Booking not found for your account.');
            }
            if ($booking['booking_status'] !== BOOKING_STATUS_APPROVED) {
                throw new DomainException('Payment can only be submitted for an approved booking.');
            }

            $existingPayment = Payment::findLatestByBookingId($bookingId, $pdo, true);
            if ($existingPayment && in_array($existingPayment['payment_status'], [
                PAYMENT_STATUS_PENDING_VERIFICATION,
                PAYMENT_STATUS_VERIFIED,
            ], true)) {
                throw new DomainException('A payment is already pending verification or has been verified.');
            }

            $paymentId = Payment::create([
                'booking_id' => $bookingId,
                'amount_paid' => (float) $booking['total_charge'],
                'payment_method' => $method,
                'transaction_reference' => $reference,
                'payment_status' => PAYMENT_STATUS_PENDING_VERIFICATION,
            ], $pdo);
            $pdo->commit();

            return $paymentId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function verify(int $paymentId, int $actorId, string $actorRole): void
    {
        $this->setDecision($paymentId, PAYMENT_STATUS_VERIFIED, $actorId, $actorRole);
    }

    public function reject(int $paymentId, int $actorId, string $actorRole): void
    {
        $this->setDecision($paymentId, PAYMENT_STATUS_REJECTED, $actorId, $actorRole);
    }

    private function setDecision(int $paymentId, string $status, int $actorId, string $actorRole): void
    {
        if (!in_array($actorRole, [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER], true)) {
            throw new DomainException('You are not allowed to verify or reject payments.');
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        try {
            $payment = Payment::findByIdForUpdate($paymentId, $pdo);
            if (!$payment) {
                throw new DomainException('Payment record not found.');
            }
            if ($payment['payment_status'] !== PAYMENT_STATUS_PENDING_VERIFICATION) {
                throw new DomainException('Only a payment pending verification can be updated.');
            }

            $booking = Booking::findByIdForUpdate((int) $payment['booking_id'], $pdo);
            if (!$booking) {
                throw new DomainException('The payment booking could not be found.');
            }

            if ($status === PAYMENT_STATUS_VERIFIED) {
                if ($booking['booking_status'] !== BOOKING_STATUS_APPROVED) {
                    throw new DomainException('Only an approved booking can be confirmed by payment verification.');
                }
                $this->verifyPaymentWithReceipt($paymentId, $actorId, $pdo);
                (new BookingService())->changeStatus(
                    (int) $booking['booking_id'],
                    BOOKING_STATUS_CONFIRMED,
                    $actorId,
                    $actorRole,
                    true,
                    $pdo
                );
            } else {
                if (!Payment::updateStatus($paymentId, PAYMENT_STATUS_REJECTED, $actorId, null, $pdo)) {
                    throw new RuntimeException('Payment rejection could not be saved.');
                }
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function verifyPaymentWithReceipt(int $paymentId, int $actorId, PDO $pdo): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $receiptNumber = $this->nextReceiptNumber($pdo);
            try {
                if (!Payment::updateStatus($paymentId, PAYMENT_STATUS_VERIFIED, $actorId, $receiptNumber, $pdo)) {
                    throw new RuntimeException('Payment verification could not be saved.');
                }
                return;
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062 || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('A unique receipt number could not be generated.');
    }

    private function nextReceiptNumber(PDO $pdo): string
    {
        $prefix = 'RCT-' . date('Ymd') . '-';
        $stmt = $pdo->prepare('SELECT receipt_number FROM payments WHERE receipt_number LIKE :receipt_prefix ORDER BY receipt_number DESC LIMIT 1 FOR UPDATE');
        $stmt->execute([':receipt_prefix' => $prefix . '%']);
        $lastReceipt = $stmt->fetchColumn();
        $sequence = $lastReceipt === false ? 1 : ((int) substr((string) $lastReceipt, -4)) + 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}