<?php
declare(strict_types=1);
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

    <main class="container">
        <section class="card">
            <h1>Available venues</h1>
            <p>Browse venues and choose a date that matches your event needs.</p>
        </section>

        <div class="status-grid">
            <?php foreach ($venues as $venue): ?>
                <article class="card venue-card">
                    <?php if (!empty($venue['image_path'])): ?>
                        <img src="<?php echo e(venue_image_url($venue['image_path'] ?? null)); ?>" alt="<?php echo e($venue['venue_name']); ?>" class="venue-image">
                    <?php endif; ?>
                    <h3><?php echo e($venue['venue_name']); ?></h3>
                    <p><strong>Type:</strong> <?php echo e($venue['venue_type']); ?></p>
                    <p><strong>Location:</strong> <?php echo e($venue['location']); ?></p>
                    <p><strong>Capacity:</strong> <?php echo e((string) $venue['capacity']); ?></p>
                    <p><strong>Price:</strong> <?php echo e(money_format_usd((float) $venue['standard_price'])); ?></p>
                    <a href="<?php echo e(BASE_URL . '/?r=venue/details&id=' . (int) $venue['venue_id']); ?>" class="button primary">View details</a>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
