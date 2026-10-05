<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Guest Routes
 *
 * File:
 * backend/routes/guests.php
 *
 * Endpoints:
 *
 * GET    /api/guests
 * GET    /api/guests/{id}
 * POST   /api/guests
 * PUT    /api/guests/{id}
 * PATCH  /api/guests/{id}
 * DELETE /api/guests/{id}
 *
 * GET    /api/guests/search
 *
 * Authentication:
 * All guest-management routes require authentication.
 */

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/role.php';
require_once __DIR__ . '/../controllers/GuestController.php';

if (!class_exists('GuestController', false)) {
    class GuestController
    {
        public function __construct()
        {
        }
    }
}

if (!function_exists('authMiddleware')) {
    function authMiddleware(): void
    {
        // Fallback for standalone/static analysis environments.
    }
}

if (!function_exists('roleMiddleware')) {
    function roleMiddleware(array $roles = []): void
    {
        // Fallback for standalone/static analysis environments.
        $roles = array_values($roles);

        if ($roles === []) {
            return;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Guest Controller
|--------------------------------------------------------------------------
*/

$guestController = new GuestController();

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| Guest information is private hotel/customer data.
| Every endpoint requires an authenticated user.
|
*/

authMiddleware();

/*
|--------------------------------------------------------------------------
| Request Information
|--------------------------------------------------------------------------
*/

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$uri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$uri = rtrim(
    (string) $uri,
    '/'
);

$guestCollectionPath = '/api/guests';

/*
|--------------------------------------------------------------------------
| Extract Guest ID
|--------------------------------------------------------------------------
|
| Supports:
|
| /api/guests/10
|
*/

$guestId = null;

if (
    preg_match(
        '#^/api/guests/([1-9][0-9]*)$#D',
        $uri,
        $matches
    )
) {
    $guestId = (int) $matches[1];
}

/*
|--------------------------------------------------------------------------
| Helper: JSON Response
|--------------------------------------------------------------------------
|
| This fallback is used only if the project's response helper
| has not already been loaded by index.php.
|
*/

if (!function_exists('guestRouteResponse')) {

    function guestRouteResponse(
        mixed $data,
        int $status = 200
    ): never {

        http_response_code($status);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            [
                'success' => $status >= 200 && $status < 300,
                'data' => $data,
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Helper: Request Body
|--------------------------------------------------------------------------
*/

if (!function_exists('guestRouteInput')) {

    function guestRouteInput(): array
    {
        $raw = file_get_contents(
            'php://input'
        );

        if (
            $raw === false ||
            trim($raw) === ''
        ) {
            return [];
        }

        $data = json_decode(
            $raw,
            true
        );

        if (
            !is_array($data)
        ) {
            guestRouteResponse(
                [
                    'message' =>
                        'Invalid JSON request body.',
                ],
                400
            );
        }

        return $data;
    }
}

/*
|--------------------------------------------------------------------------
| Helper: Controller Method Dispatcher
|--------------------------------------------------------------------------
|
| The controller may expose slightly different method names
| depending on the implementation. This helper keeps routing
| centralized while supporting the standard names used in this
| project.
|
*/

if (!function_exists('guestCallController')) {

    function guestCallController(
        object $controller,
        array $methods,
        array $arguments = []
    ): mixed {

        foreach ($methods as $methodName) {

            if (
                method_exists(
                    $controller,
                    $methodName
                )
            ) {
                return $controller->{$methodName}(
                    ...$arguments
                );
            }
        }

        throw new RuntimeException(
            'Guest controller method is not implemented.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| GET /api/guests/search
|--------------------------------------------------------------------------
|
| Search guests.
|
| Query examples:
|
| ?q=john
| ?search=john
| ?email=john@example.com
| ?phone=5551234
|
| Search is handled before /api/guests/{id}.
|
*/

if (
    $method === 'GET' &&
    $uri === $guestCollectionPath . '/search'
) {

    /*
     * Searching guest records is normally available to
     * reception/front-desk staff and administrators.
     */
    roleMiddleware([
        'admin',
        'manager',
        'receptionist',
        'front_desk',
    ]);

    $query = $_GET;

    $result = guestCallController(
        $guestController,
        [
            'search',
            'searchGuests',
            'getGuests',
        ],
        [$query]
    );

    guestRouteResponse(
        $result
    );
}

/*
|--------------------------------------------------------------------------
| GET /api/guests
|--------------------------------------------------------------------------
|
| List guests.
|
| Supported query parameters may include:
|
| ?page=1
| ?per_page=20
| ?search=john
| ?status=active
| ?email=john@example.com
| ?phone=5551234
|
*/

if (
    $method === 'GET' &&
    $uri === $guestCollectionPath &&
    $guestId === null
) {

    roleMiddleware([
        'admin',
        'manager',
        'receptionist',
        'front_desk',
    ]);

    $page =
        isset($_GET['page'])
            ? max(
                1,
                (int) $_GET['page']
            )
            : 1;

    $perPage =
        isset($_GET['per_page'])
            ? max(
                1,
                min(
                    100,
                    (int) $_GET['per_page']
                )
            )
            : 20;


    $filters = $_GET;

    $result = guestCallController(
        $guestController,
        [
            'index',
            'getAll',
            'getGuests',
            'list',
        ],
        [
            $filters,
            $page,
            $perPage,
        ]
    );

    guestRouteResponse(
        $result
    );
}

/*
|--------------------------------------------------------------------------
| GET /api/guests/{id}
|--------------------------------------------------------------------------
|
| Get a single guest.
|
*/

if (
    $method === 'GET' &&
    $guestId !== null
) {

    roleMiddleware([
        'admin',
        'manager',
        'receptionist',
        'front_desk',
    ]);

    $result = guestCallController(
        $guestController,
        [
            'show',
            'get',
            'getGuest',
            'find',
        ],
        [
            $guestId,
        ]
    );

    guestRouteResponse(
        $result
    );
}

/*
|--------------------------------------------------------------------------
| POST /api/guests
|--------------------------------------------------------------------------
|
| Create a guest.
|
*/

if (
    $method === 'POST' &&
    $uri === $guestCollectionPath
) {

    roleMiddleware([
        'admin',
        'manager',
        'receptionist',
        'front_desk',
    ]);

    $data =
        guestRouteInput();


    $result = guestCallController(
        $guestController,
        [
            'store',
            'create',
            'createGuest',
        ],
        [
            $data,
        ]
    );

    guestRouteResponse(
        $result,
        201
    );
}

/*
|--------------------------------------------------------------------------
| PUT /api/guests/{id}
|--------------------------------------------------------------------------
|
| Full guest update.
|
*/

if (
    $method === 'PUT' &&
    $guestId !== null
) {

    roleMiddleware([
        'admin',
        'manager',
        'receptionist',
        'front_desk',
    ]);

    $data =
        guestRouteInput();


    $result = guestCallController(
        $guestController,
        [
            'update',
            'updateGuest',
        ],
        [
            $guestId,
            $data,
        ]
    );

    guestRouteResponse(
        $result
    );
}

/*
|--------------------------------------------------------------------------
| PATCH /api/guests/{id}
|--------------------------------------------------------------------------
|
| Partial guest update.
|
*/

if (
    $method === 'PATCH' &&
    $guestId !== null
) {

    roleMiddleware([
        'admin',
        'manager',
        'receptionist',
        'front_desk',
    ]);

    $data =
        guestRouteInput();

    $result = guestCallController(
        $guestController,
        [
            'update',
            'updateGuest',
            'patch',
        ],
        [
            $guestId,
            $data,
        ]
    );

    guestRouteResponse(
        $result
    );
}


/*
|--------------------------------------------------------------------------
| DELETE /api/guests/{id}
|--------------------------------------------------------------------------
|
| Delete guest.
|
| Deleting guest records is restricted to administrators and
| managers because guest information can be connected to
| historical bookings and invoices.
|
*/

if (
    $method === 'DELETE' &&
    $guestId !== null
) {

    roleMiddleware([
        'admin',
        'manager',
    ]);

    $result = guestCallController(
        $guestController,
        [
            'destroy',
            'delete',
            'deleteGuest',
        ],
        [
            $guestId,
        ]
    );


    guestRouteResponse(
        $result
    );
}

/*
|--------------------------------------------------------------------------
| Method Not Allowed
|--------------------------------------------------------------------------
*/

$allowedMethods = [
    'GET',
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
];

if (
    in_array(
        $method,
        $allowedMethods,
        true
    )
) {

    guestRouteResponse(
        [
            'message' =>
                'Guest route not found.',
        ],
        404
    );
}

/*
|--------------------------------------------------------------------------
| Unsupported HTTP Method
|--------------------------------------------------------------------------
*/

header(
    'Allow: ' .
    implode(
        ', ',
        $allowedMethods
    )
);

guestRouteResponse(
    [
        'message' =>
            'HTTP method not allowed.',
    ],
    405
);
