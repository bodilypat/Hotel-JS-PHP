<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Role Authorization Middleware
 *
 * File:
 * backend/middleware/role.php
 *
 * Responsibilities:
 * - Check authenticated user role
 * - Restrict routes by role
 * - Support one or multiple allowed roles
 * - Return HTTP 403 for insufficient permissions
 *
 * Authentication should be performed before this middleware.
 *
 * Example:
 *
 * $request = AuthMiddleware::requireAuth($request);
 * $request = RoleMiddleware::requireRole(
 *     $request,
 *     ['admin', 'manager']
 * );
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

require_once BASE_PATH . '/middleware/auth.php';

// ---------------------------------------------------------
// Role Middleware
// ---------------------------------------------------------

class RoleMiddleware
{
    /**
     * Supported application roles.
     */
    private const ALLOWED_ROLES = [
        'admin',
        'manager',
        'receptionist',
        'staff',
        'guest',
    ];

    // =====================================================
    // Handle
    // =====================================================

    /**
     * Require one of the specified roles.
     *
     * @param array $request
     * @param string|array $roles
     * @return array
     */
    public static function handle(
        array $request,
        string|array $roles
    ): array {

        // -------------------------------------------------
        // Authentication Check
        // -------------------------------------------------

        if (
            !AuthMiddleware::isAuthenticated(
                $request
            )
        ) {
            self::unauthorized(
                'Authentication is required.'
            );
        }

        // -------------------------------------------------
        // Normalize Roles
        // -------------------------------------------------

        $requiredRoles =
            self::normalizeRoles(
                $roles
            );

        if (
            empty($requiredRoles)
        ) {
            self::forbidden(
                'No valid roles were specified.'
            );
        }

        // -------------------------------------------------
        // Get User Role
        // -------------------------------------------------

        $userRole =
            self::getUserRole(
                $request
            );

        if (
            $userRole === null
        ) {
            self::forbidden(
                'User role could not be determined.'
            );
        }

        if (
            !in_array(
                $userRole,
                self::ALLOWED_ROLES,
                true
            )
        ) {
            self::forbidden(
                'User role is not supported.'
            );
        }

        // -------------------------------------------------
        // Role Check
        // -------------------------------------------------

        if (
            !in_array(
                $userRole,
                $requiredRoles,
                true
            )
        ) {
            self::forbidden(
                'You do not have permission to access this resource.'
            );
        }

        // -------------------------------------------------
        // Authorization Information
        // -------------------------------------------------

        $request['authorization'] = [
            'authorized' => true,
            'role' => $userRole,
            'required_roles' => $requiredRoles,
        ];
        return $request;
    }

    // =====================================================
    // Convenience Methods
    // =====================================================

    /**
     * Require a specific role.
     *
     * Example:
     *
     * $request = RoleMiddleware::requireRole(
     *     $request,
     *     'admin'
     * );
     */
    public static function requireRole(
        array $request,
        string|array $role
    ): array {

        return self::handle(
            $request,
            $role
        );
    }

    /**
     * Require one of multiple roles.
     *
     * Example:
     *
     * $request = RoleMiddleware::requireAnyRole(
     *     $request,
     *     ['admin', 'manager']
     * );
     */
    public static function requireAnyRole(
        array $request,
        array $roles
    ): array {

        return self::handle(
            $request,
            $roles
        );
    }

    /**
     * Require administrator.
     */
    public static function requireAdmin(
        array $request
    ): array {

        return self::handle(
            $request,
            'admin'
        );
    }

    /**
     * Require guest access.
     */
    public static function requireGuest(
        array $request
    ): array {

        return self::handle(
            $request,
            'guest'
        );
    }

    /**
     * Require management access.
     *
     * Admins and managers are allowed.
     */
    public static function requireManagement(
        array $request
    ): array {

        return self::handle(
            $request,
            [
                'admin',
                'manager',
            ]
        );
    }

    /**
     * Require staff access.
     *
     * Admins, managers, receptionists and staff
     * are allowed.
     */
    public static function requireStaff(
        array $request
    ): array {

        return self::handle(
            $request,
            [
                'admin',
                'manager',
                'receptionist',
                'staff',
            ]
        );
    }

    // =====================================================
    // Permission Helpers
    // =====================================================

    /**
     * Determine whether the authenticated user has
     * one of the specified roles.
     */
    public static function hasRole(
        array $request,
        string|array $roles
    ): bool {

        if (
            !AuthMiddleware::isAuthenticated(
                $request
            )
        ) {
            return false;
        }

        $userRole =
            self::getUserRole(
                $request
            );

        if (
            $userRole === null
        ) {
            return false;
        }

        $requiredRoles =
            self::normalizeRoles(
                $roles
            );

        return in_array(
            $userRole,
            $requiredRoles,
            true
        );
    }

    /**
     * Check whether user is an administrator.
     */
    public static function isAdmin(
        array $request
    ): bool {

        return self::hasRole(
            $request,
            'admin'
        );
    }

    /**
     * Check whether user is a guest.
     */
    public static function isGuest(
        array $request
    ): bool {

        return self::hasRole(
            $request,
            'guest'
        );
    }

    /**
     * Check whether user is manager.
     */
    public static function isManager(
        array $request
    ): bool {

        return self::hasRole(
            $request,
            'manager'
        );
    }

    /**
     * Check whether user is staff.
     */
    public static function isStaff(
        array $request
    ): bool {

        return self::hasRole(
            $request,
            [
                'admin',
                'manager',
                'receptionist',
                'staff',
            ]
        );
    }

    // =====================================================
    // Role Normalization
    // =====================================================

    /**
     * Get the normalized authenticated role.
     */
    private static function getUserRole(
        array $request
    ): ?string {

        $userRole =
            AuthMiddleware::role(
                $request
            );

        if (
            $userRole === null
        ) {
            return null;
        }

        $userRole = strtolower(
            trim(
                (string) $userRole
            )
        );

        if (
            $userRole === ''
        ) {
            return null;
        }

        return $userRole;
    }

    /**
     * Normalize and validate roles.
     *
     * @param string|array $roles
     * @return array
     */
    private static function normalizeRoles(
        string|array $roles
    ): array {

        if (
            is_string($roles)
        ) {
            $roles = [
                $roles,
            ];
        }

        $normalized = [];

        foreach ($roles as $role) {

            if (
                !is_string($role)
            ) {
                continue;
            }

            $role = strtolower(
                trim($role)
            );

            if (
                $role === ''
            ) {
                continue;
            }

            if (
                !in_array(
                    $role,
                    self::ALLOWED_ROLES,
                    true
                )
            ) {
                continue;
            }

            if (
                !in_array(
                    $role,
                    $normalized,
                    true
                )
            ) {
                $normalized[] = $role;
            }
        }
        return $normalized;
    }

    // =====================================================
    // HTTP Responses
    // =====================================================

    /**
     * Return HTTP 401.
     */
    private static function unauthorized(
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

    /**
     * Return HTTP 403.
     */
    private static function forbidden(
        string $message
    ): never {

        http_response_code(403);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            [
                'success' => false,
                'message' => $message,
                'error' => 'FORBIDDEN',
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}
