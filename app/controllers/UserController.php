<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class UserController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR], 'staff')) {
            return;
        }

        $users = User::getAll();
        $lastInvitation = $_SESSION['last_invitation'] ?? null;
        unset($_SESSION['last_invitation']);

        $this->render('staff/users', [
            'pageTitle' => 'User Management',
            'users' => $users,
            'lastInvitation' => $lastInvitation,
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
                error_log('CSRF validation failed for user/create.');
                set_flash('error', 'Your session has expired. Please try again.');
                $this->redirect(BASE_URL . '/?r=user/index');
            }

            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            if ($fullName === '' && ($firstName !== '' || $lastName !== '')) {
                $fullName = trim($firstName . ' ' . $lastName);
            }

            $email = trim((string) ($_POST['email'] ?? ''));
            $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? '');

            $errors = [];

            if ($fullName === '') {
                $errors[] = 'First and last name are required.';
            }
            if (!is_valid_email($email)) {
                $errors[] = 'A valid council email address is required.';
            }
            if ($phoneNumber !== '' && !is_valid_phone($phoneNumber)) {
                $errors[] = 'Phone number format is invalid.';
            }
            if (!in_array($role, [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT], true)) {
                $errors[] = 'A valid staff role is required.';
            }
            if ($email !== '' && User::findByEmail($email)) {
                $errors[] = 'This email address already exists.';
            }

            // Optional direct password fallback if explicitly supplied; otherwise use 24-hour invitation token
            $useInvitationWorkflow = ($password === '');
            if (!$useInvitationWorkflow) {
                $passwordErrors = validate_password($password);
                foreach ($passwordErrors as $passwordError) {
                    $errors[] = $passwordError;
                }
            }

            if ($errors) {
                set_flash('error', implode(' ', $errors));
                $this->redirect(BASE_URL . '/?r=user/index#add');
            }

            if ($useInvitationWorkflow) {
                $plainToken = bin2hex(random_bytes(32));
                $encodedInvite = User::encodeInvitationHash($plainToken);

                $newUserId = User::create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
                    'password_hash' => $encodedInvite,
                    'role' => $role,
                    'account_status' => 'Inactive',
                ]);

                $activationUrl = $this->buildActivationUrl($plainToken);
                $this->dispatchOnboardingInvitation($fullName, $email, $role, $activationUrl);

                $_SESSION['last_invitation'] = [
                    'full_name' => $fullName,
                    'email' => $email,
                    'role' => $role,
                    'activation_url' => $activationUrl,
                ];

                if (function_exists('audit_log')) {
                    audit_log('user.provision_invite', [
                        'new_user_id' => $newUserId,
                        'email' => $email,
                        'role' => $role,
                    ]);
                }

                set_flash('success', 'Staff profile created in Pending Activation state. A 24-hour onboarding activation link has been generated.');
                $this->redirect(BASE_URL . '/?r=user/index#staff');
            }

            $newUserId = User::create([
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role' => $role,
                'account_status' => 'Active',
            ]);

            if (function_exists('audit_log')) {
                audit_log('user.create_direct', [
                    'new_user_id' => $newUserId,
                    'email' => $email,
                    'role' => $role,
                ]);
            }

            set_flash('success', 'Staff account created successfully.');
            $this->redirect(BASE_URL . '/?r=user/index#staff');
        }

        $this->redirect(BASE_URL . '/?r=user/index');
    }

    public function resendInvite(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for user/resendInvite.');
            set_flash('error', 'Your session has expired.');
            $this->redirect(BASE_URL . '/?r=user/index#staff');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $user = User::findById($userId);

        if (!$user || !User::isPendingActivation($user)) {
            set_flash('error', 'This account is already activated or does not exist.');
            $this->redirect(BASE_URL . '/?r=user/index#staff');
        }

        $plainToken = bin2hex(random_bytes(32));
        $encodedInvite = User::encodeInvitationHash($plainToken);
        User::updateInvitationToken($userId, $encodedInvite);

        $activationUrl = $this->buildActivationUrl($plainToken);
        $this->dispatchOnboardingInvitation((string) $user['full_name'], (string) $user['email'], (string) $user['role'], $activationUrl);

        $_SESSION['last_invitation'] = [
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'activation_url' => $activationUrl,
        ];

        if (function_exists('audit_log')) {
            audit_log('user.invite_resent', [
                'target_user_id' => $userId,
                'email' => $user['email'],
                'role' => $user['role'],
            ]);
        }

        set_flash('success', 'A new 24-hour invitation link has been issued for ' . $user['full_name'] . '.');
        $this->redirect(BASE_URL . '/?r=user/index#staff');
    }

    public function toggleStatus(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR], 'staff')) {
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

        if (User::isPendingActivation($user)) {
            set_flash('error', 'This staff account is awaiting password setup via its invitation link.');
            $this->redirect(BASE_URL . '/?r=user/index#staff');
        }

        $currentUser = current_staff_user();
        if ($userId === (int) ($currentUser['id'] ?? 0)) {
            set_flash('error', 'You cannot deactivate your own account.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }

        $nextStatus = $user['account_status'] === 'Active' ? 'Inactive' : 'Active';
        if ($nextStatus === 'Inactive' && $user['role'] === ROLE_ADMINISTRATOR && User::countActiveAdministrators() <= 1) {
            set_flash('error', 'The last active Administrator cannot be deactivated.');
            $this->redirect(BASE_URL . '/?r=user/index');
        }
        User::updateStatus($userId, $nextStatus);

        if (function_exists('audit_log')) {
            audit_log('user.status_toggle', [
                'target_user_id' => $userId,
                'email' => $user['email'],
                'role' => $user['role'],
                'previous_status' => $user['account_status'],
                'new_status' => $nextStatus,
            ]);
        }

        set_flash('success', 'User account status updated to ' . $nextStatus . '.');
        $this->redirect(BASE_URL . '/?r=user/index');
    }

    private function buildActivationUrl(string $plainToken): string
    {
        if (str_starts_with(BASE_URL, 'http://') || str_starts_with(BASE_URL, 'https://')) {
            return rtrim(BASE_URL, '/') . '/?r=auth/activate&token=' . urlencode($plainToken);
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/' . ltrim(BASE_URL, '/') . '/?r=auth/activate&token=' . urlencode($plainToken);
    }

    private function dispatchOnboardingInvitation(string $fullName, string $email, string $role, string $activationUrl): void
    {
        $subject = 'City of Harare Property Management System - Staff Account Activation';
        $body = "Dear {$fullName},\r\n\r\n"
            . "An administrator has provisioned a {$role} account for you on the City of Harare Property Management System (COH-PMS).\r\n\r\n"
            . "Please set your password and activate your account within 24 hours using the secure link below:\r\n"
            . "{$activationUrl}\r\n\r\n"
            . "Regards,\r\nCity of Harare ICT Administration";

        error_log("[COH-PMS Onboarding Invitation] To: {$email} | Role: {$role} | Link: {$activationUrl}");
        if (function_exists('mail') && ini_get('SMTP')) {
            @mail($email, $subject, $body, "From: no-reply@hararecity.co.zw\r\nContent-Type: text/plain; charset=UTF-8");
        }
    }
}
