<?php
declare(strict_types=1);

// CHAPTER 5.3.6 - Reporting Implementation
class ReportController extends Controller
{
    public function index(): void
    {
        $allowedRoles = [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT];
        if ($this->denyIfNotRole($allowedRoles)) {
            return;
        }

        $filters = $this->validatedFilters($_GET);
        $user = current_user();
        $allowedReports = $this->reportsForRole((string) $user['role']);
        $reportService = new ReportService();
        $bookingReport = in_array('bookings', $allowedReports, true) ? $reportService->getBookingReport($filters) : ['rows' => [], 'totals' => []];
        $usageReport = in_array('usage', $allowedReports, true) ? $reportService->getUsageReport($filters) : ['rows' => [], 'totals' => [], 'range_days' => 0];
        $paymentReport = in_array('payments', $allowedReports, true) ? $reportService->getPaymentReport($filters) : ['rows' => [], 'totals' => []];
        $revenueReport = in_array('revenue', $allowedReports, true) ? $reportService->getRevenueReport($filters) : ['rows' => [], 'by_month' => [], 'totals' => []];
        $canViewAllReports = in_array($user['role'], [ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT], true);

        $this->render('staff/reports', [
            'pageTitle' => 'Reports',
            'user' => $user,
            'allowedReports' => $allowedReports,
            'canViewAllReports' => $canViewAllReports,
            'filters' => $filters,
            'venues' => Report::getVenueOptions(),
            'summary' => $canViewAllReports ? $reportService->getSummary($filters) : [],
            'bookingReport' => $bookingReport,
            'usageReport' => $usageReport,
            'paymentReport' => $paymentReport,
            'revenueReport' => $revenueReport,
        ]);
    }

    public function exportCsv(): void
    {
        $allowedRoles = [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT];
        if ($this->denyIfNotRole($allowedRoles)) {
            return;
        }

        $type = trim((string) ($_GET['type'] ?? 'bookings'));
        $allowedReports = $this->reportsForRole((string) current_user()['role']);
        if (!in_array($type, $allowedReports, true)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => 'Forbidden']);
            return;
        }

        $filters = $this->validatedFilters($_GET);
        [$header, $rows, $totals] = (new ReportService())->getCsvData($type, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_report.csv"');

        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $header);

        foreach ($rows as $row) {
            fputcsv($output, array_map([$this, 'protectCsvValue'], $row));
        }
        fputcsv($output, array_map([$this, 'protectCsvValue'], $totals));

        fclose($output);
        exit;
    }

    private function validatedFilters(array $input): array
    {
        $errors = [];
        $filters = [];
        foreach (['start_date', 'end_date', 'venue_id', 'venue_type', 'booking_status', 'payment_status'] as $key) {
            $value = $input[$key] ?? '';
            if (!is_scalar($value)) {
                $errors[] = 'Report filters must have a single value.';
                $value = '';
            }
            $filters[$key] = trim((string) $value);
        }

        foreach (['start_date', 'end_date'] as $dateKey) {
            if ($filters[$dateKey] !== '' && !is_valid_date($filters[$dateKey])) {
                $errors[] = 'Report date filters must use valid calendar dates.';
                $filters[$dateKey] = '';
            }
        }
        if ($filters['start_date'] !== '' && $filters['end_date'] !== '' && $filters['end_date'] < $filters['start_date']) {
            $errors[] = 'The end date must be on or after the start date.';
            $filters['start_date'] = '';
            $filters['end_date'] = '';
        }

        if ($filters['venue_id'] !== '') {
            if (!ctype_digit($filters['venue_id']) || (int) $filters['venue_id'] < 1 || !Report::venueExists((int) $filters['venue_id'])) {
                $errors[] = 'The selected venue filter is invalid.';
                $filters['venue_id'] = '';
            } else {
                $filters['venue_id'] = (int) $filters['venue_id'];
            }
        } else {
            $filters['venue_id'] = 0;
        }

        $venueTypes = ['Community Hall', 'Community Centre', 'Stadium', 'Open Space', 'Other'];
        if ($filters['venue_type'] !== '' && !in_array($filters['venue_type'], $venueTypes, true)) {
            $errors[] = 'The selected venue type is invalid.';
            $filters['venue_type'] = '';
        }
        $bookingStatuses = [
            BOOKING_STATUS_PENDING,
            BOOKING_STATUS_APPROVED,
            BOOKING_STATUS_REJECTED,
            BOOKING_STATUS_CANCELLED,
            BOOKING_STATUS_CONFIRMED,
            BOOKING_STATUS_COMPLETED,
        ];
        if ($filters['booking_status'] !== '' && !in_array($filters['booking_status'], $bookingStatuses, true)) {
            $errors[] = 'The selected booking status is invalid.';
            $filters['booking_status'] = '';
        }
        $paymentStatuses = [
            PAYMENT_STATUS_PENDING_VERIFICATION,
            PAYMENT_STATUS_VERIFIED,
            PAYMENT_STATUS_REJECTED,
        ];
        if ($filters['payment_status'] !== '' && !in_array($filters['payment_status'], $paymentStatuses, true)) {
            $errors[] = 'The selected payment status is invalid.';
            $filters['payment_status'] = '';
        }

        if ($errors !== []) {
            set_flash('error', implode(' ', array_unique($errors)));
        }

        return $filters;
    }

    private function protectCsvValue(mixed $value): string|int|float
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    private function reportsForRole(string $role): array
    {
        if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT], true)) {
            return ['bookings', 'usage', 'payments', 'revenue'];
        }
        if ($role === ROLE_BOOKING_OFFICER) {
            return ['bookings', 'usage'];
        }
        if ($role === ROLE_REVENUE_OFFICER) {
            return ['payments', 'revenue'];
        }

        return [];
    }
}
