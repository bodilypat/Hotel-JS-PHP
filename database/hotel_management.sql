-- ============================================================
-- Hotel Management System
-- Master Database Schema
-- File: database/hotel_management.sql
-- ============================================================

-- ------------------------------------------------------------
-- Database
-- ------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `hotel_management`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `hotel_management`;


-- ============================================================
-- USERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,

    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,

    `password` VARCHAR(255) NOT NULL,

    `role` ENUM(
        'admin',
        'manager',
        'receptionist',
        'staff',
        'accountant'
    ) NOT NULL DEFAULT 'staff',

    `status` ENUM(
        'active',
        'inactive',
        'suspended',
        'pending'
    ) NOT NULL DEFAULT 'active',

    `email_verified_at` DATETIME NULL,

    `password_changed_at` DATETIME NULL,

    `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,

    `locked_until` DATETIME NULL,

    `last_login_at` DATETIME NULL,

    `last_login_ip` VARCHAR(45) NULL,

    `profile_image` VARCHAR(255) NULL,

    `password_reset_token` VARCHAR(255) NULL,

    `password_reset_expires_at` DATETIME NULL,

    `email_verification_token` VARCHAR(255) NULL,

    `email_verification_expires_at` DATETIME NULL,

    `notes` TEXT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_users_email`
        (`email`),

    UNIQUE KEY `uq_users_password_reset_token`
        (`password_reset_token`),

    UNIQUE KEY `uq_users_email_verification_token`
        (`email_verification_token`),

    KEY `idx_users_role`
        (`role`),

    KEY `idx_users_status`
        (`status`),

    KEY `idx_users_phone`
        (`phone`),

    KEY `idx_users_last_login`
        (`last_login_at`),

    KEY `idx_users_created_at`
        (`created_at`),

    KEY `idx_users_locked_until`
        (`locked_until`),

    CONSTRAINT `chk_users_failed_attempts`
        CHECK (`failed_login_attempts` >= 0)

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- GUESTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `guests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,

    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,

    `date_of_birth` DATE NULL,
    `gender` VARCHAR(30) NULL,
    `nationality` VARCHAR(100) NULL,

    `id_type` VARCHAR(50) NULL,
    `id_number` VARCHAR(100) NULL,

    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,

    `emergency_contact_name` VARCHAR(200) NULL,
    `emergency_contact_phone` VARCHAR(30) NULL,

    `notes` TEXT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_guests_email`
        (`email`),

    UNIQUE KEY `uq_guests_id_type_number`
        (`id_type`, `id_number`),

    KEY `idx_guests_first_name`
        (`first_name`),

    KEY `idx_guests_last_name`
        (`last_name`),

    KEY `idx_guests_phone`
        (`phone`),

    KEY `idx_guests_city`
        (`city`),

    KEY `idx_guests_country`
        (`country`),

    KEY `idx_guests_nationality`
        (`nationality`),

    KEY `idx_guests_id_number`
        (`id_number`),

    KEY `idx_guests_name`
        (`last_name`, `first_name`),

    KEY `idx_guests_created_at`
        (`created_at`)

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- ROOMS
-- ============================================================

