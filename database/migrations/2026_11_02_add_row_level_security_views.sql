-- ---------------------------------------------------------------------------
-- COH-PMS row-level-security approximation (MariaDB/MySQL)
--
-- MySQL and MariaDB do not implement native row-level security. This migration
-- creates read-only views that filter customer rows by the identity exported by
-- config/database.php through the @coh_pms_customer_id session variable.
--
-- The views are a defence-in-depth layer: application ownership checks remain
-- mandatory. A database account that can run arbitrary SQL can set the session
-- variable itself, so these views must not be treated as a replacement for
-- server-side authorisation.
--
-- Run once after the base schema:
--   mysql -u root coh_pms < database/migrations/2026_11_02_add_row_level_security_views.sql
-- ---------------------------------------------------------------------------

DROP VIEW IF EXISTS v_customer_receipts;
DROP VIEW IF EXISTS v_customer_payments;
DROP VIEW IF EXISTS v_customer_bookings;
DROP FUNCTION IF EXISTS coh_current_customer_id;

DELIMITER $$

CREATE FUNCTION coh_current_customer_id()
RETURNS INT UNSIGNED
NOT DETERMINISTIC
NO SQL
RETURN COALESCE(CAST(@coh_pms_customer_id AS UNSIGNED), 0)$$

DELIMITER ;

CREATE VIEW v_customer_bookings AS
SELECT
    b.*,
    v.venue_name,
    v.location,
    c.full_name AS customer_name,
    r.full_name AS reviewer_name,
    pr.rule_name,
    (SELECT p.payment_status
       FROM payments p
      WHERE p.booking_id = b.booking_id
      ORDER BY p.payment_id DESC
      LIMIT 1) AS payment_status,
    (SELECT p.receipt_number
       FROM payments p
      WHERE p.booking_id = b.booking_id
      ORDER BY p.payment_id DESC
      LIMIT 1) AS receipt_number
FROM bookings b
LEFT JOIN venues v ON v.venue_id = b.venue_id
LEFT JOIN users c ON c.user_id = b.customer_id
LEFT JOIN users r ON r.user_id = b.reviewed_by
LEFT JOIN pricing_rules pr ON pr.pricing_rule_id = b.pricing_rule_id
WHERE b.customer_id = coh_current_customer_id();

CREATE VIEW v_customer_payments AS
SELECT
    p.*,
    b.booking_reference,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.event_type,
    b.total_charge,
    b.customer_id,
    v.venue_name
FROM payments p
JOIN bookings b ON b.booking_id = p.booking_id
JOIN venues v ON v.venue_id = b.venue_id
WHERE b.customer_id = coh_current_customer_id();

CREATE VIEW v_customer_receipts AS
SELECT
    p.*,
    b.booking_reference,
    b.customer_id,
    b.venue_id,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.event_type,
    b.expected_attendance,
    b.standard_charge,
    b.event_type_charge,
    b.adjustment_amount,
    b.total_charge,
    c.full_name AS customer_name,
    v.venue_name,
    pr.rule_name,
    verifier.full_name AS verified_by_name
FROM payments p
JOIN bookings b ON b.booking_id = p.booking_id
JOIN users c ON c.user_id = b.customer_id
JOIN venues v ON v.venue_id = b.venue_id
LEFT JOIN pricing_rules pr ON pr.pricing_rule_id = b.pricing_rule_id
LEFT JOIN users verifier ON verifier.user_id = p.verified_by
WHERE b.customer_id = coh_current_customer_id();
