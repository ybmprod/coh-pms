<?php
declare(strict_types=1);

class EventtypeController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], 'staff')) {
            return;
        }

        $eventTypes = EventType::getAllWithUsage();
        $confirmDisable = null;
        $confirmDisableId = (int) ($_GET['confirm_disable'] ?? 0);

        if ($confirmDisableId > 0) {
            $candidate = EventType::findById($confirmDisableId);
            if ($candidate !== null && $candidate['status'] === 'Active') {
                $usage = EventType::getUsage($confirmDisableId, (string) $candidate['event_name']);
                if ($usage['active_booking_count'] > 0) {
                    $confirmDisable = [
                        'event_type' => $candidate,
                        'usage' => $usage,
                    ];
                }
            }
        }

        $this->render('staff/event_types', [
            'pageTitle' => 'Event Types & Charges',
            'eventTypes' => $eventTypes,
            'confirmDisable' => $confirmDisable,
            'venueTypes' => EventType::VENUE_TYPES,
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        $name = trim((string) ($_POST['event_name'] ?? ''));
        $chargeType = trim((string) ($_POST['charge_type'] ?? 'Fixed Amount'));
        $chargeValue = (float) ($_POST['charge_value'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'Active'));
        $description = trim((string) ($_POST['description'] ?? ''));
        $venueTypes = $this->selectedVenueTypes($_POST);

        if ($name === '') {
            set_flash('error', 'Event type name is required.');
            $this->redirect(BASE_URL . '/?r=eventtype/index#add');
        }

        if ($chargeValue < 0) {
            set_flash('error', 'Charge value cannot be negative.');
            $this->redirect(BASE_URL . '/?r=eventtype/index#add');
        }

        if ($venueTypes === []) {
            set_flash('error', 'Select at least one appropriate venue type.');
            $this->redirect(BASE_URL . '/?r=eventtype/index#add');
        }

        if (EventType::findByName($name)) {
            set_flash('error', 'An event type with this name already exists.');
            $this->redirect(BASE_URL . '/?r=eventtype/index#add');
        }

        $id = EventType::create([
            'event_name' => $name,
            'charge_type' => $chargeType,
            'charge_value' => $chargeValue,
            'status' => $status,
            'description' => $description,
            'venue_types' => $venueTypes,
        ]);

        if (function_exists('audit_log')) {
            audit_log('event_type.create', [
                'event_type_id' => $id,
                'event_name' => $name,
                'charge_type' => $chargeType,
                'charge_value' => $chargeValue,
                'venue_types' => $venueTypes,
            ]);
        }

        set_flash('success', 'Event type added successfully.');
        $this->redirect(BASE_URL . '/?r=eventtype/index#list');
    }

    public function edit(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        $id = (int) ($_POST['event_type_id'] ?? 0);
        $eventType = EventType::findById($id);
        if (!$eventType) {
            set_flash('error', 'Event type not found.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        $name = trim((string) ($_POST['event_name'] ?? ''));
        $chargeType = trim((string) ($_POST['charge_type'] ?? 'Fixed Amount'));
        $chargeValue = (float) ($_POST['charge_value'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'Active'));
        $description = trim((string) ($_POST['description'] ?? ''));
        $venueTypes = $this->selectedVenueTypes($_POST);

        if ($name === '') {
            set_flash('error', 'Event type name is required.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        if ($chargeValue < 0) {
            set_flash('error', 'Charge value cannot be negative.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        if ($venueTypes === []) {
            set_flash('error', 'Select at least one appropriate venue type.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        EventType::update($id, [
            'event_name' => $name,
            'charge_type' => $chargeType,
            'charge_value' => $chargeValue,
            'status' => $status,
            'description' => $description,
            'venue_types' => $venueTypes,
        ]);

        if (function_exists('audit_log')) {
            audit_log('event_type.update', [
                'event_type_id' => $id,
                'event_name' => $name,
                'charge_type' => $chargeType,
                'charge_value' => $chargeValue,
                'status' => $status,
                'venue_types' => $venueTypes,
            ]);
        }

        set_flash('success', 'Event type updated successfully.');
        $this->redirect(BASE_URL . '/?r=eventtype/index');
    }

    public function toggle(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        $id = (int) ($_POST['event_type_id'] ?? 0);
        $eventType = EventType::findById($id);
        if (!$eventType) {
            set_flash('error', 'Event type not found.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        $newStatus = ($eventType['status'] === 'Active') ? 'Inactive' : 'Active';
        $usage = EventType::getUsage($id, (string) $eventType['event_name']);
        $usageConfirmed = (string) ($_POST['confirm_usage'] ?? '') === '1';

        if ($newStatus === 'Inactive' && $usage['active_booking_count'] > 0 && !$usageConfirmed) {
            $bookingLabel = $usage['active_booking_count'] === 1 ? 'booking' : 'bookings';
            set_flash(
                'warning',
                $eventType['event_name'] . ' is used by ' . $usage['active_booking_count']
                . ' active ' . $bookingLabel . '. Review the warning below before disabling it.'
            );
            $this->redirect(BASE_URL . '/?r=eventtype/index&confirm_disable=' . $id . '#list');
        }

        EventType::updateStatus($id, $newStatus);

        if (function_exists('audit_log')) {
            audit_log('event_type.toggle_status', [
                'event_type_id' => $id,
                'old_status' => $eventType['status'],
                'new_status' => $newStatus,
                'booking_count' => $usage['booking_count'],
                'active_booking_count' => $usage['active_booking_count'],
                'usage_warning_confirmed' => $usageConfirmed,
            ]);
        }

        set_flash('success', "Event type status updated to {$newStatus}.");
        $this->redirect(BASE_URL . '/?r=eventtype/index');
    }

    private function selectedVenueTypes(array $input): array
    {
        $submitted = $input['venue_types'] ?? [];
        if (!is_array($submitted)) {
            return [];
        }

        return array_values(array_intersect(
            EventType::VENUE_TYPES,
            array_map('strval', $submitted)
        ));
    }
}

