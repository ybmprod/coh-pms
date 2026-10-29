<?php
declare(strict_types=1);

class EventType
{
    public static function getAll(): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->query('SELECT * FROM event_types ORDER BY event_name ASC');

        return $stmt->fetchAll();
    }

    public static function getActive(): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->query("SELECT * FROM event_types WHERE status = 'Active' ORDER BY event_name ASC");

        return $stmt->fetchAll();
    }

    public static function findById(int $eventTypeId, ?PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM event_types WHERE event_type_id = :id LIMIT 1');
        $stmt->execute([':id' => $eventTypeId]);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    public static function findByName(string $name, ?PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM event_types WHERE event_name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    public static function create(array $data, ?PDO $pdo = null): int
    {
        $pdo = $pdo ?? get_db_connection();
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

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $eventTypeId, array $data, ?PDO $pdo = null): bool
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'UPDATE event_types
                SET event_name = :event_name,
                    charge_type = :charge_type,
                    charge_value = :charge_value,
                    status = :status,
                    description = :description,
                    updated_at = NOW()
                WHERE event_type_id = :id';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $eventTypeId,
            ':event_name' => trim((string) $data['event_name']),
            ':charge_type' => in_array($data['charge_type'] ?? '', ['Fixed Amount', 'Percentage'], true) ? $data['charge_type'] : 'Fixed Amount',
            ':charge_value' => round((float) ($data['charge_value'] ?? 0), 2),
            ':status' => in_array($data['status'] ?? '', ['Active', 'Inactive'], true) ? $data['status'] : 'Active',
            ':description' => trim((string) ($data['description'] ?? '')),
        ]);
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
}

