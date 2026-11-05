<?php
declare(strict_types=1);

/**
 * Load key=value pairs from a .env file without overriding real environment
 * variables. Keeping this tiny avoids a Composer dependency while still letting
 * each Laragon installation override credentials safely.
 */
function coh_pms_load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? false;
    }

    return ($value === false || $value === '') ? $default : (string) $value;
}

function env_flag(string $key, bool $default = false): bool
{
    $value = env_value($key);
    if ($value === null) {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOL);
}

coh_pms_load_env(dirname(__DIR__) . '/.env');

define('SESSION_TIMEOUT', (int) (env_value('SESSION_TIMEOUT', '3600') ?? '3600'));

// Secure cookies follow the real request scheme only; forwarded headers are not trusted.
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

if (!defined('APP_IS_HTTPS')) {
    define('APP_IS_HTTPS', $isHttps);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('coh_pms_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');
    ini_set('session.gc_maxlifetime', (string) SESSION_TIMEOUT);

    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Single idle timeout. The cleared session keeps a flag so the login page
    // can explain why the visitor was signed out.
    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['session_expired'] = true;
    }
    $_SESSION['last_activity'] = time();
}

date_default_timezone_set(env_value('APP_TIMEZONE', 'Africa/Harare') ?? 'Africa/Harare');

define('APP_NAME', 'City of Harare Property Management System');
define('APP_VERSION', env_value('APP_VERSION', '1.0.0') ?? '1.0.0');
define('APP_ENV', env_value('APP_ENV', 'development') ?? 'development');
define('APP_DEBUG', APP_ENV !== 'production' && env_flag('APP_DEBUG', APP_ENV === 'development'));
define('APP_URL', env_value('APP_URL', env_value('APP_BASE_URL', '') ?? '') ?? '');

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (!defined('BASE_URL')) {
    if (APP_URL !== '') {
        define('BASE_URL', rtrim(APP_URL, '/'));
    } elseif ($envBaseUrl = env_value('COH_PMS_BASE_URL')) {
        define('BASE_URL', rtrim($envBaseUrl, '/'));
    } elseif (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        define('BASE_URL', 'http://localhost/coh-pms/public');
    } else {
        $scheme = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $baseUrl = rtrim($scheme . $host . $scriptDir, '/');
        define('BASE_URL', $baseUrl !== '' ? $baseUrl : $scheme . $host);
    }
}

define('APP_ROOT', dirname(__DIR__));
define('APP_PUBLIC_PATH', APP_ROOT . '/public');
define('APP_VIEW_PATH', APP_ROOT . '/views');
define('APP_UPLOAD_PATH', APP_PUBLIC_PATH . '/uploads/venues');
define('APP_STORAGE_PATH', APP_ROOT . '/storage');
define('APP_LOG_PATH', APP_STORAGE_PATH . '/logs');
