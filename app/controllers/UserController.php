<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class UserController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        $users = User::getAll();
        $this->render('staff/users', [
            'pageTitle' => 'User Management',
            'users' => $users,
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for user/create.');
                set_flash('error', 'Your session has expired. Please try again.');
                $this->redirect(BASE_URL . '/?r=user/index');
            }

            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? '');
            $accountStatus = (string) ($_POST['account_status'] ?? 'Active');

            $errors = [];

            if ($fullName === '') {
                $errors[] = 'Full name is required.';
            }
            if (!is_valid_email($email)) {
                $errors[] = 'A valid email address is required.';
            }
            if ($phoneNumber !== '' && !is_valid_phone($phoneNumber)) {
                $errors[] = 'Phone number format is invalid.';
            }
            if (!in_array($role, [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT], true)) {
                $errors[] = 'A valid staff role is required.';
            }
            if (User::findByEmail($email)) {
                $errors[] = 'This email address already exists.';
            }
            if (!in_array($accountStatus, ['Active', 'Inactive'], true)) {
                $errors[] = 'Account status is invalid.';
            }

            $passwordErrors = validate_password($password);
            foreach ($passwordErrors as $passwordError) {
                $errors[] = $passwordError;
            }

            if ($errors) {
                set_flash('error', implode(' ', $errors));
                $this->redirect(BASE_URL . '/?r=user/index');
            }

            User::create([
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'account_status' => $accountStatus,
            ]);

            set_flash('success', 'Staff account created successfully.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }

        $this->redirect(BASE_URL . '/?r=user/index');
    }

    public function toggleStatus(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for user/toggleStatus.');
            set_flash('error', 'Your session has expired.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $user = User::findById($userId);

        if (!$user) {
            set_flash('error', 'User not found.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }

        if ($userId === (int) current_user()['id']) {
            set_flash('error', 'You cannot deactivate your own account.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }

        $nextStatus = $user['account_status'] === 'Active' ? 'Inactive' : 'Active';
        if ($nextStatus === 'Inactive' && $user['role'] === ROLE_ADMINISTRATOR && User::countActiveAdministrators() <= 1) {
            set_flash('error', 'The last active Administrator cannot be deactivated.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }
        User::updateStatus($userId, $nextStatus);

        set_flash('success', 'User account status updated to ' . $nextStatus . '.');
        $this->redirect(BASE_URL . '/?r=user/index');
    }
}
