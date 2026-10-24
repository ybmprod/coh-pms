<?php
declare(strict_types=1);
$flash = get_flash();
$payments = $payments ?? [];
$selectedStatus = $selectedStatus ?? PAYMENT_STATUS_PENDING_VERIFICATION;
$user = current_staff_user();
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

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="payment-verification-title">
            <header class="page-header">
                <h1 id="payment-verification-title">Payment verification</h1>
                <p>Review submitted payments, confirm transactions, and issue receipts.</p>
            </header>

            <section class="card" aria-labelledby="payments-queue-heading">
                <header class="section-heading">
                    <h2 id="payments-queue-heading"><?php echo e($selectedStatus); ?> payments</h2>

                    <form method="get" action="<?php echo e(BASE_URL . '/'); ?>" class="inline-form">
                        <input type="hidden" name="r" value="payment/index">
                        <label for="payment-status-filter" style="font-size: var(--text-xs); font-weight: 600;">Payment status</label>
                        <select id="payment-status-filter" name="status">
                            <?php foreach ([PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED, PAYMENT_STATUS_REJECTED] as $status): ?>
                                <option value="<?php echo e($status); ?>" <?php echo $selectedStatus === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button secondary small-button">Filter</button>
                    </form>
                </header>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Customer</th>
                            <th>Venue</th>
                            <th class="money">Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Receipt</th>
                            <?php if ($canVerifyPayments): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments === []): ?>
                            <tr>
                                <td colspan="<?php echo $canVerifyPayments ? '9' : '8'; ?>" class="empty-state-cell">
                                    <div class="empty-state">
                                        <span class="empty-state-icon"><i class="fi fi-sr-credit-card" aria-hidden="true"></i></span>
                                        <h3>No payments in this queue</h3>
                                        <p>There are currently no payment records with status &ldquo;<?php echo e($selectedStatus); ?>&rdquo;.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td data-label="Booking"><a href="<?php echo e(BASE_URL . '/?r=booking/details&id=' . (int) $payment['booking_id'] . '&portal=staff'); ?>"><strong><?php echo e($payment['booking_reference']); ?></strong></a></td>
                                    <td data-label="Customer"><?php echo e($payment['customer_name']); ?></td>
                                    <td data-label="Venue"><?php echo e($payment['venue_name']); ?></td>
                                    <td data-label="Amount" class="money"><?php echo e(money_format_usd((float) $payment['amount_paid'])); ?></td>
                                    <td data-label="Method"><?php echo e($payment['payment_method']); ?></td>
                                    <td data-label="Reference"><?php echo e($payment['transaction_reference']); ?></td>
                                    <td data-label="Status"><?php echo status_badge($payment['payment_status']); ?></td>
                                    <td data-label="Receipt">
                                        <?php if ($payment['payment_status'] === PAYMENT_STATUS_VERIFIED): ?>
                                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=payment/receipt&booking_id=' . (int) $payment['booking_id'] . '&portal=staff'); ?>">View receipt</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($canVerifyPayments): ?>
                                        <td data-label="Action">
                                            <?php if ($payment['payment_status'] === PAYMENT_STATUS_PENDING_VERIFICATION): ?>
                                                <form method="post" action="<?php echo e(BASE_URL . '/?r=payment/verify'); ?>" class="inline-form">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="payment_id" value="<?php echo e((string) $payment['payment_id']); ?>">
                                                    <input type="hidden" name="booking_id" value="<?php echo e((string) $payment['booking_id']); ?>">
                                                    <select name="payment_status" aria-label="Verification decision for <?php echo e($payment['booking_reference']); ?>">
                                                        <option value="Verified">Verified</option>
                                                        <option value="Rejected">Rejected</option>
                                                    </select>
                                                    <button type="submit" class="button primary small-button">Submit</button>
                                                </form>
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
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
