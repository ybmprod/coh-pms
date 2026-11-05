<?php
declare(strict_types=1);

/**
 * Database-backed pagination regression suite.
 *
 * Verifies that the paged model queries return valid windows, that report
 * totals still cover the whole filtered set, and that CSV export stays
 * unpaginated. Reports SKIP when MySQL is unavailable so the CI gate can run
 * on a machine without a database.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/autoload.php';
require_once __DIR__ . '/../app/helpers/format_helper.php';
require_once __DIR__ . '/../app/helpers/pagination_helper.php';

$passCount = 0;
$failCount = 0;

$runCase = static function (string $name, callable $test) use (&$passCount, &$failCount): void {
    try {
        $result = $test();
        if ($result !== true) {
            throw new RuntimeException('Condition returned false.');
        }
        echo 'PASS: ', $name, PHP_EOL;
        $passCount++;
    } catch (Throwable $exception) {
        echo 'FAIL: ', $name, ' - ', $exception->getMessage(), PHP_EOL;
        $failCount++;
    }
};

try {
    $pdo = get_db_connection();
    $pdo->query('SELECT 1');
} catch (Throwable $exception) {
    echo 'SKIP Database-backed pagination tests (database unavailable: ', $exception->getMessage(), ').', PHP_EOL;
    exit(0);
}

$perPage = 5;
$bookingTotal = Booking::countAll();
$bookingPageOne = Booking::getAll($perPage, 0);
$bookingPageTwo = Booking::getAll($perPage, $perPage);

$runCase('Booking count is positive', static fn(): bool => $bookingTotal > 0);

$runCase(
    'Booking page returns at most one page and never an empty first page',
    static fn(): bool => count($bookingPageOne) > 0 && count($bookingPageOne) <= $perPage
);

$runCase(
    'Booking page two starts on a different record',
    static fn(): bool => count($bookingPageTwo) === 0
        || ($bookingPageOne[0]['booking_id'] ?? null) !== ($bookingPageTwo[0]['booking_id'] ?? null)
);

$customerId = (int) $pdo->query('SELECT customer_id FROM bookings ORDER BY booking_id ASC LIMIT 1')->fetchColumn();
$customerRead = get_db_connection('customer_read');
$customerRead->exec('SET @coh_pms_customer_id = ' . $customerId);
$customerTotal = Booking::countByCustomer($customerId);
$customerRows = Booking::getByCustomer($customerId, 3, 0);

$runCase('Customer booking count is positive for a seeded customer', static fn(): bool => $customerTotal > 0);
$runCase(
    'Customer booking page respects its own limit',
    static fn(): bool => count($customerRows) > 0 && count($customerRows) <= 3
);

$paymentTotal = Payment::countByStatus(null);
$verifiedTotal = Payment::countByStatus(PAYMENT_STATUS_VERIFIED);
$paymentRows = Payment::getByStatus(null, $perPage, 0);

$runCase('Verified payments do not outnumber all payments', static fn(): bool => $verifiedTotal <= $paymentTotal && $paymentTotal > 0);
$runCase('Payment page respects the limit', static fn(): bool => count($paymentRows) > 0 && count($paymentRows) <= $perPage);

$userTotal = User::countAll();
$userRows = User::getAll($perPage, 0);
$runCase('User page respects the limit', static fn(): bool => count($userRows) > 0 && count($userRows) <= $perPage && $userTotal >= count($userRows));

$venueTotal = Venue::countAll();
$venueRows = Venue::getAll($perPage, 0);
$runCase('Venue page respects the limit', static fn(): bool => count($venueRows) > 0 && count($venueRows) <= $perPage && $venueTotal >= count($venueRows));

$service = new ReportService();
$bookingTotals = Report::getBookingReportTotals([]);
$bookingFirst = $service->getBookingReport([], $perPage, 1);
$bookingSecond = $service->getBookingReport([], $perPage, 2);

$runCase('Booking report totals cover the whole filtered set', static fn(): bool => $bookingTotals['count'] === $bookingTotal);
$runCase(
    'Booking report page respects the limit and keeps stable totals',
    static fn(): bool => count($bookingFirst['rows']) <= $perPage
        && $bookingFirst['totals'] === $bookingSecond['totals']
);

$paymentTotals = Report::getPaymentReportTotals([]);
$paymentFirst = $service->getPaymentReport([], $perPage, 1);
$paymentSecond = $service->getPaymentReport([], $perPage, 2);

$runCase('Payment report totals cover all payments', static fn(): bool => $paymentTotals['count'] === $paymentTotal);
$runCase(
    'Payment report page respects the limit and keeps stable totals',
    static fn(): bool => count($paymentFirst['rows']) <= $perPage
        && $paymentFirst['totals'] === $paymentSecond['totals']
);

$usageFirst = $service->getUsageReport([], $perPage, 1);
$usageSecond = $service->getUsageReport([], $perPage, 2);
$runCase(
    'Usage report page respects the limit and keeps stable totals',
    static fn(): bool => count($usageFirst['rows']) <= $perPage
        && $usageFirst['totals'] === $usageSecond['totals']
);

$revenueFirst = $service->getRevenueReport([], $perPage, 1);
$revenueSecond = $service->getRevenueReport([], $perPage, 2);
$runCase(
    'Revenue report page respects the limit and keeps stable totals',
    static fn(): bool => count($revenueFirst['rows']) <= $perPage
        && $revenueFirst['totals'] === $revenueSecond['totals']
);

$csv = $service->getCsvData('bookings', []);
$runCase('CSV export ignores pagination and covers every booking', static fn(): bool => count($csv[1]) === $bookingTotal);

$runCase(
    'Pager renders nothing when the result fits on one page',
    static fn(): bool => render_pagination(pagination_meta(4, 10, 1), 'booking/index') === ''
);

echo PHP_EOL, $passCount, ' passed, ', $failCount, ' failed.', PHP_EOL;
exit($failCount === 0 ? 0 : 1);
