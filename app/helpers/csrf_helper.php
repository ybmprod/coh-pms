<?php
declare(strict_types=1);

/**
 * Simple CSRF protection: one token per browser, kept in the session and
 * mirrored in a host-only cookie.
 *
 * A POST is accepted when the submitted token matches either copy. The cookie
 * copy is what keeps forms working when the browser sends no session cookie
 * (stale tab, replaced session, privacy settings) — without it those requests
 * looked like an expired session even though the form was genuine.
 */

function csrf_cookie_name(): string
{
    return 'coh_pms_csrf';
}

/**
 * Store the token in the session and make sure the cookie copy matches it.
 */
function csrf_store_token(string $token): void
{
    $_SESSION['csrf_token'] = $token;

    if ((string) ($_COOKIE[csrf_cookie_name()] ?? '') === $token) {
        return;
    }

    $_COOKIE[csrf_cookie_name()] = $token;

    if (headers_sent()) {
        return;
    }

    setcookie(csrf_cookie_name(), $token, [
        'expires'  => 0,
        'path'     => '/',
        'secure'   => defined('APP_IS_HTTPS') ? APP_IS_HTTPS : false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function csrf_token(): string
{
    $token = (string) ($_SESSION['csrf_token'] ?? '');

    if ($token === '') {
        // A new session can adopt the token the browser already holds.
        $mirrored = (string) ($_COOKIE[csrf_cookie_name()] ?? '');
        $token = preg_match('/^[a-f0-9]{64}$/', $mirrored) === 1
            ? $mirrored
            : bin2hex(random_bytes(32));
    }

    csrf_store_token($token);

    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Issue a new token after a privilege change (login, logout, password reset) so
 * a token captured before authentication cannot authorise a later request.
 */
function csrf_rotate(): string
{
    $token = bin2hex(random_bytes(32));
    csrf_store_token($token);

    return $token;
}

function verify_csrf_token(?string $token = null): bool
{
    $candidate = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!is_string($candidate) || $candidate === '') {
        return false;
    }

    $known = [
        (string) ($_SESSION['csrf_token'] ?? ''),
        (string) ($_COOKIE[csrf_cookie_name()] ?? ''),
    ];

    foreach ($known as $expected) {
        if ($expected !== '' && hash_equals($expected, $candidate)) {
            return true;
        }
    }

    error_log('CSRF rejected: token matches neither the session nor the cookie copy.');

    return false;
}
