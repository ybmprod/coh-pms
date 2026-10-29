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

    public function calculate(int $venueId, string $date, string $start, string $end, ?string $eventType = null, float $eventTypeCharge = 0.0): array
    {
        $venue = ($this->venueFinder)($venueId);
        if (!$venue) {
            throw new InvalidArgumentException('Venue not found.');
        }

        $startMinutes = $this->timeToMinutes($start);
        $endMinutes = $this->timeToMinutes($end);
        if (!$this->isValidBookingWindow($startMinutes, $endMinutes)) {
            throw new InvalidArgumentException(
                'End time must be later than start time. Overnight bookings are only allowed from evening (18:00 / 6:00 PM) into the next morning (up to 09:00 / 9:00 AM).'
            );
        }

        // Support valid overnight bookings (evening PM -> next morning AM)
        $durationMinutes = $endMinutes > $startMinutes
            ? ($endMinutes - $startMinutes)
            : ((24 * 60 - $startMinutes) + $endMinutes);
        $durationHours = $durationMinutes / 60;
        $standardCharge = round((float) $venue['standard_price'], 2);
        // Calculate the event-type charge before applying the pricing-rule adjustment
        if ($eventTypeCharge === 0.0 && !empty($eventType) && class_exists('EventType')) {
            $et = EventType::findByName($eventType);
            if ($et && ($et['status'] ?? 'Active') === 'Active') {
                if (($et['charge_type'] ?? '') === 'Percentage') {
                    $eventTypeCharge = round($standardCharge * ((float) $et['charge_value'] / 100), 2);
                } else {
                    $eventTypeCharge = round((float) ($et['charge_value'] ?? 0), 2);
                }
            }
        }

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
        $totalCharge = max(0.0, $standardCharge + $eventTypeCharge + $adjustmentAmount);

        return [
            'standard_charge' => round($standardCharge, 2),
            'event_type_charge' => round($eventTypeCharge, 2),
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

    /**
     * Validates that the booking time window is either:
     * 1. A same-day booking where end time is strictly after start time, OR
     * 2. A genuine overnight booking starting in the evening/night (18:00–23:59)
     *    and ending the next morning (00:00–09:00).
     * Reversed daytime hours (e.g. 14:00 to 10:00) are rejected.
     */
    public function isValidBookingWindow(int $startMinutes, int $endMinutes): bool
    {
        if ($endMinutes > $startMinutes) {
            return true;
        }
        if ($endMinutes === $startMinutes) {
            return false;
        }

        // Cross-midnight (end < start): require evening start (>= 18:00) and morning end (<= 09:00)
        return $startMinutes >= (18 * 60) && $endMinutes <= (9 * 60);
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