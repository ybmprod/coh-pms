<?php
declare(strict_types=1);
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
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <main class="container narrow">
        <section class="card error-box">
            <h1>403</h1>
            <p>You do not have permission to view this page.</p>
            <a class="button primary" href="<?php echo e(BASE_URL . '/?r=dashboard/index'); ?>">Return to dashboard</a>
        </section>
    </main>
    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
