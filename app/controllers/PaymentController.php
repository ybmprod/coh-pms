<?php
declare(strict_types=1);

// CHAPTER 5.3.5 - Payment Recording and Verification
class PaymentController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER], 'staff')) {
            return;
        }

        $statusParam = trim((string) ($_GET['status'] ?? ''));
        $allowedStatuses = [
            PAYMENT_STATUS_PENDING_VERIFICATION,
            PAYMENT_STATUS_VERIFIED,
            PAYMENT_STATUS_REJECTED,
        ];
        if ($statusParam === 'All') {
            $status = null;
        } elseif ($statusParam === '' || !in_array($statusParam, $allowedStatuses, true)) {
            $status = PAYMENT_STATUS_PENDING_VERIFICATION;
        } else {
            $status = $statusParam;
        }

        $perPage = pagination_per_page();
        $pagination = pagination_page_param($_GET['page'] ?? null, Payment::countByStatus($status), $perPage);

        $this->render('staff/payments', [
            'pageTitle' => 'Payment Verification',
            'payments' => Payment::getByStatus($status, $perPage, $pagination['offset']),
            'selectedStatus' => $status ?? 'All',
            'pagination' => $pagination,
        ]);
    }

    public function submit(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for payment/submit.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        $user = current_customer_user();
        if (!rate_limit_allow('payment_submit:' . (int) $user['id'], 10, 3600)) {
            audit_log('security.rate_limit', ['action' => 'payment_submit', 'user_id' => (int) $user['id']]);
            set_flash('error', 'Too many payment submissions. Please wait before trying again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        try {
            (new PaymentService())->submit(
                (int) ($_POST['booking_id'] ?? 0),
                (int) $user['id'],
                $_POST
            );
        } catch (Throwable $e) {
            error_log('Failed to submit payment: ' . $e->getMessage());
            set_flash('error', user_error_message($e, 'Payment could not be submitted. Please try again.'));
            $this->redirect(BASE_URL . '/?r=booking/customerList#history');
        }

        set_flash('success', 'Payment submitted for verification.');
        $this->redirect(BASE_URL . '/?r=booking/customerList#history');
    }

    public function verify(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER], 'staff')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for payment/verify.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=payment/index');
        }

        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        $status = trim((string) ($_POST['payment_status'] ?? ''));
        $allowed = [PAYMENT_STATUS_VERIFIED, PAYMENT_STATUS_REJECTED];

        if (!in_array($status, $allowed, true)) {
            set_flash('error', 'Payment status is invalid.');
            $this->redirect(BASE_URL . '/?r=payment/index');
        }

        try {
            $paymentService = new PaymentService();
            $actor = current_staff_user();
            if ($status === PAYMENT_STATUS_VERIFIED) {
                $paymentService->verify($paymentId, (int) $actor['id'], (string) $actor['role']);
            } else {
                $paymentService->reject($paymentId, (int) $actor['id'], (string) $actor['role']);
            }
        } catch (Throwable $e) {
            error_log('Failed to update payment: ' . $e->getMessage());
            set_flash('error', user_error_message($e, 'Payment could not be updated. Please try again.'));
            $this->redirect(BASE_URL . '/?r=payment/index');
        }
        set_flash('success', 'Payment status updated to ' . $status . '.');
        $this->redirect(BASE_URL . '/?r=payment/index&status=' . urlencode($status));
    }

    public function receipt(): void
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

        $user = current_user($portalScope);
        $bookingId = (int) ($_GET['booking_id'] ?? 0);
        $receipt = $portalScope === 'customer'
            ? Payment::getReceiptByBookingIdForCustomer($bookingId, (int) $user['id'])
            : Payment::getReceiptByBookingId($bookingId);
        if (!$receipt || $receipt['payment_status'] !== PAYMENT_STATUS_VERIFIED || empty($receipt['receipt_number'])) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Receipt not found']);
            return;
        }

        if ($portalScope === 'customer' && (int) $receipt['customer_id'] !== (int) $user['id']) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => 'Forbidden']);
            return;
        }

        $this->render('customer/receipt', [
            'pageTitle' => 'Receipt ' . $receipt['receipt_number'],
            'portalScope' => $portalScope,
            'receipt' => $receipt,
        ]);
    }
}
