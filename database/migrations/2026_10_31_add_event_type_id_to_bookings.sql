-- Migration: link bookings to the event_types catalogue.
-- Purpose : Booking::create() writes bookings.event_type_id, but the column was
--           missing from the original schema, which caused every new booking to fail.
-- Safe    : the migration checks information_schema, so it can be re-run safely on
--           both MySQL 8 and MariaDB (no "ADD COLUMN IF NOT EXISTS" dependency).

USE `coh_pms`;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND COLUMN_NAME = 'event_type_id'
);

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND INDEX_NAME = 'idx_bookings_event_type'
);

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND CONSTRAINT_NAME = 'fk_bookings_event_type'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `bookings` ADD COLUMN `event_type_id` INT DEFAULT NULL AFTER `venue_id`',
    'SELECT ''bookings.event_type_id already exists'' AS migration_status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE `bookings` ADD KEY `idx_bookings_event_type` (`event_type_id`)',
    'SELECT ''idx_bookings_event_type already exists'' AS migration_status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @fk_exists = 0,
    'ALTER TABLE `bookings` ADD CONSTRAINT `fk_bookings_event_type` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`event_type_id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''fk_bookings_event_type already exists'' AS migration_status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
