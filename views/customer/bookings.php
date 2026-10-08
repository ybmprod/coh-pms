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

    <main class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main">
            <div id="request" class="tab-section active">
            <div style="margin-bottom: 32px;">
                <p style="font-size: 0.875rem; font-weight: 500; color: var(--accent); margin: 0; text-transform: uppercase;">Book a Venue</p>
                <h1 style="margin: 8px 0 0; font-size: 1.875rem; font-weight: 600;">Venue Booking Request</h1>
                <p style="margin: 8px 0 0; font-size: 0.875rem; color: var(--muted);">Select a venue and provide event details to submit a request.</p>
            </div>

            <div style="display: flex; align-items: center; margin-bottom: 40px;">
                <div style="display: flex; align-items: center; gap: 8px; color: var(--primary); font-size: 0.875rem; font-weight: 500;">
                    <span style="width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center;">1</span> Booking details
                </div>
                <div style="width: 48px; height: 1px; background: var(--border); margin: 0 12px;"></div>
                <div style="display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 0.875rem;">
                    <span style="width: 28px; height: 28px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; color: var(--text);">2</span> Review request
                </div>
                <div style="width: 48px; height: 1px; background: var(--border); margin: 0 12px;"></div>
                <div style="display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 0.875rem;">
                    <span style="width: 28px; height: 28px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; color: var(--text);">3</span> Submit
                </div>
            </div>

            <div class="booking-grid">
                <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/create'); ?>" class="card stacked-form" data-availability-endpoint="<?php echo e(BASE_URL . '/?r=booking/checkAvailability'); ?>">
                    <?php echo csrf_field(); ?>
                    
                    <div style="margin-bottom: 24px;">
                        <span class="eyebrow" style="color: var(--accent); margin-bottom: 8px;">BOOK A VENUE</span>
                        <h2 style="margin-bottom: 4px; font-size: 1.4rem;">Event details</h2>
                        <p style="color: var(--muted); font-size: 0.9rem; margin: 0;">Provide the information used to assess your booking request.</p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div class="field-group" style="grid-column: 1 / -1;">
                            <label for="venue_id">Venue</label>
                            <select id="venue_id" name="venue_id" required>
                                <option value="">Select a venue</option>
                                <?php $preselectedVenueId = (int) ($_GET['venue_id'] ?? 0); ?>
                                <?php foreach ($venues as $venue): ?>
                                    <option value="<?php echo e((string) $venue['venue_id']); ?>"<?php echo (int) $venue['venue_id'] === $preselectedVenueId ? ' selected' : ''; ?>><?php echo e($venue['venue_name']); ?> - <?php echo e($venue['location']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group" style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                            <div>
                                <label for="booking_date">Booking date</label>
                                <input id="booking_date" name="booking_date" type="date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%;">
                            </div>
                            <div>
                                <label for="start_time">Start time</label>
                                <input id="start_time" name="start_time" type="time" required style="width: 100%;">
                            </div>
                            <div>
                                <label for="end_time">End time</label>
                                <input id="end_time" name="end_time" type="time" required style="width: 100%;">
                            </div>
                        </div>
                        <div class="field-group">
                            <label for="event_type">Event type</label>
                            <input id="event_type" name="event_type" type="text" required>
                        </div>
                        <div class="field-group">
                            <label for="number_of_attendees">Expected attendance</label>
                            <input id="number_of_attendees" name="number_of_attendees" type="number" min="1" required>
                        </div>
                    </div>

                    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end;">
                        <button type="submit" class="button primary">Submit booking request</button>
                    </div>
                </form>

                <aside class="card" style="position: sticky; top: 24px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start; margin-bottom: 24px;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: #eaf1f6; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 600;">$</div>
                        <div>
                            <h2 style="margin: 0; font-size: 1.1rem;">Booking estimate</h2>
                            <p style="margin: 0; font-size: 0.85rem; color: var(--muted);">Full price before submission</p>
                        </div>
                    </div>

                    <div id="booking-live-result" aria-live="polite" aria-atomic="true" style="margin: 0; padding: 0; border: 0; background: transparent;">
                        <dl style="display: flex; flex-direction: column; gap: 16px; margin: 0;">
                            <div style="display: flex; justify-content: space-between;"><dt style="color: var(--muted); font-weight: 500; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em;">Standard charge</dt><dd id="standard-charge" style="font-weight: 500; margin: 0;">-</dd></div>
                            <div style="display: flex; justify-content: space-between;"><dt style="color: var(--muted); font-weight: 500; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em;">Pricing rule</dt><dd id="pricing-rule" style="font-weight: 400; margin: 0;">-</dd></div>
                            <div style="display: flex; justify-content: space-between;"><dt style="color: var(--muted); font-weight: 500; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.05em;">Adjustment</dt><dd id="adjustment-amount" style="font-weight: 400; margin: 0;">-</dd></div>
                            <div style="display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--border);"><dt style="color: var(--text); font-weight: 500;">Estimated total</dt><dd id="total-charge" style="color: var(--primary); font-size: 1.4rem; font-weight: 600; margin: 0;">-</dd></div>
                        </dl>
                        <p id="availability-message" style="margin-top: 24px; margin-bottom: 0; padding: 12px; border-radius: var(--radius-md); background: #f1f5f8; font-size: 0.85rem; color: var(--muted);">Select a venue, date, start time, and end time.</p>
                    </div>
                </aside>
            </div>
            </div>

            <div id="history" class="tab-section">
            <div class="card">
                <h2>My booking history</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Charge</th>
                            <th>Status</th>
                            <th>Payment status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo e($booking['booking_reference']); ?></td>
                                <td><?php echo e($booking['venue_name'] ?? '-'); ?></td>
                                <td><?php echo e(format_date((string) $booking['booking_date'])); ?></td>
                                <td><?php echo e($booking['start_time'] . ' - ' . $booking['end_time']); ?></td>
                                <td><?php echo e(money_format_usd((float) $booking['total_charge'])); ?></td>
                                <td><?php echo status_badge($booking['booking_status']); ?></td>
                                <td><?php echo status_badge($booking['payment_status'] ?? null, 'Not submitted'); ?></td>
                                <td>
                                    <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=booking/details&id=' . (int) $booking['booking_id']); ?>">Details</a>
                                    <?php
                                    $paymentStatus = $booking['payment_status'] ?? null;
                                    $canCancel = $booking['booking_status'] === BOOKING_STATUS_PENDING
                                        || ($booking['booking_status'] === BOOKING_STATUS_APPROVED
                                            && !in_array($paymentStatus, [PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED], true));
                                    ?>
                                    <?php if ($canCancel): ?>
                                        <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/cancel'); ?>" class="inline-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo e((string) $booking['booking_id']); ?>">
                                            <button type="submit" class="button secondary small-button">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (($booking['payment_status'] ?? '') === PAYMENT_STATUS_VERIFIED): ?>
                                        <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=payment/receipt&booking_id=' . (int) $booking['booking_id']); ?>">Receipt</a>
                                    <?php endif; ?>
                                    <?php if (
                                        $booking['booking_status'] === BOOKING_STATUS_APPROVED
                                        && !in_array($booking['payment_status'] ?? '', [PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED], true)
                                    ): ?>
                                        <form method="post" action="<?php echo e(BASE_URL . '/?r=payment/submit'); ?>" class="inline-form stacked-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo e((string) $booking['booking_id']); ?>">
                                            <select name="payment_method" required>
                                                <option value="Cash">Cash</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                                <option value="Mobile Money">Mobile Money</option>
                                                <option value="Card">Card</option>
                                            </select>
                                            <input type="text" name="transaction_reference" placeholder="Reference" maxlength="100" required>
                                            <button type="submit" class="button secondary small-button">Pay</button>
                                        </form>
                                    <?php elseif (($booking['payment_status'] ?? '') !== PAYMENT_STATUS_VERIFIED): ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
    <script src="<?php echo e(BASE_URL . '/assets/js/booking.js'); ?>" defer></script>
</body>
</html>
