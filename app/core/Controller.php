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

    protected function currentUser(?string $scope = null): ?array
    {
        return current_user($scope);
    }

    protected function requireAuth(?string $scope = null): bool
    {
        $resolvedScope = current_portal_scope($scope);
        $sessionKey = $resolvedScope === 'staff' ? 'staff_user_id' : 'customer_user_id';
        $userId = (int) ($_SESSION[$sessionKey] ?? 0);

        if ($userId <= 0) {
            $user = current_user($resolvedScope);
            return $user !== null;
        }

        $user = User::findById($userId);
        $roleValid = $resolvedScope === 'staff'
            ? ($user && $user['role'] !== ROLE_CUSTOMER)
            : ($user && $user['role'] === ROLE_CUSTOMER);

        if (!$user || $user['account_status'] !== 'Active' || !$roleValid) {
            logout_user($resolvedScope);
            set_flash('error', 'Your account is inactive or your session has expired. Please sign in again.');
            $loginRoute = $resolvedScope === 'staff' ? 'auth/staffLogin' : 'auth/login';
            $this->redirect(BASE_URL . '/?r=' . $loginRoute);
        }

        return true;
    }

    protected function requireRole(array $roles, ?string $scope = null): bool
    {
        $resolvedScope = $scope ?? $this->inferScopeFromRoles($roles);
        if (!$this->requireAuth($resolvedScope)) {
            return false;
        }

        $user = $this->currentUser($resolvedScope);

        if (!$user || !isset($user['role'])) {
            return false;
        }

        return in_array($user['role'], $roles, true);
    }

    protected function denyIfNotRole(array $roles, ?string $scope = null): bool
    {
        $resolvedScope = $scope ?? $this->inferScopeFromRoles($roles);
        if (!$this->requireAuth($resolvedScope)) {
            $loginRoute = $resolvedScope === 'staff' ? 'auth/staffLogin' : 'auth/login';
            $this->redirect(BASE_URL . '/?r=' . $loginRoute);
        }

        if (!$this->requireRole($roles, $resolvedScope)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => 'Forbidden']);
            return true;
        }

        return false;
    }

    private function inferScopeFromRoles(array $roles): string
    {
        if ($roles === [ROLE_CUSTOMER]) {
            return 'customer';
        }

        if (!in_array(ROLE_CUSTOMER, $roles, true)) {
            return 'staff';
        }

        return current_portal_scope();
    }
}
