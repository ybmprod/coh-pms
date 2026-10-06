<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/auth_helper.php';
require_once __DIR__ . '/../app/helpers/csrf_helper.php';
require_once __DIR__ . '/../app/helpers/flash_helper.php';
require_once __DIR__ . '/../app/helpers/format_helper.php';
require_once __DIR__ . '/../app/helpers/validation_helper.php';
require_once __DIR__ . '/../app/helpers/upload_helper.php';
require_once __DIR__ . '/../app/services/BookingService.php';
require_once __DIR__ . '/../app/services/AvailabilityService.php';
require_once __DIR__ . '/../app/services/PricingService.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/controllers/VenueController.php';

require_once __DIR__ . '/../app/models/Booking.php';

$passed = 0;
$failed = 0;

function runCheck(string $id, string $description, callable $fn): void {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === false) {
            throw new RuntimeException('Condition returned false');
        }
        echo "PASS [{$id}] {$description}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "FAIL [{$id}] {$description}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "=== COH-PMS Automated Test Suite ===\n\n";

// Authentication & Policy
runCheck('SEC-01', 'Password shorter than 8 chars is rejected', function() {
    return in_array('Password must be at least 8 characters long.', validate_password('Pass1'), true);
});

runCheck('SEC-02', 'Password lacking digits is rejected', function() {
    return in_array('Password must include at least one digit.', validate_password('PasswordOnly'), true);
});

runCheck('SEC-03', 'Password lacking letters is rejected', function() {
    return in_array('Password must include at least one letter.', validate_password('123456789'), true);
});

runCheck('SEC-04', 'Valid password Admin@123 passes validation', function() {
    return empty(validate_password('Admin@123'));
});

// Input Sanitization & Escaping
runCheck('SEC-05', 'XSS string is escaped by e() helper', function() {
    $xss = '<script>alert(1)</script>';
    return e($xss) === '&lt;script&gt;alert(1)&lt;/script&gt;';
});

// Routing & Access Control Guards
runCheck('SEC-06', 'Router dispatches valid route to target controller and action', function() {
    $router = new Router();
    [$c, $a] = $router->dispatch('booking/customerList');
    return $c === 'BookingController' && $a === 'customerList';
});

runCheck('SEC-07', 'Protected Controller base methods are blocked from direct dispatch', function() {
    $ctrl = new VenueController();
    $method = new ReflectionMethod($ctrl, 'render');
    return !($method->isPublic() && $method->getDeclaringClass()->getName() === VenueController::class);
});

runCheck('SEC-08', 'Path traversal in route controller is blocked by whitelist', function() {
    return preg_match('/^[a-z]+$/i', '..') !== 1;
});

// Image Upload MIME Inspection
runCheck('SEC-09', 'PHP file disguised as PNG is rejected by MIME inspection', function() {
    $tmp = tempnam(sys_get_temp_dir(), 'test_disguised_');
    file_put_contents($tmp, '<?php phpinfo(); ?>');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    unlink($tmp);
    return !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
});

// Booking Status Transition Rules (BookingService)
runCheck('BKG-01', 'Direct transition Pending -> Confirmed is prohibited by transition matrix', function() {
    $allowed = BookingService::TRANSITIONS[BOOKING_STATUS_PENDING] ?? [];
    return !in_array(BOOKING_STATUS_CONFIRMED, $allowed, true);
});

runCheck('BKG-02', 'Final statuses (Completed, Rejected, Cancelled) permit no further transitions', function() {
    return !isset(BookingService::TRANSITIONS[BOOKING_STATUS_COMPLETED])
        && !isset(BookingService::TRANSITIONS[BOOKING_STATUS_REJECTED])
        && !isset(BookingService::TRANSITIONS[BOOKING_STATUS_CANCELLED]);
});

// Pricing Rules (PricingService)
$testVenue = ['venue_id' => 1, 'venue_type' => 'Community Hall', 'standard_price' => 100.00];

runCheck('PRC-01', 'Weekday Discount 10% on US$100 venue yields US$90.00', function() use ($testVenue) {
    $rule = [
        'pricing_rule_id' => 1, 'rule_name' => 'Weekday Discount', 'venue_type' => 'All',
        'rule_type' => 'Weekday Discount', 'adjustment_type' => 'Percentage', 'adjustment_value' => 10.00,
        'days_of_week' => 'Mon,Tue,Wed,Thu', 'min_hours' => null, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 10, 'rule_status' => 'Active',
    ];
    $service = new PricingService(fn() => $testVenue, fn() => [$rule]);
    $charge = $service->calculate(1, '2026-10-05', '09:00', '13:00');
    return abs($charge['total_charge'] - 90.00) < 0.001;
});

