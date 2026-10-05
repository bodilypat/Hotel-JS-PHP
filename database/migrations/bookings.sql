-- ============================================================
-- Hotel Management System
-- Migration: bookings.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Optional human-readable booking reference
    `booking_reference` VARCHAR(50) NULL,

    -- Relationships
    `guest_id` BIGINT UNSIGNED NOT NULL,
    `room_id` BIGINT UNSIGNED NOT NULL,

    -- Stay dates
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,

    -- Occupancy
    `number_of_guests` INT UNSIGNED NOT NULL DEFAULT 1,
    `adults` INT UNSIGNED NOT NULL DEFAULT 1,
    `children` INT UNSIGNED NOT NULL DEFAULT 0,
    `number_of_nights` INT UNSIGNED NOT NULL DEFAULT 1,

    -- Pricing
    `room_rate` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',

    -- Booking status
    `status` ENUM(
        'pending',
        'confirmed',
        'checked_in',
        'checked_out',
        'completed',
        'cancelled',
        'no_show'
    ) NOT NULL DEFAULT 'pending',

    -- Payment status
    `payment_status` ENUM(
        'unpaid',
        'partial',
        'paid',
        'refunded',
        'failed'
    ) NOT NULL DEFAULT 'unpaid',

    -- Booking source
    `source` VARCHAR(50) NULL,

    -- Guest requests
    `special_requests` TEXT NULL,
    `notes` TEXT NULL,

    -- Cancellation
    `cancellation_reason` TEXT NULL,
    `cancelled_at` DATETIME NULL,

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_bookings_reference`
        (`booking_reference`),

    -- Lookup indexes
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

    -- Useful for availability queries
    KEY `idx_bookings_room_dates`
        (`room_id`, `check_in`, `check_out`),

    -- Foreign keys
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

    -- Basic data validation
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
