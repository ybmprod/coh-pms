<?php
declare(strict_types=1);

// CHAPTER 5.3.6 - Reporting Implementation
class ReportService
{
    private const AVAILABLE_HOURS_PER_DAY = 12;

    public function getDashboardSummary(string $role): array
    {
        $metrics = Report::getDashboardMetrics();
        $keysByRole = [
            ROLE_ADMINISTRATOR => array_keys($metrics),
            ROLE_BOOKING_OFFICER => ['pending_bookings', 'today_bookings'],
            ROLE_REVENUE_OFFICER => ['pending_payments', 'verified_revenue_this_month'],
            ROLE_COUNCIL_MANAGEMENT => array_keys($metrics),
        ];
        $visibleKeys = $keysByRole[$role] ?? [];

        return array_intersect_key($metrics, array_flip($visibleKeys));
    }

    public function getSummary(array $filters = []): array
    {
        $bookingsByStatus = array_fill_keys([
            BOOKING_STATUS_PENDING,
            BOOKING_STATUS_APPROVED,
            BOOKING_STATUS_REJECTED,
            BOOKING_STATUS_CANCELLED,
            BOOKING_STATUS_CONFIRMED,
            BOOKING_STATUS_COMPLETED,
        ], 0);

        foreach (Report::getBookingStatusCounts($filters) as $row) {
            $bookingsByStatus[$row['booking_status']] = (int) $row['booking_count'];
        }

        return [
            'total_bookings' => array_sum($bookingsByStatus),
            'bookings_by_status' => $bookingsByStatus,
            'verified_revenue' => round(Report::getVerifiedRevenueTotal($filters), 2),
            'pending_verification_amount' => round(Report::getPendingVerificationTotal($filters), 2),
        ];
    }

