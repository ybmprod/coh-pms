<?php
declare(strict_types=1);
?>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?php echo e(BASE_URL); ?>" aria-label="City of Harare Property Management System home">
            <span class="brand-mark" aria-hidden="true">
                <img src="<?php echo e(BASE_URL); ?>/assets/images/coh-pms-logo.png" alt="COH Logo">
            </span>
            <span class="brand-copy">
                <span style="display: block; font-weight: 500;">City of Harare Property Management System</span>
                <span class="mini-role" style="display: block; font-size: 0.8rem; margin-top: 2px;">(COH-PMS)</span>
            </span>
        </a>

        <nav class="header-actions" aria-label="Account" style="align-items: center; display: flex; gap: 12px; flex-wrap: wrap;">
            <div class="header-contact" style="display: flex; gap: 16px; align-items: center; font-size: 0.85rem; color: var(--text); margin-right: 12px; padding-right: 16px; border-right: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span style="font-weight: 500; white-space: nowrap;">0242 774 141</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <a href="mailto:info@hararecity.co.zw" style="color: inherit; text-decoration: none; font-weight: 500; white-space: nowrap;">info@hararecity.co.zw</a>
                </div>
            </div>

            <?php if (is_logged_in()): ?>
                <span style="font-weight: 500; color: var(--text); font-size: 0.9rem;"><?php echo e(current_user()['role'] ?? 'User'); ?></span>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=auth/logout'); ?>" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <button type="submit" class="button primary">Logout</button>
                </form>
            <?php else: ?>
                <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>">Log in</a>
                <a class="button primary" href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>">Create account</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
