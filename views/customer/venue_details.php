<?php
declare(strict_types=1);
$user = current_user();
$venue = $venue ?? [];
$flash = get_flash();

$imageUrl = venue_image_url($venue['image_path'] ?? null);
$venueStatus = $venue['venue_status'] ?? 'Available';
$isBookable = $venueStatus === 'Available';
$facilities = array_values(array_filter(array_map('trim', explode(',', (string) ($venue['facilities'] ?? '')))));
$description = trim((string) ($venue['description'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Venue Details'); ?> | <?php echo e(APP_NAME); ?></title>
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
            <a href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>" class="cv-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                All venues
            </a>

            <header class="cv-hero<?php echo $imageUrl === '' ? ' cv-hero-noimage' : ''; ?>"
                <?php if ($imageUrl !== ''): ?>style="background-image: linear-gradient(180deg, rgba(8, 45, 70, 0.10) 0%, rgba(8, 45, 70, 0.35) 45%, rgba(8, 45, 70, 0.92) 100%), url('<?php echo e($imageUrl); ?>');"<?php endif; ?>>
                <div class="cv-hero-content">
                    <span class="cv-type-chip cv-type-chip-static"><?php echo e($venue['venue_type'] ?? 'Venue'); ?></span>
                    <h1><?php echo e($venue['venue_name'] ?? 'Venue'); ?></h1>
                    <p class="cv-hero-location">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <?php echo e($venue['location'] ?? '-'); ?>
                    </p>
                </div>
            </header>

            <div class="cv-stats">
                <div class="cv-stat">
                    <span class="cv-meta-label">Capacity</span>
                    <span class="cv-stat-value"><?php echo e(number_format((int) ($venue['capacity'] ?? 0))); ?> <small>guests</small></span>
                </div>
                <div class="cv-stat">
                    <span class="cv-meta-label">Standard price</span>
                    <span class="cv-stat-value cv-price"><?php echo e(money_format_usd((float) ($venue['standard_price'] ?? 0))); ?></span>
                </div>
                <div class="cv-stat">
                    <span class="cv-meta-label">Venue type</span>
                    <span class="cv-stat-value"><?php echo e($venue['venue_type'] ?? '-'); ?></span>
                </div>
                <div class="cv-stat">
                    <span class="cv-meta-label">Status</span>
                    <span class="cv-stat-value"><?php echo status_badge($venueStatus); ?></span>
                </div>
            </div>

            <div class="cv-detail-grid">
                <div class="cv-detail-main">
                    <section class="card">
                        <h2 class="cv-section-title">About this venue</h2>
                        <?php if ($description !== ''): ?>
                            <p style="margin: 0; white-space: pre-line;"><?php echo e($description); ?></p>
                        <?php else: ?>
                            <p style="margin: 0; color: var(--muted);">No description has been added for this venue yet.</p>
                        <?php endif; ?>
                    </section>

                    <section class="card">
                        <h2 class="cv-section-title">Facilities</h2>
                        <?php if (!empty($facilities)): ?>
                            <ul class="cv-facilities">
                                <?php foreach ($facilities as $facility): ?>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <?php echo e($facility); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="margin: 0; color: var(--muted);">Facilities have not been listed for this venue yet.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="card cv-book-card">
                    <h2 class="cv-section-title">Book this venue</h2>
                    <p style="margin: 0 0 6px; color: var(--muted); font-size: 0.88rem;">Standard charge</p>
                    <p class="cv-book-price"><?php echo e(money_format_usd((float) ($venue['standard_price'] ?? 0))); ?></p>
                    <p style="margin: 0 0 18px; color: var(--muted); font-size: 0.82rem;">Final price may change with weekday, weekend or off-peak pricing rules. You will see the exact total before you submit.</p>

                    <?php if ($isBookable): ?>
                        <a href="<?php echo e(BASE_URL . '/?r=booking/customerList&venue_id=' . (int) ($venue['venue_id'] ?? 0) . '#request'); ?>" class="button primary" style="width: 100%;">Request booking</a>
                    <?php else: ?>
                        <button type="button" class="button ghost" style="width: 100%;" disabled>Currently <?php echo e(strtolower($venueStatus)); ?></button>
                    <?php endif; ?>

                    <ol class="cv-steps">
                        <li>Choose your date and times</li>
                        <li>Council reviews your request</li>
                        <li>Pay and receive your receipt</li>
                    </ol>
                </aside>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
