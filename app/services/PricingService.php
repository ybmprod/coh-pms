<?php
declare(strict_types=1);

// CHAPTER 5.3.4 - Pricing Rules Implementation
class PricingService
{
    private Closure $venueFinder;
    private Closure $activeRulesFinder;

    public function __construct(?Closure $venueFinder = null, ?Closure $activeRulesFinder = null)
    {
        $this->venueFinder = $venueFinder ?? static fn(int $venueId): ?array => Venue::findById($venueId);
        $this->activeRulesFinder = $activeRulesFinder ?? static fn(): array => PricingRule::getActive();
    }

    public function calculate(int $venueId, string $date, string $start, string $end): array
    {
        $venue = ($this->venueFinder)($venueId);
        if (!$venue) {
            throw new InvalidArgumentException('Venue not found.');
        }

        $startMinutes = $this->timeToMinutes($start);
        $endMinutes = $this->timeToMinutes($end);
        if ($endMinutes <= $startMinutes) {
            throw new InvalidArgumentException('End time must be later than start time.');
        }

        $durationHours = ($endMinutes - $startMinutes) / 60;
        $standardCharge = round((float) $venue['standard_price'], 2);
        $selectedRule = null;

        foreach (($this->activeRulesFinder)() as $rule) {
            if (($rule['rule_status'] ?? 'Active') !== 'Active') {
                continue;
            }
            if ($rule['venue_type'] !== 'All' && $rule['venue_type'] !== $venue['venue_type']) {
                continue;
            }
            if (!$this->ruleMatchesDate($rule, $date)) {
                continue;
            }
            if (!empty($rule['days_of_week']) && !$this->ruleMatchesDay($rule['days_of_week'], $date)) {
                continue;
            }
            if (!empty($rule['min_hours']) && $durationHours < (float) $rule['min_hours']) {
                continue;
            }

            $priority = (int) $rule['priority'];
            if (
                $selectedRule === null
                || $priority > (int) $selectedRule['priority']
                || ($priority === (int) $selectedRule['priority']
                    && (int) $rule['pricing_rule_id'] < (int) $selectedRule['pricing_rule_id'])
            ) {
                $selectedRule = $rule;
            }
        }

        $adjustmentAmount = $selectedRule === null
            ? 0.0
            : $this->applyAdjustment($standardCharge, $selectedRule);
        $totalCharge = max(0.0, $standardCharge + $adjustmentAmount);

        return [
            'standard_charge' => round($standardCharge, 2),
            'adjustment_amount' => round($adjustmentAmount, 2),
            'total_charge' => round($totalCharge, 2),
            'pricing_rule_id' => $selectedRule['pricing_rule_id'] ?? null,
            'rule_name' => $selectedRule['rule_name'] ?? null,
        ];
    }

    private function timeToMinutes(string $time): int
    {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            throw new InvalidArgumentException('Time must use the HH:MM format.');
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));
        return ($hours * 60) + $minutes;
    }

    private function ruleMatchesDate(array $rule, string $date): bool
    {
        if (!empty($rule['start_date']) && $date < $rule['start_date']) {
            return false;
        }
        if (!empty($rule['end_date']) && $date > $rule['end_date']) {
            return false;
        }

        return true;
    }

    private function ruleMatchesDay(string $daysOfWeek, string $date): bool
    {
        return in_array(date('D', strtotime($date)), array_map('trim', explode(',', $daysOfWeek)), true);
    }

    private function applyAdjustment(float $standardCharge, array $rule): float
    {
        $directions = [
            'Weekday Discount' => 'discount',
            'Off-Peak Discount' => 'discount',
            'Long Booking Discount' => 'discount',
            'Weekend Surcharge' => 'surcharge',
            'Peak Demand Surcharge' => 'surcharge',
        ];
        $direction = $directions[$rule['rule_type']] ?? null;
        if ($direction === null) {
            throw new InvalidArgumentException('Pricing rule type is invalid.');
        }

        $value = (float) $rule['adjustment_value'];
        $amount = $rule['adjustment_type'] === 'Fixed Amount'
            ? $value
            : $standardCharge * ($value / 100);

        if ($direction === 'discount') {
            return -min($standardCharge, $amount);
        }

        return $amount;
    }
}