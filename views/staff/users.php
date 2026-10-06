<?php
declare(strict_types=1);
$user = current_user();
$flash = get_flash();
$users = $users ?? [];
$allowedRoles = [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT];
$staffUsers = array_filter($users, fn($u) => $u['role'] !== ROLE_CUSTOMER);
$customerUsers = array_filter($users, fn($u) => $u['role'] === ROLE_CUSTOMER);
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

    <main class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main">
            <div class="card">
                <h1>User Management</h1>
                <p>Create staff accounts and activate or deactivate them as needed.</p>
            </div>

            <div id="add" class="card form-card tab-section" data-tab="add">
                <h2>Create staff account</h2>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=user/create'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="field-group">
                        <label for="full_name">Full name</label>
                        <input id="full_name" name="full_name" type="text" required>
                    </div>

                    <div class="field-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" required>
                    </div>

                    <div class="field-group">
                        <label for="phone_number">Phone number</label>
                        <input id="phone_number" name="phone_number" type="tel">
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <div class="field-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach ($allowedRoles as $roleOption): ?>
                                <option value="<?php echo e($roleOption); ?>"><?php echo e($roleOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="account_status">Account status</label>
                        <select id="account_status" name="account_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="button primary full-width">Create account</button>
                </form>
            </div>

            <div id="staff" class="card tab-section active" data-tab="staff">
                <h2>Staff List</h2>
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
                            <tr>
                                <td><?php echo e($existingUser['full_name']); ?></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($existingUser['account_status'] === 'Active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button secondary small-button">
                                            <?php echo e($existingUser['account_status'] === 'Active' ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="customers" class="card tab-section" data-tab="customers">
                <h2>Customers</h2>
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
                            <tr>
                                <td><?php echo e($existingUser['full_name']); ?></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($existingUser['account_status'] === 'Active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button secondary small-button">
                                            <?php echo e($existingUser['account_status'] === 'Active' ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html><div id="staff" class="card tab-section active" data-tab="staff">
                <h2>Staff List</h2>
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
                            <tr>
                                <td><?php echo e($existingUser['full_name']); ?></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($existingUser['account_status'] === 'Active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button secondary small-button">
                                            <?php echo e($existingUser['account_status'] === 'Active' ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="customers" class="card tab-section" data-tab="customers">
                <h2>Customers</h2>
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
                            <tr>
                                <td><?php echo e($existingUser['full_name']); ?></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($existingUser['account_status'] === 'Active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button secondary small-button">
                                            <?php echo e($existingUser['account_status'] === 'Active' ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
declare(strict_types=1);
$user = current_user();
$flash = get_flash();
$users = $users ?? [];
$allowedRoles = [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT];
$staffUsers = array_filter($users, fn($u) => $u['role'] !== ROLE_CUSTOMER);
$customerUsers = array_filter($users, fn($u) => $u['role'] === ROLE_CUSTOMER);
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

    <main class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main">
            <div class="card">
                <h1>User Management</h1>
                <p>Create staff accounts and activate or deactivate them as needed.</p>
            </div>

            <div id="add" class="card form-card tab-section" data-tab="add">
                <h2>Create staff account</h2>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=user/create'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="field-group">
                        <label for="full_name">Full name</label>
                        <input id="full_name" name="full_name" type="text" required>
                    </div>

                    <div class="field-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" required>
                    </div>

                    <div class="field-group">
                        <label for="phone_number">Phone number</label>
                        <input id="phone_number" name="phone_number" type="tel">
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <div class="field-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach ($allowedRoles as $roleOption): ?>
                                <option value="<?php echo e($roleOption); ?>"><?php echo e($roleOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="account_status">Account status</label>
                        <select id="account_status" name="account_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="button primary full-width">Create account</button>
                </form>
            </div>

            <div id="staff" class="card tab-section active" data-tab="staff">
                <h2>Staff List</h2>
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
                            <tr>
                                <td><?php echo e($existingUser['full_name']); ?></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($existingUser['account_status'] === 'Active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button secondary small-button">
                                            <?php echo e($existingUser['account_status'] === 'Active' ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="customers" class="card tab-section" data-tab="customers">
                <h2>Customers</h2>
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
                            <tr>
                                <td><?php echo e($existingUser['full_name']); ?></td>
                                <td><?php echo e($existingUser['email']); ?></td>
                                <td><?php echo e($existingUser['role']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo e($existingUser['account_status'] === 'Active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($existingUser['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=user/toggleStatus'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo e((string) $existingUser['user_id']); ?>">
                                        <button type="submit" class="button secondary small-button">
                                            <?php echo e($existingUser['account_status'] === 'Active' ? 'Deactivate' : 'Activate'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
