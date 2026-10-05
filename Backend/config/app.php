<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Application Configuration
 *
 * File:
 * backend/config/app.php
 */

// ---------------------------------------------------------
// Prevent Direct Access
// ---------------------------------------------------------

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!function_exists('app_env_value')) {
    /**
     * Retrieve a value from the environment with a safe fallback.
     */
    function app_env_value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('app_normalize_environment')) {
    /**
     * Normalize the runtime environment value.
     */
    function app_normalize_environment(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array(
            $normalized,
            ['production', 'staging', 'testing', 'development'],
            true
        )
            ? $normalized
            : 'development';
    }
}

if (!function_exists('app_normalize_url')) {
    /**
     * Normalize a URL and ensure it does not end with a trailing slash.
     */
    function app_normalize_url(string $value, string $default): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            $normalized = $default;
        }

        return rtrim($normalized, '/');
    }
}

if (!function_exists('app_normalize_api_prefix')) {
    /**
     * Ensure the API prefix always starts with a single leading slash.
     */
    function app_normalize_api_prefix(string $value): string
    {
        $normalized = '/' . trim($value, '/');

        return $normalized === '/' ? '/api' : $normalized;
    }
}


// ---------------------------------------------------------
// Application Environment
// ---------------------------------------------------------

$appEnvironment = app_env_value(
    'APP_ENV',
    'development'
);

$appEnvironment = app_normalize_environment(
    (string) $appEnvironment
);


// ---------------------------------------------------------
// Debug Mode
// ---------------------------------------------------------

$debugValue = app_env_value(
    'APP_DEBUG',
    'false'
);

$appDebug = filter_var(
    (string) $debugValue,
    FILTER_VALIDATE_BOOLEAN
);

$appDebug = $appDebug === true;


// ---------------------------------------------------------
// Application Name
// ---------------------------------------------------------

$appName = trim(
    (string) app_env_value(
        'APP_NAME',
        'Hotel Management System'
    )
);

if ($appName === '') {
    $appName = 'Hotel Management System';
}


// ---------------------------------------------------------
// Application URL
// ---------------------------------------------------------

$appUrl = app_normalize_url(
    (string) app_env_value('APP_URL', 'http://localhost'),
    'http://localhost'
);


// ---------------------------------------------------------
// API Configuration
// ---------------------------------------------------------

$apiPrefix = app_normalize_api_prefix(
    (string) app_env_value('API_PREFIX', '/api')
);


// ---------------------------------------------------------
// API Version
// ---------------------------------------------------------

$apiVersion = trim(
    (string) app_env_value('API_VERSION', 'v1'),
    '/'
);

if ($apiVersion === '') {
    $apiVersion = 'v1';
}


// ---------------------------------------------------------
// Timezone
// ---------------------------------------------------------

$appTimezone = trim(
    (string) app_env_value('APP_TIMEZONE', 'UTC')
);

if (
    !in_array(
        $appTimezone,
        timezone_identifiers_list(),
        true
    )
) {
    $appTimezone = 'UTC';
}

date_default_timezone_set(
    $appTimezone
);


// ---------------------------------------------------------
// Locale
// ---------------------------------------------------------

$appLocale = trim(
    (string) app_env_value('APP_LOCALE', 'en_US')
);

if ($appLocale === '') {
    $appLocale = 'en_US';
}


// ---------------------------------------------------------
// Currency
// ---------------------------------------------------------

$appCurrency = strtoupper(
    trim(
        (string) app_env_value('APP_CURRENCY', 'USD')
    )
);

if ($appCurrency === '') {
    $appCurrency = 'USD';
}


// ---------------------------------------------------------
// Application Settings
// ---------------------------------------------------------

define(
    'APP_NAME',
    $appName
);

define(
    'APP_ENV',
    $appEnvironment
);

define(
    'APP_DEBUG',
    $appDebug
);

define(
    'APP_URL',
    $appUrl
);

