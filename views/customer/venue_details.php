<?php
declare(strict_types=1);
$venue = $venue ?? [];
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

    <main class="container narrower">
        <section class="card">
            <?php if (!empty($venue['image_path'])): ?>
                <img src="<?php echo e(venue_image_url($venue['image_path'] ?? null)); ?>" alt="<?php echo e($venue['venue_name']); ?>" class="venue-image large-image">
            <?php endif; ?>

            <h1><?php echo e($venue['venue_name'] ?? 'Venue'); ?></h1>
            <p><strong>Type:</strong> <?php echo e($venue['venue_type'] ?? '-'); ?></p>
            <p><strong>Location:</strong> <?php echo e($venue['location'] ?? '-'); ?></p>
            <p><strong>Capacity:</strong> <?php echo e((string) ($venue['capacity'] ?? 0)); ?></p>
            <p><strong>Standard price:</strong> <?php echo e(money_format_usd((float) ($venue['standard_price'] ?? 0))); ?></p>
            <p><strong>Status:</strong> <?php echo status_badge($venue['venue_status'] ?? 'Available'); ?></p>
            <p><?php echo e($venue['description'] ?? 'No description available.'); ?></p>
            <?php if (!empty($venue['facilities'])): ?>
                <p><strong>Facilities:</strong> <?php echo e($venue['facilities']); ?></p>
            <?php endif; ?>
            <a href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>" class="button primary">Back to venues</a>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
