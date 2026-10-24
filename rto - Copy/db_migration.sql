-- ============================================================

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appointment_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `service` VARCHAR(100) NOT NULL,
    `applicant_name` VARCHAR(150) NOT NULL,
    `mobile_no` VARCHAR(15) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `appointment_slot` VARCHAR(40) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'Confirmed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `admin_users`
    (`username`, `password_hash`, `display_name`)
VALUES
    ('admin', '$2y$10$nGNmTqgLrwEo9uklZv/ZP.Fhz2U5PpI0HxkX58ceRT6x7g1CUcOrS', 'RTO Administrator');

-- RTO DB Migration: Add inspection columns to vehicles table
-- Run this once in phpMyAdmin or MySQL CLI
-- Database: rto_managementt
-- ============================================================

ALTER TABLE `users`
    MODIFY COLUMN `password` VARCHAR(255) NOT NULL;

ALTER TABLE `vehicles`
    ADD COLUMN IF NOT EXISTS `inspection_center` VARCHAR(100) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `inspection_date`   DATE         DEFAULT NULL AFTER `inspection_center`,
    ADD COLUMN IF NOT EXISTS `inspection_time`   VARCHAR(20)  DEFAULT NULL AFTER `inspection_date`;

ALTER TABLE `challans`
    ADD COLUMN IF NOT EXISTS `user_id` INT NULL AFTER `challan_no`,
    ADD COLUMN IF NOT EXISTS `paid_at` DATETIME NULL,
    ADD COLUMN IF NOT EXISTS `payment_reference` VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS `bank_reference` VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS `bank_name` VARCHAR(40) NULL;

ALTER TABLE `vehicles`
    ADD COLUMN IF NOT EXISTS `registration_payment_status` VARCHAR(20) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `registration_payment_method` VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS `registration_payment_reference` VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS `registration_bank_reference` VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS `registration_bank_name` VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS `registration_paid_at` DATETIME NULL;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `application_id` INT DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(40) NOT NULL,
    `transaction_reference` VARCHAR(80) NOT NULL UNIQUE,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Paid',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `payments`
    ADD COLUMN IF NOT EXISTS `bank_reference` VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS `bank_name` VARCHAR(40) NULL;

ALTER TABLE `documents`
    ADD COLUMN IF NOT EXISTS `user_id` INT NULL,
    ADD COLUMN IF NOT EXISTS `application_id` INT NULL,
    ADD COLUMN IF NOT EXISTS `applicant_name` VARCHAR(150) NULL;

ALTER TABLE `licence`
    ADD COLUMN IF NOT EXISTS `user_id` INT NULL;

CREATE TABLE IF NOT EXISTS `licence_renewals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `licence_id` INT NOT NULL,
    `licence_data` LONGTEXT NOT NULL,
    `renewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_licence_renewals_user` (`user_id`),
    INDEX `idx_licence_renewals_licence` (`licence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
