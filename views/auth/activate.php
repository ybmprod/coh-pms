<?php
declare(strict_types=1);
$flash = get_flash();
$tokenStatus = $tokenStatus ?? 'invalid';
$token = $token ?? '';
$invitedUser = $invitedUser ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Activate Staff Account'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container narrower">
        <?php if ($tokenStatus === 'valid' && $invitedUser): ?>
            <section class="card form-card" style="border-top: 4px solid var(--primary-dark);" aria-labelledby="activate-heading">
                <header class="form-header">
                    <span class="eyebrow">Staff Onboarding</span>
                    <h1 id="activate-heading">Activate your staff account</h1>
                    <p>Set a secure password to complete your City of Harare staff profile setup.</p>
                </header>

                <dl class="details-list" style="margin: 0 0 20px;">
                    <div>
                        <dt>Staff member</dt>
                        <dd><?php echo e($invitedUser['full_name']); ?></dd>
                    </div>
                    <div>
                        <dt>Assigned role</dt>
                        <dd><?php echo e($invitedUser['role']); ?></dd>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <dt>Council email</dt>
                        <dd><?php echo e($invitedUser['email']); ?></dd>
                    </div>
                </dl>

                <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/activate'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">

                    <div class="field-group">
                        <label for="password">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                        <span class="helper-text" style="margin-top: 2px;">Minimum 8 characters including at least one letter and one digit.</span>
                    </div>

                    <div class="field-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
                    </div>

                    <button type="submit" class="button primary full-width">Activate account &amp; continue</button>
                </form>
            </section>
        <?php elseif ($tokenStatus === 'expired'): ?>
            <section class="card error-box" aria-labelledby="expired-token-title">
                <span class="error-code">Invitation Expired &bull; 24-Hour Limit</span>
                <h1 id="expired-token-title">Activation link has expired</h1>
                <p>
                    This staff onboarding invitation link was valid for 24 hours and has expired.
                    Please contact a System Administrator to click <strong>Resend Invitation</strong> on your staff profile.
                </p>
                <div class="button-row">
                    <a class="button secondary" href="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>">Go to Staff Login</a>
                </div>
            </section>
        <?php else: ?>
            <section class="card error-box" aria-labelledby="invalid-token-title">
                <span class="error-code">Invalid Token &bull; Verification Failed</span>
                <h1 id="invalid-token-title">Invalid activation link</h1>
                <p>
                    This onboarding invitation link is invalid or has already been used to activate the account.
                    If you have already set your password, you can sign in directly below.
                </p>
                <div class="button-row">
                    <a class="button primary" href="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>">Go to Staff Login</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
