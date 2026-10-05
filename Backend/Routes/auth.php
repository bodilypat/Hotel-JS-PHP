<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Authentication Routes
 *
 * File:
 * backend/routes/auth.php
 *
 * Base URL:
 * /api/auth
 *
 * The front controller passes a request context array:
 *
 * [
 *     'method'  => 'POST',
 *     'uri'     => '/api/auth/login',
 *     'path'    => '/auth/login',
 *     'params'  => [...],
 *     'body'    => [...],
 *     'query'   => [...],
 *     'headers' => [...],
 * ]
 */

// ---------------------------------------------------------
// Dependencies
// ---------------------------------------------------------

require_once BASE_PATH . '/controllers/AuthController.php';


// ---------------------------------------------------------
// Return Route Handler
// ---------------------------------------------------------

return function (array $request): void {

    // -----------------------------------------------------
    // Controller
    // -----------------------------------------------------

    $controller = new AuthController();


    // -----------------------------------------------------
    // Request Information
    // -----------------------------------------------------

    $method = strtoupper(
        (string) ($request['method'] ?? 'GET')
    );

    $path = (string) ($request['path'] ?? '/');


    // -----------------------------------------------------
    // Normalize Path
    // -----------------------------------------------------

    /*
     * The front controller normally provides:
     *
     * /auth/login
     *
     * but this also handles:
     *
     * /api/auth/login
     *
     * if the route is called directly.
     */

    $path = preg_replace(
        '#^/api#',
        '',
        $path
    );

    $path = '/' . trim(
        (string) $path,
        '/'
    );

    if ($path === '//') {
        $path = '/';
    }


    // -----------------------------------------------------
    // Route Definitions
    // -----------------------------------------------------

    $routes = [
        ['POST', '/auth/register', 'register'],
        ['POST', '/auth/login', 'login'],
        ['POST', '/auth/logout', 'logout'],
        ['GET', '/auth/me', 'me'],
        ['POST', '/auth/refresh', 'refresh'],
        ['POST', '/auth/forgot-password', 'forgotPassword'],
        ['POST', '/auth/reset-password', 'resetPassword'],
        ['POST', '/auth/verify-email', 'verifyEmail'],
        ['POST', '/auth/resend-verification', 'resendVerification'],
        ['POST', '/auth/change-password', 'changePassword'],
    ];

    foreach ($routes as [$routeMethod, $routePath, $handler]) {
        if ($method === $routeMethod && $path === $routePath) {
            $controller->{$handler}($request);

            return;
        }
    }


    // -----------------------------------------------------
    // Method / Route Not Found
    // -----------------------------------------------------

    $response = [
        'success' => false,
        'message' => 'Authentication route not found.',
    ];

    http_response_code(404);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $response,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
};
