<?php
declare(strict_types=1);

/**
 * Cross-cutting security helpers that are not tied to one module: CSP nonces,
 * client IP resolution and a generic file-backed action rate limiter.
 */

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(18));
    }

    return $nonce;
}

function client_ip_address(): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return $ip !== '' ? $ip : 'cli';
}

function rate_limit_file_path(): string
{
    $logDir = function_exists('ensure_log_directory')
        ? ensure_log_directory()
        : (defined('APP_LOG_PATH') ? APP_LOG_PATH : dirname(__DIR__, 2) . '/storage/logs');

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    return $logDir . '/action_rate_limits.json';
}

/**
 * @return array<string, array{count:int, reset_at:int}>
 */
function rate_limit_read_all(): array
{
    $file = rate_limit_file_path();
    if (!is_file($file)) {
        return [];
    }

    $raw = @file_get_contents($file);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($decoded)) {
        return [];
    }

    $safe = [];
    foreach ($decoded as $key => $value) {
        if (!is_string($key) || !is_array($value)) {
            continue;
        }
        $safe[$key] = [
            'count' => max(0, (int) ($value['count'] ?? 0)),
            'reset_at' => max(0, (int) ($value['reset_at'] ?? 0)),
        ];
    }

    return $safe;
}

/**
 * @param array<string, array{count:int, reset_at:int}> $data
 */
function rate_limit_write_all(array $data): void
{
    $file = rate_limit_file_path();
    $lockPath = $file . '.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock !== false && flock($lock, LOCK_EX)) {
        @file_put_contents($file, json_encode($data), LOCK_EX);
        flock($lock, LOCK_UN);
        fclose($lock);
        return;
    }

    if ($lock !== false) {
        fclose($lock);
    }
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Fixed-window limiter. Returns false when the caller has exhausted its quota.
 * Keys are namespaced by action, for example "register:203.0.113.4".
 */
function rate_limit_allow(string $key, int $maxAttempts, int $windowSeconds): bool
{
    $key = preg_replace('/[^a-zA-Z0-9_.:\-]/', '_', $key) ?? 'unknown';
    $maxAttempts = max(1, $maxAttempts);
    $windowSeconds = max(1, $windowSeconds);
    $now = time();

    $data = rate_limit_read_all();

    foreach ($data as $existingKey => $entry) {
        if ((int) $entry['reset_at'] <= $now) {
            unset($data[$existingKey]);
        }
    }

    $entry = $data[$key] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];
    if ((int) $entry['reset_at'] <= $now) {
        $entry = ['count' => 0, 'reset_at' => $now + $windowSeconds];
    }

    if ((int) $entry['count'] >= $maxAttempts) {
        $data[$key] = $entry;
        rate_limit_write_all($data);
        return false;
    }

    $entry['count'] = (int) $entry['count'] + 1;
    $data[$key] = $entry;
    rate_limit_write_all($data);

    return true;
}

function rate_limit_reset(string $key): void
{
    $key = preg_replace('/[^a-zA-Z0-9_.:\-]/', '_', $key) ?? 'unknown';
    $data = rate_limit_read_all();
    unset($data[$key]);
    rate_limit_write_all($data);
}
