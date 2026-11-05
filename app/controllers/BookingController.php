<?php
declare(strict_types=1);

// CHAPTER 5.3.3 - Booking and Availability Management
class BookingController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT], 'staff')) {
            return;
        }

        $perPage = pagination_per_page();
        $pagination = pagination_page_param($_GET['page'] ?? null, Booking::countAll(), $perPage);
        $bookings = Booking::getAll($perPage, $pagination['offset']);
        $bookingActions = [];
        $bookingService = new BookingService();
        foreach ($bookings as $booking) {
            $bookingActions[(int) $booking['booking_id']] = $bookingService->allowedStaffActions($booking);
        }

        $this->render('staff/bookings', [
            'pageTitle' => 'Bookings',
            'bookings' => $bookings,
            'bookingActions' => $bookingActions,
            'pagination' => $pagination,
        ]);
    }

    public function customerList(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }

        $user = current_customer_user();
        $perPage = pagination_per_page(10);
        $pagination = pagination_page_param(
            $_GET['page'] ?? null,
            Booking::countByCustomer((int) $user['id']),
            $perPage
        );

        $this->render('customer/bookings', [
            'pageTitle' => 'My Bookings',
            'bookings' => Booking::getByCustomer((int) $user['id'], $perPage, $pagination['offset']),
            'venues' => Venue::getActive(),
            'eventTypes' => class_exists('EventType') ? EventType::getActive() : [],
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for booking/create.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        $user = current_customer_user();
        if (!rate_limit_allow('booking_write:' . (int) $user['id'], 30, 3600)) {
            audit_log('security.rate_limit', ['action' => 'booking_create', 'user_id' => (int) $user['id']]);
            set_flash('error', 'Too many booking changes. Please wait before trying again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        try {
            $booking = (new BookingService())->createBooking($_POST, (int) $user['id']);
        } catch (Throwable $e) {
            error_log('Failed to create booking: ' . $e->getMessage());
            set_flash('error', user_error_message($e, 'Booking could not be submitted. Please try again.'));
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }
        set_flash('success', 'Booking request submitted. Reference: ' . ($booking['booking_reference'] ?? ''));
        $this->redirect(BASE_URL . '/?r=booking/customerList#history');
    }

    public function edit(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }

        $user = current_customer_user();
        $booking = Booking::findByIdForCustomer((int) ($_GET['id'] ?? 0), (int) $user['id']);
        if (!$booking) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Booking not found']);
            return;
        }
        if (!BookingService::canCustomerEdit($booking, (int) $user['id'])) {
            set_flash('error', 'Only a Pending booking that belongs to you can be amended.');
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        $this->render('customer/booking_edit', [
            'pageTitle' => 'Amend booking ' . $booking['booking_reference'],
            'booking' => $booking,
            'venues' => Venue::getActive(),
            'eventTypes' => class_exists('EventType') ? EventType::getActive() : [],
        ]);
    }

    public function update(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for booking/update.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $user = current_customer_user();
        if (!rate_limit_allow('booking_write:' . (int) $user['id'], 30, 3600)) {
            audit_log('security.rate_limit', ['action' => 'booking_update', 'user_id' => (int) $user['id']]);
            set_flash('error', 'Too many booking changes. Please wait before trying again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        try {
            $booking = (new BookingService())->updateBooking($bookingId, $_POST, (int) $user['id']);
            set_flash('success', 'Booking updated. Reference: ' . ($booking['booking_reference'] ?? ''));
            $this->redirect(BASE_URL . '/?r=booking/details&id=' . $bookingId);
        } catch (DomainException $exception) {
            set_flash('error', $exception->getMessage());
            $this->redirect(BASE_URL . '/?r=booking/edit&id=' . $bookingId);
        } catch (Throwable $exception) {
            error_log('Failed to update booking: ' . $exception->getMessage());
            set_flash('error', 'Booking could not be updated. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/edit&id=' . $bookingId);
        }
    }

    public function checkAvailability(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['available' => false, 'message' => 'Method not allowed.']);
            return;
        }
        if (!is_logged_in('customer') && !is_logged_in('staff')) {
            http_response_code(401);
            echo json_encode(['available' => false, 'message' => 'Please sign in to check availability.']);
            return;
        }

        $venueId = (int) ($_GET['venue_id'] ?? 0);
        $date = trim((string) ($_GET['booking_date'] ?? ''));
        $start = trim((string) ($_GET['start_time'] ?? ''));
        $end = trim((string) ($_GET['end_time'] ?? ''));
        $eventType = trim((string) ($_GET['event_type'] ?? ''));
        $result = [
            'available' => false,
            'message' => 'Select a venue, date, start time, and end time.',
            'standard_charge' => null,
            'event_type_charge' => null,
            'adjustment_amount' => null,
            'total_charge' => null,
            'rule_name' => null,
        ];

        $excludeBookingId = 0;
        $requestedExcludeId = (int) ($_GET['exclude_booking_id'] ?? 0);
        if ($requestedExcludeId > 0 && is_logged_in('customer')) {
            $customer = current_customer_user();
            $pendingBooking = $customer
                ? Booking::findByIdForCustomer($requestedExcludeId, (int) $customer['id'])
                : null;
            if ($pendingBooking && $customer && BookingService::canCustomerEdit($pendingBooking, (int) $customer['id'])) {
                $excludeBookingId = $requestedExcludeId;
            }
        }

        if ($venueId > 0 && $date !== '' && $start !== '' && $end !== '') {
            $result = array_merge(
                $result,
                (new AvailabilityService())->check($venueId, $date, $start, $end, $excludeBookingId)
            );
            try {
                $charge = (new PricingService())->calculate($venueId, $date, $start, $end, $eventType);
                $result['standard_charge'] = $charge['standard_charge'];
                $result['event_type_charge'] = $charge['event_type_charge'];
                $result['adjustment_amount'] = $charge['adjustment_amount'];
                $result['total_charge'] = $charge['total_charge'];
                $result['rule_name'] = $charge['rule_name'];
            } catch (Throwable $exception) {
                if (!$exception instanceof DomainException) {
                    error_log('Availability pricing failed: ' . $exception->getMessage());
                }
                if ($result['available']) {
                    $result['available'] = false;
                    $result['message'] = user_error_message(
                        $exception,
                        'Pricing could not be calculated. Please try again.'
                    );
                }
            }
        } elseif ($venueId > 0) {
            try {
                $previewDate = $date !== '' ? $date : date('Y-m-d');
                $previewStart = ($start !== '' && $end !== '' && $start !== $end) ? $start : '09:00';
                $previewEnd = ($start !== '' && $end !== '' && $start !== $end) ? $end : '10:00';
                $charge = (new PricingService())->calculate($venueId, $previewDate, $previewStart, $previewEnd, $eventType);
                $result['standard_charge'] = $charge['standard_charge'];
                $result['event_type_charge'] = $charge['event_type_charge'];
                if ($date !== '') {
                    $result['adjustment_amount'] = $charge['adjustment_amount'];
                    $result['total_charge'] = $charge['total_charge'];
                    $result['rule_name'] = $charge['rule_name'];
                } else {
                    $result['adjustment_amount'] = 0.0;
                    $result['total_charge'] = round($charge['standard_charge'] + $charge['event_type_charge'], 2);
                    $result['rule_name'] = 'Select date & time for rule adjustments';
                }
            } catch (Throwable $exception) {
                if (!$exception instanceof DomainException) {
                    error_log('Availability preview failed: ' . $exception->getMessage());
                }
                // Keep default nulls if venue lookup fails
            }
        }

        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }

    public function cancel(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for booking/cancel.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        try {
            (new BookingService())->cancelByCustomer(
                (int) ($_POST['booking_id'] ?? 0),
                (int) current_customer_user()['id']
            );
            set_flash('success', 'Booking cancelled successfully.');
        } catch (DomainException $exception) {
            set_flash('error', $exception->getMessage());
        } catch (Throwable $e) {
            error_log('Failed to cancel booking: ' . $e->getMessage());
            set_flash('error', 'Booking could not be cancelled. Please try again.');
        }
        $this->redirect(BASE_URL . '/?r=booking/customerList#history');
    }

    public function details(): void
    {
        $portalScope = is_staff_portal_request() ? 'staff' : 'customer';
        if ($portalScope === 'staff') {
            if ($this->denyIfNotRole([
                ROLE_ADMINISTRATOR,
                ROLE_BOOKING_OFFICER,
                ROLE_REVENUE_OFFICER,
                ROLE_COUNCIL_MANAGEMENT,
            ], 'staff')) {
                return;
            }
        } else {
            if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
                return;
            }
        }

        $bookingId = (int) ($_GET['id'] ?? 0);
        $user = current_user($portalScope);
        $booking = $portalScope === 'customer'
            ? Booking::findByIdForCustomer($bookingId, (int) $user['id'])
            : Booking::findById($bookingId);
        if (!$booking) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Booking not found']);
            return;
        }

        if ($portalScope === 'customer' && (int) $booking['customer_id'] !== (int) $user['id']) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => 'Forbidden']);
            return;
        }

        $payment = $portalScope === 'customer'
            ? Payment::findLatestByBookingIdForCustomer($bookingId, (int) $user['id'])
            : Payment::findLatestByBookingId($bookingId);

        $this->render('customer/booking_details', [
            'pageTitle' => 'Booking ' . $booking['booking_reference'],
            'portalScope' => $portalScope,
            'booking' => $booking,
            'payment' => $payment,
        ]);
    }

    public function updateStatus(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for booking/updateStatus.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/index');
        }

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $status = trim((string) ($_POST['booking_status'] ?? ''));
        $actor = current_staff_user();
        try {
            (new BookingService())->changeStatus($bookingId, $status, (int) $actor['id'], (string) $actor['role']);
        } catch (DomainException $e) {
            set_flash('error', $e->getMessage());
            $this->redirect(BASE_URL . '/?r=booking/index');
        }
        set_flash('success', 'Booking status updated to ' . $status . '.');
        $this->redirect(BASE_URL . '/?r=booking/index');
    }
}
