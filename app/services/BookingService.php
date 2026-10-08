<?php
declare(strict_types=1);

// CHAPTER 5.3.3 - Booking and Availability Management
class BookingService
{
    public const TRANSITIONS = [
        BOOKING_STATUS_PENDING => [BOOKING_STATUS_APPROVED, BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED],
        BOOKING_STATUS_APPROVED => [BOOKING_STATUS_CONFIRMED, BOOKING_STATUS_CANCELLED],
        BOOKING_STATUS_CONFIRMED => [BOOKING_STATUS_COMPLETED, BOOKING_STATUS_CANCELLED],
    ];

    public function changeStatus(
        int $bookingId,
        string $newStatus,
        int $actorId,
        string $actorRole,
        bool $paymentVerification = false,
        ?PDO $pdo = null
    ): void {
        if (!$paymentVerification && !in_array($actorRole, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], true)) {
            throw new DomainException('You are not allowed to change booking status.');
        }
        if ($paymentVerification && !in_array($actorRole, [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER], true)) {
            throw new DomainException('You are not allowed to confirm this booking through payment verification.');
        }

        $booking = Booking::findById($bookingId, $pdo);
        if (!$booking) {
            throw new DomainException('Booking not found.');
        }
        if ($newStatus === BOOKING_STATUS_CONFIRMED && !$paymentVerification) {
            throw new DomainException('A booking can only be confirmed through payment verification.');
        }

        $allowed = self::TRANSITIONS[$booking['booking_status']] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            throw new DomainException('Cannot change booking status from ' . $booking['booking_status'] . ' to ' . $newStatus . '.');
        }
        if ($newStatus === BOOKING_STATUS_COMPLETED && $booking['booking_date'] >= date('Y-m-d')) {
            throw new DomainException('A booking can only be marked Completed after its date has passed.');
        }

        if (!Booking::updateStatus($bookingId, $newStatus, $actorId, $pdo)) {
            throw new RuntimeException('Booking status could not be updated.');
        }
    }

    public function cancelByCustomer(int $bookingId, int $customerId): void
    {
        $booking = Booking::findById($bookingId);
        if (!$booking || (int) $booking['customer_id'] !== $customerId) {
            throw new DomainException('Booking not found for your account.');
        }

        $status = $booking['booking_status'];
        if (!in_array($status, [BOOKING_STATUS_PENDING, BOOKING_STATUS_APPROVED], true)) {
            throw new DomainException('This booking can no longer be cancelled.');
        }

        $payment = Payment::findLatestByBookingId($bookingId);
        if ($status === BOOKING_STATUS_APPROVED && $payment && in_array($payment['payment_status'], [
            PAYMENT_STATUS_PENDING_VERIFICATION,
            PAYMENT_STATUS_VERIFIED,
        ], true)) {
            throw new DomainException('This booking cannot be cancelled while a payment is pending or verified.');
        }

        if (!Booking::updateStatus($bookingId, BOOKING_STATUS_CANCELLED)) {
            throw new RuntimeException('Booking could not be cancelled.');
        }
    }

    public function createBooking(array $input, int $customerId): array
    {
        $venueId = (int) ($input['venue_id'] ?? 0);
        $date = trim((string) ($input['booking_date'] ?? ''));
        $start = trim((string) ($input['start_time'] ?? ''));
        $end = trim((string) ($input['end_time'] ?? ''));
        $eventType = trim((string) ($input['event_type'] ?? ''));
        $attendees = (int) ($input['number_of_attendees'] ?? 0);

        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            throw new DomainException('Booking date must be a valid date.');
        }
        if ($date < date('Y-m-d')) {
            throw new DomainException('Booking date cannot be in the past.');
        }
        $this->assertTime($start);
        $this->assertTime($end);
        if ($end <= $start) {
            throw new DomainException('End time must be later than start time.');
        }
        if ($eventType === '' || mb_strlen($eventType) > 100) {
            throw new DomainException('Event type is required and must be no more than 100 characters.');
        }
        if ($attendees < 1) {
            throw new DomainException('Number of attendees must be greater than zero.');
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();
        try {
            $venue = Venue::findByIdForUpdate($venueId, $pdo);
            if (!$venue) {
                throw new DomainException('Please select a valid venue.');
            }
            if ($venue['venue_status'] !== 'Available') {
                throw new DomainException('This venue is not currently available.');
            }
            // Removed restriction: Customers should be able to book venues despite exceeding estimated value
            // if ($attendees > (int) $venue['capacity']) {
            //     throw new DomainException('Number of attendees exceeds the venue capacity.');
            // }

            $availability = (new AvailabilityService())->check($venueId, $date, $start, $end, 0, $pdo);
            if (!$availability['available']) {
                throw new DomainException($availability['message']);
            }

            $charge = (new PricingService())->calculate($venueId, $date, $start, $end);
            $bookingId = Booking::create([
                'booking_reference' => 'TMP-' . bin2hex(random_bytes(8)),
                'customer_id' => $customerId,
                'venue_id' => $venueId,
                'pricing_rule_id' => $charge['pricing_rule_id'],
                'booking_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'event_type' => $eventType,
                'number_of_attendees' => $attendees,
                'standard_charge' => $charge['standard_charge'],
                'adjustment_amount' => $charge['adjustment_amount'],
                'total_charge' => $charge['total_charge'],
                'booking_status' => BOOKING_STATUS_PENDING,
            ], $pdo);
            $this->assignBookingReference($bookingId, $date, $pdo);
            $booking = Booking::findById($bookingId, $pdo);
            $pdo->commit();

            return $booking ?? ['booking_id' => $bookingId, 'booking_reference' => null];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function assertTime(string $time): void
    {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            throw new DomainException('Time must use the HH:MM format.');
        }
    }

    public function allowedStaffActions(array $booking): array
    {
        if ($booking['booking_status'] === BOOKING_STATUS_PENDING) {
            return [BOOKING_STATUS_APPROVED, BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED];
        }
        if ($booking['booking_status'] === BOOKING_STATUS_APPROVED) {
            return [BOOKING_STATUS_CANCELLED];
        }
        if ($booking['booking_status'] === BOOKING_STATUS_CONFIRMED) {
            $actions = [BOOKING_STATUS_CANCELLED];
            if ($booking['booking_date'] < date('Y-m-d')) {
                $actions[] = BOOKING_STATUS_COMPLETED;
            }
            return $actions;
        }

        return [];
    }

    private function assignBookingReference(int $bookingId, string $bookingDate, PDO $pdo): void
    {
        $datePart = str_replace('-', '', $bookingDate);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $highestSequence = 0;
            foreach (Booking::getReferencesForDate($bookingDate, $bookingId, $pdo) as $reference) {
                if (preg_match('/^COH-' . preg_quote($datePart, '/') . '-(\d{4})$/', (string) $reference, $matches)) {
                    $highestSequence = max($highestSequence, (int) $matches[1]);
                }
            }

            $reference = 'COH-' . $datePart . '-' . str_pad((string) ($highestSequence + 1), 4, '0', STR_PAD_LEFT);
            try {
                if (!Booking::updateReference($bookingId, $reference, $pdo)) {
                    throw new RuntimeException('Booking reference could not be saved.');
                }
                return;
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062 || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('A unique booking reference could not be generated.');
    }
}