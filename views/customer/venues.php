<?php
declare(strict_types=1);
$user = current_user();
$venues = $venues ?? [];
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Available Venues'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main">
            <div style="margin-bottom: 28px;">
                <p class="eyebrow" style="margin: 0;">Browse Venues</p>
                <h1 style="margin: 8px 0 0; font-size: 1.875rem;">Available venues</h1>
                <p style="margin: 8px 0 0; font-size: 0.9rem; color: var(--muted);">Compare council venues, then open one to see full details and book.</p>
            </div>

            <?php if (empty($venues)): ?>
                <div class="card" style="text-align: center; color: var(--muted);">No venues are available right now. Please check back later.</div>
            <?php else: ?>
                <div class="cv-grid">
                    <?php foreach ($venues as $venue): ?>
                        <?php $imageUrl = venue_image_url($venue['image_path'] ?? null); ?>
                        <article class="cv-card">
                            <div class="cv-card-media">
                                <?php if ($imageUrl !== ''): ?>
                                    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($venue['venue_name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="cv-placeholder" aria-hidden="true">
                                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    </div>
                                <?php endif; ?>
                                <span class="cv-type-chip"><?php echo e($venue['venue_type']); ?></span>
                            </div>

                            <div class="cv-card-body">
                                <h3><?php echo e($venue['venue_name']); ?></h3>
                                <p class="cv-location">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <?php echo e($venue['location']); ?>
                                </p>

                                <div class="cv-meta">
                                    <div>
                                        <span class="cv-meta-label">Capacity</span>
                                        <span class="cv-meta-value"><?php echo e(number_format((int) $venue['capacity'])); ?></span>
                                    </div>
                                    <div>
                                        <span class="cv-meta-label">From</span>
                                        <span class="cv-meta-value cv-price"><?php echo e(money_format_usd((float) $venue['standard_price'])); ?></span>
                                    </div>
                                </div>

                                <a href="<?php echo e(BASE_URL . '/?r=venue/details&id=' . (int) $venue['venue_id']); ?>" class="button primary cv-card-button">View details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
