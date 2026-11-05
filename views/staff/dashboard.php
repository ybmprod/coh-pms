<?php
declare(strict_types=1);

$isStaffPortal = ($portalScope ?? '') === 'staff' || (($portalScope ?? '') !== 'customer' && is_staff_portal_request());
$user = $user ?? ($isStaffPortal ? current_staff_user() : current_customer_user());
$flash = get_flash();
$dashboard = $dashboard ?? [];
$role = (string) ($user['role'] ?? '');
$fullName = (string) ($user['full_name'] ?? 'User');
$nameParts = preg_split('/\s+/', trim($fullName)) ?: [];
$firstName = $nameParts[0] ?? 'there';
$greeting = (string) ($dashboard['greeting'] ?? 'Welcome');
$todayLabel = (string) ($dashboard['today_label'] ?? date('l, j F Y'));
$memberSince = !empty($user['created_at']) ? date('F Y', strtotime((string) $user['created_at'])) : null;

$metrics = $dashboard['metrics'] ?? [];
$dashboardCounts = $dashboard['dashboard_counts'] ?? [];
$showBookings = (bool) ($dashboard['show_bookings'] ?? false);
$showRevenue = (bool) ($dashboard['show_revenue'] ?? false);
$statusCounts = $dashboard['status_counts'] ?? [];
$statusTotal = (int) ($dashboard['status_total'] ?? 0);
$topVenues = $dashboard['top_venues'] ?? [];
$monthlyRevenue = $dashboard['monthly_revenue'] ?? [];
$revenueGrowth = $dashboard['revenue_growth'] ?? null;
$todaySchedule = $dashboard['today_schedule'] ?? [];
$upcomingBookings = $dashboard['upcoming_bookings'] ?? [];
$recentBookings = $dashboard['recent_bookings'] ?? [];
$nextBooking = $dashboard['next_booking'] ?? null;
$journey = $dashboard['journey'] ?? ['steps' => [], 'status' => 'idle', 'reference' => null];
$recommendedVenues = $dashboard['recommended_venues'] ?? [];
$customerMetrics = $dashboard['metrics'] ?? [];
$upcomingCount = (int) ($dashboard['upcoming_count'] ?? 0);

