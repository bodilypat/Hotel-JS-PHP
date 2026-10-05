<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/routes/reports.php
 *
 * Report API routes.
 */

require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/role.php';

if (!class_exists('ReportController', false)) {
    class ReportController
    {
        public function __call(string $name, array $arguments): void
        {
            unset($arguments);

            http_response_code(501);

            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => "Report action '{$name}' is not implemented.",
                'data' => null,
            ]);

            exit;
        }
    }
}

$reportController = new ReportController();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$uri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?? '/';

$segments = array_values(
    array_filter(
        explode('/', trim($uri, '/')),
        static fn ($segment): bool => $segment !== ''
    )
);

$segments = array_map(
    static fn ($segment): string => strtolower((string) $segment),
    $segments
);

/*
|--------------------------------------------------------------------------
| Locate "reports" in URI
|--------------------------------------------------------------------------
*/

$reportsIndex = array_search(
    'reports',
    $segments,
    true
);

if ($reportsIndex === false) {
    return;
}

$reportSegments = array_slice(
    $segments,
    $reportsIndex + 1
);

$reportId = null;
$action = null;

/*
|--------------------------------------------------------------------------
| Extract report ID / action
|--------------------------------------------------------------------------
*/

if (
    isset($reportSegments[0])
    && ctype_digit((string) $reportSegments[0])
) {
    $reportId = (int) $reportSegments[0];
    $action = $reportSegments[1] ?? null;
} else {
    $action = $reportSegments[0] ?? null;
}

$normalizeAction = static function ($segment): ?string {
    if ($segment === null) {
        return null;
    }

    $segment = trim((string) $segment);

    if ($segment === '') {
        return null;
    }

    return strtolower($segment);
};

$action = $normalizeAction($action);

$dispatchReportAction = static function (string $actionName, ?int $reportIdToShow = null) use ($reportController): void {
    if (!method_exists($reportController, $actionName) || !is_callable([$reportController, $actionName])) {
        http_response_code(501);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => false,
            'message' => "Report action '{$actionName}' is not implemented.",
            'data' => null,
        ]);

        exit;
    }

    if ($reportIdToShow !== null) {
        $reportController->{$actionName}($reportIdToShow);
        return;
    }

    $reportController->{$actionName}();
};

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (function_exists('requireAuth')) {
    requireAuth();
}

/*
|--------------------------------------------------------------------------
| GET /api/reports
|--------------------------------------------------------------------------
|
| General report summary.
|
| Query parameters:
|
| ?date_from=2026-01-01
| ?date_to=2026-01-31
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === null
) {
    $dispatchReportAction('index');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/dashboard
|--------------------------------------------------------------------------
|
| Dashboard report/summary.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'dashboard'
) {
    $dispatchReportAction('dashboard');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/revenue
|--------------------------------------------------------------------------
|
| Revenue report.
|
| Query parameters:
|
| ?date_from=2026-01-01
| ?date_to=2026-01-31
| ?group_by=day
| ?payment_method=card
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && ($action === 'revenue' || $action === 'revenue-report')
) {
    $dispatchReportAction('revenue');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/occupancy
|--------------------------------------------------------------------------
|
| Occupancy report.
|
| Query parameters:
|
| ?date_from=2026-01-01
| ?date_to=2026-01-31
| ?room_type=deluxe
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && ($action === 'occupancy' || $action === 'occupancy-report')
) {
    $dispatchReportAction('occupancy');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/bookings
|--------------------------------------------------------------------------
|
| Booking statistics report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'bookings'
) {
    $dispatchReportAction('bookings');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/guests
|--------------------------------------------------------------------------
|
| Guest statistics report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'guests'
) {
    $dispatchReportAction('guests');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/rooms
|--------------------------------------------------------------------------
|
| Room statistics report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'rooms'
) {
    $dispatchReportAction('rooms');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/payments
|--------------------------------------------------------------------------
|
| Payment statistics report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'payments'
) {
    $dispatchReportAction('payments');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/staff
|--------------------------------------------------------------------------
|
| Staff statistics report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'staff'
) {
    $dispatchReportAction('staff');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/monthly
|--------------------------------------------------------------------------
|
| Monthly hotel performance report.
|
| Query parameters:
|
| ?year=2026
| ?month=1
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'monthly'
) {
    $dispatchReportAction('monthly');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/yearly
|--------------------------------------------------------------------------
|
| Yearly hotel performance report.
|
| Query parameters:
|
| ?year=2026
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'yearly'
) {
    $dispatchReportAction('yearly');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/summary
|--------------------------------------------------------------------------
|
| Overall hotel performance summary.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'summary'
) {
    $dispatchReportAction('summary');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/export
|--------------------------------------------------------------------------
|
| Export report.
|
| Query parameters:
|
| ?type=revenue
| ?format=csv
| ?date_from=2026-01-01
| ?date_to=2026-01-31
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'export'
) {
    $dispatchReportAction('export');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/financial
|--------------------------------------------------------------------------
|
| Financial report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'financial'
) {
    $dispatchReportAction('financial');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/performance
|--------------------------------------------------------------------------
|
| Hotel performance report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId === null
    && $action === 'performance'
) {
    $dispatchReportAction('performance');
    return;
}

/*
|--------------------------------------------------------------------------
| GET /api/reports/{id}
|--------------------------------------------------------------------------
|
| Retrieve a specific saved report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'GET'
    && $reportId !== null
    && $action === null
) {
    $dispatchReportAction('show', $reportId);
    return;
}

/*
|--------------------------------------------------------------------------
| POST /api/reports/generate
|--------------------------------------------------------------------------
|
| Generate a custom report.
|--------------------------------------------------------------------------
*/

if (
    $method === 'POST'
    && $reportId === null
    && $action === 'generate'
) {
    $dispatchReportAction('generate');
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
    'message' => 'Report route not found.',
    'data' => null,
]);

exit;
