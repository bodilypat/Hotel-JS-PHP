-- ============================================================
-- Hotel Management System
-- Migration: rooms.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `rooms` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Room identification
    `room_number` VARCHAR(20) NOT NULL,
    `room_type` VARCHAR(50) NOT NULL,

    -- Room description
    `name` VARCHAR(150) NULL,
    `description` TEXT NULL,

    -- Capacity
    `capacity` INT UNSIGNED NOT NULL DEFAULT 1,
    `max_guests` INT UNSIGNED NOT NULL DEFAULT 1,

    -- Pricing
    `price_per_night` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',

    -- Room status
    `status` ENUM(
        'available',
        'occupied',
        'maintenance',
        'inactive'
    ) NOT NULL DEFAULT 'available',

    -- Availability flag
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,

    -- Floor/location
    `floor` INT NULL,

    -- Room features
    `bed_type` VARCHAR(50) NULL,
    `bed_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `bathroom_type` VARCHAR(50) NULL,

    -- Amenities stored as JSON
    `amenities` JSON NULL,

    -- Room image
    `image` VARCHAR(255) NULL,

    -- Additional information
    `notes` TEXT NULL,

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Room number must be unique
    UNIQUE KEY `uq_rooms_room_number`
        (`room_number`),

    -- Lookup indexes
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

    -- Validation constraints
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
