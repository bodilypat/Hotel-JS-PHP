-- ============================================================
-- Hotel Management System
-- Migration: staff.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `staff` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Optional link to application user account
    `user_id` BIGINT UNSIGNED NULL,

    -- Employee identification
    `employee_number` VARCHAR(50) NOT NULL,

    -- Personal information
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,

    -- Contact information
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,

    -- Employment information
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

    -- Staff status
    `status` ENUM(
        'active',
        'inactive',
        'on_leave',
        'terminated'
    ) NOT NULL DEFAULT 'active',

    -- Compensation
    `salary` DECIMAL(12,2) NULL,
    `salary_currency` CHAR(3) NOT NULL DEFAULT 'USD',

    -- Address
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,

    -- Emergency contact
    `emergency_contact_name` VARCHAR(200) NULL,
    `emergency_contact_phone` VARCHAR(30) NULL,
    `emergency_contact_relationship` VARCHAR(50) NULL,

    -- Profile
    `profile_image` VARCHAR(255) NULL,

    -- Additional information
    `notes` TEXT NULL,

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Employee number must be unique
    UNIQUE KEY `uq_staff_employee_number`
        (`employee_number`),

    -- A user account can belong to only one staff record
    UNIQUE KEY `uq_staff_user_id`
        (`user_id`),

    -- Contact indexes
    KEY `idx_staff_email`
        (`email`),

    KEY `idx_staff_phone`
        (`phone`),

    -- Employment indexes
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

    -- Foreign key to users
    CONSTRAINT `fk_staff_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    -- Salary must not be negative
    CONSTRAINT `chk_staff_salary`
        CHECK (
            `salary` IS NULL
            OR `salary` >= 0
        ),

    -- Termination date cannot be before hire date
    CONSTRAINT `chk_staff_employment_dates`
        CHECK (
            `hire_date` IS NULL
            OR `termination_date` IS NULL
            OR `termination_date` >= `hire_date`
        )

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
