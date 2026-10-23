<?php
declare(strict_types=1);

$customerUser = current_customer_user();
?>
<a href="#main-content" class="skip-link">Skip to main content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo e(BASE_URL . '/?r=home/index'); ?>" aria-label="City of Harare Property Management System home">
            <span class="brand-mark" aria-hidden="true">
                <img src="<?php echo e(BASE_URL); ?>/assets/images/coh-pms-logo.png" alt="COH Logo">
            </span>
            <span class="brand-copy">
                <span class="brand-title">City of Harare Property Management System</span>
                <span class="mini-role">Municipal Venue Hire Portal (COH-PMS)</span>
            </span>
        </a>

        <nav class="header-actions" aria-label="Account and municipal contact">
            <address class="header-contact">
                <span class="header-contact-item">
                    <i class="fi fi-sr-phone-call" aria-hidden="true"></i>
                    <span>0242 774 141</span>
                </span>
                <span class="header-contact-item">
                    <i class="fi fi-sr-envelope" aria-hidden="true"></i>
                    <a href="mailto:info@hararecity.co.zw">info@hararecity.co.zw</a>
                </span>
            </address>

            <?php if ($customerUser !== null): ?>
                <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=dashboard/customer'); ?>">
                    <i class="fi fi-sr-apps" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
                <span class="header-user-badge">
                    <i class="fi fi-sr-user" aria-hidden="true" style="margin-right: 6px;"></i><?php echo e($customerUser['role'] ?? 'Customer'); ?>
                </span>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/logout'); ?>" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="portal" value="customer">
                    <button type="submit" class="button primary">
                        <i class="fi fi-sr-sign-out-alt" aria-hidden="true"></i>
                        <span>Logout</span>
                    </button>
                </form>
            <?php else: ?>
                <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>">
                    <i class="fi fi-sr-sign-in-alt" aria-hidden="true"></i>
                    <span>Log in</span>
                </a>
                <a class="button primary" href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>">
                    <i class="fi fi-sr-user-add" aria-hidden="true"></i>
                    <span>Create account</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
