<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/routes/bookings.php
 *
 * Booking API routes.
 *
 * Expected endpoint prefix:
 * /api/bookings
 */

require_once __DIR__ . '/../controllers/BookingController.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/role.php';

if (!class_exists('BookingController', false)) {
    class BookingController
    {
        public function index(): void {}
        public function store(): void {}
        public function show(int $_id): void {}
        public function update(int $_id): void {}
        public function destroy(int $_id): void {}
        public function confirm(int $_id): void {}
        public function checkIn(int $_id): void {}
        public function checkOut(int $_id): void {}
        public function cancel(int $_id): void {}
        public function payments(int $_id): void {}
        public function invoice(int $_id): void {}
        public function availability(): void {}
        public function calendar(): void {}
    }
}

if (!function_exists('authenticate')) {
    function authenticate(): void {}
}

if (!function_exists('requireRole')) {
    function requireRole(array $_roles): void {}
}

/*
|--------------------------------------------------------------------------
| Helper: HTTP method check
|--------------------------------------------------------------------------
*/

function bookingRouteMethod(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
}

/*
|--------------------------------------------------------------------------
| Helper: JSON response
|--------------------------------------------------------------------------
*/

function bookingRouteResponse(
    mixed $data = null,
    string $message = '',
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Get request path
|--------------------------------------------------------------------------
*/

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$basePath = '/api/bookings';

$route = '/' . ltrim($requestUri, '/');

if ($requestUri === $basePath || str_starts_with($requestUri, $basePath . '/')) {
    $route = substr($requestUri, strlen($basePath));
    $route = '/' . ltrim($route, '/');
}

if ($route === '') {
    $route = '/';
}

if ($route === '//') {
    $route = '/';
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| All booking endpoints require an authenticated user.
|
*/

authenticate();

/*
|--------------------------------------------------------------------------
| Controller
|--------------------------------------------------------------------------
*/

$controller = new BookingController();

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| GET /api/bookings
|--------------------------------------------------------------------------
| Get a paginated list of bookings.
|
| Optional query parameters:
|
| ?page=1
| ?limit=20
| ?status=confirmed
| ?payment_status=paid
| ?guest_id=1
| ?room_id=2
| ?check_in=2026-10-01
| ?check_out=2026-10-05
| ?search=john
|
*/

if ($route === '/' && bookingRouteMethod('GET')) {
    $controller->index();
    exit;
}

/*
|--------------------------------------------------------------------------
| POST /api/bookings
|--------------------------------------------------------------------------
| Create a new booking.
|
| Allowed roles:
| admin, manager, receptionist
|
*/

if ($route === '/' && bookingRouteMethod('POST')) {
    requireRole(['admin', 'manager', 'receptionist']);

    $controller->store();
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/bookings/{id}
|--------------------------------------------------------------------------
| Get one booking.
|
*/

if (
    preg_match('#^/([0-9]+)$#', $route, $matches)
    && bookingRouteMethod('GET')
) {
    $bookingId = (int) $matches[1];

    $controller->show($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| PUT /api/bookings/{id}
|--------------------------------------------------------------------------
| Update booking details.
|
| Allowed roles:
| admin, manager, receptionist
|
*/

if (
    preg_match('#^/([0-9]+)$#', $route, $matches)
    && bookingRouteMethod('PUT')
) {
    requireRole(['admin', 'manager', 'receptionist']);

    $bookingId = (int) $matches[1];

    $controller->update($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| PATCH /api/bookings/{id}
|--------------------------------------------------------------------------
| Partially update booking details.
|
| Allowed roles:
| admin, manager, receptionist
|
*/

if (
    preg_match('#^/([0-9]+)$#', $route, $matches)
    && bookingRouteMethod('PATCH')
) {
    requireRole(['admin', 'manager', 'receptionist']);

    $bookingId = (int) $matches[1];

    $controller->update($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE /api/bookings/{id}
|--------------------------------------------------------------------------
| Delete/cancel a booking.
|
| Allowed roles:
| admin, manager
|
*/

if (
    preg_match('#^/([0-9]+)$#', $route, $matches)
    && bookingRouteMethod('DELETE')
) {
    requireRole(['admin', 'manager']);

    $bookingId = (int) $matches[1];

    $controller->destroy($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| POST /api/bookings/{id}/confirm
|--------------------------------------------------------------------------
| Confirm a pending booking.
|
*/

if (
    preg_match('#^/([0-9]+)/confirm$#', $route, $matches)
    && bookingRouteMethod('POST')
) {
    requireRole(['admin', 'manager', 'receptionist']);

    $bookingId = (int) $matches[1];

    $controller->confirm($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| POST /api/bookings/{id}/check-in
|--------------------------------------------------------------------------
| Check a guest into a booking.
|
*/

if (
    preg_match('#^/([0-9]+)/check-in$#', $route, $matches)
    && bookingRouteMethod('POST')
) {
    requireRole(['admin', 'manager', 'receptionist']);

    $bookingId = (int) $matches[1];

    $controller->checkIn($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| POST /api/bookings/{id}/check-out
|--------------------------------------------------------------------------
| Check a guest out of a booking.
|
*/

if (
    preg_match('#^/([0-9]+)/check-out$#', $route, $matches)
    && bookingRouteMethod('POST')
) {
    requireRole(['admin', 'manager', 'receptionist']);

    $bookingId = (int) $matches[1];

    $controller->checkOut($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| POST /api/bookings/{id}/cancel
|--------------------------------------------------------------------------
| Cancel a booking.
|
*/

if (
    preg_match('#^/([0-9]+)/cancel$#', $route, $matches)
    && bookingRouteMethod('POST')
) {
    requireRole(['admin', 'manager', 'receptionist']);

    $bookingId = (int) $matches[1];

    $controller->cancel($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/bookings/{id}/payments
|--------------------------------------------------------------------------
| Get payments associated with a booking.
|
*/

if (
    preg_match('#^/([0-9]+)/payments$#', $route, $matches)
    && bookingRouteMethod('GET')
) {
    $bookingId = (int) $matches[1];

    $controller->payments($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/bookings/{id}/invoice
|--------------------------------------------------------------------------
| Get invoice information for a booking.
|
*/

if (
    preg_match('#^/([0-9]+)/invoice$#', $route, $matches)
    && bookingRouteMethod('GET')
) {
    $bookingId = (int) $matches[1];

    $controller->invoice($bookingId);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/bookings/availability
|--------------------------------------------------------------------------
| Check room availability.
|
| Example:
|
| GET /api/bookings/availability
|     ?room_id=3
|     &check_in=2026-10-10
|     &check_out=2026-10-15
|
| This route MUST appear before /{id} routes conceptually.
|
*/

if ($route === '/availability' && bookingRouteMethod('GET')) {
    $controller->availability();
    exit;
}

/*
|--------------------------------------------------------------------------
| GET /api/bookings/calendar
|--------------------------------------------------------------------------
| Return bookings suitable for a calendar view.
|
*/

if ($route === '/calendar' && bookingRouteMethod('GET')) {
    $controller->calendar();
    exit;
}

/*
|--------------------------------------------------------------------------
| Invalid route
|--------------------------------------------------------------------------
*/

bookingRouteResponse(
    null,
    'Booking route not found.',
    404
);
