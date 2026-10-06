<?php
declare(strict_types=1);

// CHAPTER 5.3.4 - Pricing Rules Implementation
class PricingruleController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        $pricingRules = PricingRule::getAll();
        $this->render('staff/pricing_rules', [
            'pageTitle' => 'Pricing Rules',
            'pricingRules' => $pricingRules,
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for pricingrule/create.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        $data = $this->normalizeData($_POST);
        $errors = $this->validateRuleData($data);

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        PricingRule::create($data);

        set_flash('success', 'Pricing rule created successfully.');
        $this->redirect(BASE_URL . '/?r=pricingrule/index');
    }

    public function edit(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for pricingrule/edit.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        $pricingRuleId = (int) ($_POST['pricing_rule_id'] ?? 0);
        $rule = PricingRule::findById($pricingRuleId);

        if (!$rule) {
            set_flash('error', 'Pricing rule not found.');
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        $data = $this->normalizeData($_POST);
        $errors = $this->validateRuleData($data, $pricingRuleId);

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        PricingRule::update($pricingRuleId, $data);

        set_flash('success', 'Pricing rule updated successfully.');
        $this->redirect(BASE_URL . '/?r=pricingrule/index');
    }

    public function toggleStatus(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for pricingrule/toggleStatus.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        $pricingRuleId = (int) ($_POST['pricing_rule_id'] ?? 0);
        $rule = PricingRule::findById($pricingRuleId);

        if (!$rule) {
            set_flash('error', 'Pricing rule not found.');
            $this->redirect(BASE_URL . '/?r=pricingrule/index');
        }

        $nextStatus = $rule['rule_status'] === 'Active' ? 'Inactive' : 'Active';
        PricingRule::setStatus($pricingRuleId, $nextStatus);

        set_flash('success', 'Pricing rule status updated to ' . $nextStatus . '.');
        $this->redirect(BASE_URL . '/?r=pricingrule/index');
    }

    private function normalizeData(array $input): array
    {
        return [
            'rule_name' => trim((string) ($input['rule_name'] ?? '')),
            'venue_type' => trim((string) ($input['venue_type'] ?? '')),
            'rule_type' => trim((string) ($input['rule_type'] ?? '')),
            'adjustment_type' => trim((string) ($input['adjustment_type'] ?? '')),
            'adjustment_value' => trim((string) ($input['adjustment_value'] ?? '')),
            'days_of_week' => trim((string) ($input['days_of_week'] ?? '')),
            'min_hours' => trim((string) ($input['min_hours'] ?? '')),
            'start_date' => trim((string) ($input['start_date'] ?? '')),
            'end_date' => trim((string) ($input['end_date'] ?? '')),
            'priority' => trim((string) ($input['priority'] ?? '1')),
            'rule_status' => trim((string) ($input['rule_status'] ?? 'Active')),
        ];
    }

    private function validateRuleData(array $data, int $ignoreId = 0): array
    {
        $errors = [];

        if ($data['rule_name'] === '') {
            $errors[] = 'Rule name is required.';
        }

        $allowedVenueTypes = ['All', 'Community Hall', 'Community Centre', 'Stadium', 'Open Space', 'Other'];
        if (!in_array($data['venue_type'], $allowedVenueTypes, true)) {
            $errors[] = 'Venue type is invalid.';
        }

        $allowedRuleTypes = [
            'Weekday Discount',
            'Weekend Surcharge',
            'Off-Peak Discount',
            'Long Booking Discount',
            'Peak Demand Surcharge',
        ];
        if (!in_array($data['rule_type'], $allowedRuleTypes, true)) {
            $errors[] = 'Rule type is invalid.';
        }

        $allowedAdjustmentTypes = ['Percentage', 'Fixed Amount'];
        if (!in_array($data['adjustment_type'], $allowedAdjustmentTypes, true)) {
            $errors[] = 'Adjustment type is invalid.';
        }

        if (!is_numeric($data['adjustment_value']) || (float) $data['adjustment_value'] <= 0) {
            $errors[] = 'Adjustment value must be greater than zero.';
        } elseif ($data['adjustment_type'] === 'Percentage' && (float) $data['adjustment_value'] > 100) {
            $errors[] = 'Percentage adjustment cannot exceed 100.';
        }

        $days = array_filter(array_map('trim', explode(',', $data['days_of_week'])));
        if ($data['days_of_week'] !== '' && $days === []) {
            $errors[] = 'Days of week must use a valid CSV list.';
        }

        $allowedDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        if ($data['days_of_week'] !== '') {
            foreach ($days as $day) {
                if (!in_array($day, $allowedDays, true)) {
                    $errors[] = 'Days of week contain an invalid value.';
                    break;
                }
            }
        }

        if ($data['min_hours'] !== '' && (!is_numeric($data['min_hours']) || (float) $data['min_hours'] <= 0)) {
            $errors[] = 'Minimum hours must be greater than zero when supplied.';
        }

        if ($data['start_date'] !== '' && !is_valid_date($data['start_date'])) {
            $errors[] = 'Start date is invalid.';
        }

        if ($data['end_date'] !== '' && !is_valid_date($data['end_date'])) {
            $errors[] = 'End date is invalid.';
        }

        if ($data['start_date'] !== '' && $data['end_date'] !== '' && $data['end_date'] < $data['start_date']) {
            $errors[] = 'End date must be on or after the start date.';
        }

        if (
            !is_numeric($data['priority'])
            || (float) $data['priority'] < 1
            || floor((float) $data['priority']) !== (float) $data['priority']
        ) {
            $errors[] = 'Priority must be an integer of at least 1.';
        }

        if (!in_array($data['rule_status'], ['Active', 'Inactive'], true)) {
            $errors[] = 'Rule status is invalid.';
        }

        return $errors;
    }
}
