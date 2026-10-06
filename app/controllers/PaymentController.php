<?php
declare(strict_types=1);

// CHAPTER 5.3.5 - Payment Recording and Verification
class PaymentController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER])) {
            return;
        }

        $status = trim((string) ($_GET['status'] ?? PAYMENT_STATUS_PENDING_VERIFICATION));
        $allowedStatuses = [
            PAYMENT_STATUS_PENDING_VERIFICATION,
            PAYMENT_STATUS_VERIFIED,
            PAYMENT_STATUS_REJECTED,
        ];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = PAYMENT_STATUS_PENDING_VERIFICATION;
        }

        $this->render('staff/payments', [
            'pageTitle' => 'Payment Verification',
            'payments' => Payment::getByStatus($status),
            'selectedStatus' => $status,
        ]);
    }

    public function submit(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for payment/submit.');
            set_flash('error', 'Your session has expired. Please try again.');
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        $user = current_user();
        try {
            (new PaymentService())->submit(
                (int) ($_POST['booking_id'] ?? 0),
                (int) $user['id'],
                $_POST
            );
        } catch (Throwable $e) {
            set_flash('error', $e->getMessage());
            $this->redirect(BASE_URL . '/?r=booking/customerList');
        }

        set_flash('success', 'Payment submitted for verification.');
        $this->redirect(BASE_URL . '/?r=booking/customerList');
    }

    public function verify(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER])) {
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
            $actor = current_user();
            if ($status === PAYMENT_STATUS_VERIFIED) {
                $paymentService->verify($paymentId, (int) $actor['id'], (string) $actor['role']);
            } else {
                $paymentService->reject($paymentId, (int) $actor['id'], (string) $actor['role']);
            }
        } catch (Throwable $e) {
            set_flash('error', $e->getMessage());
            $this->redirect(BASE_URL . '/?r=payment/index');
        }
        set_flash('success', 'Payment status updated to ' . $status . '.');
        $this->redirect(BASE_URL . '/?r=payment/index&status=' . urlencode($status));
    }

    public function receipt(): void
    {
        if (!$this->requireAuth()) {
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        $user = current_user();
        if ($user['role'] !== ROLE_CUSTOMER && $this->denyIfNotRole([
            ROLE_ADMINISTRATOR,
            ROLE_BOOKING_OFFICER,
            ROLE_REVENUE_OFFICER,
            ROLE_COUNCIL_MANAGEMENT,
        ])) {
            return;
        }

        $bookingId = (int) ($_GET['booking_id'] ?? 0);
        $receipt = Payment::getReceiptByBookingId($bookingId);
        if (!$receipt || $receipt['payment_status'] !== PAYMENT_STATUS_VERIFIED || empty($receipt['receipt_number'])) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Receipt not found']);
            return;
        }

        if ($user['role'] === ROLE_CUSTOMER && (int) $receipt['customer_id'] !== (int) $user['id']) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => 'Forbidden']);
            return;
        }

        $this->render('customer/receipt', [
            'pageTitle' => 'Receipt ' . $receipt['receipt_number'],
            'receipt' => $receipt,
        ]);
    }
}
