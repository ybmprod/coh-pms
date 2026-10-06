<?php
declare(strict_types=1);

$flashMessage = $flash ?? null;
$loggedIn = is_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Staff Portal | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body style="background: #f4f7fa;">
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flashMessage): ?>
        <div class="flash flash-<?php echo e($flashMessage['type']); ?>" role="alert">
            <?php echo e($flashMessage['message']); ?>
        </div>
    <?php endif; ?>

    <main class="container home-main">
        <section class="hero" style="text-align: center; padding-top: 48px; padding-bottom: 56px; background: radial-gradient(circle at 90% 20%, rgb(217 164 65 / 23%), transparent 27%), linear-gradient(130deg, #0b3c5d 0%, #082d46 100%);">
            <div style="margin-bottom: 24px;">
                <img src="<?php echo e(BASE_URL); ?>/assets/images/coh-pms-logo.png" alt="COH Logo" style="width: 140px; height: 140px; border-radius: 50%; border: 3px solid var(--accent); object-fit: cover; background: #fff; box-shadow: var(--shadow-md);">
            </div>
            <span class="hero-eyebrow">City of Harare Property Management System</span>
            <h1 style="margin-left: auto; margin-right: auto;">Council Staff Portal</h1>
            <p style="margin-left: auto; margin-right: auto;">
                Welcome to the internal workspace. Manage venues, review customer booking requests, process payments, and configure system pricing rules.
            </p>
            <div class="button-row" style="justify-content: center;">
                <?php if ($loggedIn): ?>
                    <a href="<?php echo e(BASE_URL . '/?r=dashboard/index'); ?>" class="button primary">
                        Enter Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>" class="button primary">
                        Staff Login
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section class="home-steps" aria-labelledby="steps-title">
            <div class="section-heading">
                <span class="eyebrow">Portal Overview</span>
                <h2 id="steps-title">Administrative Workflows</h2>
            </div>

            <div class="status-grid">
                <article class="card step-card">
                    <span class="step-number" aria-hidden="true">01</span>
                    <h3>Review Bookings</h3>
                    <p>Process pending customer requests. Approve or reject bookings based on availability.</p>
                </article>

                <article class="card step-card">
                    <span class="step-number" aria-hidden="true">02</span>
                    <h3>Manage Venues</h3>
                    <p>Update venue details, capacities, and base pricing for all council facilities.</p>
                </article>

                <article class="card step-card">
                    <span class="step-number" aria-hidden="true">03</span>
                    <h3>Payment Verification</h3>
                    <p>Verify customer payments and issue receipts for approved and completed bookings.</p>
                </article>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>

