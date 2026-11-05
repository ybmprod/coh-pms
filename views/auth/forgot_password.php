<?php
declare(strict_types=1);
$flash = get_flash();
$email = $email ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Forgot Password'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container narrower">
        <section class="card form-card" aria-labelledby="forgot-password-heading">
            <header class="form-header">
                <span class="eyebrow">Account Security</span>
                <h1 id="forgot-password-heading">Forgot your password?</h1>
                <p>Enter the email address on your customer account. If it matches an active account, we will email a secure password reset link that expires in 60 minutes.</p>
            </header>

            <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/forgotPassword'); ?>" class="stacked-form">
                <?php echo csrf_field(); ?>

                <div class="field-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?php echo e((string) $email); ?>">
                </div>

                <button type="submit" class="button primary full-width">Email secure reset link</button>
            </form>

            <p class="helper-text">Remembered your password? <a href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>">Return to sign in</a>.</p>
            <p class="helper-text">Need a customer account? <a href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>">Create one here</a>.</p>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
