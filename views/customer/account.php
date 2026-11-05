<?php
declare(strict_types=1);
$flash = get_flash();
$user = $user ?? current_customer_user();
$memberSince = !empty($user['created_at']) ? date('j F Y', strtotime((string) $user['created_at'])) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Account Security'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="account-security-heading">
            <header class="page-header">
                <div>
                    <span class="eyebrow">Account Security</span>
                    <h1 id="account-security-heading">Password &amp; account access</h1>
                    <p>Keep your customer account secure. Password changes are confirmed through a single-use link sent to your email address.</p>
                </div>
            </header>

            <div class="account-security-grid">
                <section class="card" aria-labelledby="account-profile-heading">
                    <header class="form-header">
                        <h2 id="account-profile-heading">Account profile</h2>
                        <p>These details identify the account that will receive the secure link.</p>
                    </header>

                    <dl class="details-list">
                        <div>
                            <dt>Full name</dt>
                            <dd><?php echo e((string) ($user['full_name'] ?? '')); ?></dd>
                        </div>
                        <div>
                            <dt>Email address</dt>
                            <dd><?php echo e((string) ($user['email'] ?? '')); ?></dd>
                        </div>
                        <div>
                            <dt>Account type</dt>
                            <dd><?php echo e((string) ($user['role'] ?? ROLE_CUSTOMER)); ?></dd>
                        </div>
                        <?php if ($memberSince !== null): ?>
                            <div>
                                <dt>Member since</dt>
                                <dd><?php echo e($memberSince); ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </section>

                <section class="card" aria-labelledby="change-password-heading">
                    <header class="form-header">
                        <h2 id="change-password-heading">Change your password</h2>
                        <p>For your protection, confirm your current password before we email a secure change link.</p>
                    </header>

                    <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/changePassword'); ?>" class="stacked-form">
                        <?php echo csrf_field(); ?>

                        <div class="field-group">
                            <label for="current_password">Current password</label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                        </div>

                        <button type="submit" class="button primary full-width">Email secure change link</button>
                    </form>

                    <div class="account-security-note">
                        <i class="fi fi-sr-shield-check" aria-hidden="true"></i>
                        <p>The link will be sent to <strong><?php echo e(mask_email((string) ($user['email'] ?? ''))); ?></strong>, expires in 60 minutes and can only be used once. The City of Harare will never ask you for your password or this link.</p>
                    </div>
                </section>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
