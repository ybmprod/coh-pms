<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - Dashboard Composition and Operational Overview
class DashboardService
{
    private const BOOKING_STATUSES = [
        BOOKING_STATUS_PENDING,
        BOOKING_STATUS_APPROVED,
        BOOKING_STATUS_REJECTED,
        BOOKING_STATUS_CANCELLED,
        BOOKING_STATUS_CONFIRMED,
        BOOKING_STATUS_COMPLETED,
    ];

    public function getStaffDashboard(string $role): array
    {
        $dashboardCounts = (new ReportService())->getDashboardSummary($role);
        $allMetrics = Report::getDashboardMetrics();
        $showBookings = in_array($role, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_COUNCIL_MANAGEMENT], true);
        $showRevenue = in_array($role, [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT], true);

        $statusCounts = $showBookings ? $this->normaliseStatusCounts(Report::getBookingStatusCounts()) : [];
        $monthlyRevenue = $showRevenue ? $this->fillMonthlyRevenue(Report::getMonthlyRevenueSeries(6)) : [];

        return [
            'dashboard_counts' => $dashboardCounts,
            'metrics' => $allMetrics,
            'show_bookings' => $showBookings,
            'show_revenue' => $showRevenue,
            'status_counts' => $statusCounts,
            'status_total' => array_sum($statusCounts),
            'today_schedule' => $showBookings ? Report::getTodaySchedule(6) : [],
            'upcoming_bookings' => $showBookings ? Report::getUpcomingBookings(5) : [],
            'recent_bookings' => $showBookings ? Report::getRecentBookings(5) : [],
            'top_venues' => $showBookings ? Report::getTopVenues(5) : [],
            'monthly_revenue' => $monthlyRevenue,
            'revenue_growth' => $showRevenue
                ? $this->revenueGrowth(
                    (float) $allMetrics['verified_revenue_this_month'],
                    (float) $allMetrics['last_month_revenue']
                )
                : null,
            'greeting' => $this->greeting(),
            'today_label' => date('l, j F Y'),
            'role' => $role,
        ];
    }

    public function getCustomerDashboard(int $customerId): array
    {
        $metrics = Report::getCustomerDashboardMetrics($customerId);
        $nextBooking = Report::getCustomerUpcomingBooking($customerId);
        $recentBookings = Report::getCustomerRecentBookings($customerId, 4);
        $allBookings = Booking::getByCustomer($customerId);
        $upcomingCount = $this->countUpcomingBookings($allBookings);

        return [
            'metrics' => $metrics,
            'upcoming_count' => $upcomingCount,
            'next_booking' => $nextBooking,
            'recent_bookings' => $recentBookings,
            'journey' => $this->buildJourney($nextBooking ?? ($recentBookings[0] ?? null)),
            'recommended_venues' => $this->recommendedVenues($allBookings, 3),
            'greeting' => $this->greeting(),
            'today_label' => date('l, j F Y'),
        ];
    }

    private function normaliseStatusCounts(array $rows): array
    {
        $counts = array_fill_keys(self::BOOKING_STATUSES, 0);
        foreach ($rows as $row) {
            $status = (string) ($row['booking_status'] ?? '');
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['booking_count'];
            }
        }

        return $counts;
    }

    private function fillMonthlyRevenue(array $rows): array
    {
        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[(string) $row['month_key']] = [
                'amount' => round((float) $row['total_amount'], 2),
                'count' => (int) $row['payment_count'],
            ];
        }

        $start = new DateTimeImmutable('first day of this month');
        $start = $start->modify('-5 months');
        $series = [];
        for ($index = 0; $index < 6; $index++) {
            $month = $start->modify('+' . $index . ' months');
            $key = $month->format('Y-m');
            $entry = $byMonth[$key] ?? ['amount' => 0.0, 'count' => 0];
            $series[] = [
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'value' => $entry['amount'],
                'count' => $entry['count'],
                'partial' => $month->format('Y-m') === date('Y-m'),
            ];
        }

        return $series;
    }

    private function revenueGrowth(float $current, float $previous): array
    {
        if ($previous <= 0.0) {
            return [
                'available' => $current > 0.0,
                'percent' => $current > 0.0 ? 100.0 : 0.0,
                'direction' => $current > 0.0 ? 'up' : 'flat',
                'label' => $current > 0.0 ? 'New this month' : 'No change',
            ];
        }

        $percent = (($current - $previous) / $previous) * 100;
        $rounded = round($percent, 1);
        $direction = $rounded > 0 ? 'up' : ($rounded < 0 ? 'down' : 'flat');

        return [
            'available' => true,
            'percent' => abs($rounded),
            'direction' => $direction,
            'label' => $direction === 'up'
                ? number_format(abs($rounded), 1) . '% higher than last month'
                : ($direction === 'down'
                    ? number_format(abs($rounded), 1) . '% lower than last month'
                    : 'Level with last month'),
        ];
    }

    private function countUpcomingBookings(array $bookings): int
    {
        $today = date('Y-m-d');
        $count = 0;
        foreach ($bookings as $booking) {
            $status = (string) ($booking['booking_status'] ?? '');
            if ((string) ($booking['booking_date'] ?? '') >= $today
                && in_array($status, [BOOKING_STATUS_PENDING, BOOKING_STATUS_APPROVED, BOOKING_STATUS_CONFIRMED], true)
            ) {
                $count++;
            }
        }

        return $count;
    }

    private function buildJourney(?array $booking): array
    {
        $steps = [
            ['key' => 'request', 'label' => 'Request Submitted', 'icon' => 'fi-sr-paper-plane'],
            ['key' => 'approval', 'label' => 'Council Review', 'icon' => 'fi-sr-shield-check'],
            ['key' => 'payment', 'label' => 'Payment Verified', 'icon' => 'fi-sr-credit-card'],
            ['key' => 'completion', 'label' => 'Event Completed', 'icon' => 'fi-sr-trophy'],
        ];

        if ($booking === null) {
            foreach ($steps as &$step) {
                $step['state'] = 'upcoming';
            }
            unset($step);
            return ['steps' => $steps, 'status' => 'idle', 'reference' => null];
        }

        $status = (string) ($booking['booking_status'] ?? '');
        $paymentStatus = (string) ($booking['payment_status'] ?? '');
        $currentIndex = 1;

        if ($status === BOOKING_STATUS_PENDING) {
            $currentIndex = 1;
        } elseif ($status === BOOKING_STATUS_APPROVED) {
            $currentIndex = 2;
        } elseif ($status === BOOKING_STATUS_CONFIRMED || $paymentStatus === PAYMENT_STATUS_VERIFIED) {
            $currentIndex = 3;
        } elseif ($status === BOOKING_STATUS_COMPLETED) {
            $currentIndex = 3;
        } elseif (in_array($status, [BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED], true)) {
            $currentIndex = 1;
        }

        foreach ($steps as $index => &$step) {
            if ($index < $currentIndex) {
                $step['state'] = 'complete';
            } elseif ($index === $currentIndex) {
                $step['state'] = 'current';
            } else {
                $step['state'] = 'upcoming';
            }
        }
        unset($step);

        if ($status === BOOKING_STATUS_COMPLETED) {
            $steps[3]['state'] = 'complete';
            $steps[3]['current'] = false;
        }
        if (in_array($status, [BOOKING_STATUS_REJECTED, BOOKING_STATUS_CANCELLED], true)) {
            $steps[1]['state'] = 'stopped';
            $steps[2]['state'] = 'upcoming';
            $steps[3]['state'] = 'upcoming';
        }

        return [
            'steps' => $steps,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'reference' => $booking['booking_reference'] ?? null,
        ];
    }

    private function recommendedVenues(array $bookings, int $limit): array
    {
        $bookedIds = array_map(static fn(array $booking): int => (int) $booking['venue_id'], $bookings);
        $popular = Report::getTopVenues(12);
        $recommended = [];

        foreach ($popular as $venue) {
            if (!in_array((int) $venue['venue_id'], $bookedIds, true)) {
                $recommended[] = $venue;
            }
            if (count($recommended) >= $limit) {
                break;
            }
        }

        if (count($recommended) < $limit) {
            foreach (Venue::getActive() as $venue) {
                if (count($recommended) >= $limit) {
                    break;
                }
                if (in_array((int) $venue['venue_id'], $bookedIds, true)) {
                    continue;
                }
                foreach ($recommended as $existing) {
                    if ((int) $existing['venue_id'] === (int) $venue['venue_id']) {
                        continue 2;
                    }
                }
                $recommended[] = [
                    'venue_id' => $venue['venue_id'],
                    'venue_name' => $venue['venue_name'],
                    'venue_type' => $venue['venue_type'],
                    'location' => $venue['location'],
                    'capacity' => $venue['capacity'],
                    'standard_price' => $venue['standard_price'],
                    'image_path' => $venue['image_path'],
                    'booking_count' => 0,
                    'booked_value' => 0.0,
                ];
            }
        }

        return $recommended;
    }

    private function greeting(): string
    {
        $hour = (int) date('G');
        if ($hour < 12) {
            return 'Good morning';
        }
        if ($hour < 17) {
            return 'Good afternoon';
        }
        return 'Good evening';
    }
}
