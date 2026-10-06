<?php
declare(strict_types=1);
$receipt = $receipt ?? [];
$user = current_user();
$adjustmentValue = (float) $receipt['adjustment_amount'];
$adjustmentLabel = $adjustmentValue < 0 ? 'Discount' : ($adjustmentValue > 0 ? 'Surcharge' : 'Adjustment');
$backRoute = 'booking/details&id=' . (int) $receipt['booking_id'];
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
    <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>

    <main class="container narrow">
        <section class="card receipt-sheet">
            <h1>Payment receipt</h1>
            <p class="receipt-number">Receipt <?php echo e($receipt['receipt_number']); ?></p>

            <dl class="details-list">
                <div><dt>Booking reference</dt><dd><?php echo e($receipt['booking_reference']); ?></dd></div>
                <div><dt>Customer</dt><dd><?php echo e($receipt['customer_name']); ?></dd></div>
                <div><dt>Venue</dt><dd><?php echo e($receipt['venue_name']); ?></dd></div>
                <div><dt>Booking date</dt><dd><?php echo e(format_date((string) $receipt['booking_date'])); ?></dd></div>
                <div><dt>Time</dt><dd><?php echo e($receipt['start_time'] . ' - ' . $receipt['end_time']); ?></dd></div>
                <div><dt>Standard charge</dt><dd><?php echo e(money_format_usd((float) $receipt['standard_charge'])); ?></dd></div>
                <div><dt>Pricing rule</dt><dd><?php echo e($receipt['rule_name'] ?? 'No matching rule'); ?></dd></div>
                <div>
                    <dt><?php echo e($adjustmentLabel); ?></dt>
                    <dd><?php echo e(money_format_usd(abs($adjustmentValue))); ?></dd>
                </div>
                <div><dt>Final charge</dt><dd><?php echo e(money_format_usd((float) $receipt['total_charge'])); ?></dd></div>
                <div><dt>Payment method</dt><dd><?php echo e($receipt['payment_method']); ?></dd></div>
                <div><dt>Transaction reference</dt><dd><?php echo e($receipt['transaction_reference']); ?></dd></div>
                <div><dt>Verified by</dt><dd><?php echo e($receipt['verified_by_name'] ?? '-'); ?></dd></div>
                <div><dt>Verification date</dt><dd><?php echo e(format_datetime((string) $receipt['verification_date'])); ?></dd></div>
            </dl>

            <div class="button-row no-print">
                <button type="button" class="button primary" onclick="window.print()">Print receipt</button>
                <a class="button secondary" href="<?php echo e(BASE_URL . '/?' . $backRoute); ?>">Booking details</a>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
