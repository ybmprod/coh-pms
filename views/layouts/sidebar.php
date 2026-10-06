<?php
declare(strict_types=1);

$user = current_user();
$role = $user['role'] ?? '';

$isAdministrator = $role === ROLE_ADMINISTRATOR;
$isBookingOfficer = $role === ROLE_BOOKING_OFFICER;
$isRevenueOfficer = $role === ROLE_REVENUE_OFFICER;
$isCouncilManagement = $role === ROLE_COUNCIL_MANAGEMENT;
$isCustomer = $role === ROLE_CUSTOMER;

$canViewStaffArea = in_array(
    $role,
    [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT],
    true
);

$venueExpanded = str_contains(current_page_attr('venue/index'), 'page');
$pricingExpanded = str_contains(current_page_attr('pricingrule/index'), 'page');
$bookingExpanded = str_contains(current_page_attr('booking/index'), 'page');
$userExpanded = str_contains(current_page_attr('user/index'), 'page');
?>
<aside class="sidebar">
    <nav aria-label="Main navigation">
        <ul class="sidebar-nav">
            <li>
                <a href="<?php echo e(BASE_URL . '/?r=dashboard/index'); ?>"<?php echo current_page_attr('dashboard/index'); ?>>
                    <span class="nav-icon" aria-hidden="true">⌂</span>Dashboard
                </a>
            </li>



            <?php if ($isAdministrator || $isBookingOfficer): ?>
                <li class="accordion-item <?php echo $venueExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $venueExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">⌖</span>Venue management</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <a href="<?php echo e(BASE_URL . '/?r=venue/index#add'); ?>" class="tab-link" data-target="add-venue">Add venue</a>
                        <a href="<?php echo e(BASE_URL . '/?r=venue/index#list'); ?>" class="tab-link" data-target="list-venues">View venues</a>
                    </div>
                </li>
            <?php endif; ?>

            <?php if ($isAdministrator): ?>
                <li class="accordion-item <?php echo $pricingExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $pricingExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">◇</span>Pricing rules</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <a href="<?php echo e(BASE_URL . '/?r=pricingrule/index#add'); ?>" class="tab-link" data-target="add-rule">Add rule</a>
                        <a href="<?php echo e(BASE_URL . '/?r=pricingrule/index#list'); ?>" class="tab-link" data-target="list-rules">View rules</a>
                    </div>
                </li>
            <?php endif; ?>

            <?php if ($canViewStaffArea): ?>
                <li class="accordion-item <?php echo $bookingExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $bookingExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">▤</span>Bookings</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <a href="<?php echo e(BASE_URL . '/?r=booking/index'); ?>" class="tab-link">All bookings</a>
                    </div>
                </li>
                <?php
                $reportsExpanded = str_contains(current_page_attr('report/index'), 'page');
                ?>
                <li class="accordion-item <?php echo $reportsExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $reportsExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">▥</span>Reports</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT], true)): ?>
                            <a href="<?php echo e(BASE_URL . '/?r=report/index#summary'); ?>" class="tab-link" data-target="summary">Summary</a>
                        <?php endif; ?>
                        
                        <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_COUNCIL_MANAGEMENT], true)): ?>
                            <a href="<?php echo e(BASE_URL . '/?r=report/index#bookings'); ?>" class="tab-link" data-target="bookings">Bookings</a>
                            <a href="<?php echo e(BASE_URL . '/?r=report/index#usage'); ?>" class="tab-link" data-target="usage">Usage</a>
                        <?php endif; ?>

                        <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_REVENUE_OFFICER, ROLE_COUNCIL_MANAGEMENT], true)): ?>
                            <a href="<?php echo e(BASE_URL . '/?r=report/index#payments'); ?>" class="tab-link" data-target="payments">Payments</a>
                            <a href="<?php echo e(BASE_URL . '/?r=report/index#revenue'); ?>" class="tab-link" data-target="revenue">Revenue</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>

            <?php if (in_array($role, [ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER], true)): ?>
                <li>
                    <a href="<?php echo e(BASE_URL . '/?r=payment/index'); ?>"<?php echo current_page_attr('payment/index'); ?>>
                        <span class="nav-icon" aria-hidden="true">＄</span>Payments
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($isCustomer): ?>
                <li>
                    <a href="<?php echo e(BASE_URL . '/?r=venue/customerList'); ?>"<?php echo current_page_attr('venue/customerList'); ?>>
                        <span class="nav-icon" aria-hidden="true">⌖</span>Browse venues
                    </a>
                </li>
                <?php
                $myBookingsExpanded = str_contains(current_page_attr('booking/customerList'), 'page');
                ?>
                <li class="accordion-item <?php echo $myBookingsExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $myBookingsExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">&#x1F4C5;</span>My bookings</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <a href="<?php echo e(BASE_URL . '/?r=booking/customerList#request'); ?>" class="tab-link" data-target="request">Venue Booking Request</a>
                        <a href="<?php echo e(BASE_URL . '/?r=booking/customerList#history'); ?>" class="tab-link" data-target="history">My Booking History</a>
                    </div>
                </li>
            <?php endif; ?>

            <?php if ($isAdministrator): ?>
                <li class="accordion-item <?php echo $userExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $userExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">♙</span>User management</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <a href="<?php echo e(BASE_URL . '/?r=user/index#add'); ?>" class="tab-link" data-target="add-user">Add user</a>
                        <a href="<?php echo e(BASE_URL . '/?r=user/index#staff'); ?>" class="tab-link" data-target="list-staff">Staff List</a>
                        <a href="<?php echo e(BASE_URL . '/?r=user/index#customers'); ?>" class="tab-link" data-target="list-customers">Customers</a>
                    </div>
                </li>
            <?php endif; ?>

            <?php if (!$user): ?>
                <li><a href="<?php echo e(BASE_URL . '/?r=auth/login'); ?>"<?php echo current_page_attr('auth/login'); ?>>Log in</a></li>
                <li><a href="<?php echo e(BASE_URL . '/?r=auth/register'); ?>"<?php echo current_page_attr('auth/register'); ?>>Register</a></li>
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
