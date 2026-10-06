<?php
declare(strict_types=1);

// CHAPTER 5.3.1 - User Authentication and Access Control
class DashboardController extends Controller
{
    public function index(): void
    {
        if (!$this->requireAuth()) {
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        $user = current_user();
        $this->render('staff/dashboard', [
            'pageTitle' => 'Dashboard',
            'user' => $user,
            'dashboardCounts' => $user['role'] === ROLE_CUSTOMER
                ? []
                : (new ReportService())->getDashboardSummary((string) $user['role']),
        ]);
    }

}