$kpiCards = [];
if ($isStaffPortal) {
    if ($showBookings) {
        $kpiCards[] = [
            'label' => 'Pending Bookings',
            'value' => (float) ($dashboardCounts['pending_bookings'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-calendar-clock',
            'tone' => 'warning',
            'helper' => ((int) ($metrics['overdue_approvals'] ?? 0)) > 0
                ? (int) $metrics['overdue_approvals'] . ' awaiting review beyond 48 hours'
                : 'No request has breached the 48-hour target',
            'action' => 'booking/index',
            'action_label' => 'Review Queue',
        ];
        $kpiCards[] = [
            'label' => "Today's Bookings",
            'value' => (float) ($dashboardCounts['today_bookings'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-calendar-check',
            'tone' => 'info',
            'helper' => (int) ($metrics['upcoming_bookings'] ?? 0) . ' upcoming reservations in the pipeline',
            'action' => 'booking/index',
            'action_label' => 'Open Schedule',
        ];
    }
    if ($showRevenue) {
        $kpiCards[] = [
            'label' => 'Payments To Verify',
            'value' => (float) ($dashboardCounts['pending_payments'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-credit-card',
            'tone' => 'warning',
            'helper' => money_format_usd((float) ($metrics['pending_payment_amount'] ?? 0)) . ' awaiting revenue verification',
            'action' => 'payment/index',
            'action_label' => 'Verify Payments',
        ];
        $kpiCards[] = [
            'label' => 'Revenue This Month',
            'value' => (float) ($dashboardCounts['verified_revenue_this_month'] ?? 0),
            'format' => 'money',
            'icon' => 'fi-sr-coins',
            'tone' => 'success',
            'helper' => (string) ($revenueGrowth['label'] ?? 'Verified revenue for the current month'),
            'action' => 'report/index#revenue',
            'action_label' => 'Open Report',
        ];
    }
    if (!$showBookings) {
        $kpiCards[] = [
            'label' => 'Pending Verification Value',
            'value' => (float) ($metrics['pending_payment_amount'] ?? 0),
            'format' => 'money',
            'icon' => 'fi-sr-receipt',
            'tone' => 'warning',
            'helper' => 'Value of payments awaiting verification',
            'action' => 'payment/index',
            'action_label' => 'Verify Payments',
        ];
        $kpiCards[] = [
            'label' => 'Last Month Revenue',
            'value' => (float) ($metrics['last_month_revenue'] ?? 0),
            'format' => 'money',
            'icon' => 'fi-sr-chart-histogram',
            'tone' => 'info',
            'helper' => 'Verified revenue for the previous month',
            'action' => 'report/index#revenue',
            'action_label' => 'View Trend',
        ];
    }
    if (!$showRevenue) {
        $kpiCards[] = [
            'label' => 'Active Bookings',
            'value' => (float) ($metrics['active_bookings'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-briefcase',
            'tone' => 'info',
            'helper' => 'Requests approved, pending or confirmed',
            'action' => 'booking/index',
            'action_label' => 'Manage Bookings',
        ];
        $kpiCards[] = [
            'label' => 'Available Venues',
            'value' => (float) ($metrics['available_venues'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-building',
            'tone' => 'success',
            'helper' => 'of ' . (int) ($metrics['total_venues'] ?? 0) . ' municipal facilities in the register',
            'action' => 'venue/index',
            'action_label' => 'Manage Venues',
        ];
    }
} else {
    $kpiCards = [
        [
            'label' => 'Upcoming Bookings',
            'value' => (float) $upcomingCount,
            'format' => 'number',
            'icon' => 'fi-sr-calendar-clock',
            'tone' => 'info',
            'helper' => $nextBooking
                ? 'Next visit on ' . format_date((string) $nextBooking['booking_date'])
                : 'No upcoming reservations yet',
            'action' => 'booking/customerList#history',
            'action_label' => 'View Bookings',
        ],
        [
            'label' => 'Awaiting Approval',
            'value' => (float) ($customerMetrics['pending_bookings'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-clock',
            'tone' => 'warning',
            'helper' => 'Requests currently under council review',
            'action' => 'booking/customerList#history',
            'action_label' => 'Track Requests',
        ],
        [
            'label' => 'Completed Events',
            'value' => (float) ($customerMetrics['completed_bookings'] ?? 0),
            'format' => 'number',
            'icon' => 'fi-sr-trophy',
            'tone' => 'success',
            'helper' => 'Successful municipal venue bookings',
            'action' => 'booking/customerList#history',
            'action_label' => 'View History',
        ],
        [
            'label' => 'Verified Spend',
            'value' => (float) ($customerMetrics['verified_spend'] ?? 0),
            'format' => 'money',
            'icon' => 'fi-sr-coins',
            'tone' => 'success',
            'helper' => ((float) ($customerMetrics['pending_spend'] ?? 0)) > 0
                ? money_format_usd((float) $customerMetrics['pending_spend']) . ' still pending verification'
                : 'All submitted payments are verified',
            'action' => 'booking/customerList#history',
            'action_label' => 'Review Payments',
        ],
    ];
}

$snapshotItems = [];
if ($isStaffPortal) {
    $snapshotItems = [
        ['icon' => 'fi-sr-building', 'label' => 'Available Venues', 'value' => (int) ($metrics['available_venues'] ?? 0) . ' / ' . (int) ($metrics['total_venues'] ?? 0)],
        ['icon' => 'fi-sr-users-alt', 'label' => 'Registered Customers', 'value' => (string) (int) ($metrics['total_customers'] ?? 0)],
        ['icon' => 'fi-sr-calendar', 'label' => 'Total Bookings', 'value' => (string) (int) ($metrics['total_bookings'] ?? 0)],
        ['icon' => 'fi-sr-briefcase', 'label' => 'Active Bookings', 'value' => (string) (int) ($metrics['active_bookings'] ?? 0)],
    ];
}

$quickActions = [];
if ($isStaffPortal) {
    if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_COUNCIL_MANAGEMENT], true)) {
        $quickActions[] = ['label' => 'Review Bookings', 'icon' => 'fi-sr-calendar-check', 'url' => 'booking/index', 'description' => 'Approve, reject or reschedule'];
        $quickActions[] = ['label' => 'Browse Venues', 'icon' => 'fi-sr-building', 'url' => 'venue/index', 'description' => 'Check facilities and tariffs'];
    }
    if (in_array($role, [ROLE_BOOKING_OFFICER, ROLE_ADMINISTRATOR], true)) {
        $quickActions[] = ['label' => 'Event Types', 'icon' => 'fi-sr-calendar', 'url' => 'eventtype/index', 'description' => 'Review event categories'];
        $quickActions[] = ['label' => 'Usage Reports', 'icon' => 'fi-sr-chart-pie-alt', 'url' => 'report/index#usage', 'description' => 'Measure facility demand'];
    }
    if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT], true)) {
        $quickActions[] = ['label' => 'Verify Payments', 'icon' => 'fi-sr-credit-card', 'url' => 'payment/index', 'description' => 'Confirm receipts and revenue'];
        $quickActions[] = ['label' => 'Payment Reports', 'icon' => 'fi-sr-receipt', 'url' => 'report/index#payments', 'description' => 'Audit the payment ledger'];
        $quickActions[] = ['label' => 'Revenue Reports', 'icon' => 'fi-sr-coins', 'url' => 'report/index#revenue', 'description' => 'Export verified revenue'];
    }
    if ($role === ROLE_ADMINISTRATOR) {
        $quickActions[] = ['label' => 'Manage Users', 'icon' => 'fi-sr-users-alt', 'url' => 'user/index', 'description' => 'Staff and customer accounts'];
        $quickActions[] = ['label' => 'Pricing Rules', 'icon' => 'fi-sr-tags', 'url' => 'pricingrule/index', 'description' => 'Adjust municipal tariffs'];
    }
} else {
    $quickActions = [
        ['label' => 'Book A Venue', 'icon' => 'fi-sr-paper-plane', 'url' => 'booking/customerList#request', 'description' => 'Start a new request'],
        ['label' => 'Browse Venues', 'icon' => 'fi-sr-building', 'url' => 'venue/customerList', 'description' => 'Compare halls and stadia'],
        ['label' => 'My Bookings', 'icon' => 'fi-sr-calendar-clock', 'url' => 'booking/customerList#history', 'description' => 'Track approvals and payments'],
        ['label' => 'Payment Receipts', 'icon' => 'fi-sr-receipt', 'url' => 'booking/customerList#history', 'description' => 'Review submitted payments'],
    ];
}

$pipelineSegments = [];
foreach ($statusCounts as $status => $count) {
    if ((int) $count <= 0) {
        continue;
    }
    $tone = strtolower(str_replace(' ', '-', (string) $status));
    $pipelineSegments[] = ['label' => (string) $status, 'value' => (int) $count, 'tone' => $tone];
}

$monthlyDonutSegments = chart_donut_segments(array_map(static fn(array $point): array => [
    'label' => (string) $point['label'],
    'value' => (float) $point['value'],
], $monthlyRevenue), 6, 'Other months');
$monthlyDonutTotal = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $monthlyDonutSegments));

$venueDonutSegments = chart_donut_segments(array_map(static fn(array $venue): array => [
    'label' => (string) $venue['venue_name'],
    'value' => (float) $venue['booking_count'],
    'value_label' => (int) $venue['booking_count'] . ((int) $venue['booking_count'] === 1 ? ' booking' : ' bookings'),
], $topVenues), 5, 'Other venues');
$venueDonutTotal = array_sum(array_map(static fn(array $segment): float => (float) $segment['value'], $venueDonutSegments));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?php echo e($pageTitle ?? 'Dashboard'); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo e(BASE_URL . '/assets/css/style.css'); ?>">
</head>
<body>
    <?php if ($isStaffPortal): ?>
        <?php include APP_VIEW_PATH . '/layouts/staff_header.php'; ?>
    <?php else: ?>
        <?php include APP_VIEW_PATH . '/layouts/public_header.php'; ?>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo e($flash['type']); ?>" role="alert">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <main id="main-content" class="container dashboard-layout">
        <?php include APP_VIEW_PATH . '/layouts/sidebar.php'; ?>

        <section class="dashboard-main" aria-labelledby="dashboard-welcome-title">
            <header class="dash-hero dashboard-reveal">
                <div class="dash-hero__content">
                    <span class="dash-live-tag">
                        <span class="status-dot" aria-hidden="true"></span>
                        <?php echo $isStaffPortal ? 'Council Operations Centre' : 'Customer Venue Portal'; ?>
                    </span>
                    <h1 id="dashboard-welcome-title"><?php echo e($greeting . ', ' . $firstName); ?></h1>
                    <p class="dash-hero__lead">
                        <?php if ($isStaffPortal): ?>
                            A live view of municipal venue demand, approvals, revenue verification and the service standards that keep the City of Harare moving.
                        <?php else: ?>
                            Your gateway to City of Harare venues — plan an event, follow every approval, and keep your payments in one clear timeline.
                        <?php endif; ?>
                    </p>
                    <div class="dash-hero__meta">
                        <span><i class="fi fi-sr-calendar" aria-hidden="true"></i><?php echo e($todayLabel); ?></span>
                        <span><i class="fi fi-sr-marker" aria-hidden="true"></i>Harare, Zimbabwe</span>
                        <span class="pill"><i class="fi <?php echo $isStaffPortal ? 'fi-sr-shield-check' : 'fi-sr-user'; ?>" aria-hidden="true"></i><?php echo e($role ?: ($isStaffPortal ? 'Staff' : 'Customer')); ?></span>
                    </div>
                    <div class="button-row dash-hero__actions">
                        <?php if ($isStaffPortal): ?>
                            <?php if ($showBookings): ?>
                                <a class="button primary" href="<?php echo e(BASE_URL . '/?r=booking/index'); ?>">
                                    <i class="fi fi-sr-calendar-check" aria-hidden="true"></i>
                                    <span>Review Booking Queue</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($showRevenue): ?>
                                <a class="button secondary" href="<?php echo e(BASE_URL . '/?r=payment/index'); ?>">
                                    <i class="fi fi-sr-credit-card" aria-hidden="true"></i>
                                    <span>Verify Payments</span>
                                </a>
                            <?php endif; ?>
                            <a class="button ghost" href="<?php echo e(BASE_URL . '/?r=report/index'); ?>">
                                <i class="fi fi-sr-chart-histogram" aria-hidden="true"></i>
                                <span>Open Reports</span>
                            </a>
                        <?php else: ?>
                            <a class="button primary" href="<?php echo e(BASE_URL . '/?r=booking/customerList#request'); ?>">
                                <i class="fi fi-sr-paper-plane" aria-hidden="true"></i>
                                <span>Book A Venue</span>
                            </a>
                            <a class="button secondary" href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>">
                                <i class="fi fi-sr-building" aria-hidden="true"></i>
                                <span>Browse Venues</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="dash-hero__aside" aria-label="Live dashboard status">
                    <div class="dash-clock-block">
                        <span class="dash-clock-label"><span class="status-dot" aria-hidden="true"></span>Live Council Time</span>
                        <strong class="dash-clock" data-live-clock="Africa/Harare">--:--:--</strong>
                        <span class="dash-clock-zone">Africa/Harare &middot; CAT</span>
                    </div>
                    <?php if ($isStaffPortal): ?>
                        <div class="dash-hero__status">
                            <span><i class="fi fi-sr-shield-check" aria-hidden="true"></i> Portal Operational</span>
                            <span><i class="fi fi-sr-users-alt" aria-hidden="true"></i> <?php echo e((string) (int) ($metrics['total_customers'] ?? 0)); ?> Registered Customers</span>
                        </div>
                    <?php else: ?>
                        <div class="dash-hero__status">
                            <span><i class="fi fi-sr-trophy" aria-hidden="true"></i> <?php echo e((string) ($customerMetrics['completed_bookings'] ?? 0)); ?> Completed Events</span>
                            <span><i class="fi fi-sr-user" aria-hidden="true"></i> Member Since <?php echo e($memberSince ?? date('F Y')); ?></span>
                        </div>
                    <?php endif; ?>
                </aside>
            </header>

            <div class="dash-kpi-grid">
                <?php foreach ($kpiCards as $card): ?>
                    <article class="kpi-card kpi-card--<?php echo e((string) $card['tone']); ?> dashboard-reveal">
                        <div class="kpi-card__header">
                            <span class="kpi-card__icon"><i class="fi <?php echo e((string) $card['icon']); ?>" aria-hidden="true"></i></span>
                            <span class="kpi-card__eyebrow"><?php echo $isStaffPortal ? 'Operational Metric' : 'Your Activity'; ?></span>
                        </div>
                        <h2 class="kpi-card__label"><?php echo e((string) $card['label']); ?></h2>
                        <p class="kpi-card__value">
                            <?php if ($card['format'] === 'money'): ?>
                                <span class="js-count" data-count="<?php echo e((string) $card['value']); ?>" data-prefix="US$ " data-decimals="2"><?php echo e(money_format_usd((float) $card['value'])); ?></span>
                            <?php else: ?>
                                <span class="js-count" data-count="<?php echo e((string) $card['value']); ?>" data-decimals="0"><?php echo e((string) (int) $card['value']); ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="kpi-card__helper"><?php echo e((string) $card['helper']); ?></p>
                        <div class="kpi-card__footer">
                            <a class="button ghost small-button" href="<?php echo e(BASE_URL . '/?r=' . $card['action']); ?>">
                                <span><?php echo e((string) $card['action_label']); ?></span>
                                <i class="fi fi-sr-angle-left icon-flip" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($isStaffPortal && $snapshotItems !== []): ?>
                <section class="snapshot-strip dashboard-reveal" aria-label="Council Snapshot">
                    <?php foreach ($snapshotItems as $item): ?>
                        <div class="snapshot-item">
                            <span class="snapshot-icon"><i class="fi <?php echo e((string) $item['icon']); ?>" aria-hidden="true"></i></span>
                            <span class="snapshot-copy">
                                <span><?php echo e((string) $item['label']); ?></span>
                                <strong><?php echo e((string) $item['value']); ?></strong>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if ($isStaffPortal): ?>
                <div class="dash-grid">
                    <div class="dash-column">
                        <section class="panel-card dashboard-reveal" aria-labelledby="today-ops-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Live Schedule</span>
                                    <h2 id="today-ops-title">Today's Operations</h2>
                                </div>
                                <?php if ($showBookings): ?>
                                    <a class="text-link" href="<?php echo e(BASE_URL . '/?r=booking/index'); ?>">Full Schedule <i class="fi fi-sr-angle-left icon-flip" aria-hidden="true"></i></a>
                                <?php endif; ?>
                            </header>
                            <?php if ($todaySchedule === []): ?>
                                <div class="panel-empty">
                                    <span class="panel-empty__icon"><i class="fi fi-sr-calendar-check" aria-hidden="true"></i></span>
                                    <h3>No Venue Activity Today</h3>
                                    <p>The municipal diary is clear. Upcoming reservations will appear here automatically.</p>
                                </div>
                            <?php else: ?>
                                <ol class="ops-list">
                                    <?php foreach ($todaySchedule as $item): ?>
                                        <li class="ops-item">
                                            <span class="ops-time"><?php echo e(format_time_meridiem((string) $item['start_time'])); ?><small><?php echo e(format_time_meridiem((string) $item['end_time'])); ?></small></span>
                                            <span class="ops-body">
                                                <strong><?php echo e((string) $item['venue_name']); ?></strong>
                                                <span><?php echo e((string) $item['customer_name']); ?> &middot; <?php echo e((string) ($item['expected_attendance'] ?? 0)); ?> guests</span>
                                            </span>
                                            <span class="ops-status"><?php echo status_badge((string) $item['booking_status']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                        </section>

                        <?php if ($showRevenue && $monthlyDonutSegments !== []): ?>
                            <section class="panel-card dashboard-reveal" aria-labelledby="revenue-pulse-title">
                                <header class="panel-heading">
                                    <div>
                                        <span class="panel-eyebrow">Six-Month View</span>
                                        <h2 id="revenue-pulse-title">Revenue Pulse</h2>
                                    </div>
                                    <span class="trend-badge trend-badge--<?php echo e((string) ($revenueGrowth['direction'] ?? 'flat')); ?>">
                                        <i class="fi <?php echo ($revenueGrowth['direction'] ?? 'flat') === 'down' ? 'fi-sr-angle-left' : 'fi-sr-check'; ?>" aria-hidden="true"></i>
                                        <?php echo e((string) ($revenueGrowth['label'] ?? 'Verified revenue')); ?>
                                    </span>
                                </header>
                                <p class="panel-subtitle">Verified payment value recorded by the Revenue Office over the last six months.</p>
                                <div class="donut-layout">
                                    <figure class="donut-figure">
                                        <?php echo render_donut_chart($monthlyDonutSegments, 'Verified revenue share over the last six months', 'Verified Revenue', chart_currency_short($monthlyDonutTotal)); ?>
                                    </figure>
                                    <?php echo render_donut_legend($monthlyDonutSegments, static fn(array $segment): string => money_format_usd((float) $segment['value'])); ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ($showBookings && $venueDonutSegments !== []): ?>
                            <section class="panel-card dashboard-reveal" aria-labelledby="venue-performance-title">
                                <header class="panel-heading">
                                    <div>
                                        <span class="panel-eyebrow">Facility Demand</span>
                                        <h2 id="venue-performance-title">Venue Performance</h2>
                                    </div>
                                    <a class="text-link" href="<?php echo e(BASE_URL . '/?r=report/index#usage'); ?>">Usage Report <i class="fi fi-sr-angle-left icon-flip" aria-hidden="true"></i></a>
                                </header>
                                <p class="panel-subtitle">The five busiest municipal venues by approved, confirmed and completed bookings.</p>
                                <div class="donut-layout">
                                    <figure class="donut-figure">
                                        <?php echo render_donut_chart($venueDonutSegments, 'Booking share by venue', 'Bookings', number_format($venueDonutTotal, 0)); ?>
                                    </figure>
                                    <?php echo render_donut_legend($venueDonutSegments, static fn(array $segment): string => (string) ($segment['value_label'] ?? number_format((float) $segment['value'], 0) . ' bookings')); ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>

                    <div class="dash-column">
                        <?php if ($showBookings): ?>
                            <section class="panel-card dashboard-reveal" aria-labelledby="pipeline-title">
                                <header class="panel-heading">
                                    <div>
                                        <span class="panel-eyebrow">Workflow Health</span>
                                        <h2 id="pipeline-title">Booking Pipeline</h2>
                                    </div>
                                    <span class="panel-count"><?php echo e((string) $statusTotal); ?> total</span>
                                </header>
                                <?php if ($pipelineSegments === []): ?>
                                    <div class="panel-empty panel-empty--compact">
                                        <p>No booking status data is available yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="donut-layout">
                                        <figure class="donut-figure">
                                            <?php echo render_status_donut($pipelineSegments, 'Booking status distribution'); ?>
                                        </figure>
                                        <ul class="donut-legend">
                                            <?php foreach ($statusCounts as $status => $count): ?>
                                                <li>
                                                    <span class="legend-dot legend-dot--<?php echo e(strtolower(str_replace(' ', '-', (string) $status))); ?>"></span>
                                                    <span><?php echo e((string) $status); ?></span>
                                                    <strong><?php echo e((string) (int) $count); ?></strong>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php elseif ($showRevenue): ?>
                            <section class="panel-card dashboard-reveal" aria-labelledby="revenue-snapshot-title">
                                <header class="panel-heading">
                                    <div>
                                        <span class="panel-eyebrow">Revenue Desk</span>
                                        <h2 id="revenue-snapshot-title">Revenue Snapshot</h2>
                                    </div>
                                </header>
                                <dl class="metric-list">
                                    <div><dt>Pending Verification</dt><dd><?php echo e(money_format_usd((float) ($metrics['pending_payment_amount'] ?? 0))); ?></dd></div>
                                    <div><dt>Verified This Month</dt><dd><?php echo e(money_format_usd((float) ($metrics['verified_revenue_this_month'] ?? 0))); ?></dd></div>
                                    <div><dt>Last Month</dt><dd><?php echo e(money_format_usd((float) ($metrics['last_month_revenue'] ?? 0))); ?></dd></div>
                                    <div><dt>Payments In Queue</dt><dd><?php echo e((string) (int) ($dashboardCounts['pending_payments'] ?? 0)); ?></dd></div>
                                </dl>
                            </section>
                        <?php endif; ?>

                        <section class="panel-card dashboard-reveal" aria-labelledby="quick-actions-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Shortcuts</span>
                                    <h2 id="quick-actions-title">Quick Actions</h2>
                                </div>
                            </header>
                            <div class="quick-action-grid">
                                <?php foreach ($quickActions as $action): ?>
                                    <a class="quick-action" href="<?php echo e(BASE_URL . '/?r=' . $action['url']); ?>">
                                        <span class="quick-action__icon"><i class="fi <?php echo e((string) $action['icon']); ?>" aria-hidden="true"></i></span>
                                        <span class="quick-action__copy">
                                            <strong><?php echo e((string) $action['label']); ?></strong>
                                            <span><?php echo e((string) $action['description']); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <?php if ($showBookings && $upcomingBookings !== []): ?>
                            <section class="panel-card dashboard-reveal" aria-labelledby="upcoming-title">
                                <header class="panel-heading">
                                    <div>
                                        <span class="panel-eyebrow">Forward Diary</span>
                                        <h2 id="upcoming-title">Upcoming Reservations</h2>
                                    </div>
                                </header>
                                <ul class="compact-list">
                                    <?php foreach ($upcomingBookings as $item): ?>
                                        <li>
                                            <span class="compact-date">
                                                <strong><?php echo e(date('d', strtotime((string) $item['booking_date']))); ?></strong>
                                                <span><?php echo e(date('M', strtotime((string) $item['booking_date']))); ?></span>
                                            </span>
                                            <span class="compact-copy">
                                                <strong><?php echo e((string) $item['venue_name']); ?></strong>
                                                <span><?php echo e((string) $item['customer_name']); ?> &middot; <?php echo e(format_time_meridiem((string) $item['start_time'])); ?></span>
                                            </span>
                                            <?php echo status_badge((string) $item['booking_status']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>

                        <section class="panel-card panel-card--charter dashboard-reveal" aria-labelledby="charter-title">
                            <span class="panel-eyebrow">City Of Harare</span>
                            <h2 id="charter-title">Service Charter</h2>
                            <p>Every booking request is reviewed within two working days. Verified revenue is receipted the same day it clears the Revenue Office.</p>
                            <div class="charter-meta">
                                <span><i class="fi fi-sr-clock" aria-hidden="true"></i>Mon &ndash; Fri &middot; 08:00 &ndash; 16:30</span>
                                <span><i class="fi fi-sr-phone-call" aria-hidden="true"></i>+263 242 753 000</span>
                            </div>
                        </section>
                    </div>
                </div>
            <?php else: ?>
                <div class="dash-grid">
                    <div class="dash-column">
                        <section class="panel-card next-booking-card dashboard-reveal" aria-labelledby="next-booking-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Coming Up</span>
                                    <h2 id="next-booking-title">Your Next Booking</h2>
                                </div>
                                <?php if ($nextBooking): ?>
                                    <?php echo status_badge((string) $nextBooking['booking_status']); ?>
                                <?php endif; ?>
                            </header>
                            <?php if ($nextBooking): ?>
                                <?php
                                $daysUntil = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable((string) $nextBooking['booking_date']))->format('%r%a');
                                ?>
                                <div class="next-booking-body">
                                    <div class="next-booking-main">
                                        <span class="next-booking-countdown"><?php echo $daysUntil <= 0 ? 'Today' : ($daysUntil === 1 ? 'Tomorrow' : $daysUntil . ' days away'); ?></span>
                                        <h3><?php echo e((string) $nextBooking['venue_name']); ?></h3>
                                        <p><i class="fi fi-sr-marker" aria-hidden="true"></i><?php echo e((string) $nextBooking['location']); ?></p>
                                        <div class="next-booking-meta">
                                            <span><i class="fi fi-sr-calendar" aria-hidden="true"></i><?php echo e(format_date((string) $nextBooking['booking_date'])); ?></span>
                                            <span><i class="fi fi-sr-clock" aria-hidden="true"></i><?php echo e(format_time_meridiem((string) $nextBooking['start_time'])); ?> &ndash; <?php echo e(format_time_meridiem((string) $nextBooking['end_time'])); ?></span>
                                            <span><i class="fi fi-sr-coins" aria-hidden="true"></i><?php echo e(money_format_usd((float) $nextBooking['total_charge'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="next-booking-side">
                                        <span class="next-booking-ref"><?php echo e((string) $nextBooking['booking_reference']); ?></span>
                                        <a class="button primary" href="<?php echo e(BASE_URL . '/?r=booking/details&id=' . (int) $nextBooking['booking_id']); ?>">View Booking</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="panel-empty">
                                    <span class="panel-empty__icon"><i class="fi fi-sr-calendar-clock" aria-hidden="true"></i></span>
                                    <h3>Your Calendar Is Open</h3>
                                    <p>Browse available council venues and submit your first request in minutes.</p>
                                    <a class="button primary" href="<?php echo e(BASE_URL . '/?r=booking/customerList#request'); ?>">Start A Booking</a>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="panel-card dashboard-reveal" aria-labelledby="journey-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Transparent Progress</span>
                                    <h2 id="journey-title">Your Booking Journey</h2>
                                </div>
                                <?php if (!empty($journey['reference'])): ?>
                                    <span class="panel-count"><?php echo e((string) $journey['reference']); ?></span>
                                <?php endif; ?>
                            </header>
                            <div class="journey-steps">
                                <?php foreach (($journey['steps'] ?? []) as $step): ?>
                                    <div class="journey-step journey-step--<?php echo e((string) ($step['state'] ?? 'upcoming')); ?>">
                                        <span class="journey-step__icon"><i class="fi <?php echo e((string) $step['icon']); ?>" aria-hidden="true"></i></span>
                                        <span class="journey-step__label"><?php echo e((string) $step['label']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (($journey['status'] ?? 'idle') === 'idle'): ?>
                                <p class="panel-subtitle">Your journey begins when you submit your first venue request.</p>
                            <?php elseif (in_array((string) $journey['status'], [BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED], true)): ?>
                                <p class="panel-subtitle panel-subtitle--warning">This request was stopped at the council review stage. You can submit a fresh request at any time.</p>
                            <?php else: ?>
                                <p class="panel-subtitle">Follow each milestone here — from council review through payment verification to the event itself.</p>
                            <?php endif; ?>
                        </section>

                        <section class="panel-card dashboard-reveal" aria-labelledby="recent-bookings-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Activity</span>
                                    <h2 id="recent-bookings-title">Recent Bookings</h2>
                                </div>
                                <a class="text-link" href="<?php echo e(BASE_URL . '/?r=booking/customerList#history'); ?>">View All <i class="fi fi-sr-angle-left icon-flip" aria-hidden="true"></i></a>
                            </header>
                            <?php if ($recentBookings === []): ?>
                                <div class="panel-empty panel-empty--compact">
                                    <p>No bookings yet. Your confirmed requests will appear here.</p>
                                </div>
                            <?php else: ?>
                                <ul class="compact-list">
                                    <?php foreach ($recentBookings as $item): ?>
                                        <li>
                                            <span class="compact-date">
                                                <strong><?php echo e(date('d', strtotime((string) $item['booking_date']))); ?></strong>
                                                <span><?php echo e(date('M', strtotime((string) $item['booking_date']))); ?></span>
                                            </span>
                                            <span class="compact-copy">
                                                <strong><?php echo e((string) $item['venue_name']); ?></strong>
                                                <span><?php echo e(format_time_meridiem((string) $item['start_time'])); ?> &middot; <?php echo e(money_format_usd((float) $item['total_charge'])); ?></span>
                                            </span>
                                            <?php echo status_badge((string) $item['booking_status']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </section>
                    </div>

                    <div class="dash-column">
                        <section class="panel-card dashboard-reveal" aria-labelledby="customer-actions-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Shortcuts</span>
                                    <h2 id="customer-actions-title">Quick Actions</h2>
                                </div>
                            </header>
                            <div class="quick-action-grid">
                                <?php foreach ($quickActions as $action): ?>
                                    <a class="quick-action" href="<?php echo e(BASE_URL . '/?r=' . $action['url']); ?>">
                                        <span class="quick-action__icon"><i class="fi <?php echo e((string) $action['icon']); ?>" aria-hidden="true"></i></span>
                                        <span class="quick-action__copy">
                                            <strong><?php echo e((string) $action['label']); ?></strong>
                                            <span><?php echo e((string) $action['description']); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel-card dashboard-reveal" aria-labelledby="recommended-title">
                            <header class="panel-heading">
                                <div>
                                    <span class="panel-eyebrow">Popular With Residents</span>
                                    <h2 id="recommended-title">Recommended Venues</h2>
                                </div>
                                <a class="text-link" href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>">Browse All <i class="fi fi-sr-angle-left icon-flip" aria-hidden="true"></i></a>
                            </header>
                            <?php if ($recommendedVenues === []): ?>
                                <div class="panel-empty panel-empty--compact"><p>No venue recommendations are available right now.</p></div>
                            <?php else: ?>
                                <div class="mini-venue-grid">
                                    <?php foreach ($recommendedVenues as $venue): ?>
                                        <?php
                                        $rawImagePath = (string) ($venue['image_path'] ?? '');
                                        $filename = basename(str_replace('\\', '/', $rawImagePath));
                                        $imageExists = $rawImagePath !== '' && is_file(APP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                                        $imageUrl = $imageExists ? venue_image_url($rawImagePath) : '';
                                        ?>
                                        <a class="mini-venue-card" href="<?php echo e(BASE_URL . '/?r=venue/details&id=' . (int) $venue['venue_id']); ?>">
                                            <span class="mini-venue-media">
                                                <?php if ($imageExists): ?>
                                                    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e((string) $venue['venue_name']); ?>" loading="lazy">
                                                <?php else: ?>
                                                    <span class="mini-venue-placeholder"><i class="fi fi-sr-building" aria-hidden="true"></i></span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="mini-venue-body">
                                                <span class="mini-venue-type"><?php echo e((string) $venue['venue_type']); ?></span>
                                                <strong><?php echo e((string) $venue['venue_name']); ?></strong>
                                                <span><?php echo e((string) $venue['location']); ?></span>
                                                <span class="mini-venue-meta">
                                                    <span><i class="fi fi-sr-users-alt" aria-hidden="true"></i><?php echo e((string) (int) $venue['capacity']); ?></span>
                                                    <span><?php echo e(money_format_usd((float) $venue['standard_price'])); ?></span>
                                                </span>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="panel-card panel-card--charter dashboard-reveal" aria-labelledby="support-title">
                            <span class="panel-eyebrow">We Are Here To Help</span>
                            <h2 id="support-title">Council Support</h2>
                            <p>Speak to the Housing and Community Services team about venue availability, payments or special arrangements.</p>
                            <div class="charter-meta">
                                <span><i class="fi fi-sr-phone-call" aria-hidden="true"></i>+263 242 753 000</span>
                                <span><i class="fi fi-sr-envelope" aria-hidden="true"></i>venues@coh.co.zw</span>
                                <span><i class="fi fi-sr-clock" aria-hidden="true"></i>Mon &ndash; Fri &middot; 08:00 &ndash; 16:30</span>
                            </div>
                        </section>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include APP_VIEW_PATH . '/layouts/footer.php'; ?>
    <script src="<?php echo e(BASE_URL . '/assets/js/dashboard.js'); ?>" defer></script>
</body>
</html>

