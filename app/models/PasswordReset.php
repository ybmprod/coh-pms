<?php
declare(strict_types=1);

/**
 * Single-use email password-reset tokens.
 *
 * The plain token is only ever placed in the email link. The database stores
 * its SHA-256 hash, an expiry timestamp and the time it was consumed.
 */
class PasswordReset
{
    public const TTL_SECONDS = 3600;
    public const MIN_REQUEST_INTERVAL_SECONDS = 60;

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', trim($plainToken));
    }

    public static function isValidTokenFormat(string $plainToken): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', trim($plainToken)) === 1;
    }

    /**
     * Issues a new 64-character token, invalidating any earlier unused token
     * for the same account. Returns the plain token for the email link only.
     */
    public static function issueForUser(int $userId, ?string $requestedIp = null): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = self::hashToken($plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);
        $requestedIp = $requestedIp !== null ? substr(trim($requestedIp), 0, 45) : null;

        $pdo = get_db_connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = :user_id AND used_at IS NULL'
            );
            $stmt->execute([':user_id' => $userId]);

            $stmt = $pdo->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip, created_at)
                 VALUES (:user_id, :token_hash, :expires_at, :requested_ip, NOW())'
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt,
                ':requested_ip' => $requestedIp !== '' ? $requestedIp : null,
            ]);

            $pdo->commit();
            return $plainToken;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function recentlyRequested(int $userId, int $seconds = self::MIN_REQUEST_INTERVAL_SECONDS): bool
    {
        if ($userId <= 0 || $seconds <= 0) {
            return false;
        }

        $cutoff = date('Y-m-d H:i:s', time() - $seconds);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM password_reset_tokens
             WHERE user_id = :user_id AND created_at > :cutoff'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':cutoff' => $cutoff,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Returns the token row joined with the account details needed to decide
     * whether the reset can proceed. Expired, used and unknown tokens return null.
     */
    public static function findByToken(string $plainToken): ?array
    {
        if (!self::isValidTokenFormat($plainToken)) {
            return null;
        }

        $pdo = get_db_connection();
        $stmt = $pdo->prepare(
            'SELECT prt.reset_id, prt.user_id, prt.expires_at,
                    u.full_name, u.email, u.role, u.account_status
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.user_id = prt.user_id
             WHERE prt.token_hash = :token_hash
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => self::hashToken($plainToken)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Atomically replaces the password hash and consumes every outstanding
     * token for the account. Returns false if the token was already used,
     * expired or belongs to another account.
     */
    public static function consume(int $resetId, int $userId, string $passwordHash): bool
    {
        if (!preg_match('/^\$2[ayb]\$\d{2}\$[\/A-Za-z0-9.]{53}$/', $passwordHash)) {
            throw new InvalidArgumentException('Only valid bcrypt password hashes may be stored.');
        }

        $pdo = get_db_connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT prt.reset_id
                 FROM password_reset_tokens prt
                 INNER JOIN users u ON u.user_id = prt.user_id
                 WHERE prt.reset_id = :reset_id
                   AND prt.user_id = :user_id
                   AND prt.used_at IS NULL
                   AND prt.expires_at > NOW()
                   AND u.role = \'Customer\'
                   AND u.account_status = \'Active\'
                 FOR UPDATE'
            );
            $stmt->execute([
                ':reset_id' => $resetId,
                ':user_id' => $userId,
            ]);

            if (!$stmt->fetch()) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET password_hash = :password_hash, updated_at = NOW()
                 WHERE user_id = :user_id'
            );
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':user_id' => $userId,
            ]);

            $stmt = $pdo->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = :user_id AND used_at IS NULL'
            );
            $stmt->execute([':user_id' => $userId]);

            $pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
