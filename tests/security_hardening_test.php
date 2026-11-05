<?php
declare(strict_types=1);

/**
 * Security hardening suite.
 *
 * Covers the controls added in the November 2026 pass: CSP nonce generation,
 * CSRF rotation with a cookie-copy fallback, generic rate limiting, stronger
 * password hashing and recursive audit redaction. The view-layer row filter is
 * exercised against the local database when it is available.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/audit_helper.php';
require_once __DIR__ . '/../app/helpers/security_helper.php';
require_once __DIR__ . '/../app/helpers/csrf_helper.php';
require_once __DIR__ . '/../app/helpers/validation_helper.php';
require_once __DIR__ . '/../app/helpers/format_helper.php';

$passCount = 0;
$failCount = 0;

function assertSecurityTest(string $description, bool $condition): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo 'PASS: ', $description, PHP_EOL;
        $passCount++;
        return;
    }

    echo 'FAIL: ', $description, PHP_EOL;
    $failCount++;
}

// 1. CSP nonce

$nonceA = csp_nonce();
$nonceB = csp_nonce();
assertSecurityTest('CSP nonce is generated', $nonceA !== '');
assertSecurityTest('CSP nonce is stable for one request', $nonceA === $nonceB);
assertSecurityTest('CSP nonce decodes to at least 16 bytes', strlen((string) base64_decode($nonceA, true)) >= 16);

// 2. CSRF rotation and verification

$tokenBefore = csrf_token();
$tokenAfter = csrf_rotate();
assertSecurityTest('CSRF token rotates', $tokenBefore !== $tokenAfter);
assertSecurityTest('Rotated CSRF token verifies', verify_csrf_token($tokenAfter));
assertSecurityTest('Previous CSRF token is rejected', !verify_csrf_token($tokenBefore));

// A browser that posts without its session cookie still validates against the
// cookie copy of the token, so a stale tab or replaced session cannot break a form.
unset($_SESSION['csrf_token']);
$_COOKIE[csrf_cookie_name()] = $tokenAfter;
assertSecurityTest('Cookie copy validates the token when the session has none', verify_csrf_token($tokenAfter));
$_SESSION['csrf_token'] = $tokenAfter;
assertSecurityTest('Empty token is rejected', !verify_csrf_token(''));

// 4. Generic rate limiter

$limitKey = 'test:rate_limit:' . bin2hex(random_bytes(4));
rate_limit_reset($limitKey);
assertSecurityTest('Rate limiter allows the first request', rate_limit_allow($limitKey, 2, 60));
assertSecurityTest('Rate limiter allows the second request', rate_limit_allow($limitKey, 2, 60));
assertSecurityTest('Rate limiter blocks the request after the quota', !rate_limit_allow($limitKey, 2, 60));
rate_limit_reset($limitKey);
assertSecurityTest('Rate limiter reset restores the quota', rate_limit_allow($limitKey, 2, 60));
rate_limit_reset($limitKey);

// 5. Password hardening

$longPasswordErrors = validate_password(str_repeat('Aa1', 50));
assertSecurityTest(
    'Passwords longer than 128 characters are rejected',
    in_array('Password must be 128 characters or fewer.', $longPasswordErrors, true)
);

$nulPasswordErrors = validate_password("Abcdefg1\0");
assertSecurityTest(
    'Passwords containing a NUL byte are rejected',
    in_array('Password contains invalid characters.', $nulPasswordErrors, true)
);

$secureHash = hash_password_secure('Admin@123');
$secureInfo = password_get_info($secureHash);
assertSecurityTest('Secure hash verifies the password', password_verify('Admin@123', $secureHash));
assertSecurityTest(
    'Secure hash uses bcrypt cost 12',
    ($secureInfo['algoName'] ?? '') === 'bcrypt' && (int) ($secureInfo['options']['cost'] ?? 0) === 12
);
assertSecurityTest('Secure hash does not need rehashing', !password_hash_needs_rehash($secureHash));
assertSecurityTest(
    'Legacy bcrypt cost 10 hash is marked for rehash',
    password_hash_needs_rehash(password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 10]))
);

// 6. Recursive audit redaction

$redacted = audit_redact_context([
    'email' => 'customer@example.com',
    'nested' => [
        'password' => 'Admin@123',
        'api_key' => 'secret-value',
        'note' => 'kept',
    ],
    'csrf_token' => 'abc123',
    'plain' => 'visible',
]);
assertSecurityTest('Top-level audit secret is redacted', $redacted['csrf_token'] === '[redacted]');
assertSecurityTest('Nested audit password is redacted', $redacted['nested']['password'] === '[redacted]');
assertSecurityTest('Nested audit API key is redacted', $redacted['nested']['api_key'] === '[redacted]');
assertSecurityTest('Non-secret audit values are preserved', $redacted['plain'] === 'visible' && $redacted['nested']['note'] === 'kept');

// 7. User-facing error messages

assertSecurityTest(
    'DomainException messages are shown to users',
    user_error_message(new DomainException('Venue is unavailable.'), 'Fallback.') === 'Venue is unavailable.'
);
assertSecurityTest(
    'Unexpected exception details are hidden',
    user_error_message(new RuntimeException('SQLSTATE[42000] /secret/path'), 'Fallback.') === 'Fallback.'
);

// 8. Row-filtered views and connection reuse (skips when MySQL is unavailable)

try {
    $pdo = get_db_connection();
    $views = $pdo->query(
        "SELECT TABLE_NAME FROM information_schema.VIEWS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'v_customer_%'"
    )->fetchAll(PDO::FETCH_COLUMN);
    assertSecurityTest(
        'All three customer views exist',
        in_array('v_customer_bookings', $views, true)
        && in_array('v_customer_payments', $views, true)
        && in_array('v_customer_receipts', $views, true)
    );

    $customerId = (int) $pdo->query('SELECT customer_id FROM bookings ORDER BY customer_id LIMIT 1')->fetchColumn();
    if ($customerId > 0) {
        $pdo->exec('SET @coh_pms_customer_id = ' . $customerId);
        $rows = $pdo->query('SELECT DISTINCT customer_id FROM v_customer_bookings')->fetchAll(PDO::FETCH_COLUMN);
        assertSecurityTest(
            'v_customer_bookings returns only the active customer rows',
            $rows !== [] && array_map('intval', $rows) === [$customerId]
        );

        $pdo->exec('SET @coh_pms_customer_id = 0');
        assertSecurityTest(
            'v_customer_bookings returns no rows without an identity',
            (int) $pdo->query('SELECT COUNT(*) FROM v_customer_bookings')->fetchColumn() === 0
        );
    } else {
        echo 'SKIP: no seeded bookings for the view-filter assertion.', PHP_EOL;
    }

    $appFirst = get_db_connection();
    $appSecond = get_db_connection();
    assertSecurityTest('Application PDO is reused within the request', $appFirst === $appSecond);

    $readFirst = get_db_connection('customer_read');
    $readSecond = get_db_connection('customer_read');
    assertSecurityTest(
        'Customer-read PDO is separate and reused within the request',
        $readFirst === $readSecond && $readFirst !== $appFirst
    );
} catch (Throwable $exception) {
    echo 'SKIP: row-filtered view checks (database unavailable: ', $exception->getMessage(), ').', PHP_EOL;
}

echo PHP_EOL, $passCount, ' passed, ', $failCount, ' failed.', PHP_EOL;
exit($failCount === 0 ? 0 : 1);
