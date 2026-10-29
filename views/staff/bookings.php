<?php
declare(strict_types=1);
$flash = get_flash();
$bookings = $bookings ?? [];
$bookingActions = $bookingActions ?? [];
$user = current_staff_user();
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

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="booking-approvals-title">
            <header class="page-header">
                <h1 id="booking-approvals-title">Booking approvals</h1>
                <p>Review customer requests, confirm payment status, and maintain booking records.</p>
            </header>

            <section class="card" aria-labelledby="booking-queue-title">
                <header class="section-heading">
                    <h2 id="booking-queue-title">Booking queue</h2>
                </header>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th class="money">Charge</th>
                            <th>Status</th>
                            <th>Reviewed by</th>
                            <?php if ($canReviewBookings): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="<?php echo $canReviewBookings ? '8' : '7'; ?>" class="empty-state-cell">
                                    <div class="empty-state">
                                        <span class="empty-state-icon"><i class="fi fi-sr-calendar-check" aria-hidden="true"></i></span>
                                        <h3>No bookings found</h3>
                                        <p>There are no customer booking requests to display in this queue.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td data-label="Reference"><a href="<?php echo e(BASE_URL . '/?r=booking/details&id=' . (int) $booking['booking_id'] . '&portal=staff'); ?>"><strong><?php echo e($booking['booking_reference']); ?></strong></a></td>
                                    <td data-label="Customer"><?php echo e($booking['customer_name'] ?? '-'); ?></td>
                                    <td data-label="Venue"><?php echo e($booking['venue_name'] ?? '-'); ?></td>
                                    <td data-label="Date"><?php echo e(format_date((string) $booking['booking_date']) . ' • ' . format_booking_time_range((string) $booking['start_time'], (string) $booking['end_time'])); ?></td>
                                    <td data-label="Charge" class="money"><?php echo e(money_format_usd((float) $booking['total_charge'])); ?></td>
                                    <td data-label="Status"><?php echo status_badge($booking['booking_status']); ?></td>
                                    <td data-label="Reviewed by"><?php echo e($booking['reviewer_name'] ?? '-'); ?></td>
                                    <?php if ($canReviewBookings): ?>
                                        <td data-label="Action">
                                            <?php foreach ($bookingActions[(int) $booking['booking_id']] ?? [] as $nextStatus): ?>
                                                <?php
                                                $isDestructive = in_array($nextStatus, [BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED], true);
                                                $btnClass = in_array($nextStatus, [BOOKING_STATUS_APPROVED, BOOKING_STATUS_COMPLETED], true) ? 'primary' : ($isDestructive ? 'danger' : 'secondary');
                                                $label = $actionLabels[$nextStatus] ?? $nextStatus;
                                                ?>
                                                <form method="post" action="<?php echo e(BASE_URL . '/?r=booking/updateStatus'); ?>" class="inline-form"
                                                    <?php if ($isDestructive): ?>
                                                        data-confirm-title="<?php echo e($label); ?> booking"
                                                        data-confirm="Are you sure you want to <?php echo e(strtolower($label)); ?> booking <?php echo e($booking['booking_reference']); ?>?"
                                                    <?php endif; ?>>
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo e((string) $booking['booking_id']); ?>">
                                                    <input type="hidden" name="booking_status" value="<?php echo e($nextStatus); ?>">
                                                    <button type="submit" class="button <?php echo $btnClass; ?> small-button"><?php echo e($label); ?></button>
                                                </form>
                                            <?php endforeach; ?>
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
