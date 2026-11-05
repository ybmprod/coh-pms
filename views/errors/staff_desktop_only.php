<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Staff Portal Requires a PC | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body class="staff-desktop-only-page">
    <main id="main-content" class="staff-desktop-only-shell">
        <section class="card error-box staff-desktop-only-card" aria-labelledby="staff-desktop-title">
            <span class="brand-mark staff-desktop-only-logo" aria-hidden="true">
                <img src="<?php echo e(BASE_URL); ?>/assets/images/coh-pms-logo.png" alt="City of Harare Logo">
            </span>
            <span class="error-code">Staff Management Portal &middot; PC Access Only</span>
            <h1 id="staff-desktop-title">Please open this portal on a computer</h1>
            <p>
                The City of Harare Council management workspace is configured exclusively for
                desktop and laptop computers to ensure administrative accuracy, audit integrity,
                and comprehensive financial and report tracking. Mobile phones and tablets cannot
                access staff routes.
            </p>
            <p class="staff-desktop-only-note">
                Customers can continue to browse venues, check availability, and manage reservations from any smartphone or tablet.
            </p>
            <div class="button-row staff-desktop-only-actions">
                <a class="button primary" href="<?php echo e(BASE_URL . '/?r=home/index'); ?>">
                    <i class="fi fi-sr-home" aria-hidden="true"></i>
                    <span>Go to Customer Portal</span>
                </a>
                <a class="button ghost" href="tel:0242774141">
                    <i class="fi fi-sr-phone-call" aria-hidden="true"></i>
                    <span>Call 0242 774 141</span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
