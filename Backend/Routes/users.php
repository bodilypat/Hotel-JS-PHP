<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * User Routes
 *
 * File:
 * backend/routes/users.php
 *
 * Endpoints:
 *
 * GET    /api/users
 * GET    /api/users/{id}
 * POST   /api/users
 * PUT    /api/users/{id}
 * PATCH  /api/users/{id}
 * DELETE /api/users/{id}
 *
 * GET    /api/users/me
 * PUT    /api/users/me
 * PATCH  /api/users/me
 *
 * GET    /api/users/statistics
 *
 * Authentication:
 * - All routes require authentication.
 *
 * Authorization:
 * - Admins/managers can manage users.
 * - Users can access/update their own profile.
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

require_once BASE_PATH . '/controllers/UserController.php';
require_once BASE_PATH . '/middleware/auth.php';
require_once BASE_PATH . '/middleware/role.php';

// ---------------------------------------------------------
// User Routes
// ---------------------------------------------------------

class UserRoutes
{
    private object $controller;

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        ?object $controller = null
    ) {
        if (
            $controller !== null
        ) {
            $this->controller = $controller;
            return;
        }

        if (
            class_exists('App\\Controllers\\UserController')
        ) {
            $this->controller = new \App\Controllers\UserController();
            return;
        }

        $this->controller = new class {
            public function __call(
                string $name,
                array $arguments = []
            ): mixed {
                unset($name, $arguments);
                return null;
            }
        };
    }

    /**
     * Authenticate request when middleware is available.
     */
    private function requireAuth(
        array $request
    ): array {
        if (
            class_exists('App\\Middleware\\AuthMiddleware')
        ) {
            return \App\Middleware\AuthMiddleware::requireAuth(
                $request
            );
        }

        return $request;
    }

    /**
     * Require management access when middleware is available.
     */
    private function requireManagement(
        array $request
    ): array {
        if (
            class_exists('App\\Middleware\\RoleMiddleware')
        ) {
            return \App\Middleware\RoleMiddleware::requireManagement(
                $request
            );
        }

        return $request;
    }

    /**
     * Require admin access when middleware is available.
     */
    private function requireAdmin(
        array $request
    ): array {
        if (
            class_exists('App\\Middleware\\RoleMiddleware')
        ) {
            return \App\Middleware\RoleMiddleware::requireAdmin(
                $request
            );
        }

        return $request;
    }

    // =====================================================
    // Dispatch
    // =====================================================

    /**
     * Dispatch user route.
     *
     * @param string $method
     * @param string $path
     * @param array $request
     * @return mixed
     */
    public function dispatch(
        string $method,
        string $path,
        array $request = []
    ): mixed {

        $method = strtoupper(
            trim($method)
        );

        $path = $this->normalizePath(
            $path
        );

        // -------------------------------------------------
        // Authenticate every user endpoint
        // -------------------------------------------------

        $request =
            $this->requireAuth(
                $request
            );

        // -------------------------------------------------
        // GET /api/users/me
        // -------------------------------------------------

        if (
            $method === 'GET' &&
            $path === '/api/users/me'
        ) {
            return $this->controller->me(
                $request
            );
        }

        // -------------------------------------------------
        // PUT /api/users/me
        // -------------------------------------------------

        if (
            $method === 'PUT' &&
            $path === '/api/users/me'
        ) {
            return $this->controller->updateProfile(
                $request
            );
        }

        // -------------------------------------------------
        // PATCH /api/users/me
        // -------------------------------------------------

        if (
            $method === 'PATCH' &&
            $path === '/api/users/me'
        ) {
            return $this->controller->updateProfile(
                $request
            );
        }

        // -------------------------------------------------
        // GET /api/users/statistics
        // -------------------------------------------------

        if (
            $method === 'GET' &&
            $path === '/api/users/statistics'
        ) {

            $request =
                $this->requireManagement(
                    $request
                );

            return $this->controller->statistics(
                $request
            );
        }

        // -------------------------------------------------
        // GET /api/users
        // -------------------------------------------------

        if (
            $method === 'GET' &&
            $path === '/api/users'
        ) {

            $request =
                $this->requireManagement(
                    $request
                );

            return $this->controller->index(
                $request
            );
        }

        // -------------------------------------------------
        // POST /api/users
        // -------------------------------------------------

        if (
            $method === 'POST' &&
            $path === '/api/users'
        ) {

            $request =
                $this->requireAdmin(
                    $request
                );

            return $this->controller->store(
                $request
            );
        }

        // -------------------------------------------------
        // Dynamic User ID Routes
        // -------------------------------------------------

        $userId =
            $this->getUserId(
                $path
            );

        if (
            $userId !== null
        ) {

            // ---------------------------------------------
            // GET /api/users/{id}
            // ---------------------------------------------

            if (
                $method === 'GET'
            ) {

                $request =
                    $this->requireManagement(
                        $request
                    );

                $request['params']['id'] =
                    $userId;

                return $this->controller->show(
                    $request
                );
            }

            // ---------------------------------------------
            // PUT /api/users/{id}
            // ---------------------------------------------

            if (
                $method === 'PUT'
            ) {

                $request =
                    $this->requireAdmin(
                        $request
                    );

                $request['params']['id'] =
                    $userId;

                return $this->controller->update(
                    $request
                );
            }

            // ---------------------------------------------
            // PATCH /api/users/{id}
            // ---------------------------------------------

            if (
                $method === 'PATCH'
            ) {

                $request =
                    $this->requireAdmin(
                        $request
                    );

                $request['params']['id'] =
                    $userId;

                return $this->controller->update(
                    $request
                );
            }

            // ---------------------------------------------
            // DELETE /api/users/{id}
            // ---------------------------------------------

            if (
                $method === 'DELETE'
            ) {

                $request =
                    $this->requireAdmin(
                        $request
                    );

                $request['params']['id'] =
                    $userId;

                return $this->controller->destroy(
                    $request
                );
            }
        }

        // -------------------------------------------------
        // Route Not Found
        // -------------------------------------------------

        return $this->notFound();
    }

    // =====================================================
    // Path Helpers
    // =====================================================

    /**
     * Normalize request path.
     */
    private function normalizePath(
        string $path
    ): string {

        $path = trim(
            $path
        );

        /*
         * Remove query string.
         *
         * Example:
         * /api/users?page=2
         *
         * becomes:
         * /api/users
         */
        $path = explode(
            '?',
            $path,
            2
        )[0];

        /*
         * Remove trailing slash except for root.
         */
        if (
            $path !== '/'
        ) {
            $path = rtrim(
                $path,
                '/'
            );
        }

        /*
         * Ensure leading slash.
         */
        if (
            $path === '' ||
            $path[0] !== '/'
        ) {
            $path = '/' . $path;
        }
        return $path;
    }

    /**
     * Extract numeric user ID from:
     *
     * /api/users/{id}
     */
    private function getUserId(
        string $path
    ): ?int {

        if (
            preg_match(
                '#^/api/users/([0-9]+)$#',
                $path,
                $matches
            ) !== 1
        ) {
            return null;
        }

        $id = (int) $matches[1];

        return $id > 0
            ? $id
            : null;
    }

    // =====================================================
    // Response
    // =====================================================

    /**
     * Return 404 response.
     */
    private function notFound(): never
    {
        http_response_code(404);

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
                'message' => 'User route not found.',
                'error' => 'NOT_FOUND',
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}

// ---------------------------------------------------------
// Function-Based Route API
// ---------------------------------------------------------

/**
 * Dispatch user routes.
 *
 * This function makes the route file easy to use from
 * backend/public/index.php.
 *
 * Example:
 *
 * usersRoutes(
 *     $_SERVER['REQUEST_METHOD'],
 *     parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
 *     $request
 * );
 */
if (
    !function_exists('usersRoutes')
) {

    function usersRoutes(
        string $method,
        string $path,
        array $request = []
    ): mixed {

        $routes =
            new UserRoutes();

        return $routes->dispatch(
            $method,
            $path,
            $request
        );
    }
}
