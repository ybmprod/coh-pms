<?php
declare(strict_types=1);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Staff Login'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container narrower">
        <section class="card form-card" style="border-top: 4px solid var(--primary-dark);" aria-labelledby="staff-login-heading">
            <header class="form-header">
                <span class="eyebrow">Staff Portal</span>
                <h1 id="staff-login-heading">Council Login</h1>
                <p>Secure access for administrative and officer staff.</p>
            </header>

            <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>" class="stacked-form">
                <?php echo csrf_field(); ?>

                <div class="field-group">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="">
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>

                <button type="submit" class="button primary full-width">Access Portal</button>
            </form>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
