<?php
declare(strict_types=1);
$user = current_user();
$flash = get_flash();
$bookings = $bookings ?? [];
$venues = $venues ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'My Bookings'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-label="Customer bookings workspace">
            <section id="request" class="tab-section active" data-tab="request" aria-labelledby="booking-request-title">
                <header id="booking-venue-hero" class="page-header booking-venue-hero">
                    <div class="booking-venue-hero-inner">
                        <span class="eyebrow" id="booking-hero-eyebrow">Book a Venue</span>
                        <h1 id="booking-request-title">Venue Booking Request</h1>
                        <p id="booking-hero-subtitle">Select a venue and provide event details to submit a request.</p>
                    </div>
                </header>

                <ol class="workflow-steps" aria-label="Booking steps">
                    <li class="is-active">
                        <span class="workflow-step-badge" aria-hidden="true">1</span>
                        <span>Booking details</span>
                    </li>
                    <li>
                        <span class="workflow-step-badge" aria-hidden="true">2</span>
                        <span>Review request</span>
                    </li>
                    <li>
                        <span class="workflow-step-badge" aria-hidden="true">3</span>
                        <span>Submit &amp; Pay</span>
                    </li>
                </ol>

                <div class="booking-grid">
                    <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/create'); ?>" class="card stacked-form" data-availability-endpoint="<?php echo e(BASE_URL . '/?r=booking/checkAvailability'); ?>">
                        <?php echo csrf_field(); ?>

                        <header class="form-header">
                            <h2>Event details</h2>
                            <p>Provide the information used to assess your booking request.</p>
                        </header>

                        <div class="form-grid">
                            <div class="field-group field-span-full">
                                <label for="venue_id">Venue</label>
                                <select id="venue_id" name="venue_id" required>
                                    <option value="">Select a venue</option>
                                    <?php $preselectedVenueId = (int) ($_GET['venue_id'] ?? 0); ?>
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
                                            <?php echo (int) $venue['venue_id'] === $preselectedVenueId ? ' selected' : ''; ?>><?php echo e($venue['venue_name']); ?> - <?php echo e($venue['location']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field-group">
                                <label for="booking_date">Booking date</label>
                                <input id="booking_date" name="booking_date" type="date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="field-group">
                                <label for="start_time">Start time <span id="start-time-period" class="badge" style="display: none; margin-left: 6px; font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-pill); background: var(--surface-muted); color: var(--primary-dark);"></span></label>
                                <input id="start_time" name="start_time" type="time" required>
                            </div>

                            <div class="field-group">
                                <label for="end_time">End time <span id="end-time-period" class="badge" style="display: none; margin-left: 6px; font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-pill); background: var(--surface-muted); color: var(--primary-dark);"></span></label>
                                <input id="end_time" name="end_time" type="time" required>
                            </div>

                            <div class="field-group">
                                <label for="event_type">Event type *</label>
                                <select id="event_type" name="event_type" required>
                                    <option value="">Select event type...</option>
                                    <?php if (!empty($eventTypes)): ?>
                                        <?php foreach ($eventTypes as $et): ?>
                                            <option value="<?php echo e($et['event_name']); ?>" data-charge-type="<?php echo e($et['charge_type']); ?>" data-charge-val="<?php echo e((string) $et['charge_value']); ?>">
                                                <?php echo e($et['event_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="Wedding">Wedding</option>
                                        <option value="Sports Tournament">Sports Tournament</option>
                                        <option value="Music Concert">Music Concert</option>
                                        <option value="Community Meeting">Community Meeting</option>
                                        <option value="Church Gathering">Church Gathering</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="field-group">
                                <label for="expected_attendance">Expected attendance</label>
                                <input id="expected_attendance" name="expected_attendance" type="number" min="1" required>
                            </div>
                        </div>

                        <div class="button-row" style="margin-top: 12px; padding-top: 20px; border-top: 1px solid var(--border); justify-content: flex-end;">
                            <button type="submit" class="button primary">Submit booking request</button>
                        </div>
                    </form>

                    <aside class="card cv-book-card" aria-labelledby="estimate-heading">
                        <header class="form-header">
                            <h2 id="estimate-heading">Booking estimate</h2>
                            <p>Full price before submission</p>
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
                            <p id="availability-message"><i class="fi fi-sr-info" aria-hidden="true"></i> Select a venue, date, start time, and end time.</p>
                        </div>
                    </aside>
                </div>
            </section>

            <section id="history" class="tab-section" data-tab="history" aria-labelledby="booking-history-title">
                <header class="page-header">
                    <span class="eyebrow">Reservation Records</span>
                    <h1 id="booking-history-title">My booking history</h1>
                    <p>Track approval status, submit payment details, and print official receipts.</p>
                </header>

                <section class="card" aria-label="Bookings table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Venue</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th class="money">Charge</th>
                                <th>Status</th>
                                <th>Payment status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-calendar-clock" aria-hidden="true"></i></span>
                                            <h3>No bookings yet</h3>
                                            <p>You have not submitted any venue booking requests. Choose a venue to get started.</p>
                                            <a href="#request" class="button primary small-button tab-link" data-target="request">Request a booking</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td data-label="Reference"><strong><?php echo e($booking['booking_reference']); ?></strong></td>
                                        <td data-label="Venue"><?php echo e($booking['venue_name'] ?? '-'); ?></td>
                                        <td data-label="Date"><?php echo e(format_date((string) $booking['booking_date'])); ?></td>
                                        <td data-label="Time"><?php echo e(format_booking_time_range((string) $booking['start_time'], (string) $booking['end_time'])); ?></td>
                                        <td data-label="Charge" class="money"><?php echo e(money_format_usd((float) $booking['total_charge'])); ?></td>
                                        <td data-label="Status"><?php echo status_badge($booking['booking_status']); ?></td>
                                        <td data-label="Payment status"><?php echo status_badge($booking['payment_status'] ?? null, 'Not submitted'); ?></td>
                                        <td data-label="Action">
                                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=booking/details&id=' . (int) $booking['booking_id']); ?>">Details</a>
                                            <?php
                                             $paymentStatus = $booking['payment_status'] ?? null;
                                             $canCancel = $booking['booking_status'] === BOOKING_STATUS_PENDING
                                                 || ($booking['booking_status'] === BOOKING_STATUS_APPROVED
                                                     && !in_array($paymentStatus, [PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED], true));
                                             ?>
                                            <?php if ($canCancel): ?>
                                                <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/cancel'); ?>" class="inline-form" data-confirm-title="Cancel booking" data-confirm="Are you sure you want to cancel booking <?php echo e($booking['booking_reference']); ?>?">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo e((string) $booking['booking_id']); ?>">
                                                    <button type="submit" class="button danger small-button">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (($booking['payment_status'] ?? '') === PAYMENT_STATUS_VERIFIED): ?>
                                                <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=payment/receipt&booking_id=' . (int) $booking['booking_id']); ?>">Verification</a>
                                            <?php endif; ?>
                                            <?php if (
                                                $booking['booking_status'] === BOOKING_STATUS_APPROVED
                                                && !in_array($booking['payment_status'] ?? '', [PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED], true)
                                            ): ?>
                                                <form method="post" action="<?php echo e(BASE_URL . '/?r=payment/submit'); ?>" class="inline-form stacked-form">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo e((string) $booking['booking_id']); ?>">
                                                    <select name="payment_method" aria-label="Payment method for <?php echo e($booking['booking_reference']); ?>" required>
                                                        <option value="Cash">Cash</option>
                                                        <option value="Bank Transfer">Bank Transfer</option>
                                                        <option value="Mobile Money">Mobile Money</option>
                                                        <option value="Card">Card</option>
                                                    </select>
                                                    <input type="text" name="transaction_reference" aria-label="Transaction reference for <?php echo e($booking['booking_reference']); ?>" placeholder="e.g. POP-847291" maxlength="100" required>
                                                    <button type="submit" class="button primary small-button">Submit Proof</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </section>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
    <script src="<?php echo e(BASE_URL . '/assets/js/booking.js?v=' . filemtime(APP_PUBLIC_PATH . '/assets/js/booking.js')); ?>" defer></script>
</body>
</html>
