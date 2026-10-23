<?php
declare(strict_types=1);

$isStaffPortal = is_staff_portal_request();
$user = $isStaffPortal ? current_staff_user() : current_customer_user();
$role = $user['role'] ?? '';

$isAdministrator = $isStaffPortal && $role === ROLE_ADMINISTRATOR;
$isBookingOfficer = $isStaffPortal && $role === ROLE_BOOKING_OFFICER;
$isRevenueOfficer = $isStaffPortal && $role === ROLE_REVENUE_OFFICER;
$isCouncilManagement = $isStaffPortal && $role === ROLE_COUNCIL_MANAGEMENT;
$isCustomer = !$isStaffPortal && $role === ROLE_CUSTOMER;

$canViewStaffArea = $isStaffPortal && in_array(
    $role,
    [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT],
    true
);

$venueExpanded = str_contains(current_page_attr('venue/index'), 'page');
$pricingExpanded = str_contains(current_page_attr('pricingrule/index'), 'page');
$bookingExpanded = str_contains(current_page_attr('booking/index'), 'page');
$userExpanded = str_contains(current_page_attr('user/index'), 'page');
$dashboardRoute = $isStaffPortal ? 'dashboard/index' : 'dashboard/customer';
?>
<aside class="sidebar" aria-label="Workspace navigation">
    <nav aria-label="Main navigation">
        <ul class="sidebar-nav">
            <li>
                <a href="<?php echo e(BASE_URL . '/?r=' . $dashboardRoute); ?>"<?php echo current_page_attr($dashboardRoute); ?>>
                    <i class="fi fi-sr-apps nav-icon" aria-hidden="true"></i>Dashboard
                </a>
            </li>

            <?php if ($isStaffPortal): ?>
                <?php if ($isAdministrator || $isBookingOfficer): ?>
                    <li class="accordion-item <?php echo $venueExpanded ? 'expanded' : ''; ?>">
                        <button class="accordion-header" type="button" aria-expanded="<?php echo $venueExpanded ? 'true' : 'false'; ?>">
                            <span><i class="fi fi-sr-building nav-icon" aria-hidden="true"></i>Venue management</span>
                            <span class="accordion-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="accordion-content">
                            <a href="<?php echo e(BASE_URL . '/?r=venue/index#add'); ?>" class="tab-link" data-target="add"><i class="fi fi-sr-plus" aria-hidden="true"></i>Add venue</a>
                            <a href="<?php echo e(BASE_URL . '/?r=venue/index#list'); ?>" class="tab-link" data-target="list"><i class="fi fi-sr-list" aria-hidden="true"></i>View venues</a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if ($isAdministrator): ?>
                    <li class="accordion-item <?php echo $pricingExpanded ? 'expanded' : ''; ?>">
                        <button class="accordion-header" type="button" aria-expanded="<?php echo $pricingExpanded ? 'true' : 'false'; ?>">
                            <span><i class="fi fi-sr-tags nav-icon" aria-hidden="true"></i>Pricing rules</span>
                            <span class="accordion-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="accordion-content">
                            <a href="<?php echo e(BASE_URL . '/?r=pricingrule/index#add'); ?>" class="tab-link" data-target="add"><i class="fi fi-sr-plus" aria-hidden="true"></i>Add rule</a>
                            <a href="<?php echo e(BASE_URL . '/?r=pricingrule/index#list'); ?>" class="tab-link" data-target="list"><i class="fi fi-sr-settings-sliders" aria-hidden="true"></i>View rules</a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if ($canViewStaffArea): ?>
                    <li class="accordion-item <?php echo $bookingExpanded ? 'expanded' : ''; ?>">
                        <button class="accordion-header" type="button" aria-expanded="<?php echo $bookingExpanded ? 'true' : 'false'; ?>">
                            <span><i class="fi fi-sr-calendar-clock nav-icon" aria-hidden="true"></i>Bookings</span>
                            <span class="accordion-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="accordion-content">
                            <a href="<?php echo e(BASE_URL . '/?r=booking/index'); ?>" class="tab-link"><i class="fi fi-sr-calendar-check" aria-hidden="true"></i>All bookings</a>
                        </div>
                    </li>
                    <?php
                    $reportsExpanded = str_contains(current_page_attr('report/index'), 'page');
                    ?>
                    <li class="accordion-item <?php echo $reportsExpanded ? 'expanded' : ''; ?>">
                        <button class="accordion-header" type="button" aria-expanded="<?php echo $reportsExpanded ? 'true' : 'false'; ?>">
                            <span><i class="fi fi-sr-chart-histogram nav-icon" aria-hidden="true"></i>Reports</span>
                            <span class="accordion-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="accordion-content">
                            <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT], true)): ?>
                                <a href="<?php echo e(BASE_URL . '/?r=report/index#summary'); ?>" class="tab-link" data-target="summary"><i class="fi fi-sr-chart-pie-alt" aria-hidden="true"></i>Summary</a>
                            <?php endif; ?>

                            <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_COUNCIL_MANAGEMENT], true)): ?>
                                <a href="<?php echo e(BASE_URL . '/?r=report/index#bookings'); ?>" class="tab-link" data-target="bookings"><i class="fi fi-sr-calendar" aria-hidden="true"></i>Bookings</a>
                                <a href="<?php echo e(BASE_URL . '/?r=report/index#usage'); ?>" class="tab-link" data-target="usage"><i class="fi fi-sr-clock" aria-hidden="true"></i>Usage</a>
                            <?php endif; ?>

                            <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT], true)): ?>
                                <a href="<?php echo e(BASE_URL . '/?r=report/index#payments'); ?>" class="tab-link" data-target="payments"><i class="fi fi-sr-credit-card" aria-hidden="true"></i>Payments</a>
                                <a href="<?php echo e(BASE_URL . '/?r=report/index#revenue'); ?>" class="tab-link" data-target="revenue"><i class="fi fi-sr-coins" aria-hidden="true"></i>Revenue</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER], true)): ?>
                    <li>
                        <a href="<?php echo e(BASE_URL . '/?r=payment/index'); ?>"<?php echo current_page_attr('payment/index'); ?>>
                            <i class="fi fi-sr-credit-card nav-icon" aria-hidden="true"></i>Payments
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isAdministrator): ?>
                    <li class="accordion-item <?php echo $userExpanded ? 'expanded' : ''; ?>">
                        <button class="accordion-header" type="button" aria-expanded="<?php echo $userExpanded ? 'true' : 'false'; ?>">
                            <span><i class="fi fi-sr-users-alt nav-icon" aria-hidden="true"></i>User management</span>
                            <span class="accordion-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="accordion-content">
                            <a href="<?php echo e(BASE_URL . '/?r=user/index#add'); ?>" class="tab-link" data-target="add"><i class="fi fi-sr-user-add" aria-hidden="true"></i>Add user</a>
                            <a href="<?php echo e(BASE_URL . '/?r=user/index#staff'); ?>" class="tab-link" data-target="staff"><i class="fi fi-sr-briefcase" aria-hidden="true"></i>Staff List</a>
                            <a href="<?php echo e(BASE_URL . '/?r=user/index#customers'); ?>" class="tab-link" data-target="customers"><i class="fi fi-sr-user" aria-hidden="true"></i>Customers</a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if (!$user): ?>
                    <li><a href="<?php echo e(BASE_URL . '/?r=auth/staffLogin'); ?>"<?php echo current_page_attr('auth/staffLogin'); ?>><i class="fi fi-sr-sign-in-alt nav-icon" aria-hidden="true"></i>Staff Login</a></li>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($isCustomer): ?>
                    <li>
                        <a href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>"<?php echo current_page_attr('venue/customerList'); ?>>
                            <i class="fi fi-sr-building nav-icon" aria-hidden="true"></i>Browse venues
                        </a>
                    </li>
                    <?php
                    $myBookingsExpanded = str_contains(current_page_attr('booking/customerList'), 'page');
                    ?>
                    <li class="accordion-item <?php echo $myBookingsExpanded ? 'expanded' : ''; ?>">
                        <button class="accordion-header" type="button" aria-expanded="<?php echo $myBookingsExpanded ? 'true' : 'false'; ?>">
                            <span><i class="fi fi-sr-calendar-clock nav-icon" aria-hidden="true"></i>My bookings</span>
                            <span class="accordion-chevron" aria-hidden="true"></span>
                        </button>
                        <div class="accordion-content">
                            <a href="<?php echo e(BASE_URL . '/?r=booking/customerList#request'); ?>" class="tab-link" data-target="request"><i class="fi fi-sr-plus" aria-hidden="true"></i>Venue Booking Request</a>
                            <a href="<?php echo e(BASE_URL . '/?r=booking/customerList#history'); ?>" class="tab-link" data-target="history"><i class="fi fi-sr-list" aria-hidden="true"></i>My Booking History</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>"<?php echo current_page_attr('auth/login'); ?>><i class="fi fi-sr-sign-in-alt nav-icon" aria-hidden="true"></i>Log in</a></li>
                    <li><a href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>"<?php echo current_page_attr('auth/register'); ?>><i class="fi fi-sr-user-add nav-icon" aria-hidden="true"></i>Register</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const accordionItems = document.querySelectorAll('.accordion-item');

    accordionItems.forEach(function (item) {
        const button = item.querySelector('.accordion-header');
        if (!button) return;

        button.addEventListener('click', function () {
            const shouldExpand = !item.classList.contains('expanded');

            accordionItems.forEach(function (otherItem) {
                otherItem.classList.remove('expanded');
                const otherButton = otherItem.querySelector('.accordion-header');
                if (otherButton) otherButton.setAttribute('aria-expanded', 'false');
            });

            if (shouldExpand) {
                item.classList.add('expanded');
                button.setAttribute('aria-expanded', 'true');
            }
        });
    });

    function switchTab(hash) {
        if (!hash) return;

        const currentPath = window.location.search.split('#')[0] || 'default';
        sessionStorage.setItem('activeTab_' + currentPath, hash);

        const targetId = hash.replace('#', '');
        const sections = document.querySelectorAll('.tab-section');
        let matched = false;

        sections.forEach(function (section) {
            const isMatch = section.id === targetId || section.dataset.tab === targetId;
            section.classList.toggle('active', isMatch);
            if (isMatch) matched = true;
        });

        document.querySelectorAll('.accordion-content a').forEach(function (link) {
            const linkHash = link.getAttribute('href')?.split('#')[1] || '';
            link.classList.toggle('is-active-tab', linkHash === targetId);
        });

        if (!matched && sections.length) {
            sections.forEach(function (section, index) {
                section.classList.toggle('active', index === 0);
            });
        }
    }

    if (document.querySelector('.tab-section')) {
        const currentPath = window.location.search.split('#')[0] || 'default';
        const savedTab = sessionStorage.getItem('activeTab_' + currentPath);
        switchTab(window.location.hash || savedTab || '#list');
        window.addEventListener('hashchange', function () {
            switchTab(window.location.hash);
        });
    }
});
</script>
