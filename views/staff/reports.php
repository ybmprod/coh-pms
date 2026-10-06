<?php
declare(strict_types=1);
$flash = get_flash();
$filters = $filters ?? [];
$allowedReports = $allowedReports ?? [];
$canViewAllReports = $canViewAllReports ?? false;
$venues = $venues ?? [];
$summary = $summary ?? ['total_bookings' => 0, 'bookings_by_status' => [], 'verified_revenue' => 0, 'pending_verification_amount' => 0];
$bookingReport = $bookingReport ?? ['rows' => [], 'totals' => []];
$usageReport = $usageReport ?? ['rows' => [], 'totals' => [], 'range_days' => 0];
$paymentReport = $paymentReport ?? ['rows' => [], 'totals' => []];
$revenueReport = $revenueReport ?? ['rows' => [], 'by_month' => [], 'totals' => []];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Reports'); ?> | <?php echo e(APP_NAME); ?></title>
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
            <details class="card" style="margin-bottom: 24px;">
                <summary style="font-size: 1.4rem; font-weight: 500; color: var(--primary); cursor: pointer;">Filters</summary>
                <div style="margin-top: 16px;">
                <form method="get" action="<?php echo e(BASE_URL . '/?r=report/index'); ?>" class="stacked-form inline-form">
                    <input type="hidden" name="r" value="report/index">
                    <div class="field-group">
                        <label for="start_date">From date</label>
                        <input id="start_date" name="start_date" type="date" value="<?php echo e($filters['start_date'] ?? ''); ?>">
                    </div>
                    <div class="field-group">
                        <label for="end_date">To date</label>
                        <input id="end_date" name="end_date" type="date" value="<?php echo e($filters['end_date'] ?? ''); ?>">
                    </div>
                    <div class="field-group">
                        <label for="venue_id">Venue</label>
                        <select id="venue_id" name="venue_id">
                            <option value="">All venues</option>
                            <?php foreach ($venues as $venue): ?>
                                <option value="<?php echo e((string) $venue['venue_id']); ?>" <?php echo (string) ($filters['venue_id'] ?? 0) === (string) $venue['venue_id'] ? 'selected' : ''; ?>><?php echo e($venue['venue_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="venue_type">Venue type</label>
                        <select id="venue_type" name="venue_type">
                            <option value="">All types</option>
                            <?php foreach (['Community Hall', 'Community Centre', 'Stadium', 'Open Space', 'Other'] as $venueType): ?>
                                <option value="<?php echo e($venueType); ?>" <?php echo ($filters['venue_type'] ?? '') === $venueType ? 'selected' : ''; ?>><?php echo e($venueType); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="booking_status">Booking status</label>
                        <select id="booking_status" name="booking_status">
                            <option value="">All</option>
                            <?php foreach ([BOOKING_STATUS_PENDING, BOOKING_STATUS_APPROVED, BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED, BOOKING_STATUS_CONFIRMED, BOOKING_STATUS_COMPLETED] as $status): ?>
                                <option value="<?php echo e($status); ?>" <?php echo ($filters['booking_status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="payment_status">Payment status</label>
                        <select id="payment_status" name="payment_status">
                            <option value="">All</option>
                            <?php foreach ([PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED, PAYMENT_STATUS_REJECTED] as $status): ?>
                                <option value="<?php echo e($status); ?>" <?php echo ($filters['payment_status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="button primary">Apply filters</button>
                </form>
                </div>
            </details>

            <?php if ($canViewAllReports): ?>
            <div class="tab-section" data-tab="summary">
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Total bookings</span>
                        <strong><?php echo e((string) $summary['total_bookings']); ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Verified revenue</span>
                        <strong><?php echo e(money_format_usd((float) $summary['verified_revenue'])); ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Pending verification</span>
                        <strong><?php echo e(money_format_usd((float) $summary['pending_verification_amount'])); ?></strong>
                    </div>
                </div>
                <div class="card" style="margin-top: 24px;">
                    <h2>Bookings by status</h2>
                    <ul class="summary-status-list">
                        <?php foreach ($summary['bookings_by_status'] as $status => $count): ?>
                            <li><span><?php echo e($status); ?></span><strong><?php echo e((string) $count); ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array('bookings', $allowedReports, true)): ?>
            <div class="tab-section" data-tab="bookings">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h2 style="margin: 0;">Booking report</h2>
                        <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=bookings' . buildReportQueryString($filters)); ?>">Export CSV</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Venue</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookingReport['rows'] === []): ?>
                                <tr><td colspan="6">No booking records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($bookingReport['rows'] as $row): ?>
                                    <tr>
                                        <td><?php echo e($row['booking_reference'] ?? '-'); ?></td>
                                        <td><?php echo e($row['customer_name'] ?? '-'); ?></td>
                                        <td><?php echo e($row['venue_name'] ?? '-'); ?></td>
                                        <td><?php echo e(format_date((string) ($row['booking_date'] ?? ''))); ?></td>
                                        <td><?php echo status_badge($row['booking_status'] ?? null); ?></td>
                                        <td><?php echo e(money_format_usd((float) ($row['total_charge'] ?? 0))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><th colspan="5">Totals (<?php echo e((string) ($bookingReport['totals']['count'] ?? 0)); ?> bookings)</th><th><?php echo e(money_format_usd((float) ($bookingReport['totals']['total_charge'] ?? 0))); ?></th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array('usage', $allowedReports, true)): ?>
            <div class="tab-section" data-tab="usage">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h2 style="margin: 0;">Venue usage</h2>
                        <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=usage' . buildReportQueryString($filters)); ?>">Export CSV</a>
                    </div>
                    <p>Usage assumes 12 available hours per venue per day, using the selected dates, or the first to last booking date when no dates are selected.</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Venue</th>
                                <th>Type</th>
                                <th>Bookings</th>
                                <th>Total hours</th>
                                <th>Available hours</th>
                                <th>Usage %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usageReport['rows'] === []): ?>
                                <tr><td colspan="6">No venues found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usageReport['rows'] as $row): ?>
                                    <tr>
                                        <td><?php echo e($row['venue_name'] ?? '-'); ?></td>
                                        <td><?php echo e($row['venue_type'] ?? '-'); ?></td>
                                        <td><?php echo e((string) ($row['booking_count'] ?? 0)); ?></td>
                                        <td><?php echo e((string) ($row['total_hours'] ?? 0)); ?></td>
                                        <td><?php echo e((string) ($row['available_hours'] ?? 0)); ?></td>
                                        <td><?php echo e((string) ($row['usage_percentage'] ?? 0)); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Totals</th>
                                <th><?php echo e((string) ($usageReport['totals']['booking_count'] ?? 0)); ?></th>
                                <th><?php echo e((string) ($usageReport['totals']['total_hours'] ?? 0)); ?></th>
                                <th><?php echo e((string) ($usageReport['totals']['available_hours'] ?? 0)); ?></th>
                                <th><?php echo e((string) ($usageReport['totals']['usage_percentage'] ?? 0)); ?>%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array('payments', $allowedReports, true)): ?>
            <div class="tab-section" data-tab="payments">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h2 style="margin: 0;">Payment report</h2>
                        <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=payments' . buildReportQueryString($filters)); ?>">Export CSV</a>
                    </div>
                    <p>Payment report date filters use the payment submission date.</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Venue</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($paymentReport['rows'] === []): ?>
                                <tr><td colspan="6">No payment records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($paymentReport['rows'] as $row): ?>
                                    <tr>
                                        <td><?php echo e($row['booking_reference'] ?? '-'); ?></td>
                                        <td><?php echo e($row['customer_name'] ?? '-'); ?></td>
                                        <td><?php echo e($row['venue_name'] ?? '-'); ?></td>
                                        <td><?php echo e(money_format_usd((float) ($row['amount_paid'] ?? 0))); ?></td>
                                        <td><?php echo e($row['payment_method'] ?? '-'); ?></td>
                                        <td><?php echo status_badge($row['payment_status'] ?? null); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3">Totals (<?php echo e((string) ($paymentReport['totals']['count'] ?? 0)); ?> payments)</th><th><?php echo e(money_format_usd((float) ($paymentReport['totals']['amount'] ?? 0))); ?></th><th colspan="2"></th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array('revenue', $allowedReports, true)): ?>
            <div class="tab-section" data-tab="revenue">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h2 style="margin: 0;">Revenue report</h2>
                        <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=revenue' . buildReportQueryString($filters)); ?>">Export CSV</a>
                    </div>
                    <p>Revenue includes Verified payments only; date filters use the verification date.</p>
                    <h3>Verified revenue by venue</h3>
                    <div class="report-bars" role="img" aria-label="Verified revenue by venue">
                        <?php foreach ($revenueReport['rows'] as $row): ?>
                            <div class="report-bar-row">
                                <span><?php echo e($row['venue_name']); ?></span>
                                <div class="report-bar-track"><div class="report-bar" style="width: <?php echo e((string) ($row['bar_percentage'] ?? 0)); ?>%;"></div></div>
                                <strong><?php echo e(money_format_usd((float) $row['total_amount'])); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Venue</th>
                                <th>Payments</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($revenueReport['rows'] === []): ?>
                                <tr><td colspan="3">No verified revenue records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($revenueReport['rows'] as $row): ?>
                                    <tr>
                                        <td><?php echo e($row['venue_name'] ?? '-'); ?></td>
                                        <td><?php echo e((string) ($row['payment_count'] ?? 0)); ?></td>
                                        <td><?php echo e(money_format_usd((float) ($row['total_amount'] ?? 0))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><th>Totals</th><th><?php echo e((string) ($revenueReport['totals']['payment_count'] ?? 0)); ?></th><th><?php echo e(money_format_usd((float) ($revenueReport['totals']['total_amount'] ?? 0))); ?></th></tr>
                        </tfoot>
                    </table>
                    <h3>Verified revenue by month</h3>
                    <table class="data-table">
                        <thead><tr><th>Month</th><th>Payments</th><th>Total verified revenue</th></tr></thead>
                        <tbody>
                            <?php if ($revenueReport['by_month'] === []): ?>
                                <tr><td colspan="3">No monthly revenue records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($revenueReport['by_month'] as $month): ?>
                                    <tr><td><?php echo e($month['revenue_month']); ?></td><td><?php echo e((string) $month['payment_count']); ?></td><td><?php echo e(money_format_usd((float) $month['total_amount'])); ?></td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
<?php
function buildReportQueryString(array $filters): string
{
    $pairs = [];
    foreach ($filters as $key => $value) {
        if ($value === '' || $value === 0 || $value === null) {
            continue;
        }
        $pairs[] = urlencode($key) . '=' . urlencode((string) $value);
    }
    return $pairs === [] ? '' : '&' . implode('&', $pairs);
}
?>