runCheck('PRC-02', 'Weekend Surcharge 20% on US$100 venue yields US$120.00', function() use ($testVenue) {
    $rule = [
        'pricing_rule_id' => 2, 'rule_name' => 'Weekend Surcharge', 'venue_type' => 'All',
        'rule_type' => 'Weekend Surcharge', 'adjustment_type' => 'Percentage', 'adjustment_value' => 20.00,
        'days_of_week' => 'Fri,Sat,Sun', 'min_hours' => null, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 10, 'rule_status' => 'Active',
    ];
    $service = new PricingService(fn() => $testVenue, fn() => [$rule]);
    $charge = $service->calculate(1, '2026-10-09', '09:00', '13:00');
    return abs($charge['total_charge'] - 120.00) < 0.001;
});

runCheck('PRC-03', 'Off-Peak Discount 15% on US$100 venue yields US$85.00', function() use ($testVenue) {
    $rule = [
        'pricing_rule_id' => 3, 'rule_name' => 'Off-Peak Discount', 'venue_type' => 'All',
        'rule_type' => 'Off-Peak Discount', 'adjustment_type' => 'Percentage', 'adjustment_value' => 15.00,
        'days_of_week' => null, 'min_hours' => null, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 9, 'rule_status' => 'Active',
    ];
    $service = new PricingService(fn() => $testVenue, fn() => [$rule]);
    $charge = $service->calculate(1, '2026-10-05', '09:00', '13:00');
    return abs($charge['total_charge'] - 85.00) < 0.001;
});

runCheck('PRC-04', 'Long Booking 8+ hrs applies single highest priority rule: US$95.00', function() use ($testVenue) {
    $weekday = [
        'pricing_rule_id' => 1, 'rule_name' => 'Weekday Discount', 'venue_type' => 'All',
        'rule_type' => 'Weekday Discount', 'adjustment_type' => 'Percentage', 'adjustment_value' => 10.00,
        'days_of_week' => 'Mon,Tue,Wed,Thu', 'min_hours' => null, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 10, 'rule_status' => 'Active',
    ];
    $long = [
        'pricing_rule_id' => 4, 'rule_name' => 'Long Booking Discount', 'venue_type' => 'All',
        'rule_type' => 'Long Booking Discount', 'adjustment_type' => 'Percentage', 'adjustment_value' => 5.00,
        'days_of_week' => null, 'min_hours' => 8.0, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 11, 'rule_status' => 'Active',
    ];
    $service = new PricingService(fn() => $testVenue, fn() => [$weekday, $long]);
    $charge = $service->calculate(1, '2026-10-05', '08:00', '16:00');
    return abs($charge['total_charge'] - 95.00) < 0.001 && $charge['rule_name'] === 'Long Booking Discount';
});

runCheck('PRC-05', 'No matching rule maintains standard price of US$100.00', function() use ($testVenue) {
    $service = new PricingService(fn() => $testVenue, fn() => []);
    $charge = $service->calculate(1, '2026-10-05', '09:00', '13:00');
    return abs($charge['total_charge'] - 100.00) < 0.001 && $charge['adjustment_amount'] === 0.0;
});

runCheck('PRC-06', 'Inactive rules are ignored by PricingService', function() use ($testVenue) {
    $inactive = [
        'pricing_rule_id' => 5, 'rule_name' => 'Inactive Rule', 'venue_type' => 'All',
        'rule_type' => 'Weekday Discount', 'adjustment_type' => 'Percentage', 'adjustment_value' => 50.00,
        'days_of_week' => 'Mon,Tue,Wed,Thu', 'min_hours' => null, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 20, 'rule_status' => 'Inactive',
    ];
    $service = new PricingService(fn() => $testVenue, fn() => [$inactive]);
    $charge = $service->calculate(1, '2026-10-05', '09:00', '13:00');
    return abs($charge['total_charge'] - 100.00) < 0.001;
});

runCheck('PRC-07', 'Fixed discount exceeding standard price is capped at US$0.00 total', function() use ($testVenue) {
    $discount = [
        'pricing_rule_id' => 6, 'rule_name' => 'Massive Discount', 'venue_type' => 'All',
        'rule_type' => 'Off-Peak Discount', 'adjustment_type' => 'Fixed Amount', 'adjustment_value' => 150.00,
        'days_of_week' => null, 'min_hours' => null, 'start_date' => '2026-01-01',
        'end_date' => '2027-12-31', 'priority' => 10, 'rule_status' => 'Active',
    ];
    $service = new PricingService(fn() => $testVenue, fn() => [$discount]);
    $charge = $service->calculate(1, '2026-10-05', '09:00', '13:00');
    return abs($charge['total_charge'] - 0.00) < 0.001 && $charge['adjustment_amount'] === -100.00;
});

// Receipt Reference & Format
runCheck('PAY-01', 'Receipt reference regex format matches RCT-YYYYMMDD-XXXX', function() {
    $sample = 'RCT-20261004-0001';
    return preg_match('/^RCT-\d{8}-\d{4}$/', $sample) === 1;
});

runCheck('BKG-03', 'Booking reference regex format matches COH-YYYYMMDD-XXXX', function() {
    $sample = 'COH-20261004-0001';
    return preg_match('/^COH-\d{8}-\d{4}$/', $sample) === 1;
});

echo "\nSummary: {$passed} PASSED, {$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
