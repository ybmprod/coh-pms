<?php
declare(strict_types=1);
$flash = get_flash();
$venues = $venues ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Venue Management'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main">
            <div class="card">
                <h1>Venue Management</h1>
                <p>Add and update council venue records, including availability and image upload.</p>
            </div>

            <div id="add" class="card form-card tab-section" data-tab="add">
                <h2>Add venue</h2>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=venue/create'); ?>" enctype="multipart/form-data" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="field-group">
                        <label for="venue_name">Venue name</label>
                        <input id="venue_name" name="venue_name" type="text" required>
                    </div>

                    <div class="field-group">
                        <label for="venue_type">Venue type</label>
                        <select id="venue_type" name="venue_type" required>
                            <option value="Community Hall">Community Hall</option>
                            <option value="Community Centre">Community Centre</option>
                            <option value="Stadium">Stadium</option>
                            <option value="Open Space">Open Space</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="location">Location</label>
                        <input id="location" name="location" type="text" required>
                    </div>

                    <div class="field-group">
                        <label for="capacity">Capacity</label>
                        <input id="capacity" name="capacity" type="number" min="1" required>
                    </div>

                    <div class="field-group">
                        <label for="standard_price">Standard price (US$)</label>
                        <input id="standard_price" name="standard_price" type="number" step="0.01" min="0" required>
                    </div>

                    <div class="field-group">
                        <label for="venue_status">Status</label>
                        <select id="venue_status" name="venue_status">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="facilities">Facilities</label>
                        <input id="facilities" name="facilities" type="text">
                    </div>

                    <div class="field-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="field-group">
                        <label for="image">Venue image</label>
                        <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp">
                    </div>

                    <button type="submit" class="button primary full-width">Add venue</button>
                </form>
            </div>

            <div id="list" class="card tab-section active" data-tab="list">
                <h2>Existing venues</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Image</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($venues as $venue): ?>
                            <tr>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=venue/edit'); ?>" enctype="multipart/form-data" class="inline-form stacked-form edit-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="venue_id" value="<?php echo e((string) $venue['venue_id']); ?>">
                                        <input type="text" name="venue_name" value="<?php echo e($venue['venue_name']); ?>">
                                </td>
                                    <td>
                                        <?php if (!empty($venue['image_path'])): ?>
                                            <img src="<?php echo e(venue_image_url($venue['image_path'])); ?>" alt="" class="venue-image">
                                        <?php endif; ?>
                                        <label for="venue-image-<?php echo e((string) $venue['venue_id']); ?>">Replace image</label>
                                        <input id="venue-image-<?php echo e((string) $venue['venue_id']); ?>" type="file" name="image" accept="image/png,image/jpeg,image/webp">
                                    </td>
                                <td>
                                        <select name="venue_type">
                                            <?php foreach (['Community Hall','Community Centre','Stadium','Open Space','Other'] as $type): ?>
                                                <option value="<?php echo e($type); ?>" <?php echo $type === $venue['venue_type'] ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                </td>
                                <td>
                                        <input type="text" name="location" value="<?php echo e($venue['location']); ?>">
                                </td>
                                <td>
                                        <input type="number" step="0.01" name="standard_price" value="<?php echo e((string) $venue['standard_price']); ?>">
                                </td>
                                <td>
                                        <select name="venue_status">
                                            <?php foreach (['Available','Unavailable','Under Maintenance'] as $status): ?>
                                                <option value="<?php echo e($status); ?>" <?php echo $status === $venue['venue_status'] ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                </td>
                                <td>
                                        <input type="number" name="capacity" value="<?php echo e((string) $venue['capacity']); ?>" min="1">
                                        <button type="submit" class="button primary small-button">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
