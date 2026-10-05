<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Authentication Middleware
 *
 * File:
 * backend/middleware/auth.php
 *
 * Responsibilities:
 * - Read Authorization header
 * - Extract Bearer JWT
 * - Validate JWT
 * - Resolve authenticated user
 * - Attach user to request
 *
 * Expected header:
 *
 * Authorization: Bearer <access_token>
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

require_once BASE_PATH . '/services/AuthService.php';


// ---------------------------------------------------------
// Authentication Middleware
// ---------------------------------------------------------

class AuthMiddleware
{
    private AuthService $authService;


    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        ?AuthService $authService = null
    ) {
        $this->authService =
            $authService
            ?? new AuthService();
    }

    // =====================================================
    // Handle
    // =====================================================

    /**
     * Authenticate the incoming request.
     *
     * On success, the authenticated user is added to:
     *
     * $request['user']
     *
     * @param array $request
     * @return array
     */
    public function handle(
        array $request
    ): array {

        $token = $this->getBearerToken();

        if ($token === null) {
            $this->unauthorized(
                'Authentication token is required.'
            );
        }

        // -------------------------------------------------
        // Validate Token
        // -------------------------------------------------

        try {

            $user = $this->authService
                ->authenticateToken(
                    $token
                );

        } catch (Throwable $exception) {

            $this->logAuthenticationError(
                $exception
            );

            $user = null;
        }

        if ($user === null) {
            $this->unauthorized(
                'Invalid or expired authentication token.'
            );
        }

        // -------------------------------------------------
        // Attach User
        // -------------------------------------------------

        $request['user'] = $user;

        $request['auth'] = [
            'authenticated' => true,
            'token' => $token,
        ];
        return $request;
    }

    // =====================================================
    // Static Helper
    // =====================================================

    /**
     * Convenience method for route files.
     *
     * Usage:
     *
     * $request = AuthMiddleware::requireAuth($request);
     */
    public static function requireAuth(
        array $request
    ): array {

        $middleware =
            new self();

        return $middleware->handle(
            $request
        );
    }

    // =====================================================
    // Bearer Token
    // =====================================================

    /**
     * Get Bearer token from Authorization header.
     */
    private function getBearerToken(): ?string
    {
        $header = $this->getAuthorizationHeader();

        if (
            $header === null ||
            $header === ''
        ) {
            return null;
        }

        /*
         * Expected:
         *
         * Bearer eyJhbGciOi...
         *
         * Case-insensitive matching is intentional.
         */
        if (
            !preg_match(
                '/^Bearer\s+(.+)$/i',
                trim($header),
                $matches
            )
        ) {
            return null;
        }

        $token = trim(
            $matches[1]
        );

        if (
            $token === ''
        ) {
            return null;
        }
        return $token;
    }

    /**
     * Read Authorization header.
     *
     * Supports:
     * - Apache
     * - Nginx/FastCGI
     * - PHP environments where getallheaders() exists
     */
    private function getAuthorizationHeader(): ?string
    {
        // -------------------------------------------------
        // Standard PHP Header
        // -------------------------------------------------

        if (
            function_exists('getallheaders')
        ) {

            $headers = getallheaders();

            if (
                is_array($headers)
            ) {

                foreach (
                    $headers as $name => $value
                ) {

                    if (
                        strtolower(
                            (string) $name
                        ) === 'authorization'
                    ) {
                        return trim(
                            (string) $value
                        );
                    }
                }
            }
        }

        // -------------------------------------------------
        // $_SERVER Authorization
        // -------------------------------------------------

        $serverKeys = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'Authorization',
            'authorization',
        ];

        foreach (
            $serverKeys as $key
        ) {
            if (
                isset($_SERVER[$key])
            ) {
                return trim(
                    (string) $_SERVER[$key]
                );
            }
        }
        return null;
    }

    // =====================================================
    // User Helpers
    // =====================================================

    /**
     * Check whether request is authenticated.
     */
    public static function isAuthenticated(
        array $request
    ): bool {

        if (
            !isset($request['user'])
            || !is_array($request['user'])
        ) {
            return false;
        }

        if (
            !isset($request['auth'])
            || !is_array($request['auth'])
        ) {
            return false;
        }

        return !empty(
            $request['auth']['authenticated']
        )
        && (bool) $request['auth']['authenticated'];
    }

    /**
     * Get authenticated user.
     */
    public static function user(
        array $request
    ): ?array {

        if (
            !self::isAuthenticated(
                $request
            )
        ) {
            return null;
        }
        return $request['user'];
    }

    /**
     * Get authenticated user ID.
     */
    public static function userId(
        array $request
    ): ?int {

        $user = self::user(
            $request
        );

        if (
            $user === null
        ) {
            return null;
        }

        $id = (int) (
            $user['id'] ?? 0
        );

        return $id > 0
            ? $id
            : null;
    }

    /**
     * Get authenticated user's role.
     */
    public static function role(
        array $request
    ): ?string {

        $user = self::user(
            $request
        );

        if (
            $user === null
        ) {
            return null;
        }

        $role = $user['role']
            ?? null;

        return $role !== null
            ? (string) $role
            : null;
    }

    // =====================================================
    // Authorization Response
    // =====================================================

    /**
     * Return 401 Unauthorized and terminate request.
     */
    private function unauthorized(
        string $message
    ): never {

        http_response_code(401);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        header(
            'WWW-Authenticate: Bearer'
        );

        echo json_encode(
            [
                'success' => false,
                'message' => $message,
                'error' => 'UNAUTHORIZED',
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    // =====================================================
    // Logging
    // =====================================================

    /**
     * Log authentication errors without logging tokens.
     */
    private function logAuthenticationError(
        Throwable $exception
    ): void {

        /*
         * Never log the JWT itself.
         */

        if (
            function_exists('logMessage')
        ) {

            logMessage(
                LOG_LEVEL_ERROR,
                'Authentication middleware error.',
                [
                    'exception' =>
                        get_class(
                            $exception
                        ),

                    'message' =>
                        $exception->getMessage(),
                ]
            );
            return;
        }

        /*
         * Fallback logging.
         */
        error_log(
            sprintf(
                'Authentication middleware error: %s',
                $exception->getMessage()
            )
        );
    }
}
