<?php
declare(strict_types=1);
$flash = get_flash();
$booking = $booking ?? [];
$payment = $payment ?? null;
$user = current_user();
$backRoute = ($user['role'] ?? '') === ROLE_CUSTOMER ? 'booking/customerList' : 'booking/index';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Booking details'); ?> | <?php echo e(APP_NAME); ?></title>
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
            <div class="card">
                <h1>Booking details</h1>
                <dl class="details-list">
                    <div><dt>Reference</dt><dd><?php echo e($booking['booking_reference']); ?></dd></div>
                    <div><dt>Customer</dt><dd><?php echo e($booking['customer_name'] ?? '-'); ?></dd></div>
                    <div><dt>Venue</dt><dd><?php echo e($booking['venue_name'] ?? '-'); ?></dd></div>
                    <div><dt>Date</dt><dd><?php echo e(format_date((string) $booking['booking_date'])); ?></dd></div>
                    <div><dt>Time</dt><dd><?php echo e($booking['start_time'] . ' - ' . $booking['end_time']); ?></dd></div>
                    <div><dt>Standard charge</dt><dd><?php echo e(money_format_usd((float) $booking['standard_charge'])); ?></dd></div>
                    <div><dt>Pricing rule</dt><dd><?php echo e($booking['rule_name'] ?? 'No matching rule'); ?></dd></div>
                    <div><dt>Adjustment</dt><dd><?php echo e(money_format_usd((float) $booking['adjustment_amount'])); ?></dd></div>
                    <div><dt>Total charge</dt><dd><?php echo e(money_format_usd((float) $booking['total_charge'])); ?></dd></div>
                    <div><dt>Booking status</dt><dd><?php echo status_badge($booking['booking_status']); ?></dd></div>
                    <div><dt>Payment status</dt><dd><?php echo status_badge($payment['payment_status'] ?? null, 'Not submitted'); ?></dd></div>
                    <div><dt>Receipt number</dt><dd><?php echo e($payment['receipt_number'] ?? '-'); ?></dd></div>
                </dl>
                <?php if (($payment['payment_status'] ?? '') === PAYMENT_STATUS_VERIFIED): ?>
                    <p><a class="button secondary" href="<?php echo e(BASE_URL . '/?r=payment/receipt&booking_id=' . (int) $booking['booking_id']); ?>">View receipt</a></p>
                <?php endif; ?>
            </div>

            <div style="margin-top: 16px;">
                <a class="button secondary" href="<?php echo e(BASE_URL . '/?r=' . $backRoute); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: -3px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to bookings
                </a>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
