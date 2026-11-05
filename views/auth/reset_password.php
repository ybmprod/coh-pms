<?php
declare(strict_types=1);
$flash = get_flash();
$tokenStatus = $tokenStatus ?? 'invalid';
$token = $token ?? '';
$resetUser = $resetUser ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Choose a New Password'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container narrower">
        <?php if ($tokenStatus === 'valid' && $resetUser): ?>
            <section class="card form-card" aria-labelledby="reset-password-heading">
                <header class="form-header">
                    <span class="eyebrow">Account Security</span>
                    <h1 id="reset-password-heading">Choose a new password</h1>
                    <p>Set a new password for <?php echo e($resetUser['email']); ?>. The link will stop working after this change or after 60 minutes.</p>
                </header>

                <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/resetPassword'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo e((string) $token); ?>">

                    <div class="field-group">
                        <label for="password">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                        <span class="helper-text" style="margin-top: 2px;">Minimum 8 characters including at least one letter and one digit.</span>
                    </div>

                    <div class="field-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
                    </div>

                    <button type="submit" class="button primary full-width">Change password</button>
                </form>

                <p class="helper-text">Did not request this change? Close this page and <a href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>">return to sign in</a>. Your current password is unchanged.</p>
            </section>
        <?php else: ?>
            <section class="card error-box" aria-labelledby="invalid-reset-heading">
                <span class="error-code">Invalid or Expired Link</span>
                <h1 id="invalid-reset-heading">This password reset link cannot be used</h1>
                <p>The link may have expired after 60 minutes or already been used. Request a new link to continue.</p>
                <div class="button-row">
                    <a class="button primary" href="<?php echo e(BASE_URL . '/?r=auth/forgotPassword'); ?>">Request a new link</a>
                    <a class="button secondary" href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>">Return to sign in</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
