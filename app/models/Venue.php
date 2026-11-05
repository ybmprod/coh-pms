<?php
declare(strict_types=1);

// CHAPTER 5.3.2 - Venue Management
class Venue
{
    public static function getAll(?int $limit = null, int $offset = 0): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM venues ORDER BY venue_id ASC';

        if ($limit === null) {
            return $pdo->query($sql)->fetchAll();
        }

        $stmt = $pdo->prepare($sql . ' LIMIT :limit_rows OFFSET :offset_rows');
        $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset_rows', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countAll(): int
    {
        return (int) get_db_connection()->query('SELECT COUNT(*) FROM venues')->fetchColumn();
    }

    public static function getActive(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM venues WHERE venue_status = :status ORDER BY venue_name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => 'Available']);

        return $stmt->fetchAll();
    }

    public static function findById(int $venueId, ?PDO $pdo = null): ?array
    {
        $pdo = $pdo ?? get_db_connection();
        $sql = 'SELECT * FROM venues WHERE venue_id = :venue_id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':venue_id' => $venueId]);

        $venue = $stmt->fetch();
        return $venue ?: null;
    }

    public static function findByIdForUpdate(int $venueId, PDO $pdo): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM venues WHERE venue_id = :venue_id LIMIT 1 FOR UPDATE');
        $stmt->execute([':venue_id' => $venueId]);

        $venue = $stmt->fetch();
        return $venue ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = get_db_connection();
        $sql = 'INSERT INTO venues (venue_name, venue_type, location, capacity, facilities, description, standard_price, venue_status, image_path, created_at, updated_at)
                VALUES (:venue_name, :venue_type, :location, :capacity, :facilities, :description, :standard_price, :venue_status, :image_path, NOW(), NOW())';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':venue_name' => $data['venue_name'],
            ':venue_type' => $data['venue_type'],
            ':location' => $data['location'],
            ':capacity' => (int) $data['capacity'],
            ':facilities' => $data['facilities'] ?? null,
            ':description' => $data['description'] ?? null,
            ':standard_price' => (float) $data['standard_price'],
            ':venue_status' => $data['venue_status'] ?? 'Available',
            ':image_path' => $data['image_path'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $venueId, array $data): bool
    {
        $pdo = get_db_connection();
        $sql = 'UPDATE venues
                SET venue_name = :venue_name,
                    venue_type = :venue_type,
                    location = :location,
                    capacity = :capacity,
                    facilities = :facilities,
                    description = :description,
                    standard_price = :standard_price,
                    venue_status = :venue_status,
                    image_path = :image_path,
                    updated_at = NOW()
                WHERE venue_id = :venue_id';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':venue_id' => $venueId,
            ':venue_name' => $data['venue_name'],
            ':venue_type' => $data['venue_type'],
            ':location' => $data['location'],
            ':capacity' => (int) $data['capacity'],
            ':facilities' => $data['facilities'] ?? null,
            ':description' => $data['description'] ?? null,
            ':standard_price' => (float) $data['standard_price'],
            ':venue_status' => $data['venue_status'] ?? 'Available',
            ':image_path' => $data['image_path'] ?? null,
        ]);
    }
}
