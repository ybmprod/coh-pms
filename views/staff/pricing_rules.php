<?php
declare(strict_types=1);
$flash = get_flash();
$pricingRules = $pricingRules ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Pricing Rules'); ?> | <?php echo e(APP_NAME); ?></title>
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
                <h1>Pricing Rules</h1>
                <p>Manage weekday, weekend, and long-stay pricing adjustments across venues.</p>
            </div>

            <div id="add" class="card form-card tab-section" data-tab="add">
                <h2>Add pricing rule</h2>
                <form method="post" action="<?php echo e(BASE_URL . '/?r=pricingrule/create'); ?>" class="stacked-form">
                    <?php echo csrf_field(); ?>

                    <div class="field-group">
                        <label for="rule_name">Rule name</label>
                        <input id="rule_name" name="rule_name" type="text" required>
                    </div>

                    <div class="field-group">
                        <label for="venue_type">Venue type</label>
                        <select id="venue_type" name="venue_type" required>
                            <option value="All">All</option>
                            <option value="Community Hall">Community Hall</option>
                            <option value="Community Centre">Community Centre</option>
                            <option value="Stadium">Stadium</option>
                            <option value="Open Space">Open Space</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="rule_type">Rule type</label>
                        <select id="rule_type" name="rule_type" required>
                            <option value="Weekday Discount">Weekday Discount</option>
                            <option value="Weekend Surcharge">Weekend Surcharge</option>
                            <option value="Off-Peak Discount">Off-Peak Discount</option>
                            <option value="Long Booking Discount">Long Booking Discount</option>
                            <option value="Peak Demand Surcharge">Peak Demand Surcharge</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="adjustment_type">Adjustment type</label>
                        <select id="adjustment_type" name="adjustment_type" required>
                            <option value="Percentage">Percentage</option>
                            <option value="Fixed Amount">Fixed Amount</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="adjustment_value">Adjustment value</label>
                        <input id="adjustment_value" name="adjustment_value" type="number" step="0.01" min="0" required>
                    </div>

                    <div class="field-group">
                        <label for="days_of_week">Days of week</label>
                        <input id="days_of_week" name="days_of_week" type="text" placeholder="Mon,Tue,Wed">
                    </div>

                    <div class="field-group">
                        <label for="min_hours">Min hours</label>
                        <input id="min_hours" name="min_hours" type="number" step="0.5" min="0.5">
                    </div>

                    <div class="field-group">
                        <label for="start_date">Start date</label>
                        <input id="start_date" name="start_date" type="date">
                    </div>

                    <div class="field-group">
                        <label for="end_date">End date</label>
                        <input id="end_date" name="end_date" type="date">
                    </div>

                    <div class="field-group">
                        <label for="priority">Priority</label>
                        <input id="priority" name="priority" type="number" min="1" value="1" required>
                    </div>

                    <div class="field-group">
                        <label for="rule_status">Rule status</label>
                        <select id="rule_status" name="rule_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="button primary full-width">Add rule</button>
                </form>
            </div>

            <div id="list" class="card tab-section active" data-tab="list">
                <h2>Existing rules</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rule</th>
                            <th>Venue</th>
                            <th>Type</th>
                            <th>Adjustment</th>
                            <th>Days</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pricingRules as $rule): ?>
                            <tr>
                                <form method="post" action="<?php echo e(BASE_URL . '/?r=pricingrule/edit'); ?>" class="inline-form stacked-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="pricing_rule_id" value="<?php echo e((string) $rule['pricing_rule_id']); ?>">
                                    <td>
                                        <input type="text" name="rule_name" value="<?php echo e($rule['rule_name']); ?>">
                                    </td>
                                    <td>
                                        <select name="venue_type">
                                            <?php foreach (['All','Community Hall','Community Centre','Stadium','Open Space','Other'] as $venueType): ?>
                                                <option value="<?php echo e($venueType); ?>" <?php echo $venueType === $rule['venue_type'] ? 'selected' : ''; ?>><?php echo e($venueType); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="rule_type">
                                            <?php foreach (['Weekday Discount','Weekend Surcharge','Off-Peak Discount','Long Booking Discount','Peak Demand Surcharge'] as $ruleType): ?>
                                                <option value="<?php echo e($ruleType); ?>" <?php echo $ruleType === $rule['rule_type'] ? 'selected' : ''; ?>><?php echo e($ruleType); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="compact-stack">
                                            <select name="adjustment_type">
                                                <?php foreach (['Percentage','Fixed Amount'] as $adjustmentType): ?>
                                                    <option value="<?php echo e($adjustmentType); ?>" <?php echo $adjustmentType === $rule['adjustment_type'] ? 'selected' : ''; ?>><?php echo e($adjustmentType); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="number" step="0.01" name="adjustment_value" value="<?php echo e((string) $rule['adjustment_value']); ?>" min="0">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="compact-stack">
                                            <input type="text" name="days_of_week" value="<?php echo e((string) ($rule['days_of_week'] ?? '')); ?>">
                                            <input type="number" step="0.5" name="min_hours" value="<?php echo e((string) ($rule['min_hours'] ?? '')); ?>" min="0.5" placeholder="Min hrs">
                                            <input type="date" name="start_date" value="<?php echo e((string) ($rule['start_date'] ?? '')); ?>">
                                            <input type="date" name="end_date" value="<?php echo e((string) ($rule['end_date'] ?? '')); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="priority" value="<?php echo e((string) $rule['priority']); ?>" min="1">
                                    </td>
                                    <td>
                                        <select name="rule_status">
                                            <?php foreach (['Active','Inactive'] as $status): ?>
                                                <option value="<?php echo e($status); ?>" <?php echo $status === $rule['rule_status'] ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="button-row">
                                            <button type="submit" class="button primary small-button">Update</button>
                                        </div>
                                    </td>
                                </form>
                                <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=pricingrule/toggleStatus'); ?>" class="inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="pricing_rule_id" value="<?php echo e((string) $rule['pricing_rule_id']); ?>">
                                        <?php $btnClass = $rule['rule_status'] === 'Active' ? 'secondary' : 'primary'; ?><button type="submit" class="button <?php echo $btnClass; ?> small-button"><?php echo $rule['rule_status'] === 'Active' ? 'Disable' : 'Enable'; ?></button>
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
