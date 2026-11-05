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

$statusSegments = [];
foreach (($summary['bookings_by_status'] ?? []) as $status => $count) {
    if ((int) $count > 0) {
        $statusSegments[] = [
            'label' => (string) $status,
            'value' => (int) $count,
            'tone' => strtolower(str_replace(' ', '-', (string) $status)),
        ];
    }
}

$monthlyRevenueSegments = chart_donut_segments(array_map(static fn(array $row): array => [
    'label' => date('M Y', strtotime((string) $row['revenue_month'] . '-01')),
    'value' => (float) $row['total_amount'],
], $revenueReport['by_month']), 6, 'Other months');

$revenueVenueSegments = chart_donut_segments(array_map(static fn(array $row): array => [
    'label' => (string) $row['venue_name'],
    'value' => (float) $row['total_amount'],
], $revenueReport['rows']), 6, 'Other venues');

$usageDonutSegments = chart_donut_segments(array_map(static fn(array $row): array => [
    'label' => (string) $row['venue_name'],
    'value' => (float) $row['total_hours'],
], $usageReport['rows']), 6, 'Other venues');

$monthlyRevenueTotal = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $monthlyRevenueSegments));
$revenueVenueTotal = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $revenueVenueSegments));
$usageHoursTotal = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $usageDonutSegments));

