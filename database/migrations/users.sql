-- ============================================================
-- Hotel Management System
-- Migration: users.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Account information
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,

    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,

    -- Authentication
    `password` VARCHAR(255) NOT NULL,

    -- Authorization
    `role` ENUM(
        'admin',
        'manager',
        'receptionist',
        'staff',
        'accountant'
    ) NOT NULL DEFAULT 'staff',

    -- Account status
    `status` ENUM(
        'active',
        'inactive',
        'suspended',
        'pending'
    ) NOT NULL DEFAULT 'active',

    `email_verified_at` DATETIME NULL,

    -- Password security
    `password_changed_at` DATETIME NULL,

    `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,

    `locked_until` DATETIME NULL,

    -- Authentication tracking
    `last_login_at` DATETIME NULL,

    `last_login_ip` VARCHAR(45) NULL,

    -- Profile
    `profile_image` VARCHAR(255) NULL,

    -- Password reset
    `password_reset_token` VARCHAR(255) NULL,

    `password_reset_expires_at` DATETIME NULL,

    -- Email verification
    `email_verification_token` VARCHAR(255) NULL,

    `email_verification_expires_at` DATETIME NULL,

    -- Additional information
    `notes` TEXT NULL,

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Email must uniquely identify an account
    UNIQUE KEY `uq_users_email`
        (`email`),

    -- Password reset tokens should be unique
    UNIQUE KEY `uq_users_password_reset_token`
        (`password_reset_token`),

    -- Email verification tokens should be unique
    UNIQUE KEY `uq_users_email_verification_token`
        (`email_verification_token`),

    -- Lookup indexes
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

    -- Validation
    CONSTRAINT `chk_users_failed_attempts`
        CHECK (`failed_login_attempts` >= 0)

) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
