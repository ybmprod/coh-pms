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

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="reports-page-title">
            <header class="page-header">
                <h1 id="reports-page-title">Facility Usage &amp; Revenue Reports</h1>
                <p>Filter booking records, evaluate facility utilization, and export verified revenue reports to CSV.</p>
            </header>

            <details class="card no-print">
                <summary style="font-size: var(--text-lg); font-weight: 600; color: var(--primary-dark); cursor: pointer;">Report Filters</summary>
                <div style="margin-top: 18px;">
                    <form method="get" action="<?php echo e(BASE_URL . '/?r=report/index'); ?>" class="stacked-form">
                        <input type="hidden" name="r" value="report/index">
                        <div class="form-grid">
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
                        </div>
                        <div class="button-row" style="justify-content: flex-end;">
                            <button type="submit" class="button primary">
                                <i class="fi fi-sr-filter" aria-hidden="true"></i>
                                <span>Apply filters</span>
                            </button>
                        </div>
                    </form>
                </div>
            </details>

            <?php if ($canViewAllReports): ?>
            <section id="summary" class="tab-section" data-tab="summary" aria-label="Executive summary">
                <div class="stats-grid" style="margin-top: 0;">
                    <article class="stat-card">
                        <span>Total bookings</span>
                        <strong><?php echo e((string) $summary['total_bookings']); ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>Verified revenue</span>
                        <strong><?php echo e(money_format_usd((float) $summary['verified_revenue'])); ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>Pending verification</span>
                        <strong><?php echo e(money_format_usd((float) $summary['pending_verification_amount'])); ?></strong>
                    </article>
                </div>

                <section class="card" style="margin-top: 24px;" aria-labelledby="bookings-by-status-heading">
                    <header class="section-heading">
                        <h2 id="bookings-by-status-heading">Bookings by status</h2>
                        <button type="button" class="button ghost small-button no-print" onclick="window.print()">
                            <i class="fi fi-sr-print" aria-hidden="true"></i>
                            <span>Print summary</span>
                        </button>
                    </header>
                    <ul class="summary-status-list">
                        <?php foreach ($summary['bookings_by_status'] as $status => $count): ?>
                            <li><span><?php echo status_badge((string) $status); ?></span><strong><?php echo e((string) $count); ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </section>
            <?php endif; ?>

            <?php if (in_array('bookings', $allowedReports, true)): ?>
            <section id="bookings" class="tab-section" data-tab="bookings" aria-labelledby="booking-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="booking-report-heading">Booking report</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" onclick="window.print()">
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=bookings' . buildReportQueryString($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
                        </div>
                    </header>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Venue</th>
                                <th>Event Type</th>
                                <th class="money">Event Charge</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="money">Total charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookingReport['rows'] === []): ?>
                                <tr>
                                    <td colspan="8" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-calendar" aria-hidden="true"></i></span>
                                            <h3>No booking records found</h3>
                                            <p>No bookings match the selected filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookingReport['rows'] as $row): ?>
                                    <tr>
                                        <td data-label="Reference"><strong><?php echo e($row['booking_reference'] ?? '-'); ?></strong></td>
                                        <td data-label="Customer"><?php echo e($row['customer_name'] ?? '-'); ?></td>
                                        <td data-label="Venue"><?php echo e($row['venue_name'] ?? '-'); ?></td>
                                        <td data-label="Event Type"><?php echo e($row['event_type'] ?? '-'); ?></td>
                                        <td data-label="Event Charge" class="money"><?php echo e(money_format_usd((float) ($row['event_type_charge'] ?? 0))); ?></td>
                                        <td data-label="Date"><?php echo e(format_date((string) ($row['booking_date'] ?? ''))); ?></td>
                                        <td data-label="Status"><?php echo status_badge($row['booking_status'] ?? null); ?></td>
                                        <td data-label="Total charge" class="money"><?php echo e(money_format_usd((float) ($row['total_charge'] ?? 0))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7">Totals (<?php echo e((string) ($bookingReport['totals']['count'] ?? 0)); ?> bookings)</th>
                                <th class="money"><?php echo e(money_format_usd((float) ($bookingReport['totals']['total_charge'] ?? 0))); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </article>
            </section>
            <?php endif; ?>

            <?php if (in_array('usage', $allowedReports, true)): ?>
            <section id="usage" class="tab-section" data-tab="usage" aria-labelledby="usage-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="usage-report-heading">Venue usage</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" onclick="window.print()">
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=usage' . buildReportQueryString($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
                        </div>
                    </header>
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
                                <tr>
                                    <td colspan="6" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-clock" aria-hidden="true"></i></span>
                                            <h3>No venues found</h3>
                                            <p>No venue usage data matches the selected filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usageReport['rows'] as $row): ?>
                                    <tr>
                                        <td data-label="Venue"><strong><?php echo e($row['venue_name'] ?? '-'); ?></strong></td>
                                        <td data-label="Type"><?php echo e($row['venue_type'] ?? '-'); ?></td>
                                        <td data-label="Bookings"><?php echo e((string) ($row['booking_count'] ?? 0)); ?></td>
                                        <td data-label="Total hours"><?php echo e((string) ($row['total_hours'] ?? 0)); ?></td>
                                        <td data-label="Available hours"><?php echo e((string) ($row['available_hours'] ?? 0)); ?></td>
                                        <td data-label="Usage %"><strong><?php echo e((string) ($row['usage_percentage'] ?? 0)); ?>%</strong></td>
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
                </article>
            </section>
            <?php endif; ?>

            <?php if (in_array('payments', $allowedReports, true)): ?>
            <section id="payments" class="tab-section" data-tab="payments" aria-labelledby="payment-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="payment-report-heading">Payment report</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" onclick="window.print()">
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=payments' . buildReportQueryString($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
                        </div>
                    </header>
                    <p>Payment report date filters use the payment submission date.</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Venue</th>
                                <th class="money">Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($paymentReport['rows'] === []): ?>
                                <tr>
                                    <td colspan="6" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-credit-card" aria-hidden="true"></i></span>
                                            <h3>No payment records found</h3>
                                            <p>No payments match the selected date or status filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paymentReport['rows'] as $row): ?>
                                    <tr>
                                        <td data-label="Booking"><strong><?php echo e($row['booking_reference'] ?? '-'); ?></strong></td>
                                        <td data-label="Customer"><?php echo e($row['customer_name'] ?? '-'); ?></td>
                                        <td data-label="Venue"><?php echo e($row['venue_name'] ?? '-'); ?></td>
                                        <td data-label="Amount" class="money"><?php echo e(money_format_usd((float) ($row['amount_paid'] ?? 0))); ?></td>
                                        <td data-label="Method"><?php echo e($row['payment_method'] ?? '-'); ?></td>
                                        <td data-label="Status"><?php echo status_badge($row['payment_status'] ?? null); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Totals (<?php echo e((string) ($paymentReport['totals']['count'] ?? 0)); ?> payments)</th>
                                <th class="money"><?php echo e(money_format_usd((float) ($paymentReport['totals']['amount'] ?? 0))); ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </article>
            </section>
            <?php endif; ?>

            <?php if (in_array('revenue', $allowedReports, true)): ?>
            <section id="revenue" class="tab-section" data-tab="revenue" aria-labelledby="revenue-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="revenue-report-heading">Revenue report</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" onclick="window.print()">
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=revenue' . buildReportQueryString($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
                        </div>
                    </header>
                    <p>Revenue includes Verified payments only; date filters use the verification date.</p>
                    <h3>Verified revenue by venue</h3>
                    <div class="report-bars" role="img" aria-label="Verified revenue by venue">
                        <?php foreach ($revenueReport['rows'] as $row): ?>
                            <div class="report-bar-row">
                                <div class="report-bar-track">
                                    <div class="report-bar" style="width: <?php echo e((string) ($row['bar_percentage'] ?? 0)); ?>%;"></div>
                                    <span class="report-bar-label"><?php echo e($row['venue_name']); ?></span>
                                </div>
                                <strong class="report-bar-amount"><?php echo e(money_format_usd((float) $row['total_amount'])); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Venue</th>
                                <th>Payments</th>
                                <th class="money">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($revenueReport['rows'] === []): ?>
                                <tr>
                                    <td colspan="3" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-coins" aria-hidden="true"></i></span>
                                            <h3>No verified revenue records found</h3>
                                            <p>No verified revenue transactions exist for this period.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($revenueReport['rows'] as $row): ?>
                                    <tr>
                                        <td data-label="Venue"><strong><?php echo e($row['venue_name'] ?? '-'); ?></strong></td>
                                        <td data-label="Payments"><?php echo e((string) ($row['payment_count'] ?? 0)); ?></td>
                                        <td data-label="Total" class="money"><?php echo e(money_format_usd((float) ($row['total_amount'] ?? 0))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Totals</th>
                                <th><?php echo e((string) ($revenueReport['totals']['payment_count'] ?? 0)); ?></th>
                                <th class="money"><?php echo e(money_format_usd((float) ($revenueReport['totals']['total_amount'] ?? 0))); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    <h3 style="margin-top: 28px;">Verified revenue by month</h3>
                    <table class="data-table">
                        <thead><tr><th>Month</th><th>Payments</th><th class="money">Total verified revenue</th></tr></thead>
                        <tbody>
                            <?php if ($revenueReport['by_month'] === []): ?>
                                <tr>
                                    <td colspan="3" class="empty-state-cell">
                                        <div class="empty-state">
                                            <p>No monthly revenue records found.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($revenueReport['by_month'] as $month): ?>
                                    <tr>
                                        <td data-label="Month"><strong><?php echo e($month['revenue_month']); ?></strong></td>
                                        <td data-label="Payments"><?php echo e((string) $month['payment_count']); ?></td>
                                        <td data-label="Total verified revenue" class="money"><?php echo e(money_format_usd((float) $month['total_amount'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>
            </section>
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
