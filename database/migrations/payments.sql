-- ============================================================
-- Hotel Management System
-- Migration: payments.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Payment reference
    `payment_reference` VARCHAR(100) NOT NULL,

    -- Related booking
    `booking_id` BIGINT UNSIGNED NOT NULL,

    -- Optional guest reference for faster reporting
    `guest_id` BIGINT UNSIGNED NULL,

    -- Payment amount
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',

    -- Payment method
    `payment_method` ENUM(
        'cash',
        'card',
        'bank_transfer',
        'mobile_money',
        'online',
        'other'
    ) NOT NULL DEFAULT 'cash',

    -- Payment status
    `status` ENUM(
        'pending',
        'processing',
        'completed',
        'failed',
        'cancelled',
        'refunded',
        'partially_refunded'
    ) NOT NULL DEFAULT 'pending',

    -- External payment gateway information
    `transaction_id` VARCHAR(255) NULL,
    `gateway` VARCHAR(100) NULL,

    -- Refund information
    `refunded_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `refunded_at` DATETIME NULL,
    `refund_reason` TEXT NULL,

    -- Invoice / receipt information
    `invoice_number` VARCHAR(100) NULL,
    `receipt_number` VARCHAR(100) NULL,

    -- Payment metadata
    `description` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `metadata` JSON NULL,

    -- Payment timestamps
    `paid_at` DATETIME NULL,

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Unique references
    UNIQUE KEY `uq_payments_reference`
        (`payment_reference`),

    UNIQUE KEY `uq_payments_invoice_number`
        (`invoice_number`),

    UNIQUE KEY `uq_payments_receipt_number`
        (`receipt_number`),

    -- Gateway transaction IDs should not be duplicated
    UNIQUE KEY `uq_payments_transaction`
        (`gateway`, `transaction_id`),

    -- Lookup indexes
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

    KEY `idx_payments_invoice_number`
        (`invoice_number`),

    -- Foreign key to bookings
    CONSTRAINT `fk_payments_booking`
        FOREIGN KEY (`booking_id`)
        REFERENCES `bookings` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Foreign key to guests
    CONSTRAINT `fk_payments_guest`
        FOREIGN KEY (`guest_id`)
        REFERENCES `guests` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    -- Amount validation
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
