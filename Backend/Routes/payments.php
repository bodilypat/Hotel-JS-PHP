<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/routes/payments.php
 *
 * Payment API routes.
 */

require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/role.php';

$paymentControllerClass = 'PaymentController';

if (!class_exists($paymentControllerClass, false)) {
    require_once __DIR__ . '/../controllers/PaymentController.php';
}

if (class_exists('App\\Controllers\\PaymentController')) {
    $paymentControllerClass = 'App\\Controllers\\PaymentController';
} elseif (class_exists('Backend\\Controllers\\PaymentController')) {
    $paymentControllerClass = 'Backend\\Controllers\\PaymentController';
} elseif (!class_exists($paymentControllerClass, false)) {
    $paymentControllerClass = 'PaymentController';
}

if (!class_exists($paymentControllerClass)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Payment controller is not available.',
        'data' => null,
    ]);
    exit;
}

$paymentController = new $paymentControllerClass();

$normalizeAction = static function (?string $value): ?string {
    if ($value === null) {
        return null;
    }

    $normalized = strtolower(trim($value));

    return $normalized === '' ? null : $normalized;
};

/*
|--------------------------------------------------------------------------
| Route helper
|--------------------------------------------------------------------------
|
| These routes assume the main index.php router provides:
|
| - $method
| - $uri
| - $segments
|
| Example:
|
| /api/payments
| /api/payments/15
| /api/payments/15/refund
|
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$uri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$segments = array_values(
    array_filter(
        explode('/', trim($uri, '/')),
        static fn ($segment) => $segment !== ''
    )
);

/*
|--------------------------------------------------------------------------
| Locate "payments" in the URI
|--------------------------------------------------------------------------
*/

$paymentIndex = array_search(
    'payments',
    $segments,
    true
);

if ($paymentIndex === false) {
    return;
}

/*
|--------------------------------------------------------------------------
| Everything after /payments
|--------------------------------------------------------------------------
*/

$paymentSegments = array_slice(
    $segments,
    $paymentIndex + 1
);

$paymentId = null;
$action = null;

if (
    isset($paymentSegments[0])
    && ctype_digit((string) $paymentSegments[0])
) {
    $paymentId = (int) $paymentSegments[0];
    $action = $normalizeAction($paymentSegments[1] ?? null);
} else {
    $action = $normalizeAction($paymentSegments[0] ?? null);
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    exit;
}

if ($method === 'HEAD') {
    $method = 'GET';
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| All payment routes require authentication.
|--------------------------------------------------------------------------
*/

if (function_exists('requireAuth')) {
    requireAuth();
}

/*
|--------------------------------------------------------------------------
| GET /api/payments
|--------------------------------------------------------------------------
|
| List payments.
|
| Supported query parameters:
|
| ?page=1
| ?limit=20
| ?booking_id=10
| ?guest_id=5
| ?status=completed
| ?payment_method=card
| ?date_from=2026-01-01
| ?date_to=2026-01-31
| ?search=BK-2026
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $paymentId === null
    && $action === null
) {
    $paymentController->index();
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/payments/{id}
|--------------------------------------------------------------------------
|
| Get one payment.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $paymentId !== null
    && $action === null
) {
    $paymentController->show(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| POST /api/payments
|--------------------------------------------------------------------------
|
| Create a payment.
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST'
    && $paymentId === null
    && $action === null
) {
    $paymentController->store();
    return;
}

/*
|--------------------------------------------------------------------------
| PUT /api/payments/{id}
|--------------------------------------------------------------------------
|
| Update payment.
|--------------------------------------------------------------------------
*/

if (
    $method === 'PUT'
    && $paymentId !== null
    && $action === null
) {
    $paymentController->update(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| PATCH /api/payments/{id}
|--------------------------------------------------------------------------
|
| Partial payment update.
|--------------------------------------------------------------------------
*/

if (
    $method === 'PATCH'
    && $paymentId !== null
    && $action === null
) {
    $paymentController->update(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| DELETE /api/payments/{id}
|--------------------------------------------------------------------------
|
| Delete a payment.
|--------------------------------------------------------------------------
*/

if (
    $method === 'DELETE'
    && $paymentId !== null
    && $action === null
) {
    $paymentController->destroy(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| POST /api/payments/{id}/refund
|--------------------------------------------------------------------------
|
| Refund a payment.
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST'
    && $paymentId !== null
    && $action === 'refund'
) {
    $paymentController->refund(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| POST /api/payments/{id}/void
|--------------------------------------------------------------------------
|
| Void a payment.
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST'
    && $paymentId !== null
    && $action === 'void'
) {
    $paymentController->void(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| POST /api/payments/{id}/complete
|--------------------------------------------------------------------------
|
| Mark payment as completed.
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST'
    && $paymentId !== null
    && $action === 'complete'
) {
    $paymentController->complete(
        $paymentId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/payments/booking/{bookingId}
|--------------------------------------------------------------------------
|
| Get all payments belonging to a booking.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $paymentId === null
    && $action === 'booking'
    && isset($paymentSegments[1])
    && ctype_digit($paymentSegments[1])
) {
    $bookingId = (int) $paymentSegments[1];

    $paymentController->byBooking(
        $bookingId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/payments/guest/{guestId}
|--------------------------------------------------------------------------
|
| Get payments belonging to a guest.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $paymentId === null
    && $action === 'guest'
    && isset($paymentSegments[1])
    && ctype_digit($paymentSegments[1])
) {
    $guestId = (int) $paymentSegments[1];

    $paymentController->byGuest(
        $guestId
    );
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/payments/summary
|--------------------------------------------------------------------------
|
| Payment summary/statistics.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $paymentId === null
    && $action === 'summary'
) {
    $paymentController->summary();
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/payments/methods
|--------------------------------------------------------------------------
|
| Available payment methods.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $paymentId === null
    && $action === 'methods'
) {
    $paymentController->methods();
    return;
}

/*
|--------------------------------------------------------------------------
| Unknown payment route
|--------------------------------------------------------------------------
*/

http_response_code(404);

header(
    'Content-Type: application/json; charset=utf-8'
);

echo json_encode([
    'success' => false,
    'message' => 'Payment route not found.',
    'data' => null,
]);

exit;
