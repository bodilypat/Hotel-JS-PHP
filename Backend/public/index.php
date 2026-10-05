<?php

declare(strict_types=1);

/**
 * Hotel Management System
 * Backend API - Front Controller
 *
 * File: backend/public/index.php
 */

// ---------------------------------------------------------
// Error Reporting
// ---------------------------------------------------------

error_reporting(E_ALL);
ini_set('display_errors', '0');


// ---------------------------------------------------------
// Define Base Path
// ---------------------------------------------------------

define('BASE_PATH', dirname(__DIR__));


// ---------------------------------------------------------
// Load Configuration
// ---------------------------------------------------------

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/constants.php';


// ---------------------------------------------------------
// Load Helpers
// ---------------------------------------------------------

require_once BASE_PATH . '/helpers/response.php';
require_once BASE_PATH . '/helpers/security.php';
require_once BASE_PATH . '/helpers/jwt.php';
require_once BASE_PATH . '/helpers/logger.php';

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse(array $payload, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);

        foreach ($headers as $headerName => $headerValue) {
            header($headerName . ': ' . $headerValue);
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}


// ---------------------------------------------------------
// Load Middleware
// ---------------------------------------------------------

require_once BASE_PATH . '/middleware/cors.php';


// ---------------------------------------------------------
// Apply CORS
// ---------------------------------------------------------

if (function_exists('handleCors')) {
    handleCors();
}


// ---------------------------------------------------------
// Request Information
// ---------------------------------------------------------

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$requestUri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$requestUri = '/' . trim($requestUri, '/');


// Remove /index.php from URI when PHP's built-in server
// or Apache/Nginx passes it as part of the request.
$requestUri = preg_replace(
    '#/index\.php#',
    '',
    $requestUri
);

$requestUri = '/' . trim($requestUri, '/');


// ---------------------------------------------------------
// Parse JSON Request Body
// ---------------------------------------------------------

$requestBody = [];

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (
    in_array(
        $requestMethod,
        ['POST', 'PUT', 'PATCH'],
        true
    )
) {
    if (stripos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');

        if ($rawBody !== false && trim($rawBody) !== '') {
            $decodedBody = json_decode(
                $rawBody,
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE &&
                is_array($decodedBody)
            ) {
                $requestBody = $decodedBody;
            }
        }
    } else {
        $requestBody = $_POST;
    }
}


// ---------------------------------------------------------
// API Routes
// ---------------------------------------------------------

$routes = [

    // Authentication
    'auth' => BASE_PATH . '/routes/auth.php',

    // Users
    'users' => BASE_PATH . '/routes/users.php',

    // Rooms
    'rooms' => BASE_PATH . '/routes/rooms.php',

    // Guests
    'guests' => BASE_PATH . '/routes/guests.php',

    // Bookings
    'bookings' => BASE_PATH . '/routes/bookings.php',

    // Payments
    'payments' => BASE_PATH . '/routes/payments.php',

    // Staff
    'staff' => BASE_PATH . '/routes/staff.php',

    // Reports
    'reports' => BASE_PATH . '/routes/reports.php',
];


// ---------------------------------------------------------
// API Prefix
// ---------------------------------------------------------

$apiPrefix = '/api';


// ---------------------------------------------------------
// Health Check
// ---------------------------------------------------------

if (
    $requestMethod === 'GET' &&
    ($requestUri === '/' || $requestUri === '/health')
) {
    sendJsonResponse(
        [
            'success' => true,
            'message' => 'Hotel Management System API is running.',
            'data' => [
                'status' => 'healthy',
                'timestamp' => date('c'),
            ],
        ],
        200
    );

    exit;
}


// ---------------------------------------------------------
// Validate API Prefix
// ---------------------------------------------------------

if (
    !str_starts_with(
        $requestUri,
        $apiPrefix
    )
) {
    sendJsonResponse(
        [
            'success' => false,
            'message' => 'API endpoint not found.',
        ],
        404
    );

    exit;
}


// ---------------------------------------------------------
// Extract API Route
// ---------------------------------------------------------

$routePath = substr(
    $requestUri,
    strlen($apiPrefix)
);

$routePath = '/' . trim(
    $routePath,
    '/'
);


// ---------------------------------------------------------
// Determine Route Group
// ---------------------------------------------------------

$routeSegments = array_values(
    array_filter(
        explode('/', $routePath)
    )
);

$routeGroup = $routeSegments[0] ?? '';


// ---------------------------------------------------------
// Route Not Found
// ---------------------------------------------------------

if (
    $routeGroup === '' ||
    !isset($routes[$routeGroup])
) {
    sendJsonResponse(
        [
            'success' => false,
            'message' => 'Route not found.',
            'path' => $requestUri,
            'method' => $requestMethod,
        ],
        404
    );

    exit;
}


// ---------------------------------------------------------
// Route Variables
// ---------------------------------------------------------

$routeParams = array_slice(
    $routeSegments,
    1
);


// ---------------------------------------------------------
// Route Context
// ---------------------------------------------------------

$routeContext = [
    'method' => $requestMethod,
    'uri' => $requestUri,
    'path' => $routePath,
    'params' => $routeParams,
    'body' => $requestBody,
    'query' => $_GET,
    'headers' => function_exists('getallheaders')
        ? getallheaders()
        : [],
];


// ---------------------------------------------------------
// Load Route File
// ---------------------------------------------------------

$routeFile = $routes[$routeGroup];

if (!file_exists($routeFile)) {
    sendJsonResponse(
        [
            'success' => false,
            'message' => 'Route configuration not found.',
        ],
        500
    );

    exit;
}


// ---------------------------------------------------------
// Execute Route
// ---------------------------------------------------------

try {

    /**
     * Route files should return a callable:
     *
     * return function (array $request) {
     *     ...
     * };
     */

    $routeHandler = require $routeFile;

    if (!is_callable($routeHandler)) {
        sendJsonResponse(
            [
                'success' => false,
                'message' => 'Invalid route handler.',
            ],
            500
        );

        exit;
    }

    $routeHandler($routeContext);

} catch (Throwable $exception) {

    // -----------------------------------------------------
    // Log Exception
    // -----------------------------------------------------

    if (function_exists('logMessage')) {
        logMessage(
            'error',
            $exception->getMessage(),
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }

    // -----------------------------------------------------
    // Return Safe Error Response
    // -----------------------------------------------------

    sendJsonResponse(
        [
            'success' => false,
            'message' => 'Internal server error.',
        ],
        500
    );
}
