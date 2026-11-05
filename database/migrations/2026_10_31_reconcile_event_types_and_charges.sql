USE `coh_pms`;

-- Reconcile the live database with the event-type pricing introduced in this release.
-- Run 2026_10_31_add_event_type_id_to_bookings.sql first (it adds bookings.event_type_id).
-- This migration is non-destructive: users, venues, bookings and payments are preserved.
-- It is safe to run more than once.

-- 1. Rename legacy event-type names to the canonical names used by the application.
UPDATE `event_types` SET `event_name` = 'Music Concert'       WHERE `event_name` = 'Music Concert / Performance';
UPDATE `event_types` SET `event_name` = 'Sports Event'        WHERE `event_name` = 'Sports Event / Tournament';
UPDATE `event_types` SET `event_name` = 'Church Gathering'    WHERE `event_name` = 'Church Gathering / Crusade';
UPDATE `event_types` SET `event_name` = 'Educational Seminar' WHERE `event_name` = 'Conference / Seminar';
UPDATE `event_types` SET `event_name` = 'Public Fair'         WHERE `event_name` = 'Public Fair / Exhibition';

-- 2. Insert missing canonical event types and refresh charges on existing rows.
INSERT INTO `event_types` (`event_name`, `charge_type`, `charge_value`, `status`, `description`) VALUES
('Wedding', 'Fixed Amount', 50.00, 'Active', 'A marriage ceremony, usually followed by a reception with guests, food and decoration.'),
('Community Meeting', 'Fixed Amount', 0.00, 'Active', 'A meeting where residents, ward members or civic groups discuss local matters.'),
('Sports Event', 'Fixed Amount', 100.00, 'Active', 'An organised match, tournament or athletics event with players and spectators.'),
('Public Fair', 'Fixed Amount', 75.00, 'Active', 'An open event with stalls, displays or exhibitions that attracts the public.'),
('Church Gathering', 'Fixed Amount', 0.00, 'Active', 'A church service, crusade or fellowship where people gather to worship.'),
('School Event', 'Fixed Amount', 0.00, 'Active', 'A school assembly, prize-giving or educational function for learners and teachers.'),
('Youth Workshop', 'Fixed Amount', 25.00, 'Active', 'A training or workshop session for young people, often using extra equipment.'),
('Music Concert', 'Fixed Amount', 100.00, 'Active', 'A live music performance with an audience, sound equipment and security.'),
('Drama Rehearsal', 'Fixed Amount', 0.00, 'Active', 'A practice session for a play or stage performance.'),
('Educational Seminar', 'Fixed Amount', 30.00, 'Active', 'A talk, lecture or seminar where people learn about a subject.'),
('Athletics Training', 'Fixed Amount', 20.00, 'Active', 'A training session for running, field events or other athletics activities.'),
('Flea Market', 'Fixed Amount', 50.00, 'Active', 'An informal market where traders sell goods from stalls.')
ON DUPLICATE KEY UPDATE
    `charge_type` = VALUES(`charge_type`),
    `charge_value` = VALUES(`charge_value`),
    `status` = VALUES(`status`),
    `description` = VALUES(`description`);

-- 3. Link every existing booking to an event type, including legacy free-text labels.
UPDATE `bookings` AS `b`
JOIN `event_types` AS `e` ON `e`.`event_name` = CASE
    WHEN `b`.`event_type` = 'Church Service' THEN 'Church Gathering'
    WHEN `b`.`event_type` = 'Exhibition Football Games' THEN 'Sports Event'
    WHEN `b`.`event_type` = 'Nil' THEN 'Community Meeting'
    ELSE `b`.`event_type`
END
SET `b`.`event_type_id` = `e`.`event_type_id`,
    `b`.`event_type` = `e`.`event_name`;

-- 4. Recalculate the event-type component and the booking total using stored prices.
UPDATE `bookings` AS `b`
JOIN `event_types` AS `e` ON `e`.`event_type_id` = `b`.`event_type_id`
SET `b`.`event_type_charge` = ROUND(
        CASE
            WHEN `e`.`charge_type` = 'Percentage' THEN `b`.`standard_charge` * `e`.`charge_value` / 100
            ELSE `e`.`charge_value`
        END,
        2
    );

UPDATE `bookings`
SET `event_type_charge` = 0.00
WHERE `event_type_id` IS NULL;

UPDATE `bookings`
SET `total_charge` = ROUND(GREATEST(0.00, `standard_charge` + `event_type_charge` + `adjustment_amount`), 2);

-- 5. Keep recorded payments consistent with the recalculated booking totals.
UPDATE `payments` AS `p`
JOIN `bookings` AS `b` ON `b`.`booking_id` = `p`.`booking_id`
SET `p`.`amount_paid` = `b`.`total_charge`
WHERE `p`.`amount_paid` <> `b`.`total_charge`;

-- 6. Verification output.
SELECT 'event_types' AS `check_name`, COUNT(*) AS `value` FROM `event_types`
UNION ALL
SELECT 'bookings_linked_to_event_type', COUNT(*) FROM `bookings` WHERE `event_type_id` IS NOT NULL
UNION ALL
SELECT 'bookings_unlinked', COUNT(*) FROM `bookings` WHERE `event_type_id` IS NULL
UNION ALL
SELECT 'payment_amount_mismatches', COUNT(*)
FROM `payments` AS `p`
JOIN `bookings` AS `b` ON `b`.`booking_id` = `p`.`booking_id`
WHERE `p`.`amount_paid` <> `b`.`total_charge`;
