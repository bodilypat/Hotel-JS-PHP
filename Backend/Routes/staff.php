<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/routes/staff.php
 *
 * Staff API routes.
 */

$staffControllerFiles = [
    __DIR__ . '/../Controllers/StaffController.php',
    __DIR__ . '/../controllers/StaffController.php',
];

foreach ($staffControllerFiles as $staffControllerPath) {
    if (is_file($staffControllerPath)) {
        require_once $staffControllerPath;
        break;
    }
}

if (!class_exists('StaffController', false)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Staff controller is unavailable.',
        'data' => null,
    ]);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/role.php';

$staffController = new StaffController();

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

$normalizedSegments = array_map(
    static fn ($segment) => strtolower((string) $segment),
    $segments
);

/*
|--------------------------------------------------------------------------
| Locate "staff" in URI
|--------------------------------------------------------------------------
*/

$staffIndex = array_search(
    'staff',
    $normalizedSegments,
    true
);

if ($staffIndex === false) {
    return;
}

$staffSegments = array_slice(
    $segments,
    $staffIndex + 1
);

$staffId = null;
$action = null;

/*
|--------------------------------------------------------------------------
| Extract ID / action
|--------------------------------------------------------------------------
*/

if (
    isset($staffSegments[0])
    && ctype_digit($staffSegments[0])
) {
    $staffId = (int) $staffSegments[0];
    $action = $staffSegments[1] ?? null;
} else {
    $action = $staffSegments[0] ?? null;
}

$actionKey = is_string($action)
    ? strtolower($action)
    : null;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (function_exists('requireAuth')) {
    requireAuth();
}

$handled = false;

switch ($method) {
    case 'GET':
        if ($staffId === null && $actionKey === null) {
            $staffController->index();
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === null) {
            $staffController->show($staffId);
            $handled = true;
            break;
        }

        if ($staffId === null && $actionKey === 'active') {
            $staffController->active();
            $handled = true;
            break;
        }

        if ($staffId === null && $actionKey === 'departments') {
            $staffController->departments();
            $handled = true;
            break;
        }

        if ($staffId === null && $actionKey === 'positions') {
            $staffController->positions();
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === 'schedule') {
            $staffController->schedule($staffId);
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === 'attendance') {
            $staffController->attendance($staffId);
            $handled = true;
            break;
        }

        if ($staffId === null && $actionKey === 'statistics') {
            $staffController->statistics();
            $handled = true;
            break;
        }

        if ($staffId === null && $actionKey === 'search') {
            $staffController->search();
            $handled = true;
            break;
        }
        break;

    case 'POST':
        if ($staffId === null && $actionKey === null) {
            $staffController->store();
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === 'activate') {
            $staffController->activate($staffId);
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === 'deactivate') {
            $staffController->deactivate($staffId);
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === 'attendance') {
            $staffController->recordAttendance($staffId);
            $handled = true;
            break;
        }
        break;

    case 'PUT':
        if ($staffId !== null && $actionKey === null) {
            $staffController->update($staffId);
            $handled = true;
        }
        break;

    case 'PATCH':
        if ($staffId !== null && $actionKey === null) {
            $staffController->update($staffId);
            $handled = true;
            break;
        }

        if ($staffId !== null && $actionKey === 'status') {
            $staffController->updateStatus($staffId);
            $handled = true;
        }
        break;

    case 'DELETE':
        if ($staffId !== null && $actionKey === null) {
            $staffController->destroy($staffId);
            $handled = true;
        }
        break;
}

if ($handled) {
    return;
}

/*
|--------------------------------------------------------------------------
| Unknown route
|--------------------------------------------------------------------------
*/

http_response_code(404);

header(
    'Content-Type: application/json; charset=utf-8'
);

echo json_encode([
    'success' => false,
    'message' => 'Staff route not found.',
    'data' => null,
]);

exit;
