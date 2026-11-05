<?php
declare(strict_types=1);
$user = current_customer_user();
$venues = $venues ?? [];
$eventTypes = $eventTypes ?? [];
$eventTypeFilter = $eventTypeFilter ?? '';
$allowedVenueTypes = $allowedVenueTypes ?? [];
$pagination = $pagination ?? null;
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
                <p>Explore City of Harare municipal facilities. Select a venue to view specifications, standard rates, and availability.</p>
            </header>

            <form method="get" action="<?php echo e(BASE_URL . '/'); ?>" class="card venue-suitable-filter">
                <input type="hidden" name="r" value="venue/customerList">
                <div class="field-group venue-suitable-filter__field">
                    <label for="venue_event_type">Show venues suitable for</label>
                    <select id="venue_event_type" name="event_type">
                        <option value="">Any event type</option>
                        <?php foreach ($eventTypes as $eventType): ?>
                            <option value="<?php echo e($eventType['event_name']); ?>" <?php echo $eventType['event_name'] === $eventTypeFilter ? 'selected' : ''; ?>>
                                <?php echo e($eventType['event_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="button-row venue-suitable-filter__actions">
                    <button type="submit" class="button primary small-button">Find venues</button>
                    <?php if ($eventTypeFilter !== ''): ?>
                        <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>">Clear filter</a>
                    <?php endif; ?>
                </div>
                <?php if ($eventTypeFilter !== '' && $allowedVenueTypes !== []): ?>
                    <p class="field-hint venue-suitable-filter__summary">
                        Showing venues for <strong><?php echo e($eventTypeFilter); ?></strong>:
                        <?php echo e(EventType::formatVenueTypeList($allowedVenueTypes)); ?>.
                    </p>
                <?php endif; ?>
            </form>

            <?php if (empty($venues)): ?>
                <section class="card empty-state">
                    <span class="empty-state-icon"><i class="fi fi-sr-building" aria-hidden="true"></i></span>
                    <?php if ($eventTypeFilter !== ''): ?>
                        <h3>No suitable venues found</h3>
                        <p>No available venue currently matches <strong><?php echo e($eventTypeFilter); ?></strong>. Clear the filter to browse all venues.</p>
                    <?php else: ?>
                        <h3>No venues available</h3>
                        <p>There are no municipal venues available for booking right now. Please check back later.</p>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <div class="cv-grid">
                    <?php foreach ($venues as $venue): ?>
                        <?php
                        $rawImagePath = (string) ($venue['image_path'] ?? '');
                        $filename = basename(str_replace('\\', '/', $rawImagePath));
                        $imageExists = $rawImagePath !== '' && is_file(APP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                        $imageUrl = $imageExists ? venue_image_url($rawImagePath) : '';
                        $detailQuery = ['r' => 'venue/details', 'id' => (int) $venue['venue_id']];
                        if ($eventTypeFilter !== '') {
                            $detailQuery['event_type'] = $eventTypeFilter;
                        }
                        $detailUrl = BASE_URL . '/?' . http_build_query($detailQuery);
                        ?>
                        <article class="cv-card">
                            <div class="cv-card-media">
                                <?php if ($imageExists): ?>
                                    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($venue['venue_name']); ?>" loading="lazy" data-image-fallback>
                                    <div class="cv-broken-preview" style="display: none;" aria-hidden="true">
                                        <span class="cv-broken-badge"><i class="fi fi-sr-picture" aria-hidden="true"></i></span>
                                        <p class="cv-broken-text">Image unavailable</p>
                                        <p class="cv-broken-sub"><?php echo e($venue['venue_type']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="cv-broken-preview" aria-hidden="true">
                                        <span class="cv-broken-badge"><i class="fi fi-sr-building" aria-hidden="true"></i></span>
                                        <p class="cv-broken-text">Image unavailable</p>
                                        <p class="cv-broken-sub"><?php echo e($venue['venue_type']); ?></p>
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

                                <a href="<?php echo e($detailUrl); ?>" class="button primary cv-card-button">View details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php echo render_pagination($pagination, 'venue/customerList', ['event_type' => $eventTypeFilter], 'page', '#venues-page-title'); ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
