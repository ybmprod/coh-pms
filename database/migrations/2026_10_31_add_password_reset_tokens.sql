-- Migration: add single-use email password-reset tokens for customer accounts.
-- Purpose : lets a customer request a secure password-change link without
--           changing the account status or exposing the bcrypt password hash.
-- Safe    : CREATE TABLE IF NOT EXISTS keeps the migration re-runnable.

USE `coh_pms`;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `reset_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `requested_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reset_id`),
    UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
    KEY `idx_password_reset_user` (`user_id`),
    KEY `idx_password_reset_expiry` (`expires_at`),
    CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
