<?php
declare(strict_types=1);
$user = current_customer_user();
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

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="venue-detail-title">
            <a href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>" class="cv-back-link">
                <i class="fi fi-sr-arrow-small-left" aria-hidden="true"></i>
                All venues
            </a>

            <header class="cv-hero<?php echo $imageUrl === '' ? ' cv-hero-noimage' : ''; ?>"
                <?php if ($imageUrl !== ''): ?>style="background-image: linear-gradient(180deg, rgba(8, 45, 70, 0.12) 0%, rgba(8, 45, 70, 0.42) 45%, rgba(8, 45, 70, 0.94) 100%), url('<?php echo e($imageUrl); ?>');"<?php endif; ?>>
                <div class="cv-hero-content">
                    <span class="cv-type-chip cv-type-chip-static"><?php echo e($venue['venue_type'] ?? 'Venue'); ?></span>
                    <h1 id="venue-detail-title"><?php echo e($venue['venue_name'] ?? 'Venue'); ?></h1>
                    <p class="cv-hero-location">
                        <i class="fi fi-sr-marker" aria-hidden="true"></i>
                        <?php echo e($venue['location'] ?? '-'); ?>
                    </p>
                </div>
            </header>

            <dl class="cv-stats" aria-label="Venue specifications">
                <div class="cv-stat">
                    <dt class="cv-meta-label">Capacity</dt>
                    <dd class="cv-stat-value" style="margin: 4px 0 0;"><?php echo e(number_format((int) ($venue['capacity'] ?? 0))); ?> <small>guests</small></dd>
                </div>
                <div class="cv-stat">
                    <dt class="cv-meta-label">Standard price</dt>
                    <dd class="cv-stat-value cv-price" style="margin: 4px 0 0;"><?php echo e(money_format_usd((float) ($venue['standard_price'] ?? 0))); ?></dd>
                </div>
                <div class="cv-stat">
                    <dt class="cv-meta-label">Venue type</dt>
                    <dd class="cv-stat-value" style="margin: 4px 0 0;"><?php echo e($venue['venue_type'] ?? '-'); ?></dd>
                </div>
                <div class="cv-stat">
                    <dt class="cv-meta-label">Status</dt>
                    <dd class="cv-stat-value" style="margin: 4px 0 0;"><?php echo status_badge($venueStatus); ?></dd>
                </div>
            </dl>

            <div class="cv-detail-grid">
                <div class="cv-detail-main">
                    <section class="card" aria-labelledby="about-venue-heading">
                        <h2 id="about-venue-heading" class="cv-section-title">About this venue</h2>
                        <?php if ($description !== ''): ?>
                            <p style="margin: 0; white-space: pre-line;"><?php echo e($description); ?></p>
                        <?php else: ?>
                            <p style="margin: 0; color: var(--muted);">No description has been added for this venue yet.</p>
                        <?php endif; ?>
                    </section>

                    <section class="card" aria-labelledby="facilities-heading">
                        <h2 id="facilities-heading" class="cv-section-title">Facilities</h2>
                        <?php if (!empty($facilities)): ?>
                            <ul class="cv-facilities">
                                <?php foreach ($facilities as $facility): ?>
                                    <li>
                                        <i class="fi fi-sr-check-circle" aria-hidden="true"></i>
                                        <?php echo e($facility); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="margin: 0; color: var(--muted);">Facilities have not been listed for this venue yet.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="card cv-book-card" aria-labelledby="book-venue-heading">
                    <h2 id="book-venue-heading" class="cv-section-title">Book this venue</h2>
                    <p style="margin: 0 0 6px; color: var(--muted); font-size: var(--text-xs); font-weight: 600; text-transform: uppercase;">Standard charge</p>
                    <p class="cv-book-price"><?php echo e(money_format_usd((float) ($venue['standard_price'] ?? 0))); ?></p>
                    <p style="margin: 0 0 18px; color: var(--muted); font-size: var(--text-xs);">Final price may change with weekday, weekend or off-peak pricing rules. You will see the exact total before you submit.</p>

                    <?php if ($isBookable): ?>
                        <a href="<?php echo e(BASE_URL . '/?r=booking/customerList&venue_id=' . (int) ($venue['venue_id'] ?? 0) . '#request'); ?>" class="button primary full-width">
                            <i class="fi fi-sr-calendar-clock" aria-hidden="true"></i>
                            <span>Request booking</span>
                        </a>
                    <?php else: ?>
                        <button type="button" class="button ghost full-width" disabled>Currently <?php echo e(strtolower($venueStatus)); ?></button>
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
