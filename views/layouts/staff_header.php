<?php
declare(strict_types=1);

$staffUser = current_staff_user();
?>
<a href="#main-content" class="skip-link">Skip to main content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo e(BASE_URL . '/?r=home/staff'); ?>" aria-label="City of Harare Property Management System Staff Portal">
            <span class="brand-mark" aria-hidden="true">
                <img src="<?php echo e(BASE_URL); ?>/assets/images/coh-pms-logo.png" alt="COH Logo">
            </span>
            <span class="brand-copy">
                <span class="brand-title">City of Harare Property Management System</span>
                <span class="mini-role">Council Administration &amp; Operations (COH-PMS)</span>
            </span>
        </a>

        <nav class="header-actions" aria-label="Council staff account">
            <p class="header-motto">
                &ldquo;To Provide First Class Service Delivery and Promote Investment&rdquo;
            </p>

            <?php if ($staffUser !== null): ?>
                <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=dashboard/index'); ?>">
                    <i class="fi fi-sr-apps" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
                <span class="header-user-badge">
                    <i class="fi fi-sr-shield-check" aria-hidden="true" style="margin-right: 6px;"></i><?php echo e($staffUser['role'] ?? 'Staff'); ?>
                </span>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/logout'); ?>" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="portal" value="staff">
                    <button type="submit" class="button primary">
                        <i class="fi fi-sr-sign-out-alt" aria-hidden="true"></i>
                        <span>Logout</span>
                    </button>
                </form>
            <?php else: ?>
                <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>">
                    <i class="fi fi-sr-sign-in-alt" aria-hidden="true"></i>
                    <span>Staff Login</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
