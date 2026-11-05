<?php
declare(strict_types=1);

class EventType
{
    public const VENUE_TYPES = [
        'Community Hall',
        'Community Centre',
        'Stadium',
        'Open Space',
        'Other',
    ];

    /**
     * Attach the venue types where each event type can realistically be held.
     */
    private static function attachVenueTypes(array $eventTypes, ?PDO $pdo = null): array
    {
        if ($eventTypes === []) {
            return [];
        }

        $pdo = $pdo ?? get_db_connection();
        $ids = [];
        foreach ($eventTypes as $eventType) {
            $eventTypeId = (int) ($eventType['event_type_id'] ?? 0);
            if ($eventTypeId > 0) {
                $ids[$eventTypeId] = $eventTypeId;
            }
        }

        $map = [];
        if ($ids !== []) {
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT event_type_id, venue_type
                   FROM event_type_venue_types
                  WHERE event_type_id IN ({$placeholders})"
            );
            $stmt->execute(array_values($ids));

            foreach ($stmt->fetchAll() as $row) {
                $map[(int) $row['event_type_id']][] = (string) $row['venue_type'];
            }
        }

        foreach ($eventTypes as &$eventType) {
            $eventTypeId = (int) ($eventType['event_type_id'] ?? 0);
            $eventType['venue_types'] = array_values(array_intersect(
                self::VENUE_TYPES,
                $map[$eventTypeId] ?? []
            ));
        }
        unset($eventType);

