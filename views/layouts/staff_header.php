<?php
declare(strict_types=1);

$user = current_user();
?>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo e(BASE_URL . '/?r=home/staff'); ?>" aria-label="City of Harare Property Management System Staff Portal">
            <span class="brand-mark" aria-hidden="true">
                <img src="<?php echo e(BASE_URL); ?>/assets/images/coh-pms-logo.png" alt="COH Logo">
            </span>
            <span class="brand-copy">
                <span style="display: block; font-weight: 500;">City of Harare Property Management System</span>
                <span class="mini-role" style="display: block; font-size: 0.8rem; margin-top: 2px;">(COH-PMS)</span>
            </span>
        </a>

        <nav class="header-actions" aria-label="Account" style="align-items: center; display: flex; gap: 12px;">
            <?php if (is_logged_in()): ?>
                <span style="font-weight: 500; color: var(--text); font-size: 0.9rem;"><?php echo e($user['role'] ?? 'User'); ?></span>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/logout'); ?>" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <button type="submit" class="button primary">Logout</button>
                </form>
            <?php else: ?>
                <div style="font-size: 0.85rem; color: var(--text); font-style: italic; font-weight: 500; letter-spacing: 0.02em;">
                    "To Provide First Class Service Delivery and Promote Investment"
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