define(
    'APP_TIMEZONE',
    $appTimezone
);

define(
    'APP_LOCALE',
    $appLocale
);

define(
    'APP_CURRENCY',
    $appCurrency
);

define(
    'APP_VERSION',
    '1.0.0'
);


// ---------------------------------------------------------
// API Settings
// ---------------------------------------------------------

define(
    'API_PREFIX',
    $apiPrefix
);

define(
    'API_VERSION',
    $apiVersion
);

define(
    'API_URL',
    APP_URL . API_PREFIX . '/' . API_VERSION
);


// ---------------------------------------------------------
// Frontend URL
// ---------------------------------------------------------

$frontendUrl = app_normalize_url(
    (string) app_env_value('FRONTEND_URL', 'http://localhost:3000'),
    'http://localhost:3000'
);

define(
    'FRONTEND_URL',
    $frontendUrl
);


// ---------------------------------------------------------
// Session Configuration
// ---------------------------------------------------------

$sessionName = trim(
    (string) app_env_value('SESSION_NAME', 'hotel_management_session')
);

if ($sessionName === '') {
    $sessionName = 'hotel_management_session';
}

define(
    'SESSION_NAME',
    $sessionName
);


// ---------------------------------------------------------
// Session Lifetime
// ---------------------------------------------------------

$sessionLifetime = (int) app_env_value(
    'SESSION_LIFETIME',
    7200
);

define(
    'SESSION_LIFETIME',
    max(300, $sessionLifetime)
);


// ---------------------------------------------------------
// JWT Configuration
// ---------------------------------------------------------

$jwtSecret = trim(
    (string) app_env_value('JWT_SECRET', '')
);

$jwtAlgorithm = strtoupper(
    trim(
        (string) app_env_value('JWT_ALGORITHM', 'HS256')
    )
);

if (!in_array($jwtAlgorithm, ['HS256', 'HS384', 'HS512', 'RS256', 'ES256'], true)) {
    $jwtAlgorithm = 'HS256';
}

$jwtExpiration = (int) app_env_value(
    'JWT_EXPIRATION',
    3600
);


// ---------------------------------------------------------
// JWT Security Validation
// ---------------------------------------------------------

if (
    APP_ENV === 'production' &&
    $jwtSecret !== '' &&
    strlen($jwtSecret) < 32
) {
    error_log(
        'WARNING: JWT_SECRET should contain at least 32 characters in production.'
    );
}

define(
    'JWT_SECRET',
    $jwtSecret
);

define(
    'JWT_ALGORITHM',
    $jwtAlgorithm
);

define(
    'JWT_EXPIRATION',
    max(300, $jwtExpiration)
);


// ---------------------------------------------------------
// Password Configuration
// ---------------------------------------------------------

define(
    'PASSWORD_ALGORITHM',
    PASSWORD_DEFAULT
);

define(
    'PASSWORD_MIN_LENGTH',
    8
);


// ---------------------------------------------------------
// Pagination
// ---------------------------------------------------------

$defaultPerPage = (int) app_env_value(
    'DEFAULT_PER_PAGE',
    20
);

$maxPerPage = (int) app_env_value(
    'MAX_PER_PAGE',
    100
);

define(
    'DEFAULT_PER_PAGE',
    max(1, $defaultPerPage)
);

define(
    'MAX_PER_PAGE',
    max(DEFAULT_PER_PAGE, $maxPerPage)
);


// ---------------------------------------------------------
// Upload Configuration
// ---------------------------------------------------------

$uploadMaxSize = (int) app_env_value(
    'UPLOAD_MAX_SIZE',
    5242880
);

define(
    'UPLOAD_MAX_SIZE',
    max(1024, $uploadMaxSize)
);

define(
    'UPLOAD_PATH',
    BASE_PATH . '/storage/uploads'
);


// ---------------------------------------------------------
// Logging
// ---------------------------------------------------------

