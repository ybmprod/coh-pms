<?php
declare(strict_types=1);
$user = current_customer_user();
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

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="venues-page-title">
            <header class="page-header">
                <span class="eyebrow">Browse Venues</span>
                <h1 id="venues-page-title">Available venues</h1>
                <p>Compare council venues, then open one to see full details and book.</p>
            </header>

            <?php if (empty($venues)): ?>
                <section class="card empty-state">
                    <span class="empty-state-icon"><i class="fi fi-sr-building" aria-hidden="true"></i></span>
                    <h3>No venues available</h3>
                    <p>There are no municipal venues available for booking right now. Please check back later.</p>
                </section>
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
                                        <i class="fi fi-sr-picture" aria-hidden="true" style="font-size: 32px;"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="cv-type-chip"><?php echo e($venue['venue_type']); ?></span>
                            </div>

                            <div class="cv-card-body">
                                <h2><?php echo e($venue['venue_name']); ?></h2>
                                <p class="cv-location">
                                    <i class="fi fi-sr-marker" aria-hidden="true"></i>
                                    <?php echo e($venue['location']); ?>
                                </p>

                                <dl class="cv-meta">
                                    <div>
                                        <dt class="cv-meta-label">Capacity</dt>
                                        <dd class="cv-meta-value" style="margin: 2px 0 0;"><?php echo e(number_format((int) $venue['capacity'])); ?></dd>
                                    </div>
                                    <div>
                                        <dt class="cv-meta-label">From</dt>
                                        <dd class="cv-meta-value cv-price" style="margin: 2px 0 0;"><?php echo e(money_format_usd((float) $venue['standard_price'])); ?></dd>
                                    </div>
                                </dl>

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
