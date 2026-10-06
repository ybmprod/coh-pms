<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class Controller
{
    protected function render(string $viewName, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = APP_VIEW_PATH . '/' . $viewName . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException('View not found: ' . $viewName);
        }

        include $viewFile;
    }

    protected function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    protected function currentUser(): ?array
    {
        return current_user();
    }

    protected function requireAuth(): bool
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $user = User::findById($userId);
        if (!$user || $user['account_status'] !== 'Active') {
            logout_user();
            set_flash('error', 'Your account is inactive or your session has expired. Please sign in again.');
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        return true;
    }

    protected function requireRole(array $roles): bool
    {
        if (!$this->requireAuth()) {
            return false;
        }

        $user = $this->currentUser();

        if (!$user || !isset($user['role'])) {
            return false;
        }

        return in_array($user['role'], $roles, true);
    }

    protected function denyIfNotRole(array $roles): bool
    {
        if (!$this->requireAuth()) {
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        if (!$this->requireRole($roles)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => 'Forbidden']);
            return true;
        }

        return false;
    }
}
