<?php
declare(strict_types=1);

/**
 * Simple file-based security audit logger and login rate-limit helper.
 * Never logs passwords, tokens, or sensitive credentials.
 */
function ensure_log_directory(): string
{
    $logDir = defined('APP_LOG_PATH') ? APP_LOG_PATH : dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    $htaccess = $logDir . '/.htaccess';
    if (is_dir($logDir) && !is_file($htaccess)) {
        @file_put_contents(
            $htaccess,
            "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
        );
    }

    return $logDir;
}

function audit_log(string $event, array $context = []): void
{
    $sensitiveKeys = ['password', 'password_hash', 'confirm_password', 'csrf_token', 'token', 'plain_token'];
    $safeContext = [];
    foreach ($context as $key => $value) {
        if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
            continue;
        }
        $safeContext[$key] = is_scalar($value) || $value === null ? $value : json_encode($value);
    }

    $actorId = $_SESSION['staff_user_id'] ?? $_SESSION['customer_user_id'] ?? $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $entry = sprintf(
        "[%s] event=%s ip=%s actor_id=%s context=%s\n",
        date('Y-m-d H:i:s'),
        preg_replace('/[^a-zA-Z0-9._\-]/', '_', $event),
        preg_replace('/[^a-fA-F0-9:.\-]/', '', (string) $ip),
        $actorId !== null ? (string) (int) $actorId : 'guest',
        json_encode($safeContext, JSON_UNESCAPED_SLASHES)
    );

    $logFile = ensure_log_directory() . '/audit.log';
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Basic session + file-backed login throttle (5 failed attempts -> 5-minute lock).
 */
function login_throttle_key(string $scope, string $email): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'local');
    return hash('sha256', strtolower(trim($scope)) . ':' . strtolower(trim($email)) . ':' . $ip);
}

function get_login_throttle_state(string $scope, string $email): array
{
    $key = login_throttle_key($scope, $email);
    $now = time();

    $sessionAttempts = $_SESSION['login_attempts'][$key] ?? ['count' => 0, 'locked_until' => 0];
    $fileAttempts = ['count' => 0, 'locked_until' => 0];

    $rateFile = ensure_log_directory() . '/rate_limits.json';
    if (is_file($rateFile)) {
        $raw = @file_get_contents($rateFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded[$key]) && is_array($decoded[$key])) {
            $fileAttempts = [
                'count' => (int) ($decoded[$key]['count'] ?? 0),
                'locked_until' => (int) ($decoded[$key]['locked_until'] ?? 0),
            ];
        }
    }

    $lockedUntil = max((int) ($sessionAttempts['locked_until'] ?? 0), (int) ($fileAttempts['locked_until'] ?? 0));
    $count = max((int) ($sessionAttempts['count'] ?? 0), (int) ($fileAttempts['count'] ?? 0));

    if ($lockedUntil > 0 && $lockedUntil <= $now) {
        return ['count' => 0, 'locked_until' => 0, 'is_locked' => false];
    }

    return [
        'count' => $count,
        'locked_until' => $lockedUntil,
        'is_locked' => $lockedUntil > $now,
    ];
}

function save_login_throttle_state(string $scope, string $email, int $count, int $lockedUntil): void
{
    $key = login_throttle_key($scope, $email);
    if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    if ($count === 0 && $lockedUntil === 0) {
        unset($_SESSION['login_attempts'][$key]);
    } else {
        $_SESSION['login_attempts'][$key] = ['count' => $count, 'locked_until' => $lockedUntil];
    }

    $rateFile = ensure_log_directory() . '/rate_limits.json';
    $data = [];
    if (is_file($rateFile)) {
        $raw = @file_get_contents($rateFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $now = time();
    foreach ($data as $k => $v) {
        if (!is_array($v) || ((int) ($v['locked_until'] ?? 0) > 0 && (int) $v['locked_until'] <= $now && (int) ($v['count'] ?? 0) === 0)) {
            unset($data[$k]);
        }
    }

    if ($count === 0 && $lockedUntil === 0) {
        unset($data[$key]);
    } else {
        $data[$key] = ['count' => $count, 'locked_until' => $lockedUntil, 'updated_at' => $now];
    }

    @file_put_contents($rateFile, json_encode($data), LOCK_EX);
}

function record_login_failure(string $scope, string $email, int $maxAttempts = 5, int $lockSeconds = 300): array
{
    $state = get_login_throttle_state($scope, $email);
    $count = ((int) $state['count']) + 1;
    $lockedUntil = 0;

    if ($count >= $maxAttempts) {
        $count = 0;
        $lockedUntil = time() + $lockSeconds;
    }

    save_login_throttle_state($scope, $email, $count, $lockedUntil);

    return [
        'count' => $count,
        'locked_until' => $lockedUntil,
        'is_locked' => $lockedUntil > time(),
    ];
}

function clear_login_failures(string $scope, string $email): void
{
    save_login_throttle_state($scope, $email, 0, 0);
}
