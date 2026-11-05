<?php
declare(strict_types=1);

// CHAPTER 5.3.6 - Reporting Implementation
class Report
{
    public static function getDashboardMetrics(): array
    {
        $sql = 'SELECT
                    (SELECT COUNT(*) FROM bookings WHERE booking_status = :pending_bookings) AS pending_bookings,
                    (SELECT COUNT(*) FROM payments WHERE payment_status = :pending_payments) AS pending_payments,
                    (SELECT COUNT(*) FROM bookings WHERE booking_date = :today_date) AS today_bookings,
                    (SELECT COALESCE(SUM(amount_paid), 0)
                     FROM payments
                     WHERE payment_status = :verified_revenue
                       AND verification_date >= DATE_FORMAT(CURRENT_DATE(), "%Y-%m-01")
                       AND verification_date < DATE_ADD(DATE_FORMAT(CURRENT_DATE(), "%Y-%m-01"), INTERVAL 1 MONTH)
                    ) AS verified_revenue_this_month,
                    (SELECT COUNT(*) FROM bookings) AS total_bookings,
                    (SELECT COUNT(*) FROM bookings
                     WHERE booking_status IN (:active_pending, :active_approved, :active_confirmed)) AS active_bookings,
                    (SELECT COUNT(*) FROM bookings
                     WHERE booking_date >= CURRENT_DATE()
                       AND booking_status IN (:upcoming_pending, :upcoming_approved, :upcoming_confirmed)) AS upcoming_bookings,
                    (SELECT COUNT(*) FROM venues) AS total_venues,
                    (SELECT COUNT(*) FROM venues WHERE venue_status = :venue_available) AS available_venues,
                    (SELECT COUNT(*) FROM users
                     WHERE role = :customer_role AND account_status = :active_account) AS total_customers,
                    (SELECT COALESCE(SUM(amount_paid), 0)
                     FROM payments
                     WHERE payment_status = :pending_amount) AS pending_payment_amount,
                    (SELECT COALESCE(SUM(amount_paid), 0)
                     FROM payments
                     WHERE payment_status = :last_month_verified
                       AND verification_date >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), "%Y-%m-01")
                       AND verification_date < DATE_FORMAT(CURRENT_DATE(), "%Y-%m-01")
                    ) AS last_month_revenue,
                    (SELECT COUNT(*) FROM bookings
                     WHERE booking_status = :overdue_status
                       AND created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)) AS overdue_approvals';
        $stmt = get_db_connection()->prepare($sql);
        $stmt->execute([
            ':pending_bookings' => BOOKING_STATUS_PENDING,
            ':pending_payments' => PAYMENT_STATUS_PENDING_VERIFICATION,
            ':today_date' => date('Y-m-d'),
            ':verified_revenue' => PAYMENT_STATUS_VERIFIED,
            ':active_pending' => BOOKING_STATUS_PENDING,
            ':active_approved' => BOOKING_STATUS_APPROVED,
            ':active_confirmed' => BOOKING_STATUS_CONFIRMED,
            ':upcoming_pending' => BOOKING_STATUS_PENDING,
            ':upcoming_approved' => BOOKING_STATUS_APPROVED,
            ':upcoming_confirmed' => BOOKING_STATUS_CONFIRMED,
            ':venue_available' => VENUE_STATUS_AVAILABLE,
            ':customer_role' => ROLE_CUSTOMER,
            ':active_account' => ACCOUNT_STATUS_ACTIVE,
            ':pending_amount' => PAYMENT_STATUS_PENDING_VERIFICATION,
            ':last_month_verified' => PAYMENT_STATUS_VERIFIED,
            ':overdue_status' => BOOKING_STATUS_PENDING,
        ]);

        $metrics = $stmt->fetch() ?: [];
        return [
            'pending_bookings' => (int) ($metrics['pending_bookings'] ?? 0),
            'pending_payments' => (int) ($metrics['pending_payments'] ?? 0),
            'today_bookings' => (int) ($metrics['today_bookings'] ?? 0),
            'verified_revenue_this_month' => (float) ($metrics['verified_revenue_this_month'] ?? 0),
            'total_bookings' => (int) ($metrics['total_bookings'] ?? 0),
            'active_bookings' => (int) ($metrics['active_bookings'] ?? 0),
            'upcoming_bookings' => (int) ($metrics['upcoming_bookings'] ?? 0),
            'total_venues' => (int) ($metrics['total_venues'] ?? 0),
            'available_venues' => (int) ($metrics['available_venues'] ?? 0),
            'total_customers' => (int) ($metrics['total_customers'] ?? 0),
            'pending_payment_amount' => (float) ($metrics['pending_payment_amount'] ?? 0),
            'last_month_revenue' => (float) ($metrics['last_month_revenue'] ?? 0),
            'overdue_approvals' => (int) ($metrics['overdue_approvals'] ?? 0),
        ];
    }

    public static function getUpcomingBookings(int $limit = 5): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_id, b.booking_reference, b.booking_date, b.start_time, b.end_time,
                       b.booking_status, b.total_charge, v.venue_name, v.location,
                       u.full_name AS customer_name
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                JOIN users u ON u.user_id = b.customer_id
                WHERE b.booking_date >= CURRENT_DATE()
                  AND b.booking_status IN (:pending, :approved, :confirmed)
                ORDER BY b.booking_date ASC, b.start_time ASC
                LIMIT :limit_rows';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':pending', BOOKING_STATUS_PENDING);
        $stmt->bindValue(':approved', BOOKING_STATUS_APPROVED);
        $stmt->bindValue(':confirmed', BOOKING_STATUS_CONFIRMED);
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getTodaySchedule(int $limit = 6): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_id, b.booking_reference, b.start_time, b.end_time, b.booking_status,
                       b.expected_attendance, v.venue_name, u.full_name AS customer_name
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                JOIN users u ON u.user_id = b.customer_id
                WHERE b.booking_date = CURRENT_DATE()
                  AND b.booking_status IN (:pending, :approved, :confirmed, :completed)
                ORDER BY b.start_time ASC
                LIMIT :limit_rows';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':pending', BOOKING_STATUS_PENDING);
        $stmt->bindValue(':approved', BOOKING_STATUS_APPROVED);
        $stmt->bindValue(':confirmed', BOOKING_STATUS_CONFIRMED);
        $stmt->bindValue(':completed', BOOKING_STATUS_COMPLETED);
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getRecentBookings(int $limit = 5): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_id, b.booking_reference, b.booking_date, b.start_time, b.end_time,
                       b.booking_status, b.total_charge, b.created_at, v.venue_name,
                       u.full_name AS customer_name
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                JOIN users u ON u.user_id = b.customer_id
                ORDER BY b.created_at DESC, b.booking_id DESC
                LIMIT :limit_rows';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getTopVenues(int $limit = 5): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT v.venue_id, v.venue_name, v.venue_type, v.location, v.capacity,
                       v.standard_price, v.image_path,
                       COUNT(b.booking_id) AS booking_count,
                       COALESCE(SUM(b.total_charge), 0) AS booked_value
                FROM venues v
                JOIN bookings b ON b.venue_id = v.venue_id
                WHERE b.booking_status IN (:approved, :confirmed, :completed)
                GROUP BY v.venue_id, v.venue_name, v.venue_type, v.location, v.capacity,
                         v.standard_price, v.image_path
                ORDER BY booking_count DESC, booked_value DESC, v.venue_name ASC
                LIMIT :limit_rows';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':approved', BOOKING_STATUS_APPROVED);
        $stmt->bindValue(':confirmed', BOOKING_STATUS_CONFIRMED);
        $stmt->bindValue(':completed', BOOKING_STATUS_COMPLETED);
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            $row['booking_count'] = (int) $row['booking_count'];
            $row['booked_value'] = round((float) $row['booked_value'], 2);
            return $row;
        }, $stmt->fetchAll());
    }

    public static function getMonthlyRevenueSeries(int $months = 6): array
    {
        $months = max(1, min(24, $months));
        $pdo = get_db_connection();
        $sql = 'SELECT DATE_FORMAT(verification_date, "%Y-%m") AS month_key,
                       COALESCE(SUM(amount_paid), 0) AS total_amount,
                       COUNT(*) AS payment_count
                FROM payments
                WHERE payment_status = :verified
                  AND verification_date >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL :month_offset MONTH), "%Y-%m-01")
                GROUP BY month_key
                ORDER BY month_key ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':verified', PAYMENT_STATUS_VERIFIED);
        $stmt->bindValue(':month_offset', $months - 1, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getCustomerDashboardMetrics(int $customerId): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT COUNT(*) AS total_bookings,
                       COALESCE(SUM(booking_status = :pending), 0) AS pending_bookings,
                       COALESCE(SUM(booking_status IN (:approved, :confirmed)), 0) AS active_bookings,
                       COALESCE(SUM(booking_status = :completed), 0) AS completed_bookings,
                       COALESCE(SUM(booking_status = :cancelled), 0) AS cancelled_bookings,
                       COALESCE(SUM(CASE WHEN booking_status = :completed_value THEN total_charge ELSE 0 END), 0) AS completed_value
                FROM bookings
                WHERE customer_id = :customer_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pending' => BOOKING_STATUS_PENDING,
            ':approved' => BOOKING_STATUS_APPROVED,
            ':confirmed' => BOOKING_STATUS_CONFIRMED,
            ':completed' => BOOKING_STATUS_COMPLETED,
            ':cancelled' => BOOKING_STATUS_CANCELLED,
            ':completed_value' => BOOKING_STATUS_COMPLETED,
            ':customer_id' => $customerId,
        ]);
        $metrics = $stmt->fetch() ?: [];

        $spendSql = 'SELECT
                        COALESCE(SUM(CASE WHEN p.payment_status = :verified_spend THEN p.amount_paid ELSE 0 END), 0) AS verified_spend,
                        COALESCE(SUM(CASE WHEN p.payment_status = :pending_spend THEN p.amount_paid ELSE 0 END), 0) AS pending_spend
                     FROM payments p
                     JOIN bookings b ON b.booking_id = p.booking_id
                     WHERE b.customer_id = :spend_customer_id';
        $spendStmt = $pdo->prepare($spendSql);
        $spendStmt->execute([
            ':verified_spend' => PAYMENT_STATUS_VERIFIED,
            ':pending_spend' => PAYMENT_STATUS_PENDING_VERIFICATION,
            ':spend_customer_id' => $customerId,
        ]);
        $spend = $spendStmt->fetch() ?: [];

        return [
            'total_bookings' => (int) ($metrics['total_bookings'] ?? 0),
            'pending_bookings' => (int) ($metrics['pending_bookings'] ?? 0),
            'active_bookings' => (int) ($metrics['active_bookings'] ?? 0),
            'completed_bookings' => (int) ($metrics['completed_bookings'] ?? 0),
            'cancelled_bookings' => (int) ($metrics['cancelled_bookings'] ?? 0),
            'completed_value' => round((float) ($metrics['completed_value'] ?? 0), 2),
            'verified_spend' => round((float) ($spend['verified_spend'] ?? 0), 2),
            'pending_spend' => round((float) ($spend['pending_spend'] ?? 0), 2),
        ];
    }

    public static function getCustomerUpcomingBooking(int $customerId): ?array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_id, b.booking_reference, b.booking_date, b.start_time, b.end_time,
                       b.booking_status, b.total_charge, v.venue_name, v.location,
                       (SELECT p.payment_status FROM payments p
                        WHERE p.booking_id = b.booking_id
                        ORDER BY p.payment_id DESC LIMIT 1) AS payment_status
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE b.customer_id = :customer_id
                  AND b.booking_date >= CURRENT_DATE()
                  AND b.booking_status IN (:pending, :approved, :confirmed)
                ORDER BY b.booking_date ASC, b.start_time ASC
                LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => $customerId,
            ':pending' => BOOKING_STATUS_PENDING,
            ':approved' => BOOKING_STATUS_APPROVED,
            ':confirmed' => BOOKING_STATUS_CONFIRMED,
        ]);
        $booking = $stmt->fetch();

        return $booking ?: null;
    }

    public static function getCustomerRecentBookings(int $customerId, int $limit = 4): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_id, b.booking_reference, b.booking_date, b.start_time, b.end_time,
                       b.booking_status, b.total_charge, v.venue_name,
                       (SELECT p.payment_status FROM payments p
                        WHERE p.booking_id = b.booking_id
                        ORDER BY p.payment_id DESC LIMIT 1) AS payment_status
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE b.customer_id = :customer_id
                ORDER BY b.created_at DESC, b.booking_id DESC
                LIMIT :limit_rows';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getBookingStatusCounts(array $filters = []): array
    {
        [$whereClause, $params] = self::buildBookingFilters($filters);
        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_status, COUNT(*) AS booking_count
                FROM bookings b
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                WHERE 1=1' . $whereClause . '
                GROUP BY b.booking_status';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function getVerifiedRevenueTotal(array $filters = []): float
    {
        [$whereClause, $params] = self::buildPaymentFilters($filters, 'p.verification_date');
        $pdo = get_db_connection();
        $sql = 'SELECT COALESCE(SUM(p.amount_paid), 0)
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE p.payment_status = :verified' . $whereClause;
        $params[':verified'] = PAYMENT_STATUS_VERIFIED;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    public static function getPendingVerificationTotal(array $filters = []): float
    {
        [$whereClause, $params] = self::buildPaymentFilters($filters, 'p.payment_date');
        $pdo = get_db_connection();
        $sql = 'SELECT COALESCE(SUM(p.amount_paid), 0)
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE p.payment_status = :pending' . $whereClause;
        $params[':pending'] = PAYMENT_STATUS_PENDING_VERIFICATION;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }

    public static function getBookingReport(array $filters = [], ?int $limit = null, int $offset = 0): array
    {
        [$whereClause, $params] = self::buildBookingFilters($filters);

        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_reference, u.full_name AS customer_name, v.venue_name, v.venue_type, b.event_type, b.event_type_charge, b.booking_date,
                       b.start_time, b.end_time, b.booking_status, b.total_charge
                FROM bookings b
                LEFT JOIN users u ON u.user_id = b.customer_id
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                WHERE 1=1' . $whereClause . '
                ORDER BY b.booking_date DESC, b.start_time ASC';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit_rows OFFSET :offset_rows';
        }

        $stmt = $pdo->prepare($sql);
        self::bindReportValues($stmt, $params);
        if ($limit !== null) {
            $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset_rows', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Totals always cover the whole filtered set, never just the current page,
     * so the report footer and CSV export stay correct when paging is enabled.
     */
    public static function getBookingReportTotals(array $filters = []): array
    {
        [$whereClause, $params] = self::buildBookingFilters($filters);

        $pdo = get_db_connection();
        $sql = 'SELECT COUNT(*) AS count_rows, COALESCE(SUM(b.total_charge), 0) AS total_charge
                FROM bookings b
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                WHERE 1=1' . $whereClause;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'count' => (int) ($row['count_rows'] ?? 0),
            'total_charge' => round((float) ($row['total_charge'] ?? 0), 2),
        ];
    }

    public static function getUsageReport(array $filters = []): array
    {
        $joinConditions = [
            'b.venue_id = v.venue_id',
            'b.booking_status IN (:usage_approved, :usage_confirmed, :usage_completed)',
        ];
        $params = [
            ':usage_approved' => BOOKING_STATUS_APPROVED,
            ':usage_confirmed' => BOOKING_STATUS_CONFIRMED,
            ':usage_completed' => BOOKING_STATUS_COMPLETED,
        ];

        if (!empty($filters['start_date'])) {
            $joinConditions[] = 'b.booking_date >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $joinConditions[] = 'b.booking_date <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['booking_status'])) {
            $joinConditions[] = 'b.booking_status = :booking_status';
            $params[':booking_status'] = $filters['booking_status'];
        }

        $pdo = get_db_connection();
        $sql = 'SELECT v.venue_name,
                       v.venue_type,
                       COUNT(b.booking_id) AS booking_count,
                       COALESCE(ROUND(SUM(
                           CASE
                               WHEN b.end_time > b.start_time THEN TIMESTAMPDIFF(MINUTE, CONCAT(b.booking_date, " ", b.start_time), CONCAT(b.booking_date, " ", b.end_time))
                               WHEN b.end_time < b.start_time THEN TIMESTAMPDIFF(MINUTE, CONCAT(b.booking_date, " ", b.start_time), CONCAT(DATE_ADD(b.booking_date, INTERVAL 1 DAY), " ", b.end_time))
                               ELSE 0
                           END / 60
                       ), 2), 0) AS total_hours
                FROM venues v
                LEFT JOIN bookings b ON ' . implode(' AND ', $joinConditions);
        [$venueWhere, $venueParams] = self::buildVenueFilters($filters, 'v');
        $sql .= $venueWhere . '
                GROUP BY v.venue_id, v.venue_name
                ORDER BY total_hours DESC, booking_count DESC, v.venue_name ASC';
        $params = array_merge($params, $venueParams);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function getPaymentReport(array $filters = [], ?int $limit = null, int $offset = 0): array
    {
        [$whereClause, $params] = self::buildPaymentFilters($filters, 'p.payment_date');

        $pdo = get_db_connection();
        $sql = 'SELECT p.payment_id, b.booking_reference, u.full_name AS customer_name, v.venue_name,
                       p.amount_paid, p.payment_method, p.transaction_reference, p.payment_status
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN users u ON u.user_id = b.customer_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE 1=1' . $whereClause . '
                ORDER BY p.payment_date DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit_rows OFFSET :offset_rows';
        }

        $stmt = $pdo->prepare($sql);
        self::bindReportValues($stmt, $params);
        if ($limit !== null) {
            $stmt->bindValue(':limit_rows', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset_rows', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getPaymentReportTotals(array $filters = []): array
    {
        [$whereClause, $params] = self::buildPaymentFilters($filters, 'p.payment_date');

        $pdo = get_db_connection();
        $sql = 'SELECT COUNT(*) AS count_rows, COALESCE(SUM(p.amount_paid), 0) AS amount
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE 1=1' . $whereClause;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'count' => (int) ($row['count_rows'] ?? 0),
            'amount' => round((float) ($row['amount'] ?? 0), 2),
        ];
    }

    public static function getRevenueReport(array $filters = []): array
    {
        [$whereClause, $params] = self::buildPaymentFilters($filters, 'p.verification_date');

        $pdo = get_db_connection();
        $sql = 'SELECT v.venue_name,
                       COUNT(p.payment_id) AS payment_count,
                       SUM(p.amount_paid) AS total_amount
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE p.payment_status = :verified' . $whereClause . '
                GROUP BY v.venue_id, v.venue_name
                ORDER BY total_amount DESC';

        $stmt = $pdo->prepare($sql);
        $params[':verified'] = PAYMENT_STATUS_VERIFIED;
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function getRevenueByMonth(array $filters = []): array
    {
        [$whereClause, $params] = self::buildPaymentFilters($filters, 'p.verification_date');
        $pdo = get_db_connection();
        $sql = 'SELECT DATE_FORMAT(p.verification_date, "%Y-%m") AS revenue_month,
                       COUNT(p.payment_id) AS payment_count,
                       COALESCE(SUM(p.amount_paid), 0) AS total_amount
                FROM payments p
                JOIN bookings b ON b.booking_id = p.booking_id
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE p.payment_status = :verified' . $whereClause . '
                GROUP BY DATE_FORMAT(p.verification_date, "%Y-%m")
                ORDER BY revenue_month';
        $params[':verified'] = PAYMENT_STATUS_VERIFIED;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function getUsageDateBounds(array $filters = []): array
    {
        $conditions = ['b.booking_status IN (:approved, :confirmed, :completed)'];
        $params = [
            ':approved' => BOOKING_STATUS_APPROVED,
            ':confirmed' => BOOKING_STATUS_CONFIRMED,
            ':completed' => BOOKING_STATUS_COMPLETED,
        ];
        if (!empty($filters['start_date'])) {
            $conditions[] = 'b.booking_date >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = 'b.booking_date <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['booking_status'])) {
            $conditions[] = 'b.booking_status = :booking_status';
            $params[':booking_status'] = $filters['booking_status'];
        }
        [$venueWhere, $venueParams] = self::buildVenueFilters($filters, 'v');
        if ($venueWhere !== '') {
            $conditions[] = substr($venueWhere, strlen(' WHERE '));
            $params = array_merge($params, $venueParams);
        }

        $pdo = get_db_connection();
        $sql = 'SELECT MIN(b.booking_date) AS start_date, MAX(b.booking_date) AS end_date
                FROM bookings b
                JOIN venues v ON v.venue_id = b.venue_id
                WHERE ' . implode(' AND ', $conditions);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $bounds = $stmt->fetch();
        return [
            'start_date' => $bounds['start_date'] ?? null,
            'end_date' => $bounds['end_date'] ?? null,
        ];
    }

    public static function getVenueOptions(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT venue_id, venue_name FROM venues ORDER BY venue_name ASC';
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    private static function bindReportValues(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }

    private static function buildBookingFilters(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['start_date'])) {
            $clauses[] = ' b.booking_date >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $clauses[] = ' b.booking_date <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['venue_id'])) {
            $clauses[] = ' b.venue_id = :venue_id';
            $params[':venue_id'] = (int) $filters['venue_id'];
        }

        if (!empty($filters['venue_type'])) {
            $clauses[] = ' v.venue_type = :venue_type';
            $params[':venue_type'] = $filters['venue_type'];
        }

        if (!empty($filters['booking_status'])) {
            $clauses[] = ' b.booking_status = :booking_status';
            $params[':booking_status'] = $filters['booking_status'];
        }

        $whereClause = $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses);

        return [$whereClause, $params];
    }

    private static function buildPaymentFilters(array $filters, string $dateColumn): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['start_date'])) {
            $clauses[] = ' ' . $dateColumn . ' >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $clauses[] = ' ' . $dateColumn . ' < DATE_ADD(:end_date, INTERVAL 1 DAY)';
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['venue_id'])) {
            $clauses[] = ' b.venue_id = :venue_id';
            $params[':venue_id'] = (int) $filters['venue_id'];
        }

        if (!empty($filters['venue_type'])) {
            $clauses[] = ' v.venue_type = :venue_type';
            $params[':venue_type'] = $filters['venue_type'];
        }

        if (!empty($filters['payment_status'])) {
            $clauses[] = ' p.payment_status = :payment_status';
            $params[':payment_status'] = $filters['payment_status'];
        }

        if (!empty($filters['booking_status'])) {
            $clauses[] = ' b.booking_status = :booking_status';
            $params[':booking_status'] = $filters['booking_status'];
        }

        $whereClause = $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses);

        return [$whereClause, $params];
    }

    private static function buildVenueFilters(array $filters, string $alias): array
    {
        $clauses = [];
        $params = [];
        if (!empty($filters['venue_id'])) {
            $clauses[] = $alias . '.venue_id = :venue_id';
            $params[':venue_id'] = (int) $filters['venue_id'];
        }
        if (!empty($filters['venue_type'])) {
            $clauses[] = $alias . '.venue_type = :venue_type';
            $params[':venue_type'] = $filters['venue_type'];
        }

        return [$clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses), $params];
    }

    public static function venueExists(int $venueId): bool
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT 1 FROM venues WHERE venue_id = :venue_id LIMIT 1');
        $stmt->execute([':venue_id' => $venueId]);

        return $stmt->fetchColumn() !== false;
    }
}
