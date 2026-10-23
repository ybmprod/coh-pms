<?php
declare(strict_types=1);
$isStaffPortal = is_staff_portal_request();
$returnUrl = $isStaffPortal
    ? (is_logged_in('staff') ? BASE_URL . '/?r=dashboard/index' : BASE_URL . '/?r=home/staff')
    : (is_logged_in('customer') ? BASE_URL . '/?r=dashboard/customer' : BASE_URL . '/?r=home/index');
$returnLabel = ($isStaffPortal ? is_logged_in('staff') : is_logged_in('customer')) ? 'Return to dashboard' : 'Return home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>403 - Access denied</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php if ($isStaffPortal): ?>
        <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>
    <?php else: ?>
        <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <?php endif; ?>
    <main id="main-content" class="container narrow">
        <section class="card error-box" aria-labelledby="error-403-title">
            <span class="error-code">HTTP 403 &bull; Access Denied</span>
            <h1 id="error-403-title">403 — Restricted Resource</h1>
            <p>You do not have permission to view this page with your current role credentials.</p>
            <div class="button-row">
                <a class="button primary" href="<?php echo e($returnUrl); ?>"><?php echo e($returnLabel); ?></a>
            </div>
        </section>
    </main>
    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
