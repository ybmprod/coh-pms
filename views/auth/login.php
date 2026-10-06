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
    <title><?php echo e($pageTitle ?? 'Login'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main class="container narrow">
        <section class="card form-card">
            <div class="form-header">
                <h1>Welcome back</h1>
                <p>Sign in to manage bookings and venue access.</p>
            </div>

            <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/login'); ?>" class="stacked-form">
                <?php echo csrf_field(); ?>

                <div class="field-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" required value="">
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <button type="submit" class="button primary full-width">Login</button>
            </form>

            <p class="helper-text">Need an account? <a href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>">Create one here</a>.</p>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
