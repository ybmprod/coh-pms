<?php
declare(strict_types=1);
$flash = get_flash();
$payments = $payments ?? [];
$selectedStatus = $selectedStatus ?? PAYMENT_STATUS_PENDING_VERIFICATION;
$user = current_user();
$canVerifyPayments = in_array($user['role'] ?? '', [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Payment Verification'); ?> | <?php echo e(APP_NAME); ?></title>
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
                <h1>Payment verification</h1>
                <p>Review submitted payments, confirm transactions, and issue receipts.</p>
            </div>

            <div class="card">
                <h2><?php echo e($selectedStatus); ?> payments</h2>
                <form method="get" action="<?php echo e(BASE_URL . '/'); ?>" class="inline-form" style="margin-bottom: 24px;">
                    <input type="hidden" name="r" value="payment/index">
                    <label for="payment-status-filter">Payment status</label>
                    <select id="payment-status-filter" name="status">
                        <?php foreach ([PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED, PAYMENT_STATUS_REJECTED] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $selectedStatus === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button secondary small-button">Filter</button>
                </form>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Customer</th>
                            <th>Venue</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Receipt</th>
                            <?php if ($canVerifyPayments): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments === []): ?>
                            <tr><td colspan="<?php echo $canVerifyPayments ? '9' : '8'; ?>">No payments match this status.</td></tr>
                        <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo e($payment['booking_reference']); ?></td>
                                <td><?php echo e($payment['customer_name']); ?></td>
                                <td><?php echo e($payment['venue_name']); ?></td>
                                <td><?php echo e(money_format_usd((float) $payment['amount_paid'])); ?></td>
                                <td><?php echo e($payment['payment_method']); ?></td>
                                <td><?php echo e($payment['transaction_reference']); ?></td>
                                <td><?php echo status_badge($payment['payment_status']); ?></td>
                                <td>
                                    <?php if ($payment['payment_status'] === PAYMENT_STATUS_VERIFIED): ?>
                                        <a href="<?php echo e(BASE_URL . '/?r=payment/receipt&booking_id=' . (int) $payment['booking_id']); ?>">View receipt</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <?php if ($canVerifyPayments): ?>
                                    <td>
                                    <form method="post" action="<?php echo e(BASE_URL . '/?r=payment/verify'); ?>" class="inline-form stacked-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="payment_id" value="<?php echo e((string) $payment['payment_id']); ?>">
                                        <input type="hidden" name="booking_id" value="<?php echo e((string) $payment['booking_id']); ?>">
                                        <select name="payment_status">
                                            <option value="Verified">Verified</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                        <button type="submit" class="button primary small-button">Submit</button>
                                    </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
