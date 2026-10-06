DROP DATABASE IF EXISTS `coh_pms`;
CREATE DATABASE IF NOT EXISTS `coh_pms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `coh_pms`;

CREATE TABLE `users` (
    `user_id` INT NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('Customer','Booking Officer','Revenue Officer','Administrator','Council Management') NOT NULL DEFAULT 'Customer',
    `account_status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `venues` (
    `venue_id` INT NOT NULL AUTO_INCREMENT,
    `venue_name` VARCHAR(150) NOT NULL,
    `venue_type` ENUM('Community Hall','Community Centre','Stadium','Open Space','Other') NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `capacity` INT NOT NULL,
    `facilities` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `standard_price` DECIMAL(10,2) NOT NULL,
    `venue_status` ENUM('Available','Unavailable','Under Maintenance') NOT NULL DEFAULT 'Available',
    `image_path` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`venue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pricing_rules` (
    `pricing_rule_id` INT NOT NULL AUTO_INCREMENT,
    `rule_name` VARCHAR(100) NOT NULL,
    `venue_type` ENUM('All','Community Hall','Community Centre','Stadium','Open Space','Other') NOT NULL DEFAULT 'All',
    `rule_type` ENUM('Weekday Discount','Weekend Surcharge','Off-Peak Discount','Long Booking Discount','Peak Demand Surcharge') NOT NULL,
    `adjustment_type` ENUM('Percentage','Fixed Amount') NOT NULL DEFAULT 'Percentage',
    `adjustment_value` DECIMAL(10,2) NOT NULL,
    `days_of_week` VARCHAR(30) DEFAULT NULL,
    `min_hours` DECIMAL(4,1) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `priority` INT NOT NULL DEFAULT 1,
    `rule_status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`pricing_rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bookings` (
    `booking_id` INT NOT NULL AUTO_INCREMENT,
    `booking_reference` VARCHAR(20) NOT NULL,
    `customer_id` INT NOT NULL,
    `venue_id` INT NOT NULL,
    `pricing_rule_id` INT DEFAULT NULL,
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `event_type` VARCHAR(100) DEFAULT NULL,
    `number_of_attendees` INT DEFAULT NULL,
    `standard_charge` DECIMAL(10,2) NOT NULL,
    `adjustment_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_charge` DECIMAL(10,2) NOT NULL,
    `booking_status` ENUM('Pending','Approved','Rejected','Cancelled','Confirmed','Completed') NOT NULL DEFAULT 'Pending',
    `reviewed_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`booking_id`),
    UNIQUE KEY `uq_bookings_reference` (`booking_reference`),
    KEY `idx_bookings_venue_date` (`venue_id`, `booking_date`, `start_time`, `end_time`),
    KEY `idx_bookings_customer` (`customer_id`),
    KEY `idx_bookings_status` (`booking_status`),
    CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_pricing_rule` FOREIGN KEY (`pricing_rule_id`) REFERENCES `pricing_rules` (`pricing_rule_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
    `payment_id` INT NOT NULL AUTO_INCREMENT,
    `booking_id` INT NOT NULL,
    `amount_paid` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Cash','Bank Transfer','Mobile Money','Card') NOT NULL,
    `transaction_reference` VARCHAR(100) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `payment_status` ENUM('Pending Verification','Verified','Rejected') NOT NULL DEFAULT 'Pending Verification',
    `verified_by` INT DEFAULT NULL,
    `verification_date` DATETIME DEFAULT NULL,
    `receipt_number` VARCHAR(30) DEFAULT NULL,
    PRIMARY KEY (`payment_id`),
    UNIQUE KEY `uq_payments_receipt_number` (`receipt_number`),
    KEY `idx_payments_booking` (`booking_id`),
    KEY `idx_payments_status` (`payment_status`),
    CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
