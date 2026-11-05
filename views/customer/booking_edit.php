<?php
declare(strict_types=1);
$flash = get_flash();
$booking = $booking ?? [];
$venues = $venues ?? [];
$eventTypes = $eventTypes ?? [];
$selectedVenueId = (int) ($booking['venue_id'] ?? 0);
$selectedEventType = (string) ($booking['event_type'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Amend booking'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="booking-edit-title">
            <header class="page-header">
                <span class="eyebrow">Pending Reservation</span>
                <h1 id="booking-edit-title">Amend booking <?php echo e($booking['booking_reference'] ?? ''); ?></h1>
                <p>Availability and pricing are checked again when you save, so the total always reflects the current rules.</p>
            </header>

            <div class="booking-grid">
                <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/update'); ?>" class="card stacked-form"
                    data-availability-endpoint="<?php echo e(BASE_URL . '/?r=booking/checkAvailability'); ?>"
                    data-venues-endpoint="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>"
                    data-exclude-booking-id="<?php echo e((string) ($booking['booking_id'] ?? 0)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo e((string) ($booking['booking_id'] ?? 0)); ?>">

                    <header class="form-header">
                        <h2>Event details</h2>
                        <p>Change any detail below. Your booking reference is kept unless the date changes.</p>
                    </header>

                    <div class="form-grid">
                        <div class="field-group field-span-full">
                            <label for="venue_id">Venue</label>
                            <select id="venue_id" name="venue_id" required>
                                <option value="">Select a venue</option>
                                <?php foreach ($venues as $venue): ?>
                                    <?php
                                    $vImgUrl = venue_image_url($venue['image_path'] ?? null);
                                    $vImgExists = false;
                                    if ($vImgUrl !== '' && !empty($venue['image_path'])) {
                                        $vFilename = basename(str_replace('\\', '/', (string) $venue['image_path']));
                                        $vImgExists = is_file(APP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $vFilename);
                                    }
                                    ?>
                                    <option value="<?php echo e((string) $venue['venue_id']); ?>"
                                        data-price="<?php echo e((string) ($venue['standard_price'] ?? 0)); ?>"
                                        data-name="<?php echo e((string) ($venue['venue_name'] ?? '')); ?>"
                                        data-location="<?php echo e((string) ($venue['location'] ?? '')); ?>"
                                        data-type="<?php echo e((string) ($venue['venue_type'] ?? 'Venue')); ?>"
                                        data-capacity="<?php echo e((string) ($venue['capacity'] ?? 0)); ?>"
                                        data-image="<?php echo $vImgExists ? e($vImgUrl) : ''; ?>"
                                        <?php echo (int) $venue['venue_id'] === $selectedVenueId ? ' selected' : ''; ?>><?php echo e($venue['venue_name']); ?> - <?php echo e($venue['location']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="booking_date">Booking date</label>
                            <input id="booking_date" name="booking_date" type="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo e((string) ($booking['booking_date'] ?? '')); ?>">
                        </div>

                        <div class="field-group">
                            <label for="start_time">Start time <span id="start-time-period" class="badge" style="display: none; margin-left: 6px; font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-pill); background: var(--surface-muted); color: var(--primary-dark);"></span></label>
                            <input id="start_time" name="start_time" type="time" required value="<?php echo e(substr((string) ($booking['start_time'] ?? ''), 0, 5)); ?>">
                        </div>

                        <div class="field-group">
                            <label for="end_time">End time <span id="end-time-period" class="badge" style="display: none; margin-left: 6px; font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-pill); background: var(--surface-muted); color: var(--primary-dark);"></span></label>
                            <input id="end_time" name="end_time" type="time" required value="<?php echo e(substr((string) ($booking['end_time'] ?? ''), 0, 5)); ?>">
                        </div>

                        <div class="field-group">
                            <label for="event_type">Event type *</label>
                            <select id="event_type" name="event_type" aria-describedby="event-type-hint event-type-compatibility" required>
                                <option value="">Select event type...</option>
                                <?php foreach ($eventTypes as $et): ?>
                                    <option value="<?php echo e($et['event_name']); ?>"
                                        data-charge-type="<?php echo e($et['charge_type']); ?>"
                                        data-charge-val="<?php echo e((string) $et['charge_value']); ?>"
                                        data-description="<?php echo e((string) ($et['description'] ?? '')); ?>"
                                        data-venue-types="<?php echo e(implode('|', $et['venue_types'] ?? [])); ?>"
                                        <?php echo $et['event_name'] === $selectedEventType ? ' selected' : ''; ?>>
                                        <?php echo e($et['event_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="event-type-hint" class="field-hint event-type-hint" aria-live="polite">Choose the event type that best describes your event.</p>
                            <p id="event-type-compatibility" class="field-hint event-type-compatibility" aria-live="polite"></p>
                            <a id="event-type-suitable-venues" class="text-link event-type-suitable-link" href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>" hidden>
                                Browse suitable venues
                                <i class="fi fi-sr-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="field-group">
                            <label for="expected_attendance">Expected attendance</label>
                            <input id="expected_attendance" name="expected_attendance" type="number" min="1" required value="<?php echo e((string) ($booking['expected_attendance'] ?? '')); ?>">
                        </div>
                    </div>

                    <div class="button-row" style="margin-top: 12px; padding-top: 20px; border-top: 1px solid var(--border); justify-content: flex-end;">
                        <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=booking/customerList#history'); ?>">Cancel</a>
                        <button id="booking-submit" type="submit" class="button primary">Save changes</button>
                    </div>
                </form>

                <aside class="card cv-book-card" aria-labelledby="estimate-heading">
                    <header class="form-header">
                        <h2 id="estimate-heading">Updated estimate</h2>
                        <p>Full price before saving</p>
                    </header>

                    <div id="booking-live-result" aria-live="polite" aria-atomic="true">
                        <dl class="estimate-dl">
                            <div class="estimate-row">
                                <dt>Standard charge</dt>
                                <dd id="standard-charge">-</dd>
                            </div>
                            <div class="estimate-row" id="event-type-charge-row" style="display: none;">
                                <dt>Event type charge</dt>
                                <dd id="event-type-charge">-</dd>
                            </div>
                            <div class="estimate-row">
                                <dt>Pricing rule</dt>
                                <dd id="pricing-rule">-</dd>
                            </div>
                            <div class="estimate-row">
                                <dt>Adjustment</dt>
                                <dd id="adjustment-amount">-</dd>
                            </div>
                            <div class="estimate-row estimate-row--total">
                                <dt>Estimated total</dt>
                                <dd id="total-charge">-</dd>
                            </div>
                        </dl>
                        <p id="availability-message"><i class="fi fi-sr-info" aria-hidden="true"></i> Checking updated availability and pricing...</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
    <script src="<?php echo e(BASE_URL . '/assets/js/booking.js?v=' . filemtime(APP_PUBLIC_PATH . '/assets/js/booking.js')); ?>" defer></script>
</body>
</html>
