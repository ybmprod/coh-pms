<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control

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

    $staffPrefixes = ['pricingrule/', 'report/', 'user/'];
    foreach ($staffPrefixes as $prefix) {
        if (str_starts_with($route, $prefix)) {
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
    unset($_SESSION['user']);
    $userId = (int) $user['user_id'];
    if (($user['role'] ?? '') === ROLE_CUSTOMER) {
        $_SESSION['customer_user_id'] = $userId;
    } else {
        $_SESSION['staff_user_id'] = $userId;
    }
    $_SESSION['user_id'] = $userId;
}

function logout_user(?string $scope = null): void
{
    if ($scope === 'staff') {
        unset($_SESSION['staff_user_id']);
        if (isset($_SESSION['customer_user_id'])) {
            $_SESSION['user_id'] = (int) $_SESSION['customer_user_id'];
            session_regenerate_id(true);
            return;
        }
    } elseif ($scope === 'customer') {
        unset($_SESSION['customer_user_id']);
        if (isset($_SESSION['staff_user_id'])) {
            $_SESSION['user_id'] = (int) $_SESSION['staff_user_id'];
            session_regenerate_id(true);
            return;
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    session_id('');
    session_start();
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
