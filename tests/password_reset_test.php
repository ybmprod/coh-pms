<?php
declare(strict_types=1);

/**
 * Password-reset regression suite.
 *
 * Verifies token hashing, expiry, single-use consumption, throttling and the
 * development mail fallback against the local COH-PMS database. When the
 * database is unavailable the suite reports SKIP and exits successfully so a
 * syntax-only CI machine can still run the other gates.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/PasswordReset.php';
require_once __DIR__ . '/../app/helpers/format_helper.php';
require_once __DIR__ . '/../app/helpers/mail_helper.php';

$runCase = static function (string $name, callable $test): bool {
    try {
        $test();
        echo 'PASS ', $name, PHP_EOL;
        return true;
    } catch (Throwable $exception) {
        echo 'FAIL ', $name, ': ', $exception->getMessage(), PHP_EOL;
        return false;
    }
};

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    $pdo = get_db_connection();
} catch (Throwable $exception) {
    echo 'SKIP Password reset integration tests: database unavailable (' . $exception->getMessage() . ').' . PHP_EOL;
    exit(0);
}

$tableCheck = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'")->fetchColumn();
if ($tableCheck === false) {
    echo 'FAIL Password reset integration tests: password_reset_tokens table is missing. Run database/migrations/2026_10_31_add_password_reset_tokens.sql.' . PHP_EOL;
    exit(1);
}

$email = 'reset-test-' . bin2hex(random_bytes(6)) . '@example.test';
$originalHash = password_hash('OriginalPass123', PASSWORD_BCRYPT);
$userId = null;
$allPassed = true;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, phone_number, password_hash, role, account_status, created_at, updated_at)
         VALUES (:full_name, :email, NULL, :password_hash, 'Customer', 'Active', NOW(), NOW())"
    );
    $stmt->execute([
        ':full_name' => 'Password Reset Test Customer',
        ':email' => $email,
        ':password_hash' => $originalHash,
    ]);
    $userId = (int) $pdo->lastInsertId();

    $allPassed = $runCase('Token format and SHA-256 hash are deterministic', static function () use ($assert): void {
        $token = bin2hex(random_bytes(32));
        $assert(strlen($token) === 64, 'Expected a 64-character token.');
        $assert(PasswordReset::isValidTokenFormat($token), 'Expected token to pass format validation.');
        $assert(!PasswordReset::isValidTokenFormat('not-a-token'), 'Rejected token should fail format validation.');
        $assert(PasswordReset::hashToken($token) === hash('sha256', $token), 'Token hash should be SHA-256.');
        $assert(strlen(PasswordReset::hashToken($token)) === 64, 'Token hash should be 64 hex characters.');
    }) && $allPassed;

    $plainToken = PasswordReset::issueForUser($userId, '127.0.0.1');

    $allPassed = $runCase('Issued token is stored as a hash and resolves to the customer', static function () use ($pdo, $plainToken, $userId, $assert, $email): void {
        $assert(is_string($plainToken) && PasswordReset::isValidTokenFormat($plainToken), 'Issue should return a valid token.');
        $assert(PasswordReset::recentlyRequested($userId), 'A just-issued token should trigger the request throttle.');

        $stmt = $pdo->prepare('SELECT token_hash FROM password_reset_tokens WHERE user_id = :user_id ORDER BY reset_id DESC LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $storedHash = (string) $stmt->fetchColumn();
        $assert($storedHash === PasswordReset::hashToken($plainToken), 'Only the token hash should be stored.');
        $assert($storedHash !== $plainToken, 'The plain token must never be stored.');

        $found = PasswordReset::findByToken($plainToken);
        $assert($found !== null, 'A valid token should resolve.');
        $assert((int) $found['user_id'] === $userId, 'Token should resolve to the issued customer.');
        $assert($found['email'] === $email, 'Resolved customer email should match.');
        $assert($found['role'] === 'Customer', 'Only customer tokens should be accepted.');
    }) && $allPassed;

    $replacementToken = PasswordReset::issueForUser($userId, '127.0.0.1');

    $allPassed = $runCase('Issuing a new token invalidates the previous token', static function () use ($plainToken, $replacementToken, $assert): void {
        $assert($replacementToken !== $plainToken, 'Replacement token should differ.');
        $assert(PasswordReset::findByToken($plainToken) === null, 'The older token should be invalidated.');
        $assert(PasswordReset::findByToken((string) $replacementToken) !== null, 'The newest token should be valid.');
    }) && $allPassed;

    $allPassed = $runCase('Expired and malformed tokens are rejected', static function () use ($pdo, $userId, $assert): void {
        $expiredToken = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR))'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => PasswordReset::hashToken($expiredToken),
            ':expires_at' => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $assert(PasswordReset::findByToken($expiredToken) === null, 'Expired token should be rejected.');
        $assert(PasswordReset::findByToken('invalid') === null, 'Malformed token should be rejected.');
        $assert(PasswordReset::findByToken(str_repeat('a', 64)) === null, 'Unknown token should be rejected.');
    }) && $allPassed;

    $allPassed = $runCase('A token cannot be consumed for a different account', static function () use ($replacementToken, $assert): void {
        $consumed = PasswordReset::consume(1, 999999, password_hash('NewPass123', PASSWORD_BCRYPT));
        $assert($consumed === false, 'Consuming for a different account must fail.');
        $assert(PasswordReset::findByToken((string) $replacementToken) !== null, 'The valid token should remain unused.');
    }) && $allPassed;

    $allPassed = $runCase('A token can only be consumed once and updates the password', static function () use ($pdo, $replacementToken, $userId, $assert): void {
        $token = PasswordReset::findByToken((string) $replacementToken);
        $assert($token !== null, 'Token should be valid before consumption.');

        $newHash = password_hash('NewPass123', PASSWORD_BCRYPT);
        $assert(PasswordReset::consume((int) $token['reset_id'], $userId, $newHash), 'First consumption should succeed.');
        $assert(PasswordReset::findByToken((string) $replacementToken) === null, 'Consumed token should no longer resolve.');
        $assert(PasswordReset::consume((int) $token['reset_id'], $userId, $newHash) === false, 'Second consumption should fail.');

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $storedHash = (string) $stmt->fetchColumn();
        $assert(password_verify('NewPass123', $storedHash), 'The new password should verify.');
        $assert(!password_verify('OriginalPass123', $storedHash), 'The old password should no longer verify.');
    }) && $allPassed;

    $allPassed = $runCase('URL and email masking helpers produce safe reset links', static function () use ($assert): void {
        $url = app_url('auth/resetPassword', ['token' => 'abc 123']);
        $assert(str_contains($url, '?r=auth/resetPassword'), 'Reset URL should contain the route.');
        $assert(str_contains($url, 'token=abc%20123'), 'Reset URL should URL-encode the token.');
        $assert(mask_email('amina@example.com') === 'am***@example.com', 'Masked email should reveal only two characters.');
        $assert(mask_email('a@example.com') === 'a**@example.com', 'Short local parts should still be masked.');
    }) && $allPassed;

    $allPassed = $runCase('Local mail transport writes the link to storage/logs/mail.log', static function () use ($assert): void {
        $marker = 'password-reset-test-' . bin2hex(random_bytes(5));
        putenv('MAIL_TRANSPORT=log');

        $sent = send_system_email('customer@example.test', $marker, "Reset link: https://example.test/?r=auth/resetPassword&token=demo");
        $assert($sent === true, 'Log transport should report success.');

        $logPath = dirname(__DIR__) . '/storage/logs/mail.log';
        $assert(is_file($logPath), 'mail.log should be created.');
        $contents = (string) file_get_contents($logPath);
        $assert(str_contains($contents, $marker), 'mail.log should contain the message subject.');
        $assert(str_contains($contents, 'auth/resetPassword&token=demo'), 'mail.log should contain the reset link.');
    }) && $allPassed;
} finally {
    if ($userId !== null) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }
}

exit($allPassed ? 0 : 1);
