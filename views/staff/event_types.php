<?php
declare(strict_types=1);
$flash = get_flash();
$eventTypes = $eventTypes ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Event Types'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="event-types-title">
            <header class="page-header">
                <h1 id="event-types-title">Event Types</h1>
                <p>Manage the event categories customers choose from and the charge each one adds to a booking.</p>
            </header>

            <section id="add" class="card form-card tab-section" data-tab="add" aria-labelledby="add-event-type-heading">
                <header class="form-header">
                    <h2 id="add-event-type-heading">Add event type</h2>
                    <p>The charge is added to the venue standard charge before any pricing rule is applied.</p>
                </header>

                <form method="post" action="<?php echo e(BASE_URL . '/?r=eventtype/create'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="form-grid">
                        <div class="field-group field-span-full">
                            <label for="event_name">Event name</label>
                            <input id="event_name" name="event_name" type="text" maxlength="100" placeholder="e.g. Wedding, Music Concert, Sports Tournament" required>
                        </div>

                        <div class="field-group">
                            <label for="charge_type">Charge type</label>
                            <select id="charge_type" name="charge_type" required>
                                <option value="Fixed Amount">Fixed Amount (US$)</option>
                                <option value="Percentage">Percentage of standard charge</option>
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="charge_value">Charge value</label>
                            <input id="charge_value" name="charge_value" type="number" step="0.01" min="0" value="0.00" required>
                        </div>

                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="field-group field-span-full">
                            <label for="description">Description</label>
                            <input id="description" name="description" type="text" maxlength="255">
                        </div>
                    </div>

                    <div class="button-row">
                        <button type="submit" class="button primary">Save event type</button>
                    </div>
                </form>
            </section>

            <section id="list" class="card tab-section active" data-tab="list" aria-labelledby="existing-event-types-heading">
                <header class="section-heading">
                    <h2 id="existing-event-types-heading">Existing event types</h2>
                </header>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event type</th>
                            <th>Charge</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventTypes)): ?>
                            <tr>
                                <td colspan="5" class="empty-state-cell">
                                    <div class="empty-state">
                                        <span class="empty-state-icon"><i class="fi fi-sr-calendar-star" aria-hidden="true"></i></span>
                                        <h3>No event types configured</h3>
                                        <p>Add an event type so customers can select it when requesting a booking.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventTypes as $et): ?>
                                <?php $formId = 'edit-event-type-' . (int) $et['event_type_id']; ?>
                                <tr>
                                    <td data-label="Event type">
                                        <input type="text" name="event_name" form="<?php echo e($formId); ?>" aria-label="Event name" value="<?php echo e($et['event_name']); ?>" maxlength="100" required>
                                    </td>
                                    <td data-label="Charge">
                                        <div class="compact-stack">
                                            <select name="charge_type" form="<?php echo e($formId); ?>" aria-label="Charge type">
                                                <?php foreach (['Fixed Amount', 'Percentage'] as $chargeType): ?>
                                                    <option value="<?php echo e($chargeType); ?>" <?php echo $chargeType === $et['charge_type'] ? 'selected' : ''; ?>><?php echo e($chargeType); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="number" step="0.01" min="0" name="charge_value" form="<?php echo e($formId); ?>" aria-label="Charge value" value="<?php echo e((string) $et['charge_value']); ?>">
                                        </div>
                                    </td>
                                    <td data-label="Description">
                                        <input type="text" name="description" form="<?php echo e($formId); ?>" aria-label="Description" value="<?php echo e((string) ($et['description'] ?? '')); ?>" maxlength="255">
                                    </td>
                                    <td data-label="Status">
                                        <select name="status" form="<?php echo e($formId); ?>" aria-label="Status">
                                            <?php foreach (['Active', 'Inactive'] as $status): ?>
                                                <option value="<?php echo e($status); ?>" <?php echo $status === $et['status'] ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-label="Action">
                                        <div class="button-row">
                                            <form id="<?php echo e($formId); ?>" method="post" action="<?php echo e(BASE_URL . '/?r=eventtype/edit'); ?>" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="event_type_id" value="<?php echo e((string) $et['event_type_id']); ?>">
                                                <button type="submit" class="button primary small-button">Update</button>
                                            </form>
                                            <form method="post" action="<?php echo e(BASE_URL . '/?r=eventtype/toggle'); ?>" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="event_type_id" value="<?php echo e((string) $et['event_type_id']); ?>">
                                                <?php $btnClass = $et['status'] === 'Active' ? 'secondary' : 'primary'; ?>
                                                <button type="submit" class="button <?php echo $btnClass; ?> small-button"><?php echo $et['status'] === 'Active' ? 'Disable' : 'Enable'; ?></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