CREATE TABLE IF NOT EXISTS `rooms` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `room_number` VARCHAR(20) NOT NULL,
    `room_type` VARCHAR(50) NOT NULL,

    `name` VARCHAR(150) NULL,
    `description` TEXT NULL,

    `capacity` INT UNSIGNED NOT NULL DEFAULT 1,
    `max_guests` INT UNSIGNED NOT NULL DEFAULT 1,

    `price_per_night` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',

    `status` ENUM(
        'available',
        'occupied',
        'maintenance',
        'inactive'
    ) NOT NULL DEFAULT 'available',

    `is_available` TINYINT(1) NOT NULL DEFAULT 1,

    `floor` INT NULL,

    `bed_type` VARCHAR(50) NULL,
    `bed_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `bathroom_type` VARCHAR(50) NULL,

    `amenities` JSON NULL,

    `image` VARCHAR(255) NULL,

    `notes` TEXT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_rooms_room_number`
        (`room_number`),

    KEY `idx_rooms_room_type`
        (`room_type`),

    KEY `idx_rooms_status`
        (`status`),

    KEY `idx_rooms_is_available`
        (`is_available`),

    KEY `idx_rooms_floor`
        (`floor`),

    KEY `idx_rooms_price`
        (`price_per_night`),

    KEY `idx_rooms_capacity`
        (`capacity`),

    KEY `idx_rooms_type_status`
        (`room_type`, `status`),

    CONSTRAINT `chk_rooms_capacity`
        CHECK (`capacity` >= 1),

    CONSTRAINT `chk_rooms_max_guests`
        CHECK (`max_guests` >= 1),

    CONSTRAINT `chk_rooms_price`
        CHECK (`price_per_night` >= 0),

    CONSTRAINT `chk_rooms_bed_count`
        CHECK (`bed_count` >= 1),

    CONSTRAINT `chk_rooms_available_flag`
        CHECK (`is_available` IN (0, 1))

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- BOOKINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `booking_reference` VARCHAR(50) NULL,

    `guest_id` BIGINT UNSIGNED NOT NULL,
    `room_id` BIGINT UNSIGNED NOT NULL,

    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,

    `number_of_guests` INT UNSIGNED NOT NULL DEFAULT 1,
    `adults` INT UNSIGNED NOT NULL DEFAULT 1,
    `children` INT UNSIGNED NOT NULL DEFAULT 0,
    `number_of_nights` INT UNSIGNED NOT NULL DEFAULT 1,

    `room_rate` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    `currency` CHAR(3) NOT NULL DEFAULT 'USD',

    `status` ENUM(
        'pending',
        'confirmed',
        'checked_in',
        'checked_out',
        'completed',
        'cancelled',
        'no_show'
    ) NOT NULL DEFAULT 'pending',

    `payment_status` ENUM(
        'unpaid',
        'partial',
        'paid',
        'refunded',
        'failed'
    ) NOT NULL DEFAULT 'unpaid',

    `source` VARCHAR(50) NULL,

    `special_requests` TEXT NULL,
    `notes` TEXT NULL,

    `cancellation_reason` TEXT NULL,
    `cancelled_at` DATETIME NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_bookings_reference`
        (`booking_reference`),

    KEY `idx_bookings_guest_id`
        (`guest_id`),

    KEY `idx_bookings_room_id`
        (`room_id`),

    KEY `idx_bookings_check_in`
        (`check_in`),

    KEY `idx_bookings_check_out`
        (`check_out`),

    KEY `idx_bookings_status`
        (`status`),

    KEY `idx_bookings_payment_status`
        (`payment_status`),

    KEY `idx_bookings_created_at`
        (`created_at`),

    KEY `idx_bookings_room_dates`
        (`room_id`, `check_in`, `check_out`),

    CONSTRAINT `fk_bookings_guest`
        FOREIGN KEY (`guest_id`)
        REFERENCES `guests` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_bookings_room`
        FOREIGN KEY (`room_id`)
        REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `chk_bookings_dates`
        CHECK (`check_out` > `check_in`),

    CONSTRAINT `chk_bookings_guests`
        CHECK (`number_of_guests` >= 1),

    CONSTRAINT `chk_bookings_adults`
        CHECK (`adults` >= 1),

    CONSTRAINT `chk_bookings_children`
        CHECK (`children` >= 0),

    CONSTRAINT `chk_bookings_nights`
        CHECK (`number_of_nights` >= 1),

    CONSTRAINT `chk_bookings_room_rate`
        CHECK (`room_rate` >= 0),

    CONSTRAINT `chk_bookings_subtotal`
        CHECK (`subtotal` >= 0),

    CONSTRAINT `chk_bookings_discount`
        CHECK (`discount` >= 0),

    CONSTRAINT `chk_bookings_tax`
        CHECK (`tax` >= 0),

    CONSTRAINT `chk_bookings_total`
        CHECK (`total_amount` >= 0)

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `payment_reference` VARCHAR(100) NOT NULL,

    `booking_id` BIGINT UNSIGNED NOT NULL,
    `guest_id` BIGINT UNSIGNED NULL,

    `amount` DECIMAL(12,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',

    `payment_method` ENUM(
        'cash',
        'card',
        'bank_transfer',
        'mobile_money',
        'online',
        'other'
    ) NOT NULL DEFAULT 'cash',

    `status` ENUM(
        'pending',
        'processing',
        'completed',
        'failed',
        'cancelled',
        'refunded',
        'partially_refunded'
    ) NOT NULL DEFAULT 'pending',

    `transaction_id` VARCHAR(255) NULL,
    `gateway` VARCHAR(100) NULL,

    `refunded_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `refunded_at` DATETIME NULL,
    `refund_reason` TEXT NULL,

    `invoice_number` VARCHAR(100) NULL,
    `receipt_number` VARCHAR(100) NULL,

    `description` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `metadata` JSON NULL,

    `paid_at` DATETIME NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_payments_reference`
        (`payment_reference`),

    UNIQUE KEY `uq_payments_invoice_number`
        (`invoice_number`),

    UNIQUE KEY `uq_payments_receipt_number`
        (`receipt_number`),

    UNIQUE KEY `uq_payments_transaction`
        (`gateway`, `transaction_id`),

    KEY `idx_payments_booking_id`
        (`booking_id`),

    KEY `idx_payments_guest_id`
        (`guest_id`),

    KEY `idx_payments_status`
        (`status`),

    KEY `idx_payments_payment_method`
        (`payment_method`),

    KEY `idx_payments_paid_at`
        (`paid_at`),

    KEY `idx_payments_created_at`
        (`created_at`),

    CONSTRAINT `fk_payments_booking`
        FOREIGN KEY (`booking_id`)
        REFERENCES `bookings` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_payments_guest`
        FOREIGN KEY (`guest_id`)
        REFERENCES `guests` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT `chk_payments_amount`
        CHECK (`amount` >= 0),

    CONSTRAINT `chk_payments_refunded_amount`
        CHECK (
            `refunded_amount` >= 0
            AND `refunded_amount` <= `amount`
        )

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- STAFF
-- ============================================================

CREATE TABLE IF NOT EXISTS `staff` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    `user_id` BIGINT UNSIGNED NULL,

    `employee_number` VARCHAR(50) NOT NULL,

    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,

    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,

    `department` VARCHAR(100) NULL,
    `position` VARCHAR(100) NOT NULL,

    `employment_type` ENUM(
        'full_time',
        'part_time',
        'contract',
        'temporary',
        'intern'
    ) NOT NULL DEFAULT 'full_time',

    `hire_date` DATE NULL,
    `termination_date` DATE NULL,

    `status` ENUM(
        'active',
        'inactive',
        'on_leave',
        'terminated'
    ) NOT NULL DEFAULT 'active',

    `salary` DECIMAL(12,2) NULL,
    `salary_currency` CHAR(3) NOT NULL DEFAULT 'USD',

    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,

    `emergency_contact_name` VARCHAR(200) NULL,
    `emergency_contact_phone` VARCHAR(30) NULL,
    `emergency_contact_relationship` VARCHAR(50) NULL,

    `profile_image` VARCHAR(255) NULL,

    `notes` TEXT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_staff_employee_number`
        (`employee_number`),

    UNIQUE KEY `uq_staff_user_id`
        (`user_id`),

    KEY `idx_staff_email`
        (`email`),

    KEY `idx_staff_phone`
        (`phone`),

    KEY `idx_staff_department`
        (`department`),

    KEY `idx_staff_position`
        (`position`),

    KEY `idx_staff_status`
        (`status`),

    KEY `idx_staff_employment_type`
        (`employment_type`),

    KEY `idx_staff_hire_date`
        (`hire_date`),

    KEY `idx_staff_created_at`
        (`created_at`),

    CONSTRAINT `fk_staff_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT `chk_staff_salary`
        CHECK (
            `salary` IS NULL
            OR `salary` >= 0
        ),

    CONSTRAINT `chk_staff_employment_dates`
        CHECK (
            `hire_date` IS NULL
            OR `termination_date` IS NULL
            OR `termination_date` >= `hire_date`
        )

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- DATABASE INITIALIZATION COMPLETE
-- ============================================================

SELECT
    'Hotel Management System database initialized successfully.'
    AS `message`;
