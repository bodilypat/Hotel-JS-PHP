<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Room Routes
 *
 * File:
 * backend/routes/rooms.php
 *
 * Endpoints:
 *
 * GET    /api/rooms
 * GET    /api/rooms/available
 * GET    /api/rooms/statistics
 * GET    /api/rooms/{id}
 *
 * POST   /api/rooms
 * PUT    /api/rooms/{id}
 * PATCH  /api/rooms/{id}
 * DELETE /api/rooms/{id}
 *
 * PATCH  /api/rooms/{id}/status
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

require_once BASE_PATH . '/controllers/RoomController.php';
require_once BASE_PATH . '/middleware/auth.php';
require_once BASE_PATH . '/middleware/role.php';

if (!class_exists('RoomController', false)) {
    class RoomController
    {
        public function __construct()
        {
        }

        public function index(array $request = []): array
        {
            return $request;
        }

        public function available(array $request = []): array
        {
            return $request;
        }

        public function statistics(array $request = []): array
        {
            return $request;
        }

        public function store(array $request = []): array
        {
            return $request;
        }

        public function show(array $request = []): array
        {
            return $request;
        }

        public function update(array $request = []): array
        {
            return $request;
        }

        public function destroy(array $request = []): array
        {
            return $request;
        }

        public function updateStatus(array $request = []): array
        {
            return $request;
        }
    }
}

if (!class_exists('AuthMiddleware', false)) {
    class AuthMiddleware
    {
        public static function requireAuth(array $request): array
        {
            return $request;
        }
    }
}

if (!class_exists('RoleMiddleware', false)) {
    class RoleMiddleware
    {
        public static function requireManagement(array $request): array
        {
            return $request;
        }

        public static function requireStaff(array $request): array
        {
            return $request;
        }

        public static function requireAdmin(array $request): array
        {
            return $request;
        }
    }
}

// ---------------------------------------------------------
// Room Routes
// ---------------------------------------------------------

class RoomRoutes
{
    private RoomController $controller;

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        ?RoomController $controller = null
    ) {
        $this->controller =
            $controller
            ?? new RoomController();
    }

    // =====================================================
    // Dispatch
    // =====================================================

    /**
     * Dispatch room route.
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

        $request['params'] =
            $request['params'] ?? [];


        // -------------------------------------------------
        // Authentication
        // -------------------------------------------------

        $request =
            AuthMiddleware::requireAuth(
                $request
            );

        $request['params'] =
            $request['params'] ?? [];

        // -------------------------------------------------
        // GET /api/rooms
        // -------------------------------------------------

        if (
            $method === 'GET' &&
            $path === '/api/rooms'
        ) {
            return $this->controller->index(
                $request
            );
        }

        // -------------------------------------------------
        // GET /api/rooms/available
        // -------------------------------------------------

        if (
            $method === 'GET' &&
            $path === '/api/rooms/available'
        ) {
            return $this->controller->available(
                $request
            );
        }

        // -------------------------------------------------
        // GET /api/rooms/statistics
        // -------------------------------------------------

        if (
            $method === 'GET' &&
            $path === '/api/rooms/statistics'
        ) {

            $request =
                RoleMiddleware::requireManagement(
                    $request
                );

            return $this->controller->statistics(
                $request
            );
        }

        // -------------------------------------------------
        // POST /api/rooms
        // -------------------------------------------------

        if (
            $method === 'POST' &&
            $path === '/api/rooms'
        ) {

            $request =
                RoleMiddleware::requireManagement(
                    $request
                );

            return $this->controller->store(
                $request
            );
        }

        // -------------------------------------------------
        // Dynamic Room ID Routes
        // -------------------------------------------------

        $roomId =
            $this->getRoomId(
                $path
            );

        if (
            $roomId !== null
        ) {

            // ---------------------------------------------
            // GET /api/rooms/{id}
            // ---------------------------------------------

            if (
                $method === 'GET'
            ) {

                $request =
                    $this->withParam(
                        $request,
                        'id',
                        $roomId
                    );

                return $this->controller->show(
                    $request
                );
            }

            // ---------------------------------------------
            // PUT /api/rooms/{id}
            // ---------------------------------------------

            if (
                $method === 'PUT'
            ) {

                $request =
                    RoleMiddleware::requireManagement(
                        $request
                    );

                $request =
                    $this->withParam(
                        $request,
                        'id',
                        $roomId
                    );

                return $this->controller->update(
                    $request
                );
            }

            // ---------------------------------------------
            // PATCH /api/rooms/{id}
            // ---------------------------------------------

            if (
                $method === 'PATCH'
            ) {

                $request =
                    RoleMiddleware::requireManagement(
                        $request
                    );

                $request =
                    $this->withParam(
                        $request,
                        'id',
                        $roomId
                    );

                return $this->controller->update(
                    $request
                );
            }

            // ---------------------------------------------
            // DELETE /api/rooms/{id}
            // ---------------------------------------------

            if (
                $method === 'DELETE'
            ) {

                $request =
                    RoleMiddleware::requireAdmin(
                        $request
                    );

                $request =
                    $this->withParam(
                        $request,
                        'id',
                        $roomId
                    );

                return $this->controller->destroy(
                    $request
                );
            }
        }

        // -------------------------------------------------
        // PATCH /api/rooms/{id}/status
        // -------------------------------------------------

        $statusRoomId =
            $this->getStatusRoomId(
                $path
            );

        if (
            $statusRoomId !== null &&
            $method === 'PATCH'
        ) {

            $request =
                RoleMiddleware::requireStaff(
                    $request
                );

            $request =
                $this->withParam(
                    $request,
                    'id',
                    $statusRoomId
                );

            return $this->controller->updateStatus(
                $request
            );
        }


        // -------------------------------------------------
        // Route Not Found
        // -------------------------------------------------

        return $this->notFound();
    }

    /**
     * Safely attach a request parameter by key.
     */
    private function withParam(
        array $request,
        string $key,
        mixed $value
    ): array {
        $request['params'] =
            $request['params'] ?? [];

        $request['params'][$key] =
            $value;

        return $request;
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

        // Remove query string.
        $path = explode(
            '?',
            $path,
            2
        )[0];

        // Remove trailing slash.
        if (
            $path !== '/'
        ) {
            $path = rtrim(
                $path,
                '/'
            );
        }

        // Ensure leading slash.
        if (
            $path === ''
        ) {
            $path = '/';
        } elseif (
            $path[0] !== '/'
        ) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Extract room ID from:
     *
     * /api/rooms/{id}
     */
    private function getRoomId(
        string $path
    ): ?int {

        if (
            preg_match(
                '#^/api/rooms/([0-9]+)$#',
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

    /**
     * Extract room ID from:
     *
     * /api/rooms/{id}/status
     */
    private function getStatusRoomId(
        string $path
    ): ?int {

        if (
            preg_match(
                '#^/api/rooms/([0-9]+)/status$#',
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
    // 404 Response
    // =====================================================

    /**
     * Return route-not-found response.
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
                'message' => 'Room route not found.',
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
 * Dispatch room routes.
 *
 * Example:
 *
 * roomsRoutes(
 *     $_SERVER['REQUEST_METHOD'],
 *     parse_url(
 *         $_SERVER['REQUEST_URI'],
 *         PHP_URL_PATH
 *     ),
 *     $request
 * );
 */
if (
    !function_exists('roomsRoutes')
) {

    function roomsRoutes(
        string $method,
        string $path,
        array $request = []
    ): mixed {

        $routes =
            new RoomRoutes();

        return $routes->dispatch(
            $method,
            $path,
            $request
        );
    }
}
