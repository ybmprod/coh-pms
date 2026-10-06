<?php
declare(strict_types=1);

// CHAPTER 5.3.3 - Booking and Availability Management
class BookingController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT])) {
            return;
        }

        $bookings = Booking::getAll();
        $bookingActions = [];
        $bookingService = new BookingService();
        foreach ($bookings as $booking) {
            $bookingActions[(int) $booking['booking_id']] = $bookingService->allowedStaffActions($booking);
        }

        $this->render('staff/bookings', [
            'pageTitle' => 'Bookings',
            'bookings' => $bookings,
            'bookingActions' => $bookingActions,
        ]);
    }

    public function customerList(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER])) {
            return;
        }

        $user = current_user();
        $this->render('customer/bookings', [
            'pageTitle' => 'My Bookings',
            'bookings' => Booking::getByCustomer((int) $user['id']),
            'venues' => Venue::getActive(),
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER])) {
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

        $user = current_user();
        try {
            $booking = (new BookingService())->createBooking($_POST, (int) $user['id']);
        } catch (Throwable $e) {
            set_flash('error', $e->getMessage());
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }
        set_flash('success', 'Booking request submitted. Reference: ' . ($booking['booking_reference'] ?? ''));
        $this->redirect(BASE_URL . '/?r=booking/customerList');
    }

    public function checkAvailability(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['available' => false, 'message' => 'Method not allowed.']);
            return;
        }
        if (!$this->requireAuth()) {
            http_response_code(401);
            echo json_encode(['available' => false, 'message' => 'Please sign in to check availability.']);
            return;
        }

        $venueId = (int) ($_GET['venue_id'] ?? 0);
        $date = trim((string) ($_GET['booking_date'] ?? ''));
        $start = trim((string) ($_GET['start_time'] ?? ''));
        $end = trim((string) ($_GET['end_time'] ?? ''));
        $result = [
            'available' => false,
            'message' => 'Select a venue, date, start time, and end time.',
            'standard_charge' => null,
            'adjustment_amount' => null,
            'total_charge' => null,
            'rule_name' => null,
        ];

        if ($venueId > 0 && $date !== '' && $start !== '' && $end !== '') {
            $result = array_merge(
                $result,
                (new AvailabilityService())->check($venueId, $date, $start, $end)
            );
            try {
                $charge = (new PricingService())->calculate($venueId, $date, $start, $end);
                $result['standard_charge'] = $charge['standard_charge'];
                $result['adjustment_amount'] = $charge['adjustment_amount'];
                $result['total_charge'] = $charge['total_charge'];
                $result['rule_name'] = $charge['rule_name'];
            } catch (Throwable $exception) {
                if ($result['available']) {
                    $result['available'] = false;
                    $result['message'] = $exception->getMessage();
                }
            }
        }

        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }

    public function cancel(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER])) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for booking/cancel.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        try {
            (new BookingService())->cancelByCustomer(
                (int) ($_POST['booking_id'] ?? 0),
                (int) current_user()['id']
            );
            set_flash('success', 'Booking cancelled successfully.');
        } catch (DomainException $exception) {
            set_flash('error', $exception->getMessage());
        } catch (Throwable $e) {
            error_log('Failed to cancel booking: ' . $e->getMessage());
            set_flash('error', 'Booking could not be cancelled. Please try again.');
        }
        $this->redirect(BASE_URL . '/?r=booking/customerList');
    }

    public function details(): void
    {
        if (!$this->requireAuth()) {
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        $booking = Booking::findById((int) ($_GET['id'] ?? 0));
        if (!$booking) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Booking not found']);
            return;
        }

        $user = current_user();
        if ($user['role'] === ROLE_CUSTOMER) {
            if ((int) $booking['customer_id'] !== (int) $user['id']) {
                http_response_code(403);
                $this->render('errors/403', ['pageTitle' => 'Forbidden']);
                return;
            }
        } elseif ($this->denyIfNotRole([
            ROLE_ADMINISTRATOR,
            ROLE_BOOKING_OFFICER,
            ROLE_REVENUE_OFFICER,
            ROLE_COUNCIL_MANAGEMENT,
        ])) {
            return;
        }

        $this->render('customer/booking_details', [
            'pageTitle' => 'Booking ' . $booking['booking_reference'],
            'booking' => $booking,
            'payment' => Payment::findLatestByBookingId((int) $booking['booking_id']),
        ]);
    }

    public function updateStatus(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER])) {
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
        $actor = current_user();
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
