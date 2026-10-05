<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Room Controller
 *
 * File:
 * backend/controllers/RoomController.php
 *
 * Responsibilities:
 * - List rooms
 * - Search/filter rooms
 * - Get room details
 * - Get available rooms
 * - Create rooms
 * - Update rooms
 * - Update room status
 * - Delete rooms
 * - Room statistics
 *
 * Routes:
 *
 * GET    /api/rooms
 * GET    /api/rooms/available
 * GET    /api/rooms/{id}
 * GET    /api/rooms/statistics
 *
 * POST   /api/rooms
 * PUT    /api/rooms/{id}
 * PATCH  /api/rooms/{id}
 * PATCH  /api/rooms/{id}/status
 * DELETE /api/rooms/{id}
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

require_once BASE_PATH . '/models/Room.php';

// ---------------------------------------------------------
// Room Controller
// ---------------------------------------------------------

class RoomController
{
    private const ALLOWED_ROOM_TYPES = [
        'single',
        'double',
        'twin',
        'triple',
        'suite',
        'deluxe',
        'family',
        'presidential',
    ];

    private const ALLOWED_ROOM_STATUSES = [
        'available',
        'occupied',
        'reserved',
        'maintenance',
        'cleaning',
        'out_of_order',
    ];

    /**
     * @var object|null
     */
    private $roomModel;

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        $roomModel = null
    ) {
        $roomClass = 'Room';

        $this->roomModel =
            $roomModel instanceof $roomClass
                ? $roomModel
                : new $roomClass();
    }

    /**
     * Normalize and validate room type.
     */
    private function normalizeRoomType(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        $type = strtolower(trim((string) $value));

        return in_array($type, self::ALLOWED_ROOM_TYPES, true)
            ? $type
            : null;
    }

    /**
     * Normalize and validate room status.
     */
    private function normalizeRoomStatus(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        $status = strtolower(trim((string) $value));

        return in_array($status, self::ALLOWED_ROOM_STATUSES, true)
            ? $status
            : null;
    }

    // =====================================================
    // GET /api/rooms
    // =====================================================

    /**
     * List rooms.
     *
     * Supported query parameters:
     *
     * ?page=1
     * ?per_page=20
     * ?search=101
     * ?type=single
     * ?status=available
     * ?floor=2
     * ?min_price=50
     * ?max_price=300
     */
    public function index(
        array $request = []
    ): never {

        try {

            $query =
                $request['query']
                ?? $_GET
                ?? [];

            $page = $this->positiveInt(
                $query['page'] ?? 1,
                1
            );

            $perPage = $this->positiveInt(
                $query['per_page'] ?? 20,
                20
            );

            /*
             * Prevent excessively large responses.
             */
            $perPage = min(
                $perPage,
                100
            );

            $filters = [];

            // -------------------------------------------------
            // Search
            // -------------------------------------------------

            if (
                isset($query['search']) &&
                trim(
                    (string) $query['search']
                ) !== ''
            ) {
                $filters['search'] =
                    trim(
                        (string) $query['search']
                    );
            }

            // -------------------------------------------------
            // Room Type
            // -------------------------------------------------

            if (
                isset($query['type']) &&
                trim(
                    (string) $query['type']
                ) !== ''
            ) {
                $type = $this->normalizeRoomType($query['type']);

                if ($type === null) {
                    $this->badRequest('Invalid room type.');
                }

                $filters['type'] = $type;
            }

            // -------------------------------------------------
            // Status
            // -------------------------------------------------

            if (
                isset($query['status']) &&
                trim(
                    (string) $query['status']
                ) !== ''
            ) {
                $status = $this->normalizeRoomStatus($query['status']);

                if ($status === null) {
                    $this->badRequest('Invalid room status.');
                }

                $filters['status'] = $status;
            }

            // -------------------------------------------------
            // Floor
            // -------------------------------------------------

            if (
                isset($query['floor']) &&
                is_numeric($query['floor'])
            ) {
                $floor = (int) $query['floor'];

                if ($floor >= 0) {
                    $filters['floor'] =
                        $floor;
                }
            }

            // -------------------------------------------------
            // Price Range
            // -------------------------------------------------

            if (
                isset($query['min_price']) &&
                is_numeric($query['min_price'])
            ) {

                $minPrice =
                    (float) $query['min_price'];

                if (
                    $minPrice >= 0
                ) {
                    $filters['min_price'] =
                        $minPrice;
                }
            }

            if (
                isset($query['max_price']) &&
                is_numeric($query['max_price'])
            ) {

                $maxPrice =
                    (float) $query['max_price'];

                if (
                    $maxPrice >= 0
                ) {
                    $filters['max_price'] =
                        $maxPrice;
                }
            }

            // -------------------------------------------------
            // Retrieve Rooms
            // -------------------------------------------------

            $result =
                $this->roomModel->getAll(
                    $filters,
                    $page,
                    $perPage
                );

            $this->success(
                $result['data'],
                'Rooms retrieved successfully.',
                [
                    'pagination' =>
                        $result['pagination'],
                ]
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve rooms.'
            );
        }
    }

    // =====================================================
    // GET /api/rooms/{id}
    // =====================================================

    /**
     * Get room details.
     */
    public function show(
        array $request = []
    ): never {

        try {

            $id =
                $this->getId(
                    $request
                );

            if (
                $id === null
            ) {
                $this->badRequest(
                    'A valid room ID is required.'
                );
            }

            $room =
                $this->roomModel->findById(
                    $id
                );

            if (
                $room === null
            ) {
                $this->notFound(
                    'Room not found.'
                );
            }

            $this->success(
                $room,
                'Room retrieved successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve room.'
            );
        }
    }

    // =====================================================
    // GET /api/rooms/available
    // =====================================================

    /**
     * Get available rooms.
     *
     * Optional parameters:
     *
     * ?check_in=2026-10-10
     * ?check_out=2026-10-12
     * ?type=double
     */
    public function available(
        array $request = []
    ): never {

        try {

            $query =
                $request['query']
                ?? $_GET
                ?? [];

            $checkIn =
                $this->nullableDate(
                    $query['check_in'] ?? null
                );

            $checkOut =
                $this->nullableDate(
                    $query['check_out'] ?? null
                );

            if (
                $checkIn !== null &&
                $checkOut !== null &&
                $checkOut <= $checkIn
            ) {
                $this->badRequest(
                    'Check-out date must be after check-in date.'
                );
            }

            $type = null;

            if (
                isset($query['type']) &&
                trim(
                    (string) $query['type']
                ) !== ''
            ) {
                $type = $this->normalizeRoomType($query['type']);

                if ($type === null) {
                    $this->badRequest('Invalid room type.');
                }
            }

            $rooms =
                $this->roomModel->getAvailable(
                    $checkIn,
                    $checkOut,
                    $type
                );

            $this->success(
                $rooms,
                'Available rooms retrieved successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve available rooms.'
            );
        }
    }

    // =====================================================
    // POST /api/rooms
    // =====================================================

    /**
     * Create a room.
     */
    public function store(
        array $_request = []
    ): never {

        try {

            $data =
                $this->getRequestData();

            // -------------------------------------------------
            // Required Fields
            // -------------------------------------------------

            $roomNumber =
                trim(
                    (string) (
                        $data['room_number']
                        ?? ''
                    )
                );

            $type = $this->normalizeRoomType(
                $data['type'] ?? null
            );

            $price =
                $data['price']
                ?? null;

            if (
                $roomNumber === '' ||
                $type === null ||
                $price === null
            ) {
                $this->badRequest(
                    'Room number, type and price are required.'
                );
            }

            // -------------------------------------------------
            // Validate Room Number
            // -------------------------------------------------

            if (
                strlen($roomNumber) > 50
            ) {
                $this->badRequest(
                    'Room number cannot exceed 50 characters.'
                );
            }

            // -------------------------------------------------
            // Validate Type
            // -------------------------------------------------

            if (
                $type === null
            ) {
                $this->badRequest(
                    'Invalid room type.'
                );
            }

            // -------------------------------------------------
            // Validate Price
            // -------------------------------------------------

            if (
                !is_numeric($price) ||
                (float) $price < 0
            ) {
                $this->badRequest(
                    'Room price must be a valid positive number.'
                );
            }

            $price =
                round(
                    (float) $price,
                    2
                );

            // -------------------------------------------------
            // Duplicate Room
            // -------------------------------------------------

            if (
                $this->roomModel->roomNumberExists(
                    $roomNumber
                )
            ) {
                $this->conflict(
                    'A room with this room number already exists.'
                );
            }

            // -------------------------------------------------
            // Floor
            // -------------------------------------------------

            $floor = null;

            if (
                isset($data['floor']) &&
                $data['floor'] !== ''
            ) {

                if (
                    !is_numeric(
                        $data['floor']
                    )
                ) {
                    $this->badRequest(
                        'Floor must be a valid number.'
                    );
                }

                $floor =
                    (int) $data['floor'];

                if (
                    $floor < 0
                ) {
                    $this->badRequest(
                        'Floor cannot be negative.'
                    );
                }
            }

            // -------------------------------------------------
            // Capacity
            // -------------------------------------------------

            $capacity =
                isset($data['capacity'])
                    ? (int) $data['capacity']
                    : 1;


            if (
                $capacity < 1
            ) {
                $this->badRequest(
                    'Room capacity must be at least 1.'
                );
            }

            // -------------------------------------------------
            // Status
            // -------------------------------------------------

            $status = $this->normalizeRoomStatus(
                $data['status'] ?? 'available'
            );

            if (
                $status === null
            ) {
                $this->badRequest(
                    'Invalid room status.'
                );
            }

            // -------------------------------------------------
            // Create Room
            // -------------------------------------------------

            $roomId =
                $this->roomModel->create([
                    'room_number' =>
                        $roomNumber,

                    'type' =>
                        $type,

                    'price' =>
                        $price,

                    'floor' =>
                        $floor,

                    'capacity' =>
                        $capacity,

                    'status' =>
                        $status,

                    'description' =>
                        $this->nullableString(
                            $data['description']
                            ?? null
                        ),

                    'amenities' =>
                        $this->normalizeAmenities(
                            $data['amenities']
                            ?? null
                        ),

                    'image' =>
                        $this->nullableString(
                            $data['image']
                            ?? null
                        ),
                ]);

            $room =
                $this->roomModel->findById(
                    $roomId
                );

            $this->success(
                $room,
                'Room created successfully.',
                [],
                201
            );

        } catch (
            InvalidArgumentException $exception
        ) {

            $this->badRequest(
                $exception->getMessage()
            );

        } catch (
            RuntimeException $exception
        ) {

            $this->conflict(
                $exception->getMessage()
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to create room.'
            );
        }
    }

    // =====================================================
    // PUT/PATCH /api/rooms/{id}
    // =====================================================

    /**
     * Update room.
     */
    public function update(
        array $request = []
    ): never {

        try {

            $id =
                $this->getId(
                    $request
                );


            if (
                $id === null
            ) {
                $this->badRequest(
                    'A valid room ID is required.'
                );
            }

            $existing =
                $this->roomModel->findById(
                    $id
                );

            if (
                $existing === null
            ) {
                $this->notFound(
                    'Room not found.'
                );
            }

            $data =
                $this->getRequestData();


            if (
                empty($data)
            ) {
                $this->badRequest(
                    'No update data was provided.'
                );
            }

            $updateData = [];

            // -------------------------------------------------
            // Room Number
            // -------------------------------------------------

            if (
                array_key_exists(
                    'room_number',
                    $data
                )
            ) {

                $roomNumber =
                    trim(
                        (string) $data['room_number']
                    );

                if (
                    $roomNumber === ''
                ) {
                    $this->badRequest(
                        'Room number cannot be empty.'
                    );
                }

                if (
                    strlen($roomNumber) > 50
                ) {
                    $this->badRequest(
                        'Room number cannot exceed 50 characters.'
                    );
                }

                if (
                    $this->roomModel->roomNumberExists(
                        $roomNumber,
                        $id
                    )
                ) {
                    $this->conflict(
                        'A room with this room number already exists.'
                    );
                }

                $updateData['room_number'] =
                    $roomNumber;
            }

            // -------------------------------------------------
            // Room Type
            // -------------------------------------------------

            if (
                array_key_exists(
                    'type',
                    $data
                )
            ) {
                $type = $this->normalizeRoomType($data['type']);

                if ($type === null) {
                    $this->badRequest('Invalid room type.');
                }

                $updateData['type'] = $type;
            }

            // -------------------------------------------------
            // Price
            // -------------------------------------------------

            if (
                array_key_exists(
                    'price',
                    $data
                )
            ) {

                if (
                    !is_numeric(
                        $data['price']
                    ) ||
                    (float) $data['price'] < 0
                ) {
                    $this->badRequest(
                        'Room price must be a valid positive number.'
                    );
                }

                $updateData['price'] =
                    round(
                        (float) $data['price'],
                        2
                    );
            }

            // -------------------------------------------------
            // Floor
            // -------------------------------------------------

            if (
                array_key_exists(
                    'floor',
                    $data
                )
            ) {

                if (
                    $data['floor'] === null ||
                    $data['floor'] === ''
                ) {

                    $updateData['floor'] =
                        null;

                } else {

                    if (
                        !is_numeric(
                            $data['floor']
                        )
                    ) {
                        $this->badRequest(
                            'Floor must be a valid number.'
                        );
                    }

                    $floor =
                        (int) $data['floor'];

                    if (
                        $floor < 0
                    ) {
                        $this->badRequest(
                            'Floor cannot be negative.'
                        );
                    }

                    $updateData['floor'] =
                        $floor;
                }
            }

            // -------------------------------------------------
            // Capacity
            // -------------------------------------------------

            if (
                array_key_exists(
                    'capacity',
                    $data
                )
            ) {

                $capacity =
                    (int) $data['capacity'];

                if (
                    $capacity < 1
                ) {
                    $this->badRequest(
                        'Room capacity must be at least 1.'
                    );
                }

                $updateData['capacity'] =
                    $capacity;
            }

            // -------------------------------------------------
            // Status
            // -------------------------------------------------

            if (
                array_key_exists(
                    'status',
                    $data
                )
            ) {
                $status = $this->normalizeRoomStatus($data['status']);

                if ($status === null) {
                    $this->badRequest('Invalid room status.');
                }

                $updateData['status'] = $status;
            }

            // -------------------------------------------------
            // Description
            // -------------------------------------------------

            if (
                array_key_exists(
                    'description',
                    $data
                )
            ) {

                $updateData['description'] =
                    $this->nullableString(
                        $data['description']
                    );
            }

            // -------------------------------------------------
            // Amenities
            // -------------------------------------------------

            if (
                array_key_exists(
                    'amenities',
                    $data
                )
            ) {

                $updateData['amenities'] =
                    $this->normalizeAmenities(
                        $data['amenities']
                    );
            }

            // -------------------------------------------------
            // Image
            // -------------------------------------------------

            if (
                array_key_exists(
                    'image',
                    $data
                )
            ) {

                $updateData['image'] =
                    $this->nullableString(
                        $data['image']
                    );
            }

            if (
                empty($updateData)
            ) {
                $this->badRequest(
                    'No valid fields were provided for update.'
                );
            }

            // -------------------------------------------------
            // Update
            // -------------------------------------------------

            $this->roomModel->update(
                $id,
                $updateData
            );

            $room =
                $this->roomModel->findById(
                    $id
                );

            $this->success(
                $room,
                'Room updated successfully.'
            );

        } catch (
            InvalidArgumentException $exception
        ) {

            $this->badRequest(
                $exception->getMessage()
            );

        } catch (
            RuntimeException $exception
        ) {

            $this->conflict(
                $exception->getMessage()
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to update room.'
            );
        }
    }

    // =====================================================
    // PATCH /api/rooms/{id}/status
    // =====================================================

    /**
     * Update room status.
     */
    public function updateStatus(
        array $request = []
    ): never {

        try {

            $id =
                $this->getId(
                    $request
                );

            if (
                $id === null
            ) {
                $this->badRequest(
                    'A valid room ID is required.'
                );
            }

            $existing =
                $this->roomModel->findById(
                    $id
                );

            if (
                $existing === null
            ) {
                $this->notFound(
                    'Room not found.'
                );
            }

            $data =
                $this->getRequestData();

            $status = $this->normalizeRoomStatus(
                $data['status'] ?? null
            );

            if (
                $status === null
            ) {
                $this->badRequest(
                    'Room status is required and must be valid.'
                );
            }

            $this->roomModel->updateStatus(
                $id,
                $status
            );

            $room =
                $this->roomModel->findById(
                    $id
                );

            $this->success(
                $room,
                'Room status updated successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to update room status.'
            );
        }
    }

    // =====================================================
    // DELETE /api/rooms/{id}
    // =====================================================

    /**
     * Delete room.
     */
    public function destroy(
        array $request = []
    ): never {

        try {

            $id =
                $this->getId(
                    $request
                );

            if (
                $id === null
            ) {
                $this->badRequest(
                    'A valid room ID is required.'
                );
            }

            $existing =
                $this->roomModel->findById(
                    $id
                );

            if (
                $existing === null
            ) {
                $this->notFound(
                    'Room not found.'
                );
            }

            /*
             * The model/service should prevent deletion of
             * rooms that have active/future bookings.
             */
            $deleted =
                $this->roomModel->delete(
                    $id
                );

            if (
                !$deleted
            ) {
                $this->conflict(
                    'Room cannot be deleted. It may have associated bookings.'
                );
            }

            $this->success(
                null,
                'Room deleted successfully.'
            );

        } catch (
            RuntimeException $exception
        ) {

            $this->conflict(
                $exception->getMessage()
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to delete room.'
            );
        }
    }

    // =====================================================
    // GET /api/rooms/statistics
    // =====================================================

    /**
     * Get room statistics.
     */
    public function statistics(
        array $_request = []
    ): never {

        try {

            $statistics =
                $this->roomModel->getStatistics();

            $this->success(
                $statistics,
                'Room statistics retrieved successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve room statistics.'
            );
        }
    }

    // =====================================================
    // Request Data
    // =====================================================

    /**
     * Read request body.
     *
     * Supports:
     * - Parsed request data
     * - application/x-www-form-urlencoded
     * - multipart/form-data
     * - application/json
     */
    private function getRequestData(): array
    {
        if (
            isset($_REQUEST['data']) &&
            is_array($_REQUEST['data'])
        ) {
            return $_REQUEST['data'];
        }

        if (
            !empty($_POST)
        ) {
            return $_POST;
        }

        $rawBody =
            file_get_contents(
                'php://input'
            );

        if (
            $rawBody === false ||
            trim($rawBody) === ''
        ) {
            return [];
        }

        $decoded =
            json_decode(
                $rawBody,
                true
            );

        if (
            !is_array($decoded)
        ) {
            $this->badRequest(
                'Invalid JSON request body.'
            );
        }

        return $decoded;
    }

    // =====================================================
    // Helpers
    // =====================================================

    /**
     * Extract room ID from request.
     */
    private function getId(
        array $request
    ): ?int {

        $id =
            $request['params']['id']
            ?? $request['id']
            ?? null;

        if (
            $id === null &&
            isset($_GET['id'])
        ) {
            $id = $_GET['id'];
        }

        if (
            !is_numeric($id)
        ) {
            return null;
        }

        $id = (int) $id;

        return $id > 0
            ? $id
            : null;
    }

    /**
     * Convert value to positive integer.
     */
    private function positiveInt(
        mixed $value,
        int $default
    ): int {

        if (
            !is_numeric($value)
        ) {
            return $default;
        }

        $value = (int) $value;

        return $value > 0
            ? $value
            : $default;
    }

    /**
     * Validate optional date.
     *
     * Returns YYYY-MM-DD or null.
     */
    private function nullableDate(
        mixed $value
    ): ?string {

        if (
            $value === null ||
            trim(
                (string) $value
            ) === ''
        ) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        $date =
            DateTime::createFromFormat(
                'Y-m-d',
                $value
            );

        if (
            $date === false ||
            $date->format('Y-m-d') !== $value
        ) {
            $this->badRequest(
                'Invalid date. Expected format: YYYY-MM-DD.'
            );
        }

        return $value;
    }

    /**
     * Convert an optional string to null.
     */
    private function nullableString(
        mixed $value
    ): ?string {

        if (
            $value === null
        ) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value === ''
            ? null
            : $value;
    }

    /**
     * Normalize amenities.
     *
     * Supports:
     *
     * [
     *     "WiFi",
     *     "TV",
     *     "Air Conditioning"
     * ]
     *
     * or a comma-separated string.
     */
    private function normalizeAmenities(
        mixed $amenities
    ): ?string {

        if (
            $amenities === null
        ) {
            return null;
        }

        if (
            is_string($amenities)
        ) {

            $amenities =
                array_map(
                    'trim',
                    explode(
                        ',',
                        $amenities
                    )
                );
        }

        if (
            !is_array($amenities)
        ) {
            return null;
        }

        $clean = [];

        foreach (
            $amenities as $amenity
        ) {

            if (
                !is_string($amenity)
            ) {
                continue;
            }

            $amenity =
                trim($amenity);

            if (
                $amenity === ''
            ) {
                continue;
            }

            $clean[] =
                $amenity;
        }

        $clean =
            array_values(
                array_unique(
                    $clean
                )
            );

        if (
            empty($clean)
        ) {
            return null;
        }

        return json_encode(
            $clean,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }

    // =====================================================
    // JSON Responses
    // =====================================================

    /**
     * Successful response.
     */
    private function success(
        mixed $data = null,
        string $message = 'Success.',
        array $meta = [],
        int $status = 200
    ): never {

        http_response_code(
            $status
        );

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (
            !empty($meta)
        ) {
            $response['meta'] =
                $meta;
        }

        $this->sendJson(
            $response
        );
    }

    /**
     * 400 Bad Request.
     */
    private function badRequest(
        string $message
    ): never {

        http_response_code(400);

        $this->sendJson([
            'success' => false,
            'message' => $message,
            'error' => 'BAD_REQUEST',
        ]);
    }

    /**
     * 404 Not Found.
     */
    private function notFound(
        string $message
    ): never {

        http_response_code(404);

        $this->sendJson([
            'success' => false,
            'message' => $message,
            'error' => 'NOT_FOUND',
        ]);
    }

    /**
     * 409 Conflict.
     */
    private function conflict(
        string $message
    ): never {

        http_response_code(409);

        $this->sendJson([
            'success' => false,
            'message' => $message,
            'error' => 'CONFLICT',
        ]);
    }

    /**
     * 500 Server Error.
     */
    private function serverError(
        ?Throwable $exception,
        string $message
    ): never {

        if (
            $exception !== null
        ) {
            $this->logException(
                $exception
            );
        }

        http_response_code(500);

        $this->sendJson([
            'success' => false,
            'message' => $message,
            'error' => 'SERVER_ERROR',
        ]);
    }

    /**
     * Output JSON response.
     */
    private function sendJson(
        array $response
    ): never {

        if (
            !headers_sent()
        ) {
            header(
                'Content-Type: application/json; charset=utf-8'
            );
        }

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    // =====================================================
    // Logging
    // =====================================================

    /**
     * Log exception without exposing internals.
     */
    private function logException(
        Throwable $exception
    ): void {

        if (
            function_exists('logMessage')
        ) {

            $level =
                defined('LOG_LEVEL_ERROR')
                    ? LOG_LEVEL_ERROR
                    : 'error';

            logMessage(
                $level,
                'RoomController error.',
                [
                    'exception' =>
                        get_class(
                            $exception
                        ),

                    'message' =>
                        $exception->getMessage(),

                    'file' =>
                        $exception->getFile(),

                    'line' =>
                        $exception->getLine(),
                ]
            );

            return;
        }

        error_log(
            sprintf(
                'RoomController error: %s in %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            )
        );
    }
}
