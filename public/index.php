<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/format_helper.php';
require_once __DIR__ . '/../app/helpers/validation_helper.php';
require_once __DIR__ . '/../app/helpers/csrf_helper.php';
require_once __DIR__ . '/../app/helpers/flash_helper.php';
require_once __DIR__ . '/../app/helpers/auth_helper.php';
require_once __DIR__ . '/../app/helpers/upload_helper.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Venue.php';
require_once __DIR__ . '/../app/models/PricingRule.php';
require_once __DIR__ . '/../app/models/Booking.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/services/AvailabilityService.php';
require_once __DIR__ . '/../app/services/PricingService.php';
require_once __DIR__ . '/../app/services/BookingService.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/VenueController.php';
require_once __DIR__ . '/../app/controllers/PricingruleController.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';
require_once __DIR__ . '/../app/controllers/PaymentController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/models/Report.php';
require_once __DIR__ . '/../app/services/ReportService.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$rawRoute = trim((string) ($_GET['r'] ?? ''));
$route = $rawRoute === '' ? 'home/index' : $rawRoute;
$routeParts = explode('/', trim($route, '/'));
$routeController = $routeParts[0] ?? 'home';
if (!preg_match('/^[a-z]+$/i', $routeController)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
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

$controllerClass = $controllerName;
if (!class_exists($controllerClass)) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

$controller = new $controllerClass();
$actionExists = method_exists($controller, $action);
$actionIsAllowed = false;
if ($actionExists) {
    $actionMethod = new ReflectionMethod($controller, $action);
    $actionIsAllowed = $actionMethod->isPublic()
        && $actionMethod->getDeclaringClass()->getName() === $controllerClass;
}
if (!$actionIsAllowed) {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
    exit;
}

try {
    $controller->$action();
} catch (Throwable $e) {
    if (APP_ENV === 'development') {
        error_log((string) $e);
    } else {
        error_log($e->getMessage());
    }
    http_response_code(500);
    include __DIR__ . '/../views/errors/500.php';
}
