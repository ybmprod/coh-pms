<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/PricingService.php';

$venue = [
    'venue_id' => 1,
    'venue_type' => 'Community Hall',
    'standard_price' => 100.00,
];

$makeRule = static function (
    int $id,
    string $name,
    string $type,
    float $value,
    int $priority = 1,
    ?string $days = null,
    ?float $minimumHours = null,
    ?string $startDate = '2026-01-01',
    ?string $endDate = '2027-12-31',
    string $adjustmentType = 'Percentage',
    string $status = 'Active'
): array {
    return [
        'pricing_rule_id' => $id,
        'rule_name' => $name,
        'venue_type' => 'All',
        'rule_type' => $type,
        'adjustment_type' => $adjustmentType,
        'adjustment_value' => $value,
        'days_of_week' => $days,
        'min_hours' => $minimumHours,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'priority' => $priority,
        'rule_status' => $status,
    ];
};

$runCase = static function (string $name, callable $test): bool {
    try {
        $test();
        echo 'PASS ', $name, PHP_EOL;
        return true;
    } catch (Throwable $exception) {
        echo 'FAIL ', $name, ': ', $exception->getMessage(), PHP_EOL;
        return false;
    }
};

$assertTotal = static function (array $charge, float $expected): void {
    if (abs((float) $charge['total_charge'] - $expected) > 0.001) {
        throw new RuntimeException(sprintf('Expected %.2f, got %.2f.', $expected, $charge['total_charge']));
    }
};

$calculate = static function (string $date, string $start, string $end, array $rules) use ($venue): array {
    $service = new PricingService(
        static fn(int $venueId): ?array => $venueId === 1 ? $venue : null,
        static fn(): array => $rules
    );

    return $service->calculate(1, $date, $start, $end);
};

$allPassed = true;

$allPassed = $runCase('Mon-Thu weekday discount gives 90.00', static function () use ($makeRule, $calculate, $assertTotal): void {
    $rule = $makeRule(1, 'Weekday Discount', 'Weekday Discount', 10, 10, 'Mon,Tue,Wed,Thu');
    $charge = $calculate('2026-10-05', '09:00', '13:00', [$rule]);
    $assertTotal($charge, 90.00);
}) && $allPassed;

$allPassed = $runCase('Fri-Sun weekend surcharge gives 120.00', static function () use ($makeRule, $calculate, $assertTotal): void {
    $rule = $makeRule(2, 'Weekend Surcharge', 'Weekend Surcharge', 20, 10, 'Fri,Sat,Sun');
    $charge = $calculate('2026-10-09', '09:00', '13:00', [$rule]);
    $assertTotal($charge, 120.00);
}) && $allPassed;

$allPassed = $runCase('Off-Peak-only date gives 85.00', static function () use ($makeRule, $calculate, $assertTotal): void {
    $rule = $makeRule(3, 'Off-Peak Discount', 'Off-Peak Discount', 15, 9);
    $charge = $calculate('2026-10-05', '09:00', '13:00', [$rule]);
    $assertTotal($charge, 85.00);
}) && $allPassed;

$allPassed = $runCase('8-hour weekday selects only the highest-priority rule: 95.00', static function () use ($makeRule, $calculate, $assertTotal): void {
    $weekday = $makeRule(4, 'Weekday Discount', 'Weekday Discount', 10, 10, 'Mon,Tue,Wed,Thu');
    $longBooking = $makeRule(5, 'Long Booking Discount', 'Long Booking Discount', 5, 11, null, 8.0);
    $offPeak = $makeRule(6, 'Off-Peak Discount', 'Off-Peak Discount', 15, 9);
    $charge = $calculate('2026-10-05', '08:00', '16:00', [$weekday, $longBooking, $offPeak]);
    $assertTotal($charge, 95.00);
    if ($charge['rule_name'] !== 'Long Booking Discount') {
        throw new RuntimeException('Expected Long Booking Discount to win; multiple adjustments must not stack.');
    }
}) && $allPassed;

$allPassed = $runCase('No matching rule leaves the standard charge at 100.00', static function () use ($makeRule, $calculate, $assertTotal): void {
    $rule = $makeRule(7, 'Expired Weekday Discount', 'Weekday Discount', 10, 10, 'Mon,Tue,Wed,Thu', null, '2026-01-01', '2027-12-31');
    $charge = $calculate('2028-10-02', '09:00', '13:00', [$rule]);
    $assertTotal($charge, 100.00);
}) && $allPassed;

$allPassed = $runCase('Inactive rules are ignored', static function () use ($makeRule, $calculate, $assertTotal): void {
    $inactive = $makeRule(8, 'Inactive Discount', 'Weekday Discount', 50, 20, 'Mon,Tue,Wed,Thu', null, '2026-01-01', '2027-12-31', 'Percentage', 'Inactive');
    $charge = $calculate('2026-10-05', '09:00', '13:00', [$inactive]);
    $assertTotal($charge, 100.00);
}) && $allPassed;

$allPassed = $runCase('Fixed discount above standard never makes a negative total', static function () use ($makeRule, $calculate, $assertTotal): void {
    $discount = $makeRule(9, 'Large Fixed Discount', 'Long Booking Discount', 150, 10, null, null, '2026-01-01', '2027-12-31', 'Fixed Amount');
    $charge = $calculate('2026-10-05', '09:00', '13:00', [$discount]);
    $assertTotal($charge, 0.00);
    if ((float) $charge['adjustment_amount'] !== -100.00) {
        throw new RuntimeException('Fixed discount was not capped at the standard charge.');
    }
}) && $allPassed;

$allPassed = $runCase('Equal priority selects the lower pricing_rule_id', static function () use ($makeRule, $calculate, $assertTotal): void {
    $higherId = $makeRule(12, 'Higher ID', 'Weekday Discount', 30, 10, 'Mon,Tue,Wed,Thu');
    $lowerId = $makeRule(11, 'Lower ID', 'Weekday Discount', 10, 10, 'Mon,Tue,Wed,Thu');
    $charge = $calculate('2026-10-05', '09:00', '13:00', [$higherId, $lowerId]);
    $assertTotal($charge, 90.00);
    if ($charge['rule_name'] !== 'Lower ID') {
        throw new RuntimeException('The lower pricing_rule_id did not win the tie.');
    }
}) && $allPassed;

exit($allPassed ? 0 : 1);