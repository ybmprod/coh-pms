<?php
declare(strict_types=1);
$flash = get_flash();
$bookings = $bookings ?? [];
$bookingActions = $bookingActions ?? [];
$user = current_user();
$canReviewBookings = in_array($user['role'] ?? '', [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], true);
$actionLabels = [
    BOOKING_STATUS_APPROVED => 'Approve',
    BOOKING_STATUS_REJECTED => 'Reject',
    BOOKING_STATUS_CANCELLED => 'Cancel',
    BOOKING_STATUS_COMPLETED => 'Mark Completed',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Bookings'); ?> | <?php echo e(APP_NAME); ?></title>
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
                <h1>Booking approvals</h1>
                <p>Review customer requests, confirm payment status, and maintain booking records.</p>
            </div>

            <div class="card">
                <h2>Booking queue</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Charge</th>
                            <th>Status</th>
                            <th>Reviewed by</th>
                            <?php if ($canReviewBookings): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><a href="<?php echo e(BASE_URL . '/?r=booking/details&id=' . (int) $booking['booking_id']); ?>"><?php echo e($booking['booking_reference']); ?></a></td>
                                <td><?php echo e($booking['customer_name'] ?? '-'); ?></td>
                                <td><?php echo e($booking['venue_name'] ?? '-'); ?></td>
                                <td><?php echo e(format_date((string) $booking['booking_date']) . ' ' . $booking['start_time'] . '-' . $booking['end_time']); ?></td>
                                <td><?php echo e(money_format_usd((float) $booking['total_charge'])); ?></td>
                                <td><?php echo status_badge($booking['booking_status']); ?></td>
                                <td><?php echo e($booking['reviewer_name'] ?? '-'); ?></td>
                                <?php if ($canReviewBookings): ?>
                                    <td>
                                        <?php foreach ($bookingActions[(int) $booking['booking_id']] ?? [] as $nextStatus): ?>
                                            <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/updateStatus'); ?>" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="booking_id" value="<?php echo e((string) $booking['booking_id']); ?>">
                                                <input type="hidden" name="booking_status" value="<?php echo e($nextStatus); ?>">
                                                <?php $btnClass = in_array($nextStatus, [BOOKING_STATUS_APPROVED, BOOKING_STATUS_COMPLETED]) ? 'primary' : 'secondary'; ?>
                                                <button type="submit" class="button <?php echo $btnClass; ?> small-button"><?php echo e($actionLabels[$nextStatus] ?? $nextStatus); ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endif; ?>
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
