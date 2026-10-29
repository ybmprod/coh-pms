<?php
declare(strict_types=1);

class EventtypeController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], 'staff')) {
            return;
        }

        $eventTypes = EventType::getAll();
        $this->render('staff/event_types', [
            'pageTitle' => 'Event Types & Charges',
            'eventTypes' => $eventTypes,
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

        if ($name === '') {
            set_flash('error', 'Event type name is required.');
            $this->redirect(BASE_URL . '/?r=eventtype/index#add');
        }

        if ($chargeValue < 0) {
            set_flash('error', 'Charge value cannot be negative.');
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
        ]);

        if (function_exists('audit_log')) {
            audit_log('event_type.create', [
                'event_type_id' => $id,
                'event_name' => $name,
                'charge_type' => $chargeType,
                'charge_value' => $chargeValue,
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

        if ($name === '') {
            set_flash('error', 'Event type name is required.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        if ($chargeValue < 0) {
            set_flash('error', 'Charge value cannot be negative.');
            $this->redirect(BASE_URL . '/?r=eventtype/index');
        }

        EventType::update($id, [
            'event_name' => $name,
            'charge_type' => $chargeType,
            'charge_value' => $chargeValue,
            'status' => $status,
            'description' => $description,
        ]);

        if (function_exists('audit_log')) {
            audit_log('event_type.update', [
                'event_type_id' => $id,
                'event_name' => $name,
                'charge_type' => $chargeType,
                'charge_value' => $chargeValue,
                'status' => $status,
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
        EventType::updateStatus($id, $newStatus);

        if (function_exists('audit_log')) {
            audit_log('event_type.toggle_status', [
                'event_type_id' => $id,
                'old_status' => $eventType['status'],
                'new_status' => $newStatus,
            ]);
        }

        set_flash('success', "Event type status updated to {$newStatus}.");
        $this->redirect(BASE_URL . '/?r=eventtype/index');
    }
}

