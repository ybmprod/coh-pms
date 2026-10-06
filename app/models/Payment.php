<?php
declare(strict_types=1);

// CHAPTER 5.3.5 - Payment Recording and Verification
class Payment
{
    public static function getPending(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT p.*, b.booking_reference, b.total_charge, b.customer_id, u.full_name AS customer_name, v.venue_name
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN users u ON u.user_id = b.customer_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE p.payment_status = :status
                ORDER BY p.payment_date ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => PAYMENT_STATUS_PENDING_VERIFICATION]);

        return $stmt->fetchAll();
    }

    public static function findByBookingId(int $bookingId): ?array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM payments WHERE booking_id = :booking_id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':booking_id' => $bookingId]);

        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    public static function findById(int $paymentId): ?array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE payment_id = :payment_id LIMIT 1');
        $stmt->execute([':payment_id' => $paymentId]);

        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    public static function findByIdForUpdate(int $paymentId, PDO $pdo): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE payment_id = :payment_id LIMIT 1 FOR UPDATE');
        $stmt->execute([':payment_id' => $paymentId]);

        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    public static function findLatestByBookingId(int $bookingId, ?PDO $pdo = null, bool $forUpdate = false): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'SELECT * FROM payments WHERE booking_id = :booking_id ORDER BY payment_id DESC LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':booking_id' => $bookingId]);

        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    public static function getByBooking(int $bookingId): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE booking_id = :booking_id ORDER BY payment_id DESC');
        $stmt->execute([':booking_id' => $bookingId]);

        return $stmt->fetchAll();
    }

    public static function getByStatus(?string $status = null): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT p.*, b.booking_reference, b.total_charge, b.customer_id, u.full_name AS customer_name, v.venue_name
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN users u ON u.user_id = b.customer_id
                JOIN venues v ON v.venue_id = b.venue_id';
        if ($status !== null) {
            $sql .= ' WHERE p.payment_status = :status';
        }
        $sql .= ' ORDER BY p.payment_date DESC, p.payment_id DESC';
        $stmt = $pdo->prepare($sql);
        if ($status !== null) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    public static function getReceiptByBookingId(int $bookingId): ?array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT p.*, b.booking_reference, b.customer_id, b.venue_id, b.booking_date,
                       b.start_time, b.end_time, b.standard_charge, b.adjustment_amount,
                       b.total_charge, c.full_name AS customer_name, v.venue_name,
                       pr.rule_name, verifier.full_name AS verified_by_name
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN users c ON c.user_id = b.customer_id
                JOIN venues v ON v.venue_id = b.venue_id
                LEFT JOIN pricing_rules pr ON pr.pricing_rule_id = b.pricing_rule_id
                LEFT JOIN users verifier ON verifier.user_id = p.verified_by
                WHERE b.booking_id = :booking_id
                ORDER BY p.payment_id DESC
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':booking_id' => $bookingId]);

        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    public static function create(array $data, ?PDO $pdo = null): int
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'INSERT INTO payments (
                    booking_id,
                    amount_paid,
                    payment_method,
                    transaction_reference,
                    payment_date,
                    payment_status,
                    verified_by,
                    verification_date,
                    receipt_number
                ) VALUES (
                    :booking_id,
                    :amount_paid,
                    :payment_method,
                    :transaction_reference,
                    NOW(),
                    :payment_status,
                    :verified_by,
                    :verification_date,
                    :receipt_number
                )';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':booking_id' => (int) $data['booking_id'],
            ':amount_paid' => (float) ($data['amount_paid'] ?? 0),
            ':payment_method' => $data['payment_method'],
            ':transaction_reference' => $data['transaction_reference'],
            ':payment_status' => $data['payment_status'] ?? PAYMENT_STATUS_PENDING_VERIFICATION,
            ':verified_by' => $data['verified_by'] ?? null,
            ':verification_date' => $data['verification_date'] ?? null,
            ':receipt_number' => $data['receipt_number'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(
        int $paymentId,
        string $status,
        ?int $verifiedBy = null,
        ?string $receiptNumber = null,
        ?PDO $pdo = null
    ): bool
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'UPDATE payments
                SET payment_status = :payment_status,
                    verified_by = :verified_by,
                    verification_date = NOW(),
                    receipt_number = COALESCE(:receipt_number, receipt_number)
                WHERE payment_id = :payment_id';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':payment_id' => $paymentId,
            ':payment_status' => $status,
            ':verified_by' => $verifiedBy,
            ':receipt_number' => $receiptNumber,
        ]);
    }
}