$activeFilterChips = [];
if (($filters['start_date'] ?? '') !== '' || ($filters['end_date'] ?? '') !== '') {
    $activeFilterChips[] = [
        'label' => 'Dates',
        'value' => (($filters['start_date'] ?? '') !== '' ? format_date($filters['start_date']) : 'Any')
            . ' – ' . (($filters['end_date'] ?? '') !== '' ? format_date($filters['end_date']) : 'Any'),
    ];
}
if (!empty($filters['venue_id'])) {
    $venueLabel = 'Venue #' . (int) $filters['venue_id'];
    foreach ($venues as $venue) {
        if ((int) $venue['venue_id'] === (int) $filters['venue_id']) {
            $venueLabel = (string) $venue['venue_name'];
            break;
        }
    }
    $activeFilterChips[] = ['label' => 'Venue', 'value' => $venueLabel];
}
foreach (['venue_type' => 'Type', 'booking_status' => 'Booking', 'payment_status' => 'Payment'] as $filterKey => $filterLabel) {
    if (($filters[$filterKey] ?? '') !== '') {
        $activeFilterChips[] = ['label' => $filterLabel, 'value' => (string) $filters[$filterKey]];
    }
}
$activeFilterCount = count($activeFilterChips);
$clearFiltersUrl = BASE_URL . '/?r=report/index';
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

            <div class="filter-toolbar no-print">
                <div class="filter-toolbar__status">
                    <span class="filter-toolbar__icon" aria-hidden="true"><i class="fi fi-sr-filter"></i></span>
                    <div class="filter-toolbar__heading">
                        <span class="filter-toolbar__title">Report filters</span>
                        <p class="filter-toolbar__summary">
                            <?php if ($activeFilterCount === 0): ?>
                                Showing all records
                            <?php else: ?>
                                <?php echo e((string) $activeFilterCount); ?> active <?php echo $activeFilterCount === 1 ? 'filter' : 'filters'; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($activeFilterChips !== []): ?>
                        <ul class="filter-chips" aria-label="Active filters">
                            <?php foreach ($activeFilterChips as $chip): ?>
                                <li class="filter-chip"><span><?php echo e($chip['label']); ?></span><strong><?php echo e($chip['value']); ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="filter-toolbar__actions">
                    <?php if ($activeFilterCount > 0): ?>
                        <a class="button ghost small-button" href="<?php echo e($clearFiltersUrl); ?>">
                            <i class="fi fi-sr-refresh" aria-hidden="true"></i>
                            <span>Clear filters</span>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="button primary small-button" data-filter-open aria-haspopup="dialog" aria-controls="report-filter-dialog">
                        <i class="fi fi-sr-settings-sliders" aria-hidden="true"></i>
                        <span>Filter</span>
                    </button>
                </div>
            </div>

            <dialog id="report-filter-dialog" class="filter-dialog" aria-labelledby="report-filter-title">
                <form method="get" action="<?php echo e(BASE_URL . '/?r=report/index'); ?>" class="filter-dialog__form">
                    <input type="hidden" name="r" value="report/index">
                    <header class="filter-dialog__header">
                        <div>
                            <span class="panel-eyebrow">Report Scope</span>
                            <h2 id="report-filter-title">Filter reports</h2>
                            <p>Narrow bookings, usage, payments and revenue to a date range or specific records.</p>
                        </div>
                        <button type="button" class="filter-dialog__close" data-filter-close aria-label="Close filter dialog">
                            <i class="fi fi-sr-cross-circle" aria-hidden="true"></i>
                        </button>
                    </header>
                    <div class="filter-dialog__body">
                        <div class="form-grid">
                            <div class="field-group">
                                <label for="start_date">From Date</label>
                                <input id="start_date" name="start_date" type="date" value="<?php echo e($filters['start_date'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label for="end_date">To Date</label>
                                <input id="end_date" name="end_date" type="date" value="<?php echo e($filters['end_date'] ?? ''); ?>">
                            </div>
                            <div class="field-group">
                                <label for="venue_id">Venue</label>
                                <select id="venue_id" name="venue_id">
                                    <option value="">All Venues</option>
                                    <?php foreach ($venues as $venue): ?>
                                        <option value="<?php echo e((string) $venue['venue_id']); ?>" <?php echo (string) ($filters['venue_id'] ?? 0) === (string) $venue['venue_id'] ? 'selected' : ''; ?>><?php echo e($venue['venue_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label for="venue_type">Venue Type</label>
                                <select id="venue_type" name="venue_type">
                                    <option value="">All Types</option>
                                    <?php foreach (['Community Hall', 'Community Centre', 'Stadium', 'Open Space', 'Other'] as $venueType): ?>
                                        <option value="<?php echo e($venueType); ?>" <?php echo ($filters['venue_type'] ?? '') === $venueType ? 'selected' : ''; ?>><?php echo e($venueType); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label for="booking_status">Booking Status</label>
                                <select id="booking_status" name="booking_status">
                                    <option value="">All</option>
                                    <?php foreach ([BOOKING_STATUS_PENDING, BOOKING_STATUS_APPROVED, BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED, BOOKING_STATUS_CONFIRMED, BOOKING_STATUS_COMPLETED] as $status): ?>
                                        <option value="<?php echo e($status); ?>" <?php echo ($filters['booking_status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label for="payment_status">Payment Status</label>
                                <select id="payment_status" name="payment_status">
                                    <option value="">All</option>
                                    <?php foreach ([PAYMENT_STATUS_PENDING_VERIFICATION, PAYMENT_STATUS_VERIFIED, PAYMENT_STATUS_REJECTED] as $status): ?>
                                        <option value="<?php echo e($status); ?>" <?php echo ($filters['payment_status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <footer class="filter-dialog__footer">
                        <a class="button ghost small-button" href="<?php echo e($clearFiltersUrl); ?>">
                            <i class="fi fi-sr-refresh" aria-hidden="true"></i>
                            <span>Clear all</span>
                        </a>
                        <div class="button-row">
                            <button type="button" class="button secondary" data-filter-close>Cancel</button>
                            <button type="submit" class="button primary">
                                <i class="fi fi-sr-filter" aria-hidden="true"></i>
                                <span>Apply filters</span>
                            </button>
                        </div>
                    </footer>
                </form>
            </dialog>

            <?php if ($canViewAllReports): ?>
            <section id="summary" class="tab-section" data-tab="summary" aria-label="Executive Summary">
                <div class="stats-grid report-stats">
                    <article class="stat-card">
                        <span>Total Bookings</span>
                        <strong><?php echo e((string) $summary['total_bookings']); ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>Verified Revenue</span>
                        <strong><?php echo e(money_format_usd((float) $summary['verified_revenue'])); ?></strong>
                    </article>
                    <article class="stat-card">
                        <span>Pending Verification</span>
                        <strong><?php echo e(money_format_usd((float) $summary['pending_verification_amount'])); ?></strong>
                    </article>
                </div>

                <div class="report-chart-grid">
                    <article class="card chart-card" aria-labelledby="bookings-by-status-heading">
                        <header class="section-heading">
                            <div>
                                <span class="panel-eyebrow">Portfolio Mix</span>
                                <h2 id="bookings-by-status-heading">Bookings By Status</h2>
                            </div>
                            <button type="button" class="button ghost small-button no-print" data-print>
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print Summary</span>
                            </button>
                        </header>
                        <?php if ($statusSegments === []): ?>
                            <div class="panel-empty panel-empty--compact"><p>No booking data is available for the selected filters.</p></div>
                        <?php else: ?>
                            <div class="donut-layout donut-layout--report">
                                <figure class="donut-figure">
                                    <?php echo render_status_donut($statusSegments, 'Booking status distribution'); ?>
                                </figure>
                                <ul class="donut-legend">
                                    <?php foreach ($summary['bookings_by_status'] as $status => $count): ?>
                                        <li>
                                            <span class="legend-dot legend-dot--<?php echo e(strtolower(str_replace(' ', '-', (string) $status))); ?>"></span>
                                            <span><?php echo e((string) $status); ?></span>
                                            <strong><?php echo e((string) $count); ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="card chart-card" aria-labelledby="status-summary-heading">
                        <header class="section-heading">
                            <div>
                                <span class="panel-eyebrow">Operational Readout</span>
                                <h2 id="status-summary-heading">Status Summary</h2>
                            </div>
                        </header>
                        <ul class="summary-status-list">
                            <?php foreach ($summary['bookings_by_status'] as $status => $count): ?>
                                <li><span><?php echo status_badge((string) $status); ?></span><strong><?php echo e((string) $count); ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </div>
            </section>
            <?php endif; ?>

            <?php if (in_array('bookings', $allowedReports, true)): ?>
            <section id="bookings" class="tab-section" data-tab="bookings" aria-labelledby="booking-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="booking-report-heading">Booking Report</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" data-print>
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print Report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=bookings' . build_report_query_string($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
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
                                <th class="money">Total Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookingReport['rows'] === []): ?>
                                <tr>
                                    <td colspan="8" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-calendar" aria-hidden="true"></i></span>
                                            <h3>No Booking Records Found</h3>
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
                                        <td data-label="Total Charge" class="money"><?php echo e(money_format_usd((float) ($row['total_charge'] ?? 0))); ?></td>
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

                    <?php echo render_pagination($bookingReport['pagination'] ?? null, 'report/index', $filters, 'page_bookings', '#bookings'); ?>
                </article>
            </section>
            <?php endif; ?>

            <?php if (in_array('usage', $allowedReports, true)): ?>
            <section id="usage" class="tab-section" data-tab="usage" aria-labelledby="usage-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="usage-report-heading">Venue Usage</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" data-print>
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print Report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=usage' . build_report_query_string($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
                        </div>
                    </header>
                    <p>Usage assumes 12 available hours per venue per day, using the selected dates, or the first to last booking date when no dates are selected. The donut shows each venue&rsquo;s share of total booked hours.</p>
                    <?php if ($usageDonutSegments !== []): ?>
                        <div class="chart-figure chart-figure--inset">
                            <div class="donut-layout donut-layout--report">
                                <figure class="donut-figure">
                                    <?php echo render_donut_chart($usageDonutSegments, 'Booked hours share by venue', 'Booked Hours', number_format($usageHoursTotal, 1) . ' h'); ?>
                                </figure>
                                <?php echo render_donut_legend($usageDonutSegments, static fn(array $segment): string => number_format((float) $segment['value'], 1) . ' h'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Venue</th>
                                <th>Type</th>
                                <th>Bookings</th>
                                <th>Total Hours</th>
                                <th>Available Hours</th>
                                <th>Usage %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usageReport['rows'] === []): ?>
                                <tr>
                                    <td colspan="6" class="empty-state-cell">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><i class="fi fi-sr-clock" aria-hidden="true"></i></span>
                                            <h3>No Venue Records Found</h3>
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

                    <?php echo render_pagination($usageReport['pagination'] ?? null, 'report/index', $filters, 'page_usage', '#usage'); ?>
                </article>
            </section>
            <?php endif; ?>

            <?php if (in_array('payments', $allowedReports, true)): ?>
            <section id="payments" class="tab-section" data-tab="payments" aria-labelledby="payment-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="payment-report-heading">Payment Report</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" data-print>
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print Report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=payments' . build_report_query_string($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
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
                                            <h3>No Payment Records Found</h3>
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

                    <?php echo render_pagination($paymentReport['pagination'] ?? null, 'report/index', $filters, 'page_payments', '#payments'); ?>
                </article>
            </section>
            <?php endif; ?>

            <?php if (in_array('revenue', $allowedReports, true)): ?>
            <section id="revenue" class="tab-section" data-tab="revenue" aria-labelledby="revenue-report-heading">
                <article class="card">
                    <header class="section-heading">
                        <h2 id="revenue-report-heading">Revenue Report</h2>
                        <div class="button-row no-print">
                            <button type="button" class="button ghost small-button" data-print>
                                <i class="fi fi-sr-print" aria-hidden="true"></i>
                                <span>Print Report</span>
                            </button>
                            <a class="button secondary small-button" href="<?php echo e(BASE_URL . '/?r=report/exportCsv&type=revenue' . build_report_query_string($filters)); ?>"><i class="fi fi-sr-download" aria-hidden="true"></i><span>Export CSV</span></a>
                        </div>
                    </header>
                    <p>Revenue includes Verified payments only; date filters use the verification date.</p>
                    <div class="report-chart-grid report-chart-grid--revenue">
                        <article class="chart-card chart-card--flush" aria-labelledby="revenue-month-chart-title">
                            <header class="section-heading">
                                <div>
                                    <span class="panel-eyebrow">Composition</span>
                                    <h3 id="revenue-month-chart-title">Revenue Share By Month</h3>
                                </div>
                            </header>
                            <?php if ($monthlyRevenueSegments === []): ?>
                                <div class="panel-empty panel-empty--compact"><p>No monthly revenue records match the selected filters.</p></div>
                            <?php else: ?>
                                <div class="donut-layout donut-layout--report">
                                    <figure class="donut-figure">
                                        <?php echo render_donut_chart($monthlyRevenueSegments, 'Verified revenue share by month', 'Verified Revenue', chart_currency_short($monthlyRevenueTotal)); ?>
                                    </figure>
                                    <?php echo render_donut_legend($monthlyRevenueSegments, static fn(array $segment): string => money_format_usd((float) $segment['value'])); ?>
                                </div>
                            <?php endif; ?>
                        </article>

                        <article class="chart-card chart-card--flush" aria-labelledby="revenue-venue-chart-title">
                            <header class="section-heading">
                                <div>
                                    <span class="panel-eyebrow">Facility Contribution</span>
                                    <h3 id="revenue-venue-chart-title">Revenue Share By Venue</h3>
                                </div>
                            </header>
                            <?php if ($revenueVenueSegments === []): ?>
                                <div class="panel-empty panel-empty--compact"><p>No verified revenue records match the selected filters.</p></div>
                            <?php else: ?>
                                <div class="donut-layout donut-layout--report">
                                    <figure class="donut-figure">
                                        <?php echo render_donut_chart($revenueVenueSegments, 'Verified revenue share by venue', 'Verified Revenue', chart_currency_short($revenueVenueTotal)); ?>
                                    </figure>
                                    <?php echo render_donut_legend($revenueVenueSegments, static fn(array $segment): string => money_format_usd((float) $segment['value'])); ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    </div>
                    <h3>Revenue Detail By Venue</h3>
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
                                            <h3>No Verified Revenue Records Found</h3>
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

                    <?php echo render_pagination($revenueReport['pagination'] ?? null, 'report/index', $filters, 'page_revenue', '#revenue'); ?>

                    <h3>Monthly Revenue Detail</h3>
                    <table class="data-table">
                        <thead><tr><th>Month</th><th>Payments</th><th class="money">Total Verified Revenue</th></tr></thead>
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
                                        <td data-label="Total Verified Revenue" class="money"><?php echo e(money_format_usd((float) $month['total_amount'])); ?></td>
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

    <script src="<?php echo e(BASE_URL . '/assets/js/report_filters.js'); ?>" defer></script>
    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
</body>
</html>
