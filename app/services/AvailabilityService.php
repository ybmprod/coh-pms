<?php
declare(strict_types=1);

// CHAPTER 5.3.3 - Booking and Availability Management
class AvailabilityService
{
    public function check(
        int $venueId,
        string $date,
        string $start,
        string $end,
        int $excludeBookingId = 0,
        ?PDO $pdo = null
    ): array {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            return ['available' => false, 'message' => 'Booking date must be a valid date.'];
        }
        if ($date < date('Y-m-d')) {
            return ['available' => false, 'message' => 'Booking date cannot be in the past.'];
        }
        if (!$this->isTime($start) || !$this->isTime($end)) {
            return ['available' => false, 'message' => 'Start and end times must use the HH:MM format.'];
        }
        if ($end <= $start) {
            return ['available' => false, 'message' => 'End time must be later than start time.'];
        }

        $forUpdate = $pdo !== null && $pdo->inTransaction();
        $venue = $forUpdate
            ? Venue::findByIdForUpdate($venueId, $pdo)
            : Venue::findById($venueId);
        if (!$venue || $venue['venue_status'] !== 'Available') {
            return ['available' => false, 'message' => 'This venue is not currently available.'];
        }

        $conflicts = Booking::getVenueConflicts($venueId, $date, $start, $end, $excludeBookingId, $pdo, $forUpdate);
        if ($conflicts !== []) {
            return ['available' => false, 'message' => 'This time slot overlaps with an existing booking.'];
        }

        return ['available' => true, 'message' => 'This time slot is available.'];
    }

    private function isTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }
}