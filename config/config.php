<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('coh_pms_session');
    ini_set('session.use_strict_mode', '1');
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Africa/Harare');

define('APP_NAME', 'City of Harare Property Management System');
define('APP_ENV', 'development');
define('BASE_URL', getenv('COH_PMS_BASE_URL') ?: 'http://localhost/coh-pms/public');
define('APP_ROOT', dirname(__DIR__));
define('APP_PUBLIC_PATH', APP_ROOT . '/public');
define('APP_VIEW_PATH', APP_ROOT . '/views');
define('APP_UPLOAD_PATH', APP_PUBLIC_PATH . '/uploads/venues');
