<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class DashboardController extends Controller
{
    public function index(): void
    {
        if (!is_logged_in('staff') && is_logged_in('customer') && ($_GET['portal'] ?? '') !== 'staff') {
            $this->redirect(BASE_URL . '/?r=dashboard/customer');
        }

        if ($this->denyIfNotRole([
            ROLE_ADMINISTRATOR,
            ROLE_BOOKING_OFFICER,
            ROLE_REVENUE_OFFICER,
            ROLE_COUNCIL_MANAGEMENT,
        ], 'staff')) {
            return;
        }

        $user = current_staff_user();
        $this->render('staff/dashboard', [
            'pageTitle' => 'Council Dashboard',
            'portalScope' => 'staff',
            'user' => $user,
            'dashboardCounts' => (new ReportService())->getDashboardSummary((string) $user['role']),
        ]);
    }

    public function customer(): void
    {
        if ($this->denyIfNotRole([ROLE_CUSTOMER], 'customer')) {
            return;
        }

        $user = current_customer_user();
        $this->render('staff/dashboard', [
            'pageTitle' => 'My Dashboard',
            'portalScope' => 'customer',
            'user' => $user,
            'dashboardCounts' => [],
        ]);
    }
}
