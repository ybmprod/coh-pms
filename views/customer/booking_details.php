<?php
declare(strict_types=1);
$flash = get_flash();
$booking = $booking ?? [];
$payment = $payment ?? null;
$isStaffPortal = ($portalScope ?? '') === 'staff' || (($portalScope ?? '') !== 'customer' && is_staff_portal_request());
$backRoute = $isStaffPortal ? 'booking/index' : 'booking/customerList#history';
$receiptUrl = BASE_URL . '/?r=payment/receipt&booking_id=' . (int) ($booking['booking_id'] ?? 0) . ($isStaffPortal ? '&portal=staff' : '');
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
    <?php if ($isStaffPortal): ?>
        <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>
    <?php else: ?>
        <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="booking-details-title">
            <header class="page-header">
                <span class="eyebrow">Booking Specification</span>
                <h1 id="booking-details-title">Booking details</h1>
                <p>Reference <?php echo e($booking['booking_reference'] ?? '-'); ?> &bull; Charge breakdown and verification status.</p>
            </header>

            <article class="card">
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

                <div class="button-row" style="margin-top: 20px;">
                    <?php if (($payment['payment_status'] ?? '') === PAYMENT_STATUS_VERIFIED): ?>
                        <a class="button primary" href="<?php echo e($receiptUrl); ?>">
                            <i class="fi fi-sr-receipt" aria-hidden="true"></i>
                            <span>View receipt</span>
                        </a>
                    <?php endif; ?>
                    <a class="button secondary" href="<?php echo e(BASE_URL . '/?r=' . $backRoute); ?>">
                        <i class="fi fi-sr-arrow-left" aria-hidden="true"></i>
                        <span>Back to bookings</span>
                    </a>
                </div>
            </article>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
