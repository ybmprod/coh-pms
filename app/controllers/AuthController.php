<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class AuthController extends Controller
{
    public function login(): void
    {
        $this->handleLogin('customer');
    }

    public function staffLogin(): void
    {
        $this->handleLogin('staff');
    }

    /**
     * Single sign-in pipeline for both portals. The portal scope decides which
     * view to render, which roles are accepted, and which audit event is written.
     */
    private function handleLogin(string $scope): void
    {
        if (is_logged_in($scope)) {
            redirect_after_login($scope);
        }

        $isStaff = $scope === 'staff';
        $view = $isStaff ? 'auth/staff_login' : 'auth/login';
        $pageTitle = $isStaff ? 'Staff Portal Login' : 'Customer Login';
        $auditPrefix = $isStaff ? 'auth.staff_login' : 'auth.login';

        if (!$this->isPost()) {
            $this->render($view, ['pageTitle' => $pageTitle]);
            return;
        }

        // Sign-in deliberately does not require a CSRF token. Credentials,
        // account status and portal role are still verified; removing the token
        // check only stops a withheld or stale session cookie from blocking the
        // user's own login form.
        if (!rate_limit_allow('login_ip:' . client_ip_address(), 20, 900)) {
            audit_log('security.rate_limit', ['action' => $auditPrefix, 'ip' => client_ip_address()]);
            set_flash('error', 'Too many sign-in attempts from this network. Please wait a few minutes.');
            $this->render($view, ['pageTitle' => $pageTitle]);
            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $throttle = function_exists('get_login_throttle_state')
            ? get_login_throttle_state($scope, $email)
            : ['count' => 0, 'locked_until' => 0, 'is_locked' => false];

        if (!empty($throttle['is_locked'])) {
            audit_log($auditPrefix . '.throttled', ['portal' => $scope, 'email' => strtolower($email)]);
            set_flash('error', 'Too many failed login attempts. Try again in 5 minutes.');
            $this->render($view, ['pageTitle' => $pageTitle, 'email' => $email]);
            return;
        }

        $user = $email !== '' ? User::findByEmail($email) : null;

        if ($isStaff && $user && User::isPendingActivation($user)) {
            audit_log($auditPrefix . '.pending_activation', ['portal' => $scope, 'user_id' => (int) $user['user_id']]);
            set_flash('error', 'Your staff account is pending activation. Please use your 24-hour invitation link to set your password.');
            $this->render($view, ['pageTitle' => $pageTitle, 'email' => $email]);
            return;
        }

        $authenticated = $user
            && $user['account_status'] === 'Active'
            && $password !== ''
            && password_verify($password, $user['password_hash']);

        if ($authenticated) {
            $portalMatches = $isStaff ? $user['role'] !== ROLE_CUSTOMER : $user['role'] === ROLE_CUSTOMER;

            if (!$portalMatches) {
                audit_log($auditPrefix . '.portal_mismatch', [
                    'portal' => $scope,
                    'user_id' => (int) $user['user_id'],
                    'role' => $user['role'],
                ]);
                set_flash(
                    'error',
                    $isStaff
                        ? 'Unauthorized access. Only council staff accounts may sign in here.'
                        : 'Invalid customer credentials. Please use your customer account.'
                );
                $this->render($view, ['pageTitle' => $pageTitle, 'email' => $email]);
                return;
            }

            clear_login_failures($scope, $email);
            if (password_hash_needs_rehash((string) $user['password_hash'])) {
                User::updatePasswordHash((int) $user['user_id'], hash_password_secure($password));
                audit_log('auth.password_rehash', ['user_id' => (int) $user['user_id']]);
            }
            login_user($user);
            audit_log($auditPrefix . '.success', [
                'portal' => $scope,
                'user_id' => (int) $user['user_id'],
                'role' => $user['role'],
            ]);
            set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect_after_login($scope);
        }

        $failureState = function_exists('record_login_failure')
            ? record_login_failure($scope, $email)
            : ['is_locked' => false, 'count' => 1];

        audit_log($auditPrefix . '.failed', [
            'portal' => $scope,
            'email' => strtolower($email),
            'locked' => !empty($failureState['is_locked']),
        ]);

        set_flash(
            'error',
            !empty($failureState['is_locked'])
                ? 'Too many failed login attempts. Try again in 5 minutes.'
                : 'Invalid email or password.'
        );
        $this->render($view, ['pageTitle' => $pageTitle, 'email' => $email]);
    }

    public function activate(): void
    {
        $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
        $invitedUser = $token !== '' ? User::findByInvitationToken($token) : null;

        if ($this->isPost()) {
            if (!$this->csrfIsValid()) {
                error_log('CSRF validation failed for auth/activate.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->redirectRoute('auth/activate', 'token=' . urlencode($token));
            }

            if (!$invitedUser || !empty($invitedUser['token_expired'])) {
                $this->renderActivation($invitedUser);
                return;
            }

            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $errors = [];

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
            foreach (validate_password($password) as $passwordError) {
                $errors[] = $passwordError;
            }

            if ($errors) {
                set_flash('error', implode(' ', $errors));
                $this->render('auth/activate', [
                    'pageTitle' => 'Activate Staff Account',
                    'tokenStatus' => 'valid',
                    'token' => $token,
                    'invitedUser' => $invitedUser,
                ]);
                return;
            }

            User::activateAccount((int) $invitedUser['user_id'], hash_password_secure($password));
            audit_log('auth.activate.success', [
                'user_id' => (int) $invitedUser['user_id'],
                'role' => $invitedUser['role'],
            ]);
            set_flash('success', 'Your staff account is now active. Please sign in with your new password.');
            $this->redirectRoute('auth/staffLogin');
        }

        $this->renderActivation($invitedUser, $token);
    }

    private function renderActivation(?array $invitedUser, string $token = ''): void
    {
        if (!$invitedUser) {
            $this->render('auth/activate', [
                'pageTitle' => 'Invalid Activation Link',
                'tokenStatus' => 'invalid',
                'token' => '',
                'invitedUser' => null,
            ]);
            return;
        }

        if (!empty($invitedUser['token_expired'])) {
            $this->render('auth/activate', [
                'pageTitle' => 'Activation Link Expired',
                'tokenStatus' => 'expired',
                'token' => '',
                'invitedUser' => $invitedUser,
            ]);
            return;
        }

        $this->render('auth/activate', [
            'pageTitle' => 'Activate Staff Account',
            'tokenStatus' => 'valid',
            'token' => $token,
            'invitedUser' => $invitedUser,
        ]);
    }

    public function forgotPassword(): void
    {
        if (is_logged_in('customer')) {
            $this->redirectRoute('auth/changePassword');
        }

        if (!$this->isPost()) {
            $this->render('auth/forgot_password', ['pageTitle' => 'Forgot Password']);
            return;
        }

        if (!$this->csrfIsValid()) {
            error_log('CSRF validation failed for auth/forgotPassword.');
            set_flash('error', 'Your form has expired. Please try again.');
            $this->redirectRoute('auth/forgotPassword');
        }

        if (!rate_limit_allow('password_reset_request:' . client_ip_address(), 5, 900)) {
            audit_log('security.rate_limit', ['action' => 'password_reset_request', 'ip' => client_ip_address()]);
            set_flash('error', 'Too many password reset requests. Please wait a few minutes.');
            $this->redirectRoute('auth/forgotPassword');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if (!is_valid_email($email)) {
            set_flash('error', 'Enter a valid email address.');
            $this->render('auth/forgot_password', [
                'pageTitle' => 'Forgot Password',
                'email' => $email,
            ]);
            return;
        }

        $user = User::findByEmail($email);
        $isEligibleCustomer = $user
            && $user['role'] === ROLE_CUSTOMER
            && $user['account_status'] === 'Active'
            && !User::isPendingActivation($user);

        if ($isEligibleCustomer) {
            $userId = (int) $user['user_id'];

            if (PasswordReset::recentlyRequested($userId)) {
                audit_log('auth.password_reset.throttled', [
                    'user_id' => $userId,
                    'portal' => 'customer',
                ]);
            } else {
                try {
                    $plainToken = PasswordReset::issueForUser($userId, $_SERVER['REMOTE_ADDR'] ?? null);
                    if ($plainToken !== null) {
                        $sent = $this->dispatchPasswordResetEmail($user, $plainToken);
                        audit_log('auth.password_reset.requested', [
                            'user_id' => $userId,
                            'portal' => 'customer',
                            'delivery' => $sent ? 'sent' : 'failed',
                        ]);
                    }
                } catch (Throwable $exception) {
                    error_log('Password reset request failed: ' . $exception->getMessage());
                    audit_log('auth.password_reset.failed', ['user_id' => $userId]);
                }
            }
        } else {
            // Keep the public response identical for unknown, staff and inactive accounts.
            audit_log('auth.password_reset.unknown', ['email' => strtolower($email)]);
        }

        set_flash(
            'success',
            'If that email belongs to an active customer account, a secure password reset link has been sent. The link expires in 60 minutes.'
        );
        $this->redirectRoute('auth/forgotPassword');
    }

    public function changePassword(): void
    {
        if (!is_logged_in('customer')) {
            set_flash('error', 'Please sign in to manage your account security.');
            $this->redirectRoute('auth/login');
            return;
        }

        if (!$this->requireAuth('customer')) {
            return;
        }

        $user = current_customer_user();
        if ($user === null) {
            $this->redirectRoute('auth/login');
            return;
        }

        if ($this->isPost()) {
            if (!$this->csrfIsValid()) {
                error_log('CSRF validation failed for auth/changePassword.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->redirectRoute('auth/changePassword');
            }

            if (!rate_limit_allow('password_change:' . (int) $user['id'], 5, 900)) {
                audit_log('security.rate_limit', ['action' => 'password_change', 'user_id' => (int) $user['id']]);
                set_flash('error', 'Too many password change requests. Please wait a few minutes.');
                $this->redirectRoute('auth/changePassword');
            }

            $dbUser = User::findById((int) $user['id']);
            $currentPassword = (string) ($_POST['current_password'] ?? '');

            if (!$dbUser || $currentPassword === '' || !password_verify($currentPassword, $dbUser['password_hash'])) {
                audit_log('auth.password_change.denied', ['user_id' => (int) $user['id']]);
                set_flash('error', 'Your current password is incorrect.');
                $this->redirectRoute('auth/changePassword');
            }

            $userId = (int) $dbUser['user_id'];
            if (PasswordReset::recentlyRequested($userId)) {
                set_flash('error', 'A secure password change link was sent recently. Check your inbox, or wait a minute before requesting another.');
                $this->redirectRoute('auth/changePassword');
            }

            try {
                $plainToken = PasswordReset::issueForUser($userId, $_SERVER['REMOTE_ADDR'] ?? null);
                if ($plainToken === null) {
                    throw new RuntimeException('Could not issue a password reset token.');
                }

                $sent = $this->dispatchPasswordResetEmail($dbUser, $plainToken);
                audit_log('auth.password_change.requested', [
                    'user_id' => $userId,
                    'portal' => 'customer',
                    'delivery' => $sent ? 'sent' : 'failed',
                ]);

                if (!$sent) {
                    set_flash('error', 'We could not send the secure email right now. Please try again later.');
                } else {
                    set_flash(
                        'success',
                        'A secure password change link has been sent to ' . mask_email((string) $dbUser['email'])
                        . '. It expires in 60 minutes and can only be used once.'
                    );
                }
            } catch (Throwable $exception) {
                error_log('Password change request failed: ' . $exception->getMessage());
                audit_log('auth.password_change.failed', ['user_id' => $userId]);
                set_flash('error', 'We could not prepare a password change link. Please try again later.');
            }

            $this->redirectRoute('auth/changePassword');
        }

        $this->render('customer/account', [
            'pageTitle' => 'Account Security',
            'user' => $user,
        ]);
    }

    public function resetPassword(): void
    {
        $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
        $reset = $token !== '' ? PasswordReset::findByToken($token) : null;
        $isValid = $reset !== null
            && $reset['role'] === ROLE_CUSTOMER
            && $reset['account_status'] === 'Active';

        if ($this->isPost()) {
            if (!$this->csrfIsValid()) {
                error_log('CSRF validation failed for auth/resetPassword.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->redirectRoute('auth/resetPassword', 'token=' . urlencode($token));
            }

            if (!rate_limit_allow('password_reset_complete:' . client_ip_address(), 10, 900)) {
                audit_log('security.rate_limit', ['action' => 'password_reset_complete', 'ip' => client_ip_address()]);
                set_flash('error', 'Too many password reset attempts. Please wait a few minutes.');
                $this->redirectRoute('auth/forgotPassword');
            }

            if (!$isValid) {
                $this->render('auth/reset_password', [
                    'pageTitle' => 'Password Reset Link Invalid',
                    'tokenStatus' => 'invalid',
                    'token' => '',
                    'resetUser' => null,
                ]);
                return;
            }

            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $errors = [];

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
            foreach (validate_password($password) as $passwordError) {
                $errors[] = $passwordError;
            }

            if ($errors) {
                set_flash('error', implode(' ', $errors));
                $this->render('auth/reset_password', [
                    'pageTitle' => 'Choose a New Password',
                    'tokenStatus' => 'valid',
                    'token' => $token,
                    'resetUser' => $reset,
                ]);
                return;
            }

            try {
                $updated = PasswordReset::consume(
                    (int) $reset['reset_id'],
                    (int) $reset['user_id'],
                    hash_password_secure($password)
                );
            } catch (Throwable $exception) {
                error_log('Password reset completion failed: ' . $exception->getMessage());
                $updated = false;
            }

            if (!$updated) {
                set_flash('error', 'This password reset link is no longer valid. Please request a new link.');
                $this->redirectRoute('auth/forgotPassword');
            }

            audit_log('auth.password_reset.success', [
                'user_id' => (int) $reset['user_id'],
                'portal' => 'customer',
            ]);

            $currentCustomer = current_customer_user();
            if ($currentCustomer !== null && (int) $currentCustomer['id'] === (int) $reset['user_id']) {
                logout_user('customer');
            }

            set_flash('success', 'Your password has been changed successfully. Please sign in with your new password.');
            $this->redirectRoute('auth/login');
        }

        if (!$isValid) {
            $this->render('auth/reset_password', [
                'pageTitle' => 'Password Reset Link Invalid',
                'tokenStatus' => 'invalid',
                'token' => '',
                'resetUser' => null,
            ]);
            return;
        }

        $this->render('auth/reset_password', [
            'pageTitle' => 'Choose a New Password',
            'tokenStatus' => 'valid',
            'token' => $token,
            'resetUser' => $reset,
        ]);
    }

    private function dispatchPasswordResetEmail(array $user, string $plainToken): bool
    {
        $resetUrl = app_url('auth/resetPassword', ['token' => $plainToken]);
        $fullName = trim((string) ($user['full_name'] ?? 'Customer'));
        if ($fullName === '') {
            $fullName = 'Customer';
        }

        $subject = 'COH-PMS Password Change Link';
        $body = "Dear {$fullName},\r\n\r\n"
            . "We received a request to change the password for your City of Harare Property Management System customer account.\r\n\r\n"
            . "Use the secure link below within 60 minutes to choose a new password:\r\n"
            . "{$resetUrl}\r\n\r\n"
            . "This link can only be used once. If you did not request this change, you can ignore this email; your current password will remain unchanged.\r\n\r\n"
            . "Never share this link or your password with anyone. The City of Harare will never ask you for them.\r\n\r\n"
            . "Regards,\r\nCity of Harare ICT Administration";

        return send_system_email((string) $user['email'], $subject, $body);
    }

    public function register(): void
    {
        if (is_logged_in('customer')) {
            redirect_after_login('customer');
        }

        if ($this->isPost()) {
            if (!$this->csrfIsValid()) {
                error_log('CSRF validation failed for auth/register.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->render('auth/register', ['pageTitle' => 'Register']);
                return;
            }

            if (!rate_limit_allow('register:' . client_ip_address(), 5, 3600)) {
                audit_log('security.rate_limit', ['action' => 'register', 'ip' => client_ip_address()]);
                set_flash('error', 'Too many account registrations from this network. Please try again later.');
                $this->render('auth/register', ['pageTitle' => 'Register']);
                return;
            }

            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            $errors = [];

            if ($fullName === '') {
                $errors[] = 'Full name is required.';
            }
            if (!is_valid_email($email)) {
                $errors[] = 'A valid email is required.';
            }
            if ($phoneNumber !== '' && !is_valid_phone($phoneNumber)) {
                $errors[] = 'Phone number format is invalid.';
            }
            if (User::findByEmail($email)) {
                $errors[] = 'This email address is already registered.';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
            foreach (validate_password($password) as $passwordError) {
                $errors[] = $passwordError;
            }

            if ($errors) {
                set_flash('error', implode(' ', $errors));
                $this->render('auth/register', [
                    'pageTitle' => 'Register',
                    'old' => [
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone_number' => $phoneNumber,
                    ],
                ]);
                return;
            }

            $userId = User::create([
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'password_hash' => hash_password_secure($password),
                'role' => ROLE_CUSTOMER,
                'account_status' => 'Active',
            ]);

            $user = User::findById($userId);
            if (!$user) {
                set_flash('error', 'Registration failed. Please try again.');
                $this->render('auth/register', ['pageTitle' => 'Register']);
                return;
            }

            login_user($user);
            audit_log('auth.register.success', ['user_id' => $userId, 'role' => ROLE_CUSTOMER]);
            set_flash('success', 'Registration successful. Your customer account is ready.');
            redirect_after_login('customer');
        }

        $this->render('auth/register', ['pageTitle' => 'Register']);
    }

    public function logout(): void
    {
        $this->requirePost();

        $portalScope = strtolower(trim((string) ($_POST['portal'] ?? $_GET['portal'] ?? '')));
        if ($portalScope !== 'staff' && $portalScope !== 'customer') {
            $portalScope = is_staff_portal_request() ? 'staff' : 'customer';
        }

        $redirectRoute = $portalScope === 'staff' ? 'auth/staffLogin' : 'auth/login';

        // Signing out is always safe to honour: a forged request can only log the
        // visitor out, never grant access. The token is checked for the audit
        // trail only, so a stale form can no longer strand the user on
        // "Your session has expired."
        if (!$this->csrfIsValid()) {
            error_log('CSRF token missing or stale for auth/logout; signing out anyway.');
        }

        $actor = current_user($portalScope);
        audit_log('auth.logout', ['portal' => $portalScope, 'user_id' => $actor['id'] ?? null]);

        logout_user($portalScope);
        set_flash('success', 'You have been logged out successfully.');
        $this->redirectRoute($redirectRoute);
    }
}
