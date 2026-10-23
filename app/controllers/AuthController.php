<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class AuthController extends Controller
{
    public function login(): void
    {
        if (is_logged_in('customer')) {
            redirect_after_login('customer');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for auth/login.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->render('auth/login', ['pageTitle' => 'Customer Login']);
                return;
            }

            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $attemptKey = hash('sha256', 'customer:' . strtolower($email));
            $attempts = $_SESSION['login_attempts'] ?? [];
            $attempt = $attempts[$attemptKey] ?? ['count' => 0, 'locked_until' => 0];
            if ((int) $attempt['locked_until'] > time()) {
                set_flash('error', 'Too many failed login attempts. Try again in 5 minutes.');
                $this->render('auth/login', ['pageTitle' => 'Customer Login']);
                return;
            }

            if ((int) $attempt['locked_until'] > 0) {
                $attempt = ['count' => 0, 'locked_until' => 0];
            }

            $user = $email !== '' ? User::findByEmail($email) : null;
            if ($user && $user['account_status'] === 'Active' && $password !== '' && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== ROLE_CUSTOMER) {
                    set_flash('error', 'Invalid customer credentials. Please use your customer account.');
                    $this->render('auth/login', ['pageTitle' => 'Customer Login']);
                    return;
                }

                unset($attempts[$attemptKey]);
                $_SESSION['login_attempts'] = $attempts;
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect_after_login('customer');
            }

            $attempt['count'] = (int) $attempt['count'] + 1;
            if ($attempt['count'] >= 5) {
                $attempt['count'] = 0;
                $attempt['locked_until'] = time() + 300;
                set_flash('error', 'Too many failed login attempts. Try again in 5 minutes.');
            } else {
                set_flash('error', 'Invalid email or password.');
            }
            $attempts[$attemptKey] = $attempt;
            $_SESSION['login_attempts'] = $attempts;
            $this->render('auth/login', ['pageTitle' => 'Customer Login']);
            return;
        }

        $this->render('auth/login', ['pageTitle' => 'Customer Login']);
    }

    public function staffLogin(): void
    {
        if (is_logged_in('staff')) {
            redirect_after_login('staff');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for auth/staffLogin.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
                return;
            }

            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $attemptKey = hash('sha256', 'staff:' . strtolower($email));
            $attempts = $_SESSION['login_attempts'] ?? [];
            $attempt = $attempts[$attemptKey] ?? ['count' => 0, 'locked_until' => 0];
            if ((int) $attempt['locked_until'] > time()) {
                set_flash('error', 'Too many failed login attempts. Try again in 5 minutes.');
                $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
                return;
            }

            if ((int) $attempt['locked_until'] > 0) {
                $attempt = ['count' => 0, 'locked_until' => 0];
            }

            $user = $email !== '' ? User::findByEmail($email) : null;
            if ($user && User::isPendingActivation($user)) {
                set_flash('error', 'Your staff account is pending activation. Please use your 24-hour invitation link to set your password.');
                $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
                return;
            }

            if ($user && $user['account_status'] === 'Active' && $password !== '' && password_verify($password, $user['password_hash'])) {
                if ($user['role'] === ROLE_CUSTOMER) {
                    set_flash('error', 'Unauthorized access. Only council staff accounts may sign in here.');
                    $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
                    return;
                }

                unset($attempts[$attemptKey]);
                $_SESSION['login_attempts'] = $attempts;
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect_after_login('staff');
            }

            $attempt['count'] = (int) $attempt['count'] + 1;
            if ($attempt['count'] >= 5) {
                $attempt['count'] = 0;
                $attempt['locked_until'] = time() + 300;
                set_flash('error', 'Too many failed login attempts. Try again in 5 minutes.');
            } else {
                set_flash('error', 'Invalid email or password.');
            }
            $attempts[$attemptKey] = $attempt;
            $_SESSION['login_attempts'] = $attempts;
            $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
            return;
        }

        $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
    }

    public function activate(): void
    {
        $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
        $invitedUser = $token !== '' ? User::findByInvitationToken($token) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for auth/activate.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->redirect(BASE_URL . '/?r=auth/activate&token=' . urlencode($token));
            }

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

            User::activateAccount((int) $invitedUser['user_id'], password_hash($password, PASSWORD_DEFAULT));
            set_flash('success', 'Your staff account is now active. Please sign in with your new password.');
            $this->redirect(BASE_URL . '/?r=auth/staffLogin');
        }

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

    public function register(): void
    {
        if (is_logged_in('customer')) {
            redirect_after_login('customer');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for auth/register.');
                set_flash('error', 'Your form has expired. Please try again.');
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
            $passwordErrors = validate_password($password);
            foreach ($passwordErrors as $passwordError) {
                $errors[] = $passwordError;
            }

            if ($errors) {
                set_flash('error', implode(' ', $errors));
                $this->render('auth/register', ['pageTitle' => 'Register']);
                return;
            }

            $userId = User::create([
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
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
            set_flash('success', 'Registration successful. Your customer account is ready.');
            redirect_after_login('customer');
            return;
        }

        $this->render('auth/register', ['pageTitle' => 'Register']);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $portalScope = strtolower(trim((string) ($_POST['portal'] ?? $_GET['portal'] ?? '')));
        if ($portalScope !== 'staff' && $portalScope !== 'customer') {
            $portalScope = is_staff_portal_request() ? 'staff' : 'customer';
        }

        $redirectUrl = $portalScope === 'staff'
            ? BASE_URL . '/?r=auth/staffLogin'
            : BASE_URL . '/?r=auth/login';

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for auth/logout.');
            set_flash('error', 'Your session has expired.');
            $this->redirect($redirectUrl);
        }

        logout_user($portalScope);
        set_flash('success', 'You have been logged out successfully.');
        $this->redirect($redirectUrl);
    }
}
