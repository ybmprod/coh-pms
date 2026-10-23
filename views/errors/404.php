<?php
declare(strict_types=1);
$isStaffPortal = is_staff_portal_request();
$returnUrl = $isStaffPortal ? BASE_URL . '/?r=home/staff' : BASE_URL . '/?r=home/index';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php if ($isStaffPortal): ?>
        <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>
    <?php else: ?>
        <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <?php endif; ?>
    <main id="main-content" class="container narrow">
        <section class="card error-box" aria-labelledby="error-404-title">
            <span class="error-code">HTTP 404 &bull; Not Found</span>
            <h1 id="error-404-title">404 — Page Not Found</h1>
            <p>The page you requested could not be found or may have been moved.</p>
            <div class="button-row">
                <a class="button primary" href="<?php echo e($returnUrl); ?>">Return home</a>
            </div>
        </section>
    </main>
    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