    public function getBookingReport(array $filters = []): array
    {
        $rows = Report::getBookingReport($filters);
        $totalCharge = array_sum(array_map(static fn(array $row): float => (float) $row['total_charge'], $rows));

        return [
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'total_charge' => round($totalCharge, 2),
            ],
        ];
    }

    public function getUsageReport(array $filters = []): array
    {
        $rows = Report::getUsageReport($filters);
        $bounds = Report::getUsageDateBounds($filters);
        $startDate = !empty($filters['start_date']) ? (string) $filters['start_date'] : ($bounds['start_date'] ?? null);
        $endDate = !empty($filters['end_date']) ? (string) $filters['end_date'] : ($bounds['end_date'] ?? null);
        $days = $this->getInclusiveDays($startDate, $endDate);
        $availableHoursPerVenue = $days * self::AVAILABLE_HOURS_PER_DAY;

        $bookingCount = 0;
        $totalHours = 0.0;
        $availableHours = 0.0;
        foreach ($rows as &$row) {
            $row['booking_count'] = (int) $row['booking_count'];
            $row['total_hours'] = round((float) $row['total_hours'], 2);
            $row['available_hours'] = $availableHoursPerVenue;
            $row['usage_percentage'] = $availableHoursPerVenue > 0
                ? round(($row['total_hours'] / $availableHoursPerVenue) * 100, 2)
                : 0.0;
            $bookingCount += $row['booking_count'];
            $totalHours += $row['total_hours'];
            $availableHours += $availableHoursPerVenue;
        }
        unset($row);

        return [
            'rows' => $rows,
            'totals' => [
                'booking_count' => $bookingCount,
                'total_hours' => round($totalHours, 2),
                'available_hours' => round($availableHours, 2),
                'usage_percentage' => $availableHours > 0
                    ? round(($totalHours / $availableHours) * 100, 2)
                    : 0.0,
            ],
            'range_days' => $days,
        ];
    }

    public function getPaymentReport(array $filters = []): array
    {
        $rows = Report::getPaymentReport($filters);
        $amount = array_sum(array_map(static fn(array $row): float => (float) $row['amount_paid'], $rows));

        return [
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'amount' => round($amount, 2),
            ],
        ];
    }

    public function getRevenueReport(array $filters = []): array
    {
        $rows = Report::getRevenueReport($filters);
        $monthlyRows = Report::getRevenueByMonth($filters);
        $totalAmount = array_sum(array_map(static fn(array $row): float => (float) $row['total_amount'], $rows));
        $maxAmount = max([0.0, ...array_map(static fn(array $row): float => (float) $row['total_amount'], $rows)]);

        foreach ($rows as &$row) {
            $amount = (float) $row['total_amount'];
            $row['total_amount'] = round($amount, 2);
            $row['payment_count'] = (int) $row['payment_count'];
            $row['bar_percentage'] = $maxAmount > 0 ? (int) round(($amount / $maxAmount) * 100) : 0;
        }
        unset($row);

        foreach ($monthlyRows as &$row) {
            $row['payment_count'] = (int) $row['payment_count'];
            $row['total_amount'] = round((float) $row['total_amount'], 2);
        }
        unset($row);

        return [
            'rows' => $rows,
            'by_month' => $monthlyRows,
            'totals' => [
                'payment_count' => array_sum(array_column($rows, 'payment_count')),
                'total_amount' => round($totalAmount, 2),
            ],
        ];
    }

    public function getCsvData(string $type, array $filters = []): array
    {
        switch ($type) {
            case 'usage':
                $report = $this->getUsageReport($filters);
                $rows = array_map(static fn(array $row): array => [
                    $row['venue_name'],
                    $row['booking_count'],
                    number_format($row['total_hours'], 2, '.', ''),
                    number_format($row['usage_percentage'], 2, '.', '') . '%',
                ], $report['rows']);
                $totals = [
                    'TOTAL',
                    $report['totals']['booking_count'],
                    number_format($report['totals']['total_hours'], 2, '.', ''),
                    number_format($report['totals']['usage_percentage'], 2, '.', '') . '%',
                ];
                return [['Venue', 'Booking Count', 'Total Hours', 'Usage %'], $rows, $totals];

            case 'payments':
                $report = $this->getPaymentReport($filters);
                $rows = array_map(static fn(array $row): array => [
                    $row['payment_id'],
                    $row['booking_reference'],
                    $row['customer_name'],
                    $row['venue_name'],
                    number_format((float) $row['amount_paid'], 2, '.', ''),
                    $row['payment_method'],
                    $row['transaction_reference'],
                    $row['payment_status'],
                ], $report['rows']);
                $totals = ['TOTAL', '', '', '', number_format($report['totals']['amount'], 2, '.', ''), '', '', ''];
                return [['Payment ID', 'Booking Reference', 'Customer', 'Venue', 'Amount', 'Method', 'Reference', 'Status'], $rows, $totals];

            case 'revenue':
                $report = $this->getRevenueReport($filters);
                $rows = array_map(static fn(array $row): array => [
                    $row['venue_name'],
                    $row['payment_count'],
                    number_format((float) $row['total_amount'], 2, '.', ''),
                ], $report['rows']);
                $totals = ['TOTAL', $report['totals']['payment_count'], number_format($report['totals']['total_amount'], 2, '.', '')];
                return [['Venue', 'Payment Count', 'Verified Revenue'], $rows, $totals];

            default:
                $report = $this->getBookingReport($filters);
                $rows = array_map(static fn(array $row): array => [
                    $row['booking_reference'],
                    $row['customer_name'] ?? '-',
                    $row['venue_name'] ?? '-',
                    $row['booking_date'],
                    $row['start_time'],
                    $row['end_time'],
                    $row['booking_status'],
                    number_format((float) $row['total_charge'], 2, '.', ''),
                ], $report['rows']);
                $totals = ['TOTAL', '', '', '', '', '', $report['totals']['count'] . ' bookings', number_format($report['totals']['total_charge'], 2, '.', '')];
                return [['Reference', 'Customer', 'Venue', 'Date', 'Start Time', 'End Time', 'Status', 'Total Charge'], $rows, $totals];
        }
    }

    private function getInclusiveDays(?string $startDate, ?string $endDate): int
    {
        if ($startDate === null || $endDate === null || $startDate === '' || $endDate === '') {
            return 0;
        }

        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        if ($end < $start) {
            return 0;
        }

        return (int) $start->diff($end)->days + 1;
    }
}