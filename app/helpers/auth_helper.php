<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control

/**
 * Detects mobile devices (phones and tablets) from HTTP headers and Chromium client hints.
 * Used to enforce the PC-only policy for the Council Staff Management Portal.
 */
function is_mobile_device_request(): bool
{
    $clientHint = trim((string) ($_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? ''));
    if ($clientHint === '?1' || strtolower($clientHint) === 'true') {
        return true;
    }

    $userAgent = strtolower(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    if ($userAgent === '') {
        return false;
    }

    return preg_match(
        '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',
        $userAgent
    ) === 1 || preg_match(
        '/android|ipad|playbook|silk/i',
        $userAgent
    ) === 1;
}

/**
 * Determines whether the current HTTP request belongs to the Council Staff Portal
 * or the Customer / Public Portal so the two portals never cross over.
 */
function is_staff_portal_request(): bool
{
    $explicitPortal = strtolower(trim((string) ($_GET['portal'] ?? $_POST['portal'] ?? '')));
    if ($explicitPortal === 'staff') {
        return true;
    }
    if ($explicitPortal === 'customer') {
        return false;
    }

    $route = strtolower(trim((string) ($_GET['r'] ?? ''), '/'));
    if ($route === '') {
        return false;
    }

    $staffRoutes = [
        'home/staff',
        'auth/stafflogin',
        'auth/activate',
        'dashboard/index',
        'dashboard/staff',
        'venue/index',
        'venue/create',
        'venue/edit',
        'pricingrule/index',
        'pricingrule/create',
        'pricingrule/edit',
        'pricingrule/togglestatus',
        'booking/index',
        'booking/updatestatus',
        'payment/index',
        'payment/verify',
        'report/index',
        'report/exportcsv',
        'user/index',
        'user/create',
        'user/resendinvite',
        'user/togglestatus',
    ];

    if (in_array($route, $staffRoutes, true)) {
        return true;
    }

    // Controllers that exist ONLY in the staff portal. Any action on them
    // (current or future) is automatically treated as a staff request, so a new
    // action can never silently fall back to the customer portal.
    $staffOnlyControllers = ['pricingrule', 'eventtype', 'report', 'user'];
    $controller = explode('/', $route)[0];

    if (in_array($controller, $staffOnlyControllers, true)) {
        return true;
    }

    // If an authenticated staff member is logged in and not also in an active customer session,
    // ensure routes that are not explicitly customer-facing stay in the staff workspace.
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['staff_user_id']) && empty($_SESSION['customer_user_id'])) {
        $customerOnlyRoutes = [
            'auth/login',
            'auth/register',
            'auth/forgotpassword',
            'auth/resetpassword',
            'auth/changepassword',
            'venue/customerlist',
            'booking/customerlist',
            'dashboard/customer',
        ];
        if (!in_array($route, $customerOnlyRoutes, true)) {
            return true;
        }
    }

    return false;
}

function current_portal_scope(?string $scope = null): string
{
    if ($scope === 'staff' || $scope === 'customer') {
        return $scope;
    }

    return is_staff_portal_request() ? 'staff' : 'customer';
}

function format_session_user(array $user): array
{
    return [
        'id' => (int) $user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'account_status' => $user['account_status'],
        'created_at' => $user['created_at'] ?? null,
    ];
}

function current_customer_user(): ?array
{
    $userId = (int) ($_SESSION['customer_user_id'] ?? 0);
    if ($userId <= 0) {
        $legacyId = (int) ($_SESSION['user_id'] ?? 0);
        if ($legacyId > 0) {
            $legacyUser = User::findById($legacyId);
            if ($legacyUser && $legacyUser['account_status'] === 'Active' && $legacyUser['role'] === ROLE_CUSTOMER) {
                $_SESSION['customer_user_id'] = $legacyId;
                return format_session_user($legacyUser);
            }
        }
        return null;
    }

    $user = User::findById($userId);
    if (!$user || $user['account_status'] !== 'Active' || $user['role'] !== ROLE_CUSTOMER) {
        unset($_SESSION['customer_user_id']);
        return null;
    }

    return format_session_user($user);
}

function current_staff_user(): ?array
{
    $userId = (int) ($_SESSION['staff_user_id'] ?? 0);
    if ($userId <= 0) {
        $legacyId = (int) ($_SESSION['user_id'] ?? 0);
        if ($legacyId > 0) {
            $legacyUser = User::findById($legacyId);
            if ($legacyUser && $legacyUser['account_status'] === 'Active' && $legacyUser['role'] !== ROLE_CUSTOMER) {
                $_SESSION['staff_user_id'] = $legacyId;
                return format_session_user($legacyUser);
            }
        }
        return null;
    }

    $user = User::findById($userId);
    if (!$user || $user['account_status'] !== 'Active' || $user['role'] === ROLE_CUSTOMER) {
        unset($_SESSION['staff_user_id']);
        return null;
    }

    return format_session_user($user);
}

function current_user(?string $scope = null): ?array
{
    $resolvedScope = current_portal_scope($scope);
    if ($resolvedScope === 'staff') {
        return current_staff_user();
    }

    return current_customer_user();
}

function is_logged_in(?string $scope = null): bool
{
    return current_user($scope) !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    unset($_SESSION['user'], $_SESSION['session_expired']);
    $userId = (int) $user['user_id'];
    if (($user['role'] ?? '') === ROLE_CUSTOMER) {
        $_SESSION['customer_user_id'] = $userId;
    } else {
        $_SESSION['staff_user_id'] = $userId;
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['auth_role'] = (string) ($user['role'] ?? '');
    $_SESSION['last_activity'] = time();

    if (function_exists('csrf_rotate')) {
        csrf_rotate();
    }
}

function logout_user(?string $scope = null): void
{
    if ($scope === 'staff') {
        unset($_SESSION['staff_user_id']);
        if (isset($_SESSION['customer_user_id'])) {
            $_SESSION['user_id'] = (int) $_SESSION['customer_user_id'];
            session_regenerate_id(true);
            if (function_exists('csrf_rotate')) {
                csrf_rotate();
            }
            return;
        }
    } elseif ($scope === 'customer') {
        unset($_SESSION['customer_user_id']);
        if (isset($_SESSION['staff_user_id'])) {
            $_SESSION['user_id'] = (int) $_SESSION['staff_user_id'];
            session_regenerate_id(true);
            if (function_exists('csrf_rotate')) {
                csrf_rotate();
            }
            return;
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
    // Start a fresh anonymous session so the redirect can receive the flash message
    // and a new CSRF token without calling session_start() on a destroyed session.
    session_start();
    session_regenerate_id(true);
    if (function_exists('csrf_rotate')) {
        csrf_rotate();
    }
}

function redirect_after_login(?string $scope = null): void
{
    $resolvedScope = current_portal_scope($scope);
    $user = current_user($resolvedScope);
    if (!$user) {
        $loginRoute = $resolvedScope === 'staff' ? 'auth/staffLogin' : 'auth/login';
        header('Location: ' . BASE_URL . '/?r=' . $loginRoute);
        exit;
    }

    if ($user['role'] === ROLE_CUSTOMER) {
        header('Location: ' . BASE_URL . '/?r=dashboard/customer');
        exit;
    }

    header('Location: ' . BASE_URL . '/?r=dashboard/index');
    exit;
}
