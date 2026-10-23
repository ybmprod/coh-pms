<?php
declare(strict_types=1);

$flashMessage = $flash ?? null;
$loggedIn = is_logged_in('staff');
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
<body>
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flashMessage): ?>
        <div class="flash flash-<?php echo e($flashMessage['type']); ?>" role="alert">
            <?php echo e($flashMessage['message']); ?>
        </div>
    <?php endif; ?>

    <main id="main-content" class="container home-main">
        <section class="hero" aria-labelledby="staff-hero-heading">
            <div class="hero-grid">
                <div>
                    <span class="hero-eyebrow">City of Harare Property Management System</span>
                    <h1 id="staff-hero-heading">Council Staff Portal</h1>
                    <p>
                        Welcome to the internal municipal workspace. Manage council venues, review customer booking requests, verify payments, and configure approved tariff rules.
                    </p>
                    <div class="button-row">
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
                </div>

                <aside class="hero-aside" aria-label="Operational roles">
                    <h2>Authorized Divisions</h2>
                    <ul class="hero-aside-list">
                        <li><span>Booking Officers</span><strong>Slot Review</strong></li>
                        <li><span>Revenue Officers</span><strong>Receipting</strong></li>
                        <li><span>Administrators</span><strong>Tariff &amp; Staff</strong></li>
                        <li><span>Council Management</span><strong>Audit Reports</strong></li>
                    </ul>
                </aside>
            </div>
        </section>

        <section class="home-steps" aria-labelledby="steps-title">
            <header class="section-heading">
                <div>
                    <span class="eyebrow">Portal Overview</span>
                    <h2 id="steps-title">Administrative Workflows</h2>
                </div>
            </header>

            <div class="asymmetric-steps-grid">
                <article class="card step-card step-card--featured">
                    <div>
                        <span class="step-number" aria-hidden="true">01</span>
                        <h3>Review Bookings</h3>
                        <p>Process pending customer requests with strict state-machine validation. Approve or reject bookings based on real-time facility availability.</p>
                    </div>
                </article>

                <article class="card step-card">
                    <div>
                        <span class="step-number" aria-hidden="true">02</span>
                        <h3>Manage Venues</h3>
                        <p>Update venue details, capacities, and base pricing for all council facilities.</p>
                    </div>
                </article>

                <article class="card step-card">
                    <div>
                        <span class="step-number" aria-hidden="true">03</span>
                        <h3>Payment Verification</h3>
                        <p>Verify customer payments and issue official receipts for approved bookings.</p>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
