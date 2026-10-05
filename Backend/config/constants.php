<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Application Constants
 *
 * File:
 * backend/config/constants.php
 *
 * This file contains application-wide constants that should not
 * normally be changed during runtime.
 */

// ---------------------------------------------------------
// Prevent Direct Access
// ---------------------------------------------------------

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('APP_CONSTANTS_LOADED')) {
    // =========================================================
    // USER / STAFF ROLES
    // =========================================================

    define('ROLE_ADMIN', 'admin');
    define('ROLE_MANAGER', 'manager');
    define('ROLE_RECEPTIONIST', 'receptionist');
    define('ROLE_ACCOUNTANT', 'accountant');
    define('ROLE_HOUSEKEEPING', 'housekeeping');
    define('ROLE_STAFF', 'staff');
    define('ROLE_USER', 'user');

    define('DEFAULT_USER_ROLE', ROLE_USER);

    define('USER_ROLES', [
        ROLE_ADMIN,
        ROLE_MANAGER,
        ROLE_RECEPTIONIST,
        ROLE_ACCOUNTANT,
        ROLE_HOUSEKEEPING,
        ROLE_STAFF,
        ROLE_USER,
    ]);


    // =========================================================
    // USER STATUS
    // =========================================================

    define('USER_STATUS_ACTIVE', 'active');
    define('USER_STATUS_INACTIVE', 'inactive');
    define('USER_STATUS_SUSPENDED', 'suspended');
    define('USER_STATUS_PENDING', 'pending');
    define('USER_STATUS_DELETED', 'deleted');

    define('USER_STATUSES', [
        USER_STATUS_ACTIVE,
        USER_STATUS_INACTIVE,
        USER_STATUS_SUSPENDED,
        USER_STATUS_PENDING,
        USER_STATUS_DELETED,
    ]);


    // =========================================================
    // ROOM STATUS
    // =========================================================

    define('ROOM_STATUS_AVAILABLE', 'available');
    define('ROOM_STATUS_OCCUPIED', 'occupied');
    define('ROOM_STATUS_RESERVED', 'reserved');
    define('ROOM_STATUS_MAINTENANCE', 'maintenance');
    define('ROOM_STATUS_CLEANING', 'cleaning');
    define('ROOM_STATUS_OUT_OF_SERVICE', 'out_of_service');

    define('ROOM_STATUSES', [
        ROOM_STATUS_AVAILABLE,
        ROOM_STATUS_OCCUPIED,
        ROOM_STATUS_RESERVED,
        ROOM_STATUS_MAINTENANCE,
        ROOM_STATUS_CLEANING,
        ROOM_STATUS_OUT_OF_SERVICE,
    ]);


    // =========================================================
    // ROOM TYPES
    // =========================================================

    define('ROOM_TYPE_SINGLE', 'single');
    define('ROOM_TYPE_DOUBLE', 'double');
    define('ROOM_TYPE_TWIN', 'twin');
    define('ROOM_TYPE_DELUXE', 'deluxe');
    define('ROOM_TYPE_SUITE', 'suite');
    define('ROOM_TYPE_FAMILY', 'family');
    define('ROOM_TYPE_PRESIDENTIAL', 'presidential');

    define('ROOM_TYPES', [
        ROOM_TYPE_SINGLE,
        ROOM_TYPE_DOUBLE,
        ROOM_TYPE_TWIN,
        ROOM_TYPE_DELUXE,
        ROOM_TYPE_SUITE,
        ROOM_TYPE_FAMILY,
        ROOM_TYPE_PRESIDENTIAL,
    ]);


    // =========================================================
    // BED TYPES
    // =========================================================

    define('BED_TYPE_SINGLE', 'single');
    define('BED_TYPE_DOUBLE', 'double');
    define('BED_TYPE_QUEEN', 'queen');
    define('BED_TYPE_KING', 'king');
    define('BED_TYPE_TWIN', 'twin');

    define('BED_TYPES', [
        BED_TYPE_SINGLE,
        BED_TYPE_DOUBLE,
        BED_TYPE_QUEEN,
        BED_TYPE_KING,
        BED_TYPE_TWIN,
    ]);


    // =========================================================
    // BOOKING STATUS
    // =========================================================

    define('BOOKING_STATUS_PENDING', 'pending');
    define('BOOKING_STATUS_CONFIRMED', 'confirmed');
    define('BOOKING_STATUS_CHECKED_IN', 'checked_in');
    define('BOOKING_STATUS_CHECKED_OUT', 'checked_out');
    define('BOOKING_STATUS_CANCELLED', 'cancelled');
    define('BOOKING_STATUS_NO_SHOW', 'no_show');
    define('BOOKING_STATUS_COMPLETED', 'completed');

    define('BOOKING_STATUSES', [
        BOOKING_STATUS_PENDING,
        BOOKING_STATUS_CONFIRMED,
        BOOKING_STATUS_CHECKED_IN,
        BOOKING_STATUS_CHECKED_OUT,
        BOOKING_STATUS_CANCELLED,
        BOOKING_STATUS_NO_SHOW,
        BOOKING_STATUS_COMPLETED,
    ]);


    // =========================================================
    // BOOKING SOURCES
    // =========================================================

    define('BOOKING_SOURCE_DIRECT', 'direct');
    define('BOOKING_SOURCE_WEBSITE', 'website');
    define('BOOKING_SOURCE_PHONE', 'phone');
    define('BOOKING_SOURCE_EMAIL', 'email');
    define('BOOKING_SOURCE_WALK_IN', 'walk_in');
    define('BOOKING_SOURCE_AGENT', 'agent');
    define('BOOKING_SOURCE_OTA', 'ota');

    define('BOOKING_SOURCES', [
        BOOKING_SOURCE_DIRECT,
        BOOKING_SOURCE_WEBSITE,
        BOOKING_SOURCE_PHONE,
        BOOKING_SOURCE_EMAIL,
        BOOKING_SOURCE_WALK_IN,
        BOOKING_SOURCE_AGENT,
        BOOKING_SOURCE_OTA,
    ]);


    // =========================================================
    // PAYMENT STATUS
    // =========================================================

    define('PAYMENT_STATUS_PENDING', 'pending');
    define('PAYMENT_STATUS_PAID', 'paid');
    define('PAYMENT_STATUS_PARTIAL', 'partial');
    define('PAYMENT_STATUS_FAILED', 'failed');
    define('PAYMENT_STATUS_REFUNDED', 'refunded');
    define('PAYMENT_STATUS_CANCELLED', 'cancelled');

    define('PAYMENT_STATUSES', [
        PAYMENT_STATUS_PENDING,
        PAYMENT_STATUS_PAID,
        PAYMENT_STATUS_PARTIAL,
        PAYMENT_STATUS_FAILED,
        PAYMENT_STATUS_REFUNDED,
        PAYMENT_STATUS_CANCELLED,
    ]);


    // =========================================================
    // PAYMENT METHODS
    // =========================================================

    define('PAYMENT_METHOD_CASH', 'cash');
    define('PAYMENT_METHOD_CARD', 'card');
    define('PAYMENT_METHOD_CREDIT_CARD', 'credit_card');
    define('PAYMENT_METHOD_DEBIT_CARD', 'debit_card');
    define('PAYMENT_METHOD_BANK_TRANSFER', 'bank_transfer');
    define('PAYMENT_METHOD_ONLINE', 'online');
    define('PAYMENT_METHOD_PAYPAL', 'paypal');
    define('PAYMENT_METHOD_OTHER', 'other');

    define('PAYMENT_METHODS', [
        PAYMENT_METHOD_CASH,
        PAYMENT_METHOD_CARD,
        PAYMENT_METHOD_CREDIT_CARD,
        PAYMENT_METHOD_DEBIT_CARD,
        PAYMENT_METHOD_BANK_TRANSFER,
        PAYMENT_METHOD_ONLINE,
        PAYMENT_METHOD_PAYPAL,
        PAYMENT_METHOD_OTHER,
    ]);


    // =========================================================
    // INVOICE STATUS
    // =========================================================

    define('INVOICE_STATUS_DRAFT', 'draft');
    define('INVOICE_STATUS_ISSUED', 'issued');
    define('INVOICE_STATUS_PARTIAL', 'partial');
    define('INVOICE_STATUS_PAID', 'paid');
    define('INVOICE_STATUS_OVERDUE', 'overdue');
    define('INVOICE_STATUS_CANCELLED', 'cancelled');

    define('INVOICE_STATUSES', [
        INVOICE_STATUS_DRAFT,
        INVOICE_STATUS_ISSUED,
        INVOICE_STATUS_PARTIAL,
        INVOICE_STATUS_PAID,
        INVOICE_STATUS_OVERDUE,
        INVOICE_STATUS_CANCELLED,
    ]);


    // =========================================================
    // GUEST STATUS
    // =========================================================

    define('GUEST_STATUS_ACTIVE', 'active');
    define('GUEST_STATUS_INACTIVE', 'inactive');
    define('GUEST_STATUS_BLACKLISTED', 'blacklisted');

    define('GUEST_STATUSES', [
        GUEST_STATUS_ACTIVE,
        GUEST_STATUS_INACTIVE,
        GUEST_STATUS_BLACKLISTED,
    ]);


    // =========================================================
    // GUEST DOCUMENT TYPES
    // =========================================================

    define('DOCUMENT_TYPE_PASSPORT', 'passport');
    define('DOCUMENT_TYPE_NATIONAL_ID', 'national_id');
    define('DOCUMENT_TYPE_DRIVERS_LICENSE', 'drivers_license');
    define('DOCUMENT_TYPE_OTHER', 'other');

    define('GUEST_DOCUMENT_TYPES', [
        DOCUMENT_TYPE_PASSPORT,
        DOCUMENT_TYPE_NATIONAL_ID,
        DOCUMENT_TYPE_DRIVERS_LICENSE,
        DOCUMENT_TYPE_OTHER,
    ]);


    // =========================================================
    // STAFF STATUS
    // =========================================================

    define('STAFF_STATUS_ACTIVE', 'active');
    define('STAFF_STATUS_INACTIVE', 'inactive');
    define('STAFF_STATUS_ON_LEAVE', 'on_leave');
    define('STAFF_STATUS_TERMINATED', 'terminated');

    define('STAFF_STATUSES', [
        STAFF_STATUS_ACTIVE,
        STAFF_STATUS_INACTIVE,
        STAFF_STATUS_ON_LEAVE,
        STAFF_STATUS_TERMINATED,
    ]);


    // =========================================================
    // STAFF DEPARTMENTS
    // =========================================================

    define('DEPARTMENT_MANAGEMENT', 'management');
    define('DEPARTMENT_FRONT_DESK', 'front_desk');
    define('DEPARTMENT_HOUSEKEEPING', 'housekeeping');
    define('DEPARTMENT_FOOD_BEVERAGE', 'food_beverage');
    define('DEPARTMENT_MAINTENANCE', 'maintenance');
    define('DEPARTMENT_SECURITY', 'security');
    define('DEPARTMENT_ACCOUNTING', 'accounting');
    define('DEPARTMENT_HR', 'human_resources');
    define('DEPARTMENT_IT', 'it');

    define('STAFF_DEPARTMENTS', [
        DEPARTMENT_MANAGEMENT,
        DEPARTMENT_FRONT_DESK,
        DEPARTMENT_HOUSEKEEPING,
        DEPARTMENT_FOOD_BEVERAGE,
        DEPARTMENT_MAINTENANCE,
        DEPARTMENT_SECURITY,
        DEPARTMENT_ACCOUNTING,
        DEPARTMENT_HR,
        DEPARTMENT_IT,
    ]);


    // =========================================================
    // REPORT TYPES
    // =========================================================

    define('REPORT_TYPE_REVENUE', 'revenue');
    define('REPORT_TYPE_OCCUPANCY', 'occupancy');
    define('REPORT_TYPE_BOOKINGS', 'bookings');
    define('REPORT_TYPE_PAYMENTS', 'payments');
    define('REPORT_TYPE_GUESTS', 'guests');
    define('REPORT_TYPE_ROOMS', 'rooms');
    define('REPORT_TYPE_STAFF', 'staff');

    define('REPORT_TYPES', [
        REPORT_TYPE_REVENUE,
        REPORT_TYPE_OCCUPANCY,
        REPORT_TYPE_BOOKINGS,
        REPORT_TYPE_PAYMENTS,
        REPORT_TYPE_GUESTS,
        REPORT_TYPE_ROOMS,
        REPORT_TYPE_STAFF,
    ]);


    // =========================================================
    // REPORT PERIODS
    // =========================================================

    define('REPORT_PERIOD_TODAY', 'today');
    define('REPORT_PERIOD_YESTERDAY', 'yesterday');
    define('REPORT_PERIOD_WEEK', 'week');
    define('REPORT_PERIOD_MONTH', 'month');
    define('REPORT_PERIOD_QUARTER', 'quarter');
    define('REPORT_PERIOD_YEAR', 'year');
    define('REPORT_PERIOD_CUSTOM', 'custom');

    define('REPORT_PERIODS', [
        REPORT_PERIOD_TODAY,
        REPORT_PERIOD_YESTERDAY,
        REPORT_PERIOD_WEEK,
        REPORT_PERIOD_MONTH,
        REPORT_PERIOD_QUARTER,
        REPORT_PERIOD_YEAR,
        REPORT_PERIOD_CUSTOM,
    ]);


    // =========================================================
    // HTTP STATUS CODES
    // =========================================================

    define('HTTP_OK', 200);
    define('HTTP_CREATED', 201);
    define('HTTP_ACCEPTED', 202);
    define('HTTP_NO_CONTENT', 204);

    define('HTTP_BAD_REQUEST', 400);
    define('HTTP_UNAUTHORIZED', 401);
    define('HTTP_FORBIDDEN', 403);
    define('HTTP_NOT_FOUND', 404);
    define('HTTP_METHOD_NOT_ALLOWED', 405);
    define('HTTP_CONFLICT', 409);
    define('HTTP_UNPROCESSABLE_ENTITY', 422);
    define('HTTP_TOO_MANY_REQUESTS', 429);

    define('HTTP_INTERNAL_SERVER_ERROR', 500);
    define('HTTP_NOT_IMPLEMENTED', 501);
    define('HTTP_SERVICE_UNAVAILABLE', 503);


    // =========================================================
    // API RESPONSE TYPES
    // =========================================================

    define('RESPONSE_SUCCESS', 'success');
    define('RESPONSE_ERROR', 'error');
    define('RESPONSE_VALIDATION_ERROR', 'validation_error');
    define('RESPONSE_UNAUTHORIZED', 'unauthorized');
    define('RESPONSE_FORBIDDEN', 'forbidden');
    define('RESPONSE_NOT_FOUND', 'not_found');


    // =========================================================
    // VALIDATION
    // =========================================================

    define('MIN_PASSWORD_LENGTH', 8);
    define('MAX_PASSWORD_LENGTH', 128);

    define('MIN_NAME_LENGTH', 2);
    define('MAX_NAME_LENGTH', 100);

    define('MAX_EMAIL_LENGTH', 255);
    define('MAX_PHONE_LENGTH', 30);

    define('MAX_ADDRESS_LENGTH', 500);

    define('MIN_ROOM_NUMBER_LENGTH', 1);
    define('MAX_ROOM_NUMBER_LENGTH', 20);

    define('MAX_ROOM_DESCRIPTION_LENGTH', 2000);

    define('MAX_GUEST_NOTES_LENGTH', 2000);
    define('MAX_BOOKING_NOTES_LENGTH', 2000);


    // =========================================================
    // DATE / TIME FORMATS
    // =========================================================

    define('DATE_FORMAT', 'Y-m-d');
    define('DATETIME_FORMAT', 'Y-m-d H:i:s');
    define('TIME_FORMAT', 'H:i:s');

    define('DISPLAY_DATE_FORMAT', 'M d, Y');
    define('DISPLAY_DATETIME_FORMAT', 'M d, Y H:i');


    // =========================================================
    // CHECK-IN / CHECK-OUT DEFAULTS
    // =========================================================

    define('DEFAULT_CHECK_IN_TIME', '14:00:00');
    define('DEFAULT_CHECK_OUT_TIME', '11:00:00');


    // =========================================================
    // CURRENCY / MONEY
    // =========================================================

    define('DEFAULT_CURRENCY_DECIMALS', 2);

    define('MIN_PAYMENT_AMOUNT', 0.01);
    define('MAX_PAYMENT_AMOUNT', 999999999.99);


    // =========================================================
    // FILE UPLOADS
    // =========================================================

    define('ALLOWED_IMAGE_EXTENSIONS', [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ]);

    define('ALLOWED_DOCUMENT_EXTENSIONS', [
        'pdf',
        'jpg',
        'jpeg',
        'png',
    ]);

    define('MAX_IMAGE_SIZE', 5242880);       // 5 MB
    define('MAX_DOCUMENT_SIZE', 10485760);   // 10 MB


    // =========================================================
    // MIME TYPES
    // =========================================================

    define('MIME_IMAGE_JPEG', 'image/jpeg');
    define('MIME_IMAGE_PNG', 'image/png');
    define('MIME_IMAGE_WEBP', 'image/webp');

    define('MIME_APPLICATION_PDF', 'application/pdf');


    // =========================================================
    // LOG LEVELS
    // =========================================================

    define('LOG_LEVEL_DEBUG', 'debug');
    define('LOG_LEVEL_INFO', 'info');
    define('LOG_LEVEL_WARNING', 'warning');
    define('LOG_LEVEL_ERROR', 'error');
    define('LOG_LEVEL_CRITICAL', 'critical');

    define('LOG_LEVELS', [
        LOG_LEVEL_DEBUG,
        LOG_LEVEL_INFO,
        LOG_LEVEL_WARNING,
        LOG_LEVEL_ERROR,
        LOG_LEVEL_CRITICAL,
    ]);


    // =========================================================
    // AUTHENTICATION
    // =========================================================

    define('AUTH_HEADER', 'Authorization');
    define('AUTH_SCHEME', 'Bearer');

    define('TOKEN_TYPE_ACCESS', 'access');
    define('TOKEN_TYPE_REFRESH', 'refresh');


    // =========================================================
    // SECURITY
    // =========================================================

    define('BCRYPT_COST', 12);

    define('PASSWORD_RESET_TOKEN_LENGTH', 64);

    define(
        'PASSWORD_RESET_EXPIRATION',
        3600
    );

    define(
        'EMAIL_VERIFICATION_EXPIRATION',
        86400
    );


    // =========================================================
    // CSRF
    // =========================================================

    define(
        'CSRF_TOKEN_LENGTH',
        64
    );

    define(
        'CSRF_HEADER',
        'X-CSRF-TOKEN'
    );


    // =========================================================
    // PAGINATION
    // =========================================================

    define('DEFAULT_PAGE', 1);
    define('DEFAULT_LIMIT', 20);
    define('MAX_LIMIT', 100);


    // =========================================================
    // SORTING
    // =========================================================

    define('SORT_ASC', 'asc');
    define('SORT_DESC', 'desc');

    define('SORT_DIRECTIONS', [
        SORT_ASC,
        SORT_DESC,
    ]);


    // =========================================================
    // DATABASE
    // =========================================================

    define(
        'DATABASE_DEFAULT_CHARSET',
        'utf8mb4'
    );

    define(
        'DATABASE_DEFAULT_COLLATION',
        'utf8mb4_unicode_ci'
    );


    // =========================================================
    // CACHE
    // =========================================================

    define('CACHE_DRIVER_NONE', 'none');
    define('CACHE_DRIVER_FILE', 'file');
    define('CACHE_DRIVER_REDIS', 'redis');

    define(
        'DEFAULT_CACHE_DRIVER',
        CACHE_DRIVER_NONE
    );


    // =========================================================
    // NOTIFICATION TYPES
    // =========================================================

    define('NOTIFICATION_INFO', 'info');
    define('NOTIFICATION_SUCCESS', 'success');
    define('NOTIFICATION_WARNING', 'warning');
    define('NOTIFICATION_ERROR', 'error');

    define('NOTIFICATION_TYPES', [
        NOTIFICATION_INFO,
        NOTIFICATION_SUCCESS,
        NOTIFICATION_WARNING,
        NOTIFICATION_ERROR,
    ]);


    // =========================================================
    // NOTIFICATION EVENTS
    // =========================================================

    define('EVENT_BOOKING_CREATED', 'booking.created');
    define('EVENT_BOOKING_CONFIRMED', 'booking.confirmed');
    define('EVENT_BOOKING_CANCELLED', 'booking.cancelled');
    define('EVENT_CHECK_IN', 'booking.check_in');
    define('EVENT_CHECK_OUT', 'booking.check_out');

    define('EVENT_PAYMENT_RECEIVED', 'payment.received');
    define('EVENT_PAYMENT_FAILED', 'payment.failed');
    define('EVENT_PAYMENT_REFUNDED', 'payment.refunded');

    define('EVENT_USER_REGISTERED', 'user.registered');
    define('EVENT_PASSWORD_RESET', 'password.reset');


    // =========================================================
    // API RATE LIMITING
    // =========================================================

    define('RATE_LIMIT_ENABLED', true);

    define(
        'RATE_LIMIT_REQUESTS',
        100
    );

    define(
        'RATE_LIMIT_WINDOW',
        60
    );


    // =========================================================
    // APPLICATION FEATURES
    // =========================================================

    define('FEATURE_EMAIL_NOTIFICATIONS', true);
    define('FEATURE_SMS_NOTIFICATIONS', false);
    define('FEATURE_ONLINE_PAYMENTS', true);
    define('FEATURE_REPORT_EXPORT', true);
    define('FEATURE_AUDIT_LOG', true);


    // =========================================================
    // UTILITY HELPERS
    // =========================================================

    if (!function_exists('isValidConstantValue')) {

        /**
         * Check whether a value exists in an allowed constants array.
         */
        function isValidConstantValue(
            mixed $value,
            array $allowedValues
        ): bool {
            return in_array(
                $value,
                $allowedValues,
                true
            );
        }
    }


    if (!function_exists('isValidRole')) {

        /**
         * Check whether a user role is valid.
         */
        function isValidRole(string $role): bool
        {
            return isValidConstantValue(
                $role,
                USER_ROLES
            );
        }
    }


    if (!function_exists('isValidUserStatus')) {

        /**
         * Check whether a user status is valid.
         */
        function isValidUserStatus(string $status): bool
        {
            return isValidConstantValue(
                $status,
                USER_STATUSES
            );
        }
    }


    if (!function_exists('isValidRoomStatus')) {

        /**
         * Check whether a room status is valid.
         */
        function isValidRoomStatus(string $status): bool
        {
            return isValidConstantValue(
                $status,
                ROOM_STATUSES
            );
        }
    }


    if (!function_exists('isValidRoomType')) {

        /**
         * Check whether a room type is valid.
         */
        function isValidRoomType(string $type): bool
        {
            return isValidConstantValue(
                $type,
                ROOM_TYPES
            );
        }
    }


    if (!function_exists('isValidBedType')) {

        /**
         * Check whether a bed type is valid.
         */
        function isValidBedType(string $type): bool
        {
            return isValidConstantValue(
                $type,
                BED_TYPES
            );
        }
    }


    if (!function_exists('isValidBookingStatus')) {

        /**
         * Check whether a booking status is valid.
         */
        function isValidBookingStatus(string $status): bool
        {
            return isValidConstantValue(
                $status,
                BOOKING_STATUSES
            );
        }
    }


    if (!function_exists('isValidBookingSource')) {

        /**
         * Check whether a booking source is valid.
         */
        function isValidBookingSource(string $source): bool
        {
            return isValidConstantValue(
                $source,
                BOOKING_SOURCES
            );
        }
    }


    if (!function_exists('isValidPaymentStatus')) {

        /**
         * Check whether a payment status is valid.
         */
        function isValidPaymentStatus(string $status): bool
        {
            return isValidConstantValue(
                $status,
                PAYMENT_STATUSES
            );
        }
    }


    if (!function_exists('isValidPaymentMethod')) {

        /**
         * Check whether a payment method is valid.
         */
        function isValidPaymentMethod(string $method): bool
        {
            return isValidConstantValue(
                $method,
                PAYMENT_METHODS
            );
        }
    }


    if (!function_exists('isValidGuestDocumentType')) {

        /**
         * Check whether a guest document type is valid.
         */
        function isValidGuestDocumentType(string $type): bool
        {
            return isValidConstantValue(
                $type,
                GUEST_DOCUMENT_TYPES
            );
        }
    }


    if (!function_exists('isValidStaffStatus')) {

        /**
         * Check whether a staff status is valid.
         */
        function isValidStaffStatus(string $status): bool
        {
            return isValidConstantValue(
                $status,
                STAFF_STATUSES
            );
        }
    }


    if (!function_exists('isValidDepartment')) {

        /**
         * Check whether a department is valid.
         */
        function isValidDepartment(string $department): bool
        {
            return isValidConstantValue(
                $department,
                STAFF_DEPARTMENTS
            );
        }
    }


    if (!function_exists('isValidReportType')) {

        /**
         * Check whether a report type is valid.
         */
        function isValidReportType(string $type): bool
        {
            return isValidConstantValue(
                $type,
                REPORT_TYPES
            );
        }
    }


    if (!function_exists('isValidReportPeriod')) {

        /**
         * Check whether a report period is valid.
         */
        function isValidReportPeriod(string $period): bool
        {
            return isValidConstantValue(
                $period,
                REPORT_PERIODS
            );
        }
    }


    if (!function_exists('isValidNotificationType')) {

        /**
         * Check whether a notification type is valid.
         */
        function isValidNotificationType(string $type): bool
        {
            return isValidConstantValue(
                $type,
                NOTIFICATION_TYPES
            );
        }
    }


    if (!function_exists('isValidResponseType')) {

        /**
         * Check whether a response type is valid.
         */
        function isValidResponseType(string $type): bool
        {
            return isValidConstantValue(
                $type,
                [
                    RESPONSE_SUCCESS,
                    RESPONSE_ERROR,
                    RESPONSE_VALIDATION_ERROR,
                    RESPONSE_UNAUTHORIZED,
                    RESPONSE_FORBIDDEN,
                    RESPONSE_NOT_FOUND,
                ]
            );
        }
    }

    define('APP_CONSTANTS_LOADED', true);
}