        return $eventTypes;
    }

    public static function getAll(): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->query('SELECT * FROM event_types ORDER BY event_name ASC');

        return self::attachVenueTypes($stmt->fetchAll(), $pdo);
    }

    public static function getActive(): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->query("SELECT * FROM event_types WHERE status = 'Active' ORDER BY event_name ASC");

        return self::attachVenueTypes($stmt->fetchAll(), $pdo);
    }

    /**
     * Active usage means a booking that still needs council action or is already
     * confirmed. Completed, rejected and cancelled bookings are historical and
     * must never block an event type from being retired.
     */
    private static function activeBookingStatuses(): array
    {
        return [
            BOOKING_STATUS_PENDING,
            BOOKING_STATUS_APPROVED,
            BOOKING_STATUS_CONFIRMED,
        ];
    }

    public static function getAllWithUsage(): array
    {
        $pdo = get_db_connection();
        $statuses = self::activeBookingStatuses();
        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

        $sql = "SELECT et.*,
                       (SELECT COUNT(*)
                          FROM bookings b
                         WHERE b.event_type_id = et.event_type_id
                            OR (b.event_type_id IS NULL AND b.event_type = et.event_name)) AS booking_count,
                       (SELECT COUNT(*)
                          FROM bookings b
                         WHERE (b.event_type_id = et.event_type_id
                            OR (b.event_type_id IS NULL AND b.event_type = et.event_name))
                           AND b.booking_status IN ({$placeholders})) AS active_booking_count,
                       (SELECT MAX(b.booking_date)
                          FROM bookings b
                         WHERE b.event_type_id = et.event_type_id
                            OR (b.event_type_id IS NULL AND b.event_type = et.event_name)) AS last_booking_date
                  FROM event_types et
              ORDER BY et.event_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($statuses);

        return self::attachVenueTypes($stmt->fetchAll(), $pdo);
    }

    public static function getUsage(int $eventTypeId, ?string $eventName = null): array
    {
        $pdo = get_db_connection();
        $statuses = self::activeBookingStatuses();
        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

        $where = 'event_type_id = ?';
        $params = $statuses;
        $params[] = $eventTypeId;

        if ($eventName !== null && $eventName !== '') {
            $where = '(event_type_id = ? OR (event_type_id IS NULL AND event_type = ?))';
            $params[] = $eventName;
        }

        $sql = "SELECT COUNT(*) AS booking_count,
                       COALESCE(SUM(CASE WHEN booking_status IN ({$placeholders}) THEN 1 ELSE 0 END), 0) AS active_booking_count
                  FROM bookings
                 WHERE {$where}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch() ?: [];

        return [
            'booking_count' => (int) ($row['booking_count'] ?? 0),
            'active_booking_count' => (int) ($row['active_booking_count'] ?? 0),
        ];
    }

    public static function findById(int $eventTypeId, ?PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM event_types WHERE event_type_id = :id LIMIT 1');
        $stmt->execute([':id' => $eventTypeId]);

        $item = $stmt->fetch();
        if (!$item) {
            return null;
        }

        $items = self::attachVenueTypes([$item], $pdo);
        return $items[0];
    }

    public static function findByName(string $name, ?PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM event_types WHERE event_name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);

        $item = $stmt->fetch();
        if (!$item) {
            return null;
        }

        $items = self::attachVenueTypes([$item], $pdo);
        return $items[0];
    }

    public static function getVenueTypes(int $eventTypeId, ?PDO $pdo = null): array
    {
        $pdo = $pdo ?? get_db_connection();
        $stmt = $pdo->prepare(
            'SELECT venue_type FROM event_type_venue_types WHERE event_type_id = :id'
        );
        $stmt->execute([':id' => $eventTypeId]);

        return array_values(array_intersect(
            self::VENUE_TYPES,
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        ));
    }

    public static function isVenueCompatible(array $eventType, string $venueType): bool
    {
        $allowed = $eventType['venue_types'] ?? [];
        if ($allowed === []) {
            return true;
        }

        return in_array($venueType, $allowed, true);
    }

    public static function venueSuitabilityMessage(array $eventType, array $venue): string
    {
        $venueTypes = array_values(array_intersect(
            self::VENUE_TYPES,
            (array) ($eventType['venue_types'] ?? [])
        ));

        return sprintf(
            '%s cannot be held at %s (%s). Suitable venue types: %s.',
            (string) ($eventType['event_name'] ?? 'This event type'),
            (string) ($venue['venue_name'] ?? 'this venue'),
            (string) ($venue['venue_type'] ?? 'unknown'),
            self::formatVenueTypeList($venueTypes)
        );
    }

    public static function formatVenueTypeList(array $venueTypes): string
    {
        $venueTypes = array_values(array_filter(array_map('strval', $venueTypes), static fn(string $type): bool => $type !== ''));
        if ($venueTypes === []) {
            return 'none configured';
        }

        $last = array_pop($venueTypes);
        if ($venueTypes === []) {
            return $last;
        }

        return implode(', ', $venueTypes) . ' or ' . $last;
    }

    public static function create(array $data, ?PDO $pdo = null): int
    {
        $pdo = $pdo ?? get_db_connection();
        $venueTypes = array_key_exists('venue_types', $data)
            ? (array) $data['venue_types']
            : self::VENUE_TYPES;
        $ownsTransaction = !$pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $sql = 'INSERT INTO event_types (
                        event_name,
                        charge_type,
                        charge_value,
                        status,
                        description,
                        created_at,
                        updated_at
                    ) VALUES (
                        :event_name,
                        :charge_type,
                        :charge_value,
                        :status,
                        :description,
                        NOW(),
                        NOW()
                    )';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':event_name' => trim((string) $data['event_name']),
                ':charge_type' => in_array($data['charge_type'] ?? '', ['Fixed Amount', 'Percentage'], true) ? $data['charge_type'] : 'Fixed Amount',
                ':charge_value' => round((float) ($data['charge_value'] ?? 0), 2),
                ':status' => in_array($data['status'] ?? '', ['Active', 'Inactive'], true) ? $data['status'] : 'Active',
                ':description' => trim((string) ($data['description'] ?? '')),
            ]);

            $eventTypeId = (int) $pdo->lastInsertId();
            self::syncVenueTypes($eventTypeId, $venueTypes, $pdo);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $eventTypeId;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function update(int $eventTypeId, array $data, ?PDO $pdo = null): bool
    {
        $pdo = $pdo ?? get_db_connection();
        $venueTypes = array_key_exists('venue_types', $data)
            ? (array) $data['venue_types']
            : self::VENUE_TYPES;
        $ownsTransaction = !$pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $sql = 'UPDATE event_types
                    SET event_name = :event_name,
                        charge_type = :charge_type,
                        charge_value = :charge_value,
                        status = :status,
                        description = :description,
                        updated_at = NOW()
                    WHERE event_type_id = :id';

            $stmt = $pdo->prepare($sql);
            $updated = $stmt->execute([
                ':id' => $eventTypeId,
                ':event_name' => trim((string) $data['event_name']),
                ':charge_type' => in_array($data['charge_type'] ?? '', ['Fixed Amount', 'Percentage'], true) ? $data['charge_type'] : 'Fixed Amount',
                ':charge_value' => round((float) ($data['charge_value'] ?? 0), 2),
                ':status' => in_array($data['status'] ?? '', ['Active', 'Inactive'], true) ? $data['status'] : 'Active',
                ':description' => trim((string) ($data['description'] ?? '')),
            ]);

            if ($updated) {
                self::syncVenueTypes($eventTypeId, $venueTypes, $pdo);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $updated;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function updateStatus(int $eventTypeId, string $status, ?PDO $pdo = null): bool
    {
        $pdo = $pdo ?? get_db_connection();
        $stmt = $pdo->prepare('UPDATE event_types SET status = :status, updated_at = NOW() WHERE event_type_id = :id');

        return $stmt->execute([
            ':id' => $eventTypeId,
            ':status' => in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active',
        ]);
    }

    private static function syncVenueTypes(int $eventTypeId, array $venueTypes, PDO $pdo): void
    {
        $delete = $pdo->prepare('DELETE FROM event_type_venue_types WHERE event_type_id = :id');
        $delete->execute([':id' => $eventTypeId]);

        $insert = $pdo->prepare(
            'INSERT INTO event_type_venue_types (event_type_id, venue_type)
             VALUES (:event_type_id, :venue_type)'
        );

        foreach (array_values(array_intersect(self::VENUE_TYPES, array_map('strval', $venueTypes))) as $venueType) {
            $insert->execute([
                ':event_type_id' => $eventTypeId,
                ':venue_type' => $venueType,
            ]);
        }
    }
}
