<?php
declare(strict_types=1);
$receipt = $receipt ?? [];
$isStaffPortal = ($portalScope ?? '') === 'staff' || (($portalScope ?? '') !== 'customer' && is_staff_portal_request());
$adjustmentValue = (float) $receipt['adjustment_amount'];
$adjustmentLabel = $adjustmentValue < 0 ? 'Discount' : ($adjustmentValue > 0 ? 'Surcharge' : 'Adjustment');
$detailsRoute = 'r=booking/details&id=' . (int) $receipt['booking_id'] . ($isStaffPortal ? '&portal=staff' : '');
$listRoute = $isStaffPortal ? 'r=payment/index' : 'r=booking/customerList#history';
$listLabel = $isStaffPortal ? 'Back to payments' : 'My bookings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Payment receipt'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php if ($isStaffPortal): ?>
        <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>
    <?php else: ?>
        <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <?php endif; ?>

    <main id="main-content" class="container narrow">
        <article class="card receipt-sheet" aria-labelledby="receipt-heading">
            <header class="form-header">
                <span class="eyebrow">City of Harare &bull; Official Record</span>
                <h1 id="receipt-heading">Payment Verification</h1>
                <p class="receipt-number">Payment Verification Reference: <strong><?php echo e($receipt['receipt_number']); ?></strong></p>
            </header>

            <dl class="details-list">
                <div><dt>Booking reference</dt><dd><?php echo e($receipt['booking_reference']); ?></dd></div>
                <div><dt>Customer</dt><dd><?php echo e($receipt['customer_name']); ?></dd></div>
                <div><dt>Venue</dt><dd><?php echo e($receipt['venue_name']); ?></dd></div>
                <div><dt>Event type</dt><dd><?php echo e($receipt['event_type'] ?? '-'); ?></dd></div>
                <?php if (!empty($receipt['expected_attendance'])): ?>
                    <div><dt>Expected attendance</dt><dd><?php echo e(number_format((int) $receipt['expected_attendance'])); ?> guests</dd></div>
                <?php endif; ?>
                <div><dt>Booking date</dt><dd><?php echo e(format_date((string) $receipt['booking_date'])); ?></dd></div>
                <div><dt>Time</dt><dd><?php echo e(format_booking_time_range((string) $receipt['start_time'], (string) $receipt['end_time'])); ?></dd></div>
                <div><dt>Standard charge</dt><dd><?php echo e(money_format_usd((float) $receipt['standard_charge'])); ?></dd></div>
                <?php if (!empty($receipt['event_type_charge']) && (float) $receipt['event_type_charge'] > 0): ?>
                    <div><dt>Event type charge</dt><dd><?php echo e(money_format_usd((float) $receipt['event_type_charge'])); ?></dd></div>
                <?php endif; ?>
                <div><dt>Pricing rule</dt><dd><?php echo e($receipt['rule_name'] ?? 'Standard rate applied'); ?></dd></div>
                <div>
                    <dt><?php echo e($adjustmentLabel); ?></dt>
                    <dd><?php echo e(money_format_usd(abs($adjustmentValue))); ?></dd>
                </div>
                <div><dt>Final total charge</dt><dd><?php echo e(money_format_usd((float) $receipt['total_charge'])); ?></dd></div>
                <div><dt>Payment method</dt><dd><?php echo e($receipt['payment_method']); ?></dd></div>
                <div><dt>Transaction reference</dt><dd><?php echo e($receipt['transaction_reference']); ?></dd></div>
                <div><dt>Verified by</dt><dd><?php echo e($receipt['verified_by_name'] ?? '-'); ?></dd></div>
                <div><dt>Verification date</dt><dd><?php echo e(format_datetime((string) $receipt['verification_date'])); ?></dd></div>
            </dl>

            <div class="button-row no-print">
                <button type="button" class="button primary" onclick="window.print()">
                    <i class="fi fi-sr-print" aria-hidden="true"></i>
                    <span>Print verification</span>
                </button>
                <a class="button secondary" href="<?php echo e(BASE_URL . '/?' . $detailsRoute); ?>">
                    <i class="fi fi-sr-document" aria-hidden="true"></i>
                    <span>Booking details</span>
                </a>
                <a class="button ghost" href="<?php echo e(BASE_URL . '/?' . $listRoute); ?>">
                    <i class="fi fi-sr-arrow-left" aria-hidden="true"></i>
                    <span><?php echo e($listLabel); ?></span>
                </a>
            </div>
        </article>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
