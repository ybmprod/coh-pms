<?php
declare(strict_types=1);

$isStaffPortal = ($portalScope ?? '') === 'staff' || (($portalScope ?? '') !== 'customer' && is_staff_portal_request());
$user = $user ?? ($isStaffPortal ? current_staff_user() : current_customer_user());
$flash = get_flash();
$dashboardCounts = $dashboardCounts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Dashboard'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php if ($isStaffPortal): ?>
        <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>
    <?php else: ?>
        <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="dashboard-welcome-title">
            <header class="card welcome-box">
                <span class="pill">
                    <i class="fi <?php echo $isStaffPortal ? 'fi-sr-shield-check' : 'fi-sr-user'; ?>" aria-hidden="true" style="margin-right: 6px;"></i>
                    <?php echo e($user['role'] ?? ($isStaffPortal ? 'Staff' : 'Customer')); ?>
                </span>
                <h1 id="dashboard-welcome-title">Welcome, <?php echo e($user['full_name'] ?? 'User'); ?></h1>
                <?php if ($isStaffPortal): ?>
                    <p>Here’s a summary of current activity across municipal facilities.</p>
                <?php else: ?>
                    <p>Browse available council venues, submit booking requests, and track your reservations.</p>
                <?php endif; ?>
            </header>

            <div class="status-grid">
                <?php if ($isStaffPortal): ?>
                    <?php if (array_key_exists('pending_bookings', $dashboardCounts)): ?>
                        <article class="card">
                            <h2><i class="fi fi-sr-calendar-clock" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>Pending bookings</h2>
                            <p class="dashboard-count"><?php echo e((string) $dashboardCounts['pending_bookings']); ?></p>
                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=booking/index'); ?>">View bookings</a>
                        </article>
                    <?php endif; ?>

                    <?php if (array_key_exists('pending_payments', $dashboardCounts)): ?>
                        <article class="card">
                            <h2><i class="fi fi-sr-credit-card" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>Payments awaiting verification</h2>
                            <p class="dashboard-count"><?php echo e((string) $dashboardCounts['pending_payments']); ?></p>
                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=payment/index'); ?>">View payments</a>
                        </article>
                    <?php endif; ?>

                    <?php if (array_key_exists('today_bookings', $dashboardCounts)): ?>
                        <article class="card">
                            <h2><i class="fi fi-sr-calendar-check" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>Today’s bookings</h2>
                            <p class="dashboard-count"><?php echo e((string) $dashboardCounts['today_bookings']); ?></p>
                        </article>
                    <?php endif; ?>

                    <?php if (array_key_exists('verified_revenue_this_month', $dashboardCounts)): ?>
                        <article class="card">
                            <h2><i class="fi fi-sr-coins" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>Verified revenue this month</h2>
                            <p class="dashboard-count dashboard-count--money">
                                <?php echo e(money_format_usd((float) $dashboardCounts['verified_revenue_this_month'])); ?>
                            </p>
                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=report/index'); ?>">View reports</a>
                        </article>
                    <?php endif; ?>

                    <?php if (($user['role'] ?? '') === ROLE_ADMINISTRATOR): ?>
                        <article class="card">
                            <h2><i class="fi fi-sr-users-alt" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>User management</h2>
                            <p>Manage staff and customer accounts.</p>
                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=user/index'); ?>">Manage user accounts</a>
                        </article>
                    <?php endif; ?>

                    <?php if (($user['role'] ?? '') === ROLE_COUNCIL_MANAGEMENT): ?>
                        <article class="card">
                            <h2><i class="fi fi-sr-chart-histogram" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>Reports</h2>
                            <p>Review council venue activity and revenue.</p>
                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=report/index'); ?>">Open reports</a>
                        </article>
                    <?php endif; ?>
                <?php else: ?>
                    <article class="card">
                        <h2><i class="fi fi-sr-building" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>Browse venues</h2>
                        <p>Find a venue and check its availability.</p>
                        <a class="button primary small-button" href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>">View available venues</a>
                    </article>
                    <article class="card">
                        <h2><i class="fi fi-sr-calendar-clock" aria-hidden="true" style="margin-right: 8px; color: var(--primary);"></i>My bookings</h2>
                        <p>Review reservations and payment status.</p>
                        <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=booking/customerList'); ?>">View my bookings</a>
                    </article>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
