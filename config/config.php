<?php
declare(strict_types=1);

define('SESSION_TIMEOUT', 3600); // 60 minutes reasonable idle session timeout

if (session_status() === PHP_SESSION_NONE) {
    session_name('coh_pms_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_TIMEOUT);

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['session_expired'] = true;
    }
    $_SESSION['last_activity'] = time();
}

date_default_timezone_set('Africa/Harare');

define('APP_NAME', 'City of Harare Property Management System');
define('APP_ENV', 'development');
define('BASE_URL', getenv('COH_PMS_BASE_URL') ?: 'http://localhost/coh-pms/public');
define('APP_ROOT', dirname(__DIR__));
define('APP_PUBLIC_PATH', APP_ROOT . '/public');
define('APP_VIEW_PATH', APP_ROOT . '/views');
define('APP_UPLOAD_PATH', APP_PUBLIC_PATH . '/uploads/venues');
define('APP_STORAGE_PATH', APP_ROOT . '/storage');
define('APP_LOG_PATH', APP_STORAGE_PATH . '/logs');
