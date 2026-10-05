<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * CORS Middleware
 *
 * File:
 * backend/middleware/cors.php
 *
 * Responsibilities:
 * - Configure Cross-Origin Resource Sharing
 * - Validate the requesting Origin
 * - Handle OPTIONS preflight requests
 * - Configure allowed HTTP methods
 * - Configure allowed request headers
 * - Support credentials when explicitly allowed
 *
 * IMPORTANT:
 * - Never use "*" for Access-Control-Allow-Origin when
 *   credentials are enabled.
 * - Production origins should be explicitly configured.
 */

// ---------------------------------------------------------
// Dependencies
// ---------------------------------------------------------

if (!defined('BASE_PATH')) {
    define(
        'BASE_PATH',
        dirname(__DIR__)
    );
}

$configFile = BASE_PATH . '/config/app.php';

if (file_exists($configFile)) {
    require_once $configFile;
}

// ---------------------------------------------------------
// CORS Middleware
// ---------------------------------------------------------

class CorsMiddleware
{
    /**
     * Default development origins.
     *
     * These are useful during local development.
     *
     * Production applications should configure the
     * frontend URL explicitly in app.php.
     */
    private const DEFAULT_ALLOWED_ORIGINS = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
    ];

    /**
     * Allowed HTTP methods.
     */
    private const ALLOWED_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ];

    /**
     * Allowed request headers.
     */
    private const ALLOWED_HEADERS = [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-CSRF-Token',
    ];

    /**
     * Headers exposed to the browser.
     */
    private const EXPOSED_HEADERS = [
        'Content-Length',
        'Content-Type',
        'X-Request-ID',
    ];

    /**
     * Browser preflight cache duration.
     *
     * 1 hour.
     */
    private const MAX_AGE = 3600;

    // =====================================================
    // Handle
    // =====================================================

    /**
     * Apply CORS headers.
     *
     * Call this before sending any API response.
     *
     * Example:
     *
     * CorsMiddleware::handle();
     */
    public static function handle(): void
    {
        $origin = self::getOrigin();

        // -------------------------------------------------
        // Always tell caches that response varies by Origin
        // -------------------------------------------------
        header(
            'Vary: Origin',
            false
        );

        // -------------------------------------------------
        // No Origin
        // -------------------------------------------------

        /*
         * Requests such as:
         * - server-to-server requests
         * - curl requests
         * - some same-origin requests
         *
         * may not contain an Origin header.
         *
         * They do not require an Access-Control-Allow-Origin
         * response header.
         */

        if (
            $origin === null
        ) {

            self::handlePreflightWithoutOrigin();
            return;
        }

        // -------------------------------------------------
        // Validate Origin
        // -------------------------------------------------

        if (
            !self::isAllowedOrigin(
                $origin
            )
        ) {

            self::handleDisallowedOrigin();
            return;
        }

        // -------------------------------------------------
        // Allowed Origin
        // -------------------------------------------------

        header(
            'Access-Control-Allow-Origin: ' .
            $origin
        );

        header(
            'Access-Control-Allow-Credentials: true'
        );

        header(
            'Access-Control-Expose-Headers: ' .
            implode(
                ', ',
                self::EXPOSED_HEADERS
            )
        );

        // -------------------------------------------------
        // Preflight Request
        // -------------------------------------------------

        if (
            self::isPreflightRequest()
        ) {
            self::handlePreflight();

            return;
        }
    }

    // =====================================================
    // Preflight
    // =====================================================

    /**
     * Handle an OPTIONS preflight request.
     */
    private static function handlePreflight(): never {

        $requestedMethod =
            self::getRequestedMethod();

        $requestedHeaders =
            self::getRequestedHeaders();

        // -------------------------------------------------
        // Validate Requested Method
        // -------------------------------------------------

        if (
            $requestedMethod !== null &&
            !in_array(
                strtoupper($requestedMethod),
                self::ALLOWED_METHODS,
                true
            )
        ) {

            self::sendError(
                405,
                'CORS method is not allowed.'
            );
        }

        // -------------------------------------------------
        // Validate Requested Headers
        // -------------------------------------------------

        if (
            $requestedHeaders !== null &&
            !self::areHeadersAllowed(
                $requestedHeaders
            )
        ) {

            self::sendError(
                400,
                'CORS request headers are not allowed.'
            );
        }

        // -------------------------------------------------
        // CORS Preflight Headers
        // -------------------------------------------------

        header(
            'Access-Control-Allow-Methods: ' .
            implode(
                ', ',
                self::ALLOWED_METHODS
            )
        );

        header(
            'Access-Control-Allow-Headers: ' .
            implode(
                ', ',
                self::ALLOWED_HEADERS
            )
        );

        header(
            'Access-Control-Max-Age: ' .
            self::MAX_AGE
        );

        http_response_code(204);

        exit;
    }

    /**
     * Handle OPTIONS request where Origin is absent.
     */
    private static function handlePreflightWithoutOrigin(): void
    {
        if (
            !self::isPreflightRequest()
        ) {
            return;
        }

        header(
            'Access-Control-Allow-Methods: ' .
            implode(
                ', ',
                self::ALLOWED_METHODS
            )
        );

        header(
            'Access-Control-Allow-Headers: ' .
            implode(
                ', ',
                self::ALLOWED_HEADERS
            )
        );

        header(
            'Access-Control-Max-Age: ' .
            self::MAX_AGE
        );

        http_response_code(204);

        exit;
    }

    // =====================================================
    // Origin
    // =====================================================

    /**
     * Get Origin request header.
     */
    private static function getOrigin(): ?string
    {
        if (
            !isset($_SERVER['HTTP_ORIGIN'])
        ) {
            return null;
        }

        $origin = trim(
            (string) $_SERVER['HTTP_ORIGIN']
        );

        return $origin !== ''
            ? $origin
            : null;
    }

    /**
     * Determine whether origin is allowed.
     */
    private static function isAllowedOrigin(
        string $origin
    ): bool {

        $allowedOrigins =
            self::getAllowedOrigins();

        // -------------------------------------------------
        // Explicit Origin Match
        // -------------------------------------------------

        foreach (
            $allowedOrigins as $allowedOrigin
        ) {

            if (
                self::normalizeOrigin(
                    $allowedOrigin
                ) ===
                self::normalizeOrigin(
                    $origin
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get allowed origins from configuration.
     *
     * Supported app.php configuration:
     *
     * APP_CORS_ORIGINS
     *
     * or:
     *
     * CORS_ALLOWED_ORIGINS
     */
    private static function getAllowedOrigins(): array
    {
        $origins = null;

        // -------------------------------------------------
        // Constant: APP_CORS_ORIGINS
        // -------------------------------------------------

        if (
            defined('APP_CORS_ORIGINS')
        ) {

            $origins =
                constant(
                    'APP_CORS_ORIGINS'
                );
        }

        // -------------------------------------------------
        // Constant: CORS_ALLOWED_ORIGINS
        // -------------------------------------------------

        if (
            $origins === null &&
            defined('CORS_ALLOWED_ORIGINS')
        ) {

            $origins =
                constant(
                    'CORS_ALLOWED_ORIGINS'
                );
        }

        // -------------------------------------------------
        // Environment Variable
        // -------------------------------------------------

        if (
            $origins === null
        ) {

            $environmentOrigins =
                getenv(
                    'CORS_ALLOWED_ORIGINS'
                );

            if (
                $environmentOrigins !== false &&
                trim(
                    $environmentOrigins
                ) !== ''
            ) {

                $origins =
                    preg_split(
                        '/\s*,\s*/',
                        $environmentOrigins
                    );
            }
        }

        // -------------------------------------------------
        // Normalize Configuration
        // -------------------------------------------------

        if (
            is_string($origins)
        ) {

            $origins = [
                $origins,
            ];
        }

        if (
            !is_array($origins) ||
            empty($origins)
        ) {

            $origins =
                self::DEFAULT_ALLOWED_ORIGINS;
        }

        // -------------------------------------------------
        // Clean Values
        // -------------------------------------------------

        $clean = [];

        foreach (
            $origins as $origin
        ) {

            if (
                !is_string($origin)
            ) {
                continue;
            }

            $origin = trim(
                $origin
            );

            if (
                $origin === ''
            ) {
                continue;
            }

            /*
             * "*" is deliberately ignored.
             *
             * This middleware uses credentials, so wildcard
             * Access-Control-Allow-Origin is unsafe/invalid.
             */
            if (
                $origin === '*'
            ) {
                continue;
            }

            $clean[] =
                $origin;
        }

        return array_values(
            array_unique(
                $clean
            )
        );
    }

    /**
     * Normalize an origin before comparison.
     */
    private static function normalizeOrigin(
        string $origin
    ): string {

        return rtrim(
            strtolower(
                trim($origin)
            ),
            '/'
        );
    }

    // =====================================================
    // Request Information
    // =====================================================

    /**
     * Get requested HTTP method from CORS preflight.
     */
    private static function getRequestedMethod(): ?string
    {
        if (
            !isset(
                $_SERVER[
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD'
                ]
            )
        ) {
            return null;
        }

        $method = trim(
            (string) $_SERVER[
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD'
            ]
        );

        return $method !== ''
            ? strtoupper($method)
            : null;
    }

    /**
     * Get requested headers from CORS preflight.
     */
    private static function getRequestedHeaders(): ?array
    {
        if (
            !isset(
                $_SERVER[
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS'
                ]
            )
        ) {
            return null;
        }

        $headerString = trim(
            (string) $_SERVER[
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS'
            ]
        );

        if (
            $headerString === ''
        ) {
            return null;
        }

        $headers = explode(
            ',',
            $headerString
        );

        $headers = array_map(
            static function (
                string $header
            ): string {
                return strtolower(
                    trim($header)
                );
            },
            $headers
        );

        return array_values(
            array_filter(
                $headers,
                static function (
                    string $header
                ): bool {
                    return $header !== '';
                }
            )
        );
    }

    /**
     * Check whether requested headers are allowed.
     */
    private static function areHeadersAllowed(
        array $requestedHeaders
    ): bool {

        $allowedHeaders =
            array_map(
                'strtolower',
                self::ALLOWED_HEADERS
            );

        foreach (
            $requestedHeaders as $header
        ) {

            if (
                !in_array(
                    strtolower($header),
                    $allowedHeaders,
                    true
                )
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine whether request is a CORS preflight.
     */
    private static function isPreflightRequest(): bool
    {
        $method = strtoupper(
            (string) (
                $_SERVER['REQUEST_METHOD']
                ?? ''
            )
        );

        return
            $method === 'OPTIONS' &&
            isset(
                $_SERVER[
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD'
                ]
            );
    }

    // =====================================================
    // Errors
    // =====================================================

    /**
     * Handle disallowed origin.
     */
    private static function handleDisallowedOrigin(): never
    {
        /*
         * Do not reflect an untrusted Origin.
         */

        if (
            self::isPreflightRequest()
        ) {
            self::sendError(
                403,
                'CORS origin is not allowed.'
            );
        }

        /*
         * For normal requests, the safest behavior is
         * simply to omit CORS permission and let the browser
         * enforce the policy.
         */

        http_response_code(403);

        self::jsonError(
            'CORS origin is not allowed.'
        );
        exit;
    }

    /**
     * Send JSON error response.
     */
    private static function sendError(
        int $status,
        string $message
    ): never {

        http_response_code(
            $status
        );

        self::jsonError(
            $message
        );
        exit;
    }

    /**
     * Send JSON error payload.
     */
    private static function jsonError(
        string $message
    ): void {

        if (
            !headers_sent()
        ) {
            header(
                'Content-Type: application/json; charset=utf-8'
            );
        }

        echo json_encode(
            [
                'success' => false,
                'message' => $message,
                'error' => 'CORS_ERROR',
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}

// ---------------------------------------------------------
// Optional Function-Based API
// ---------------------------------------------------------

/**
 * Apply CORS middleware.
 *
 * This allows either:
 *
 * CorsMiddleware::handle();
 *
 * or:
 *
 * cors();
 */
if (
    !function_exists('cors')
) {

    function cors(): void
    {
        CorsMiddleware::handle();
    }
}
