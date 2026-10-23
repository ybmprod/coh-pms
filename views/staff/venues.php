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

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="venue-mgmt-title">
            <header class="page-header">
                <h1>Venue Management</h1>
                <p>Add and update council venue records, including availability and image upload.</p>
            </header>

            <section id="add" class="card form-card tab-section" data-tab="add" aria-labelledby="add-venue-heading">
                <header class="form-header">
                    <h2 id="add-venue-heading">Add venue</h2>
                    <p>Register a municipal hall, community centre, stadium, or open space.</p>
                </header>

                <form method="post" action="<?php echo e(BASE_URL . '/?r=venue/create'); ?>" enctype="multipart/form-data" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="form-grid">
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

                        <div class="field-group field-span-full">
                            <label for="facilities">Facilities</label>
                            <input id="facilities" name="facilities" type="text" placeholder="e.g. PA System, Stage, Seating, Backup Generator">
                        </div>

                        <div class="field-group field-span-full">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"></textarea>
                        </div>

                        <div class="field-group field-span-full">
                            <label for="image">Venue image</label>
                            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp">
                        </div>
                    </div>

                    <button type="submit" class="button primary full-width">Add venue</button>
                </form>
            </section>

            <section id="list" class="card tab-section active" data-tab="list" aria-labelledby="existing-venues-heading">
                <header class="section-heading">
                    <h2 id="existing-venues-heading">Existing venues</h2>
                </header>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Image</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Price (US$)</th>
                            <th>Status</th>
                            <th>Capacity &amp; Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($venues as $venue): ?>
                            <?php $formId = 'edit-venue-' . (int) $venue['venue_id']; ?>
                            <tr>
                                <td>
                                    <input type="text" name="venue_name" form="<?php echo e($formId); ?>" aria-label="Venue name" value="<?php echo e($venue['venue_name']); ?>">
                                </td>
                                <td>
                                    <?php if (!empty($venue['image_path'])): ?>
                                        <img src="<?php echo e(venue_image_url($venue['image_path'])); ?>" alt="<?php echo e($venue['venue_name']); ?>" class="venue-image">
                                    <?php endif; ?>
                                    <label for="venue-image-<?php echo e((string) $venue['venue_id']); ?>" style="font-size: var(--text-xs); font-weight: 600; display: block; margin-bottom: 4px;">Replace image</label>
                                    <input id="venue-image-<?php echo e((string) $venue['venue_id']); ?>" type="file" name="image" form="<?php echo e($formId); ?>" accept="image/png,image/jpeg,image/webp" style="font-size: var(--text-xs); max-width: 170px;">
                                </td>
                                <td>
                                    <select name="venue_type" form="<?php echo e($formId); ?>" aria-label="Venue type">
                                        <?php foreach (['Community Hall','Community Centre','Stadium','Open Space','Other'] as $type): ?>
                                            <option value="<?php echo e($type); ?>" <?php echo $type === $venue['venue_type'] ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="location" form="<?php echo e($formId); ?>" aria-label="Location" value="<?php echo e($venue['location']); ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="standard_price" form="<?php echo e($formId); ?>" aria-label="Standard price" value="<?php echo e((string) $venue['standard_price']); ?>" style="max-width: 110px;">
                                </td>
                                <td>
                                    <select name="venue_status" form="<?php echo e($formId); ?>" aria-label="Venue status">
                                        <?php foreach (['Available','Unavailable','Under Maintenance'] as $status): ?>
                                            <option value="<?php echo e($status); ?>" <?php echo $status === $venue['venue_status'] ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <form id="<?php echo e($formId); ?>" method="post" action="<?php echo e(BASE_URL . '/?r=venue/edit'); ?>" enctype="multipart/form-data" class="inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="venue_id" value="<?php echo e((string) $venue['venue_id']); ?>">
                                        <input type="number" name="capacity" aria-label="Capacity" value="<?php echo e((string) $venue['capacity']); ?>" min="1" style="max-width: 95px;">
                                        <button type="submit" class="button primary small-button">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
