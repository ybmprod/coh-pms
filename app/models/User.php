<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class User
{
    public static function create(array $data): int
    {
        $pdo = get_db_connection();
        $sql = 'INSERT INTO users (full_name, email, phone_number, password_hash, role, account_status, created_at, updated_at)
                VALUES (:full_name, :email, :phone_number, :password_hash, :role, :account_status, NOW(), NOW())';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':email' => strtolower(trim($data['email'])),
            ':phone_number' => $data['phone_number'] ?? null,
            ':password_hash' => $data['password_hash'],
            ':role' => $data['role'] ?? ROLE_CUSTOMER,
            ':account_status' => $data['account_status'] ?? 'Active',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => strtolower(trim($email))]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $userId): ?array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM users WHERE user_id = :user_id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function getAll(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM users ORDER BY user_id ASC';
        return $pdo->query($sql)->fetchAll();
    }

    public static function updateStatus(int $userId, string $status): bool
    {
        $pdo = get_db_connection();
        $sql = 'UPDATE users SET account_status = :status, updated_at = NOW() WHERE user_id = :user_id';
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':status' => $status,
            ':user_id' => $userId,
        ]);
    }

    public static function countActiveAdministrators(): int
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role AND account_status = :status');
        $stmt->execute([
            ':role' => ROLE_ADMINISTRATOR,
            ':status' => 'Active',
        ]);

        return (int) $stmt->fetchColumn();
    }
}
