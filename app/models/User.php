<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class User
{
    public const INVITE_PREFIX = 'INVITE:';
    public const INVITE_TTL_SECONDS = 86400; // 24 hours

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

    public static function encodeInvitationHash(string $plainToken, int $ttlSeconds = self::INVITE_TTL_SECONDS): string
    {
        $expiresAt = time() + $ttlSeconds;
        return self::INVITE_PREFIX . hash('sha256', $plainToken) . ':' . $expiresAt;
    }

    public static function isPendingActivation(array $user): bool
    {
        return str_starts_with((string) ($user['password_hash'] ?? ''), self::INVITE_PREFIX);
    }

    public static function isInvitationExpired(array $user): bool
    {
        if (!self::isPendingActivation($user)) {
            return false;
        }
        $parts = explode(':', (string) $user['password_hash']);
        $expiresAt = isset($parts[2]) ? (int) $parts[2] : 0;
        return $expiresAt <= 0 || time() > $expiresAt;
    }

    public static function findByInvitationToken(string $plainToken): ?array
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '' || strlen($plainToken) < 32) {
            return null;
        }

        $targetHash = hash('sha256', $plainToken);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE password_hash LIKE 'INVITE:%'");
        $stmt->execute();
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $candidate) {
            $parts = explode(':', (string) ($candidate['password_hash'] ?? ''));
            if (count($parts) !== 3 || $parts[0] !== 'INVITE') {
                continue;
            }
            $storedHash = $parts[1];
            $expiresAt = (int) $parts[2];

            if (hash_equals($storedHash, $targetHash)) {
                $candidate['token_expires_at'] = $expiresAt;
                $candidate['token_expired'] = time() > $expiresAt;
                return $candidate;
            }
        }

        return null;
    }

    public static function updateInvitationToken(int $userId, string $encodedInvite): bool
    {
        $pdo = get_db_connection();
        $sql = "UPDATE users SET password_hash = :password_hash, account_status = 'Inactive', updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':password_hash' => $encodedInvite,
            ':user_id' => $userId,
        ]);
    }

    public static function activateAccount(int $userId, string $passwordHash): bool
    {
        $pdo = get_db_connection();
        $sql = "UPDATE users SET password_hash = :password_hash, account_status = 'Active', updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':user_id' => $userId,
        ]);
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
