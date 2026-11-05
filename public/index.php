<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/autoload.php';

foreach (['security', 'format', 'pagination', 'validation', 'csrf', 'flash', 'audit', 'auth', 'upload', 'chart', 'mail'] as $helper) {
    require_once __DIR__ . '/../app/helpers/' . $helper . '_helper.php';
}

// Generate CSRF token for this request.

header_remove('X-Powered-By');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('X-Permitted-Cross-Domain-Policies: none');
header('X-DNS-Prefetch-Control: off');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'nonce-" . csp_nonce() . "'; "
    . "style-src 'self' 'unsafe-inline'; "
    . "font-src 'self'; img-src 'self' data:; connect-src 'self'; "
    . "media-src 'self'; manifest-src 'self'; object-src 'none'; worker-src 'none'; frame-src 'none'; "
    . "frame-ancestors 'none'; base-uri 'self'; form-action 'self';"
);
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4 * 1024 * 1024) {
    http_response_code(413);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Request body too large.';
    exit;
}

$rawRoute = trim((string) ($_GET['r'] ?? ''));
$route = $rawRoute === '' ? 'home/index' : $rawRoute;
$routeParts = explode('/', trim($route, '/'));
$routeController = $routeParts[0] ?? 'home';
if (!preg_match('/^[a-z]+$/i', $routeController) || !Router::isValidRoute($route)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

$normalisedRoute = strtolower(trim($route, '/'));
// The Council management workspace is PC-only. Mobile devices (phones and tablets)
// are restricted from staff routes, while customer pages remain fully accessible.
if ($normalisedRoute !== 'auth/logout' && is_staff_portal_request() && is_mobile_device_request()) {
    http_response_code(403);
    include __DIR__ . '/../views/errors/staff_desktop_only.php';
    exit;
}

$router = new Router();
[$controllerName, $action] = $router->dispatch($route);

if ($controllerName === 'HomeController' && $action === 'index') {
    $dbStatus = db_test_connection();
    $flash = get_flash();
    include __DIR__ . '/../views/home.php';
    exit;
}

if ($controllerName === 'HomeController' && $action === 'staff') {
    $flash = get_flash();
    include __DIR__ . '/../views/staff_home.php';
    exit;
}

if (!class_exists($controllerName)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

$controller = new $controllerName();
$actionExists = method_exists($controller, $action);
$actionIsAllowed = false;
if ($actionExists) {
    $actionMethod = new ReflectionMethod($controller, $action);
    $actionIsAllowed = $actionMethod->isPublic()
        && $actionMethod->getDeclaringClass()->getName() === $controllerName;
}
if (!$actionIsAllowed) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

try {
    $controller->$action();
} catch (Throwable $e) {
    error_log($e instanceof DomainException ? $e->getMessage() : (string) $e);
    http_response_code(500);
    include __DIR__ . '/../views/errors/500.php';
}
