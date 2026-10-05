-- ============================================================
-- Hotel Management System
-- Migration: guests.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `guests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Personal information
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,

    -- Contact information
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,

    -- Personal details
    `date_of_birth` DATE NULL,
    `gender` VARCHAR(30) NULL,
    `nationality` VARCHAR(100) NULL,

    -- Identification
    `id_type` VARCHAR(50) NULL,
    `id_number` VARCHAR(100) NULL,

    -- Address
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,

    -- Emergency contact
    `emergency_contact_name` VARCHAR(200) NULL,
    `emergency_contact_phone` VARCHAR(30) NULL,

    -- Additional information
    `notes` TEXT NULL,

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Search indexes
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

    KEY `idx_guests_created_at`
        (`created_at`),

    -- Identification lookup
    KEY `idx_guests_id_number`
        (`id_number`),

    -- Composite name search
    KEY `idx_guests_name`
        (`last_name`, `first_name`),

    -- Email lookup
    UNIQUE KEY `uq_guests_email`
        (`email`),

    -- Prevent duplicate government ID numbers when supplied.
    UNIQUE KEY `uq_guests_id_type_number`
        (`id_type`, `id_number`)

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
