<?php
declare(strict_types=1);

// CHAPTER 5.3.6 - Reporting Implementation
class Report
{
    public static function getDashboardMetrics(): array
    {
        $sql = 'SELECT
                    (SELECT COUNT(*) FROM bookings WHERE booking_status = :pending_status) AS pending_bookings,
                    (SELECT COUNT(*) FROM payments WHERE payment_status = :pending_payment_status) AS pending_payments,
                    (SELECT COUNT(*) FROM bookings WHERE booking_date = :today_date) AS today_bookings,
                    (SELECT COALESCE(SUM(amount_paid), 0)
                     FROM payments
                     WHERE payment_status = :verified_status
                       AND verification_date >= DATE_FORMAT(CURRENT_DATE(), "%Y-%m-01")
                       AND verification_date < DATE_ADD(DATE_FORMAT(CURRENT_DATE(), "%Y-%m-01"), INTERVAL 1 MONTH)
                    ) AS verified_revenue_this_month';
        $stmt = get_db_connection()->prepare($sql);
        $stmt->execute([
            ':pending_status' => BOOKING_STATUS_PENDING,
            ':pending_payment_status' => PAYMENT_STATUS_PENDING_VERIFICATION,
            ':today_date' => date('Y-m-d'),
            ':verified_status' => PAYMENT_STATUS_VERIFIED,
        ]);

        $metrics = $stmt->fetch();
        return [
            'pending_bookings' => (int) ($metrics['pending_bookings'] ?? 0),
            'pending_payments' => (int) ($metrics['pending_payments'] ?? 0),
            'today_bookings' => (int) ($metrics['today_bookings'] ?? 0),
            'verified_revenue_this_month' => (float) ($metrics['verified_revenue_this_month'] ?? 0),
        ];
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

    public static function getBookingReport(array $filters = []): array
    {
        [$whereClause, $params] = self::buildBookingFilters($filters);

        $pdo = get_db_connection();
        $sql = 'SELECT b.booking_reference, u.full_name AS customer_name, v.venue_name, v.venue_type, b.booking_date,
                       b.start_time, b.end_time, b.booking_status, b.total_charge
                FROM bookings b
                LEFT JOIN users u ON u.user_id = b.customer_id
                LEFT JOIN venues v ON v.venue_id = b.venue_id
                WHERE 1=1' . $whereClause . '
                ORDER BY b.booking_date DESC, b.start_time ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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
                       COALESCE(ROUND(SUM(TIMESTAMPDIFF(MINUTE, CONCAT(b.booking_date, " ", b.start_time), CONCAT(b.booking_date, " ", b.end_time)) / 60), 2), 0) AS total_hours
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

    public static function getPaymentReport(array $filters = []): array
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

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
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
