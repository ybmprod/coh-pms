<?php
declare(strict_types=1);

// CHAPTER 5.3.3 - Booking and Availability Management
class Booking
{
    public static function getAll(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.*, v.venue_name, v.location, c.full_name AS customer_name, r.full_name AS reviewer_name
                FROM bookings b
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                LEFT JOIN users c ON c.user_id = b.customer_id
                LEFT JOIN users r ON r.user_id = b.reviewed_by
                ORDER BY b.booking_date DESC, b.start_time ASC';
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    public static function getByCustomer(int $customerId): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.*, v.venue_name, v.location, r.full_name AS reviewer_name,
                   (SELECT p.payment_status FROM payments p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS payment_status
                FROM bookings b
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                LEFT JOIN users r ON r.user_id = b.reviewed_by
                WHERE b.customer_id = :customer_id
                ORDER BY b.booking_date DESC, b.start_time ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':customer_id' => $customerId]);

        return $stmt->fetchAll();
    }

    public static function findById(int $bookingId, ?PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'SELECT b.*, v.venue_name, v.location, c.full_name AS customer_name, r.full_name AS reviewer_name,
                   pr.rule_name,
                   (SELECT p.payment_status FROM payments p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS payment_status,
                   (SELECT p.receipt_number FROM payments p WHERE p.booking_id = b.booking_id ORDER BY p.payment_id DESC LIMIT 1) AS receipt_number
                FROM bookings b
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                LEFT JOIN users c ON c.user_id = b.customer_id
                LEFT JOIN users r ON r.user_id = b.reviewed_by
            LEFT JOIN pricing_rules pr ON pr.pricing_rule_id = b.pricing_rule_id
                WHERE b.booking_id = :booking_id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':booking_id' => $bookingId]);

        $booking = $stmt->fetch();
        return $booking ?: null;
    }

    public static function findByIdForUpdate(int $bookingId, PDO $pdo): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE booking_id = :booking_id LIMIT 1 FOR UPDATE');
        $stmt->execute([':booking_id' => $bookingId]);

        $booking = $stmt->fetch();
        return $booking ?: null;
    }

    public static function create(array $data, ?PDO $pdo = null): int
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'INSERT INTO bookings (
                    booking_reference,
                    customer_id,
                    venue_id,
                    pricing_rule_id,
                    booking_date,
                    start_time,
                    end_time,
                    event_type,
                    number_of_attendees,
                    standard_charge,
                    adjustment_amount,
                    total_charge,
                    booking_status,
                    reviewed_by,
                    created_at,
                    updated_at
                ) VALUES (
                    :booking_reference,
                    :customer_id,
                    :venue_id,
                    :pricing_rule_id,
                    :booking_date,
                    :start_time,
                    :end_time,
                    :event_type,
                    :number_of_attendees,
                    :standard_charge,
                    :adjustment_amount,
                    :total_charge,
                    :booking_status,
                    :reviewed_by,
                    NOW(),
                    NOW()
                )';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':booking_reference' => $data['booking_reference'],
            ':customer_id' => (int) $data['customer_id'],
            ':venue_id' => (int) $data['venue_id'],
            ':pricing_rule_id' => $data['pricing_rule_id'] !== null && $data['pricing_rule_id'] !== '' ? (int) $data['pricing_rule_id'] : null,
            ':booking_date' => $data['booking_date'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':event_type' => $data['event_type'] ?? null,
            ':number_of_attendees' => $data['number_of_attendees'] ?? null,
            ':standard_charge' => (float) ($data['standard_charge'] ?? 0),
            ':adjustment_amount' => (float) ($data['adjustment_amount'] ?? 0),
            ':total_charge' => (float) ($data['total_charge'] ?? 0),
            ':booking_status' => $data['booking_status'] ?? BOOKING_STATUS_PENDING,
            ':reviewed_by' => $data['reviewed_by'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $bookingId, string $status, ?int $reviewedBy = null, ?PDO $pdo = null): bool
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'UPDATE bookings
                SET booking_status = :booking_status,
                    reviewed_by = COALESCE(:reviewed_by, reviewed_by),
                    updated_at = NOW()
                WHERE booking_id = :booking_id';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':booking_id' => $bookingId,
            ':booking_status' => $status,
            ':reviewed_by' => $reviewedBy,
        ]);
    }

    public static function getVenueConflicts(
        int $venueId,
        string $bookingDate,
        string $startTime,
        string $endTime,
        int $excludeBookingId = 0,
        ?PDO $pdo = null,
        bool $forUpdate = false
    ): array
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'SELECT * FROM bookings
                WHERE venue_id = :venue_id
                  AND booking_date = :booking_date
                  AND booking_status IN (:pending, :approved, :confirmed)
                  AND (:exclude_booking_id = 0 OR booking_id != :exclude_booking_id_value)
                  AND start_time < :end_time
                  AND end_time > :start_time';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':venue_id' => $venueId,
            ':booking_date' => $bookingDate,
            ':pending' => BOOKING_STATUS_PENDING,
            ':approved' => BOOKING_STATUS_APPROVED,
            ':confirmed' => BOOKING_STATUS_CONFIRMED,
            ':end_time' => $endTime,
            ':start_time' => $startTime,
            ':exclude_booking_id' => $excludeBookingId,
            ':exclude_booking_id_value' => $excludeBookingId,
        ]);

        return $stmt->fetchAll();
    }

    public static function getReferencesForDate(string $bookingDate, int $excludeBookingId, PDO $pdo): array
    {
        $stmt = $pdo->prepare('SELECT booking_reference FROM bookings WHERE booking_date = :booking_date AND booking_id != :booking_id FOR UPDATE');
        $stmt->execute([
            ':booking_date' => $bookingDate,
            ':booking_id' => $excludeBookingId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function updateReference(int $bookingId, string $bookingReference, PDO $pdo): bool
    {
        $stmt = $pdo->prepare('UPDATE bookings SET booking_reference = :booking_reference, updated_at = NOW() WHERE booking_id = :booking_id');

        return $stmt->execute([
            ':booking_id' => $bookingId,
            ':booking_reference' => $bookingReference,
        ]);
    }

}
