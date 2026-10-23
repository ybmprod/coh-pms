<?php
declare(strict_types=1);
$user = current_user();
$flash = get_flash();
$users = $users ?? [];
$lastInvitation = $lastInvitation ?? null;
$allowedRoles = [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT];
$staffUsers = array_filter($users, fn($u) => $u['role'] !== ROLE_CUSTOMER);
$customerUsers = array_filter($users, fn($u) => $u['role'] === ROLE_CUSTOMER);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'User Management'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="user-mgmt-title">
            <header class="page-header">
                <h1 id="user-mgmt-title">User Management</h1>
                <p>Provision staff profiles with role assignments, issue 24-hour password activation links, and manage account status.</p>
            </header>

            <?php if ($lastInvitation): ?>
                <section class="card" style="border-left: 5px solid var(--success); background: var(--success-bg);" aria-labelledby="onboarding-link-title">
                    <h2 id="onboarding-link-title" style="font-size: var(--text-lg); margin-bottom: 6px;">Onboarding Invitation Dispatched (Valid for 24 Hours)</h2>
                    <p style="margin-bottom: 10px;">
                        Staff profile for <strong><?php echo e($lastInvitation['full_name']); ?></strong> (<?php echo e($lastInvitation['role']); ?> &bull; <?php echo e($lastInvitation['email']); ?>) is awaiting self-service password setup:
                    </p>
                    <div class="button-row">
                        <input type="text" readonly value="<?php echo e($lastInvitation['activation_url']); ?>" aria-label="Activation URL" onclick="this.select();" style="flex: 1; min-width: 260px; font-family: monospace; font-size: var(--text-xs); background: var(--surface);">
                        <a class="button secondary small-button" href="<?php echo e($lastInvitation['activation_url']); ?>">Open activation link</a>
                    </div>
                </section>
            <?php endif; ?>

            <section id="add" class="card form-card tab-section" data-tab="add" aria-labelledby="create-staff-heading">
                <header class="form-header">
                    <h2 id="create-staff-heading">Provision staff account</h2>
                    <p>Create a staff profile and assign a system role. A secure 24-hour password setup link will be generated automatically.</p>
                </header>

                <form method="post" action="<?php echo e(BASE_URL . '/?r=user/create'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="first_name">First name</label>
                            <input id="first_name" name="first_name" type="text" required>
                        </div>

                        <div class="field-group">
                            <label for="last_name">Last name</label>
                            <input id="last_name" name="last_name" type="text" required>
                        </div>

                        <div class="field-group">
                            <label for="email">Council email</label>
                            <input id="email" name="email" type="email" placeholder="officer@coh.co.zw" required>
                        </div>

                        <div class="field-group">
                            <label for="phone_number">Phone number (optional)</label>
                            <input id="phone_number" name="phone_number" type="tel">
                        </div>

                        <div class="field-group field-span-full">
                            <label for="role">System role</label>
                            <select id="role" name="role" required>
                                <option value="">Select role</option>
                                <?php foreach ($allowedRoles as $roleOption): ?>
                                    <option value="<?php echo e($roleOption); ?>"><?php echo e($roleOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="button primary full-width">
                        <i class="fi fi-sr-paper-plane" aria-hidden="true"></i>
                        <span>Provision staff &amp; send activation link</span>
                    </button>
                </form>
            </section>

            <section id="staff" class="card tab-section active" data-tab="staff" aria-labelledby="staff-list-heading">
                <header class="section-heading">
                    <h2 id="staff-list-heading">Staff List</h2>
                </header>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffUsers as $existingUser): ?>
                            <?php
                            $isPendingInvite = User::isPendingActivation($existingUser);
                            $isInviteExpired = $isPendingInvite && User::isInvitationExpired($existingUser);
                            $isActive = !$isPendingInvite && $existingUser['account_status'] === 'Active';
                            ?>
                            <tr>
                                <td><strong><?php echo e($existingUser['full_name']); ?></strong></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <?php if ($isPendingInvite): ?>
                                        <span class="status-badge pending">
                                            <?php echo $isInviteExpired ? 'Invite Expired' : 'Pending Activation'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge <?php echo e($isActive ? 'active' : 'inactive'); ?>">
                                            <?php echo e($existingUser['account_status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isPendingInvite): ?>
                                        <form method="post" action="<?php echo e(BASE_URL . '/?r=user/resendInvite'); ?>" class="inline-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                            <button type="submit" class="button primary small-button">
                                                <i class="fi fi-sr-paper-plane" aria-hidden="true"></i>
                                                <span>Resend Invitation</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>" class="inline-form"
                                            <?php if ($isActive): ?>
                                                data-confirm-title="Deactivate account"
                                                data-confirm="Are you sure you want to deactivate <?php echo e($existingUser['full_name']); ?>?"
                                            <?php endif; ?>>
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                            <button type="submit" class="button <?php echo $isActive ? 'secondary' : 'primary'; ?> small-button">
                                                <?php echo e($isActive ? 'Deactivate' : 'Activate'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section id="customers" class="card tab-section" data-tab="customers" aria-labelledby="customer-list-heading">
                <header class="section-heading">
                    <h2 id="customer-list-heading">Customers</h2>
                </header>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerUsers as $existingUser): ?>
                            <?php $isActive = $existingUser['account_status'] === 'Active'; ?>
                            <tr>
                                <td><strong><?php echo e($existingUser['full_name']); ?></strong></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($isActive ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>" class="inline-form"
                                        <?php if ($isActive): ?>
                                            data-confirm-title="Deactivate customer"
                                            data-confirm="Are you sure you want to deactivate <?php echo e($existingUser['full_name']); ?>?"
                                        <?php endif; ?>>
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button <?php echo $isActive ? 'secondary' : 'primary'; ?> small-button">
                                            <?php echo e($isActive ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
