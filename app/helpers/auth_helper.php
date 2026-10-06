<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
function current_user(): ?array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $user = User::findById($userId);
    if (!$user || $user['account_status'] !== 'Active') {
        return null;
    }

    return [
        'id' => (int) $user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'account_status' => $user['account_status'],
    ];
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    unset($_SESSION['user']);
    $_SESSION['user_id'] = (int) $user['user_id'];
}

function logout_user(): void
{
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

function redirect_after_login(): void
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . BASE_URL . '/?r=auth/login');
        exit;
    }

    header('Location: ' . BASE_URL . '/?r=dashboard/index');
    exit;
}
