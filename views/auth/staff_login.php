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
    <style>
        .staff-form-card {
            border-top: 4px solid var(--primary);
        }
        .staff-badge {
            display: inline-block;
            background: #eaf1f6;
            color: var(--primary);
            padding: 4px 12px;
            border-radius: var(--radius-pill);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
    </style>
</head>
<body style="background: #eaf1f6;">
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main class="container narrow" style="margin-top: 60px;">
        <section class="card form-card staff-form-card">
            <div class="form-header" style="text-align: center;">
                <span class="staff-badge">Staff Portal</span>
                <h1 style="font-size: 1.5rem;">Council Login</h1>
                <p>Secure access for administrative and officer staff.</p>
            </div>

            <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>" class="stacked-form">
                <?php echo csrf_field(); ?>

                <div class="field-group">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" required value="">
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <button type="submit" class="button primary full-width">Access Portal</button>
            </form>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>