define(
    'LOG_PATH',
    BASE_PATH . '/storage/logs'
);

define(
    'APP_LOG_FILE',
    LOG_PATH . '/app.log'
);

define(
    'ERROR_LOG_FILE',
    LOG_PATH . '/error.log'
);


// ---------------------------------------------------------
// CORS Configuration
// ---------------------------------------------------------

$corsOriginsRaw = app_env_value(
    'CORS_ORIGINS',
    FRONTEND_URL
);

$corsOrigins = array_values(
    array_unique(
        array_filter(
            array_map(
                static function (string $origin): ?string {
                    $origin = trim($origin);

                    return $origin === '' ? null : $origin;
                },
                preg_split('/\s*,\s*/', trim((string) $corsOriginsRaw), -1, PREG_SPLIT_NO_EMPTY) ?: []
            )
        )
    )
);

if ($corsOrigins === []) {
    $corsOrigins = [FRONTEND_URL];
}

define(
    'CORS_ORIGINS',
    $corsOrigins
);

define(
    'CORS_ALLOW_CREDENTIALS',
    true
);


// ---------------------------------------------------------
// Security Headers
// ---------------------------------------------------------

define(
    'SECURITY_HEADERS_ENABLED',
    true
);


// ---------------------------------------------------------
// JSON Configuration
// ---------------------------------------------------------

define(
    'JSON_FLAGS',
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);


// ---------------------------------------------------------
// Create Required Storage Directories
// ---------------------------------------------------------

$requiredDirectories = [
    LOG_PATH,
    UPLOAD_PATH,
];

foreach ($requiredDirectories as $directory) {
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        error_log(
            sprintf(
                'WARNING: Unable to create required directory: %s',
                $directory
            )
        );
    }
}


// ---------------------------------------------------------
// Application Configuration Helper
// ---------------------------------------------------------

if (!function_exists('config')) {

    /**
     * Retrieve an application configuration value.
     *
     * Example:
     *
     * config('app.name')
     * config('app.environment')
     * config('api.url')
     */
    function config(
        string $key,
        mixed $default = null
    ): mixed {

        $configuration = [

            'app' => [
                'name' => APP_NAME,
                'environment' => APP_ENV,
                'debug' => APP_DEBUG,
                'url' => APP_URL,
                'timezone' => APP_TIMEZONE,
                'locale' => APP_LOCALE,
                'currency' => APP_CURRENCY,
                'version' => APP_VERSION,
            ],

            'api' => [
                'prefix' => API_PREFIX,
                'version' => API_VERSION,
                'url' => API_URL,
            ],

            'frontend' => [
                'url' => FRONTEND_URL,
            ],

            'session' => [
                'name' => SESSION_NAME,
                'lifetime' => SESSION_LIFETIME,
            ],

            'jwt' => [
                'secret' => JWT_SECRET,
                'algorithm' => JWT_ALGORITHM,
                'expiration' => JWT_EXPIRATION,
            ],

            'password' => [
                'algorithm' => PASSWORD_ALGORITHM,
                'min_length' => PASSWORD_MIN_LENGTH,
            ],

            'pagination' => [
                'default' => DEFAULT_PER_PAGE,
                'max' => MAX_PER_PAGE,
            ],

            'upload' => [
                'max_size' => UPLOAD_MAX_SIZE,
                'path' => UPLOAD_PATH,
            ],

            'logging' => [
                'path' => LOG_PATH,
                'app_file' => APP_LOG_FILE,
                'error_file' => ERROR_LOG_FILE,
            ],

            'cors' => [
                'origins' => CORS_ORIGINS,
                'credentials' => CORS_ALLOW_CREDENTIALS,
            ],
        ];

        $segments = explode(
            '.',
            $key
        );

        $value = $configuration;

        foreach ($segments as $segment) {

            if (
                !is_array($value) ||
                !array_key_exists(
                    $segment,
                    $value
                )
            ) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
