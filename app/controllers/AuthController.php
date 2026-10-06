<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class AuthController extends Controller
{
    public function login(): void
    {
        if (is_logged_in()) {
            redirect_after_login();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for auth/login.');
                set_flash('error', 'Your form has expired. Please try again.');
                $this->render('auth/login', ['pageTitle' => 'Login']);
                return;
            }

            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $attemptKey = hash('sha256', strtolower($email));
            $attempts = $_SESSION['login_attempts'] ?? [];
            $attempt = $attempts[$attemptKey] ?? ['count' => 0, 'locked_until' => 0];
            if ((int) $attempt['locked_until'] > time()) {
                set_flash('error', 'Too many failed login attempts. Try again in 5 minutes.');
                $this->render('auth/login', ['pageTitle' => 'Login']);
                return;
            }

            if ((int) $attempt['locked_until'] > 0) {
                $attempt = ['count' => 0, 'locked_until' => 0];
            }

            $user = $email !== '' ? User::findByEmail($email) : null;
            if ($user && $user['account_status'] === 'Active' && $password !== '' && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== ROLE_CUSTOMER) {
                    set_flash('error', 'Staff members must log in through the Staff Portal.');
                    $this->render('auth/login', ['pageTitle' => 'Customer Login']);
                    return;
                }

                $_SESSION['login_attempts'] = [];
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect_after_login();
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
            $this->render('auth/login', ['pageTitle' => 'Login']);
            return;
        }

        $this->render('auth/login', ['pageTitle' => 'Customer Login']);
    }

    public function staffLogin(): void
    {
        if (is_logged_in()) {
            redirect_after_login();
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

            $attemptKey = hash('sha256', strtolower($email));
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
            if ($user && $user['account_status'] === 'Active' && $password !== '' && password_verify($password, $user['password_hash'])) {
                // Ensure only staff can login here
                if ($user['role'] === ROLE_CUSTOMER) {
                    set_flash('error', 'Customers must log in through the public portal.');
                    $this->render('auth/staff_login', ['pageTitle' => 'Staff Portal Login']);
                    return;
                }

                $_SESSION['login_attempts'] = [];
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect_after_login();
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

    public function register(): void
    {
        if (is_logged_in()) {
            redirect_after_login();
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
            redirect_after_login();
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

        $redirectUrl = BASE_URL . '/?r=auth/login';
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] !== ROLE_CUSTOMER) {
            $redirectUrl = BASE_URL . '/?r=auth/staffLogin';
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for auth/logout.');
            set_flash('error', 'Your session has expired.');
            $this->redirect($redirectUrl);
        }

        logout_user();
        set_flash('success', 'You have been logged out successfully.');
        $this->redirect($redirectUrl);
    }
}
