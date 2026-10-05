<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Room Service
 *
 * File:
 * backend/services/RoomService.php
 *
 * Responsibilities:
 * - Room business rules
 * - Room validation
 * - Room creation/update rules
 * - Room availability rules
 * - Room status transitions
 * - Room deletion rules
 * - Room statistics
 *
 * The service layer should contain business logic.
 * Database operations remain inside Room.php.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/models/Room.php';

class RoomService
{
    private Room $roomModel;

    // =====================================================
    // Constants
    // =====================================================

    private const ROOM_TYPES = [
        'single',
        'double',
        'twin',
        'triple',
        'suite',
        'deluxe',
        'family',
        'presidential',
    ];

    private const ROOM_STATUSES = [
        'available',
        'occupied',
        'reserved',
        'maintenance',
        'cleaning',
        'out_of_order',
    ];

    /**
     * Valid room status transitions.
     *
     * This prevents invalid operational changes such as
     * moving an out-of-order room directly to occupied.
     */
    private const STATUS_TRANSITIONS = [
        'available' => [
            'reserved',
            'occupied',
            'maintenance',
            'cleaning',
            'out_of_order',
        ],

        'reserved' => [
            'available',
            'occupied',
            'maintenance',
            'cleaning',
            'out_of_order',
        ],

        'occupied' => [
            'available',
            'cleaning',
            'maintenance',
            'out_of_order',
        ],

        'cleaning' => [
            'available',
            'maintenance',
            'out_of_order',
        ],

        'maintenance' => [
            'available',
            'cleaning',
            'out_of_order',
        ],

        'out_of_order' => [
            'maintenance',
            'available',
        ],
    ];

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        ?Room $roomModel = null
    ) {
        $this->roomModel =
            $roomModel
            ?? new Room();
    }

    // =====================================================
    // List Rooms
    // =====================================================

    /**
     * Get rooms using validated filters.
     */
    public function listRooms(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        $page = $this->normalizePage($page);

        $perPage =
            $this->normalizePerPage(
                $perPage
            );


        $filters =
            $this->validateFilters(
                $filters
            );

        return $this->roomModel->getAll(
            $filters,
            $page,
            $perPage
        );
    }

    // =====================================================
    // Get Room
    // =====================================================

    /**
     * Get one room.
     */
    public function getRoom(
        int $roomId
    ): array {

        $this->validateRoomId(
            $roomId
        );

        $room =
            $this->roomModel->findById(
                $roomId
            );

        if ($room === null) {
            throw new RuntimeException(
                'Room not found.'
            );
        }

        return $room;
    }

    // =====================================================
    // Available Rooms
    // =====================================================

    /**
     * Find rooms available for a stay.
     *
     * If dates are supplied, booking conflicts are checked
     * by the Room model.
     */
    public function getAvailableRooms(
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?string $type = null
    ): array {

        $checkIn =
            $this->normalizeDate(
                $checkIn,
                'check-in date'
            );

        $checkOut =
            $this->normalizeDate(
                $checkOut,
                'check-out date'
            );

        if (
            ($checkIn === null) !==
            ($checkOut === null)
        ) {
            throw new InvalidArgumentException(
                'Both check-in and check-out dates are required.'
            );
        }

        if (
            $checkIn !== null &&
            $checkOut !== null
        ) {

            if (
                $checkOut <= $checkIn
            ) {
                throw new InvalidArgumentException(
                    'Check-out date must be after check-in date.'
                );
            }

            $this->validateStayLength(
                $checkIn,
                $checkOut
            );
        }

        if (
            $type !== null
        ) {
            $type =
                $this->normalizeRoomType(
                    $type
                );
        }

        return $this->roomModel->getAvailable(
            $checkIn,
            $checkOut,
            $type
        );
    }

    // =====================================================
    // Create Room
    // =====================================================

    /**
     * Create a room after applying business rules.
     */
    public function createRoom(
        array $data
    ): array {

        $data =
            $this->validateCreateData(
                $data
            );

        /*
         * New rooms should normally begin as available.
         * A caller may explicitly request another status only
         * when that status is operationally valid.
         */
        $status =
            $data['status']
            ?? 'available';

        if (
            !in_array(
                $status,
                self::ROOM_STATUSES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid room status.'
            );
        }

        /*
         * A newly created room should not be created as occupied
         * or reserved without an associated booking/check-in.
         */
        if (
            in_array(
                $status,
                ['occupied', 'reserved'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'A new room cannot be created directly as occupied or reserved.'
            );
        }

        $data['status'] =
            $status;

        $roomId =
            $this->roomModel->create(
                $data
            );

        return $this->getRoom(
            $roomId
        );
    }

    // =====================================================
    // Update Room
    // =====================================================

    /**
     * Update room information.
     */
    public function updateRoom(
        int $roomId,
        array $data
    ): array {

        $this->validateRoomId(
            $roomId
        );

        $existing =
            $this->getRoom(
                $roomId
            );

        $data =
            $this->validateUpdateData(
                $data,
                $existing
            );

        /*
         * Status changes are handled by changeRoomStatus()
         * so that transition rules are always applied.
         */
        if (
            array_key_exists(
                'status',
                $data
            )
        ) {

            $newStatus =
                $data['status'];

            unset(
                $data['status']
            );

            if (
                $newStatus !==
                ($existing['status'] ?? null)
            ) {

                $this->changeRoomStatus(
                    $roomId,
                    $newStatus,
                    $existing
                );
            }
        }

        if (
            !empty($data)
        ) {
            $this->roomModel->update(
                $roomId,
                $data
            );
        }

        return $this->getRoom(
            $roomId
        );
    }

    // =====================================================
    // Change Room Status
    // =====================================================

    /**
     * Apply a controlled room-status transition.
     */
    public function changeRoomStatus(
        int $roomId,
        string $newStatus,
        ?array $existingRoom = null
    ): array {

        $this->validateRoomId(
            $roomId
        );

        $newStatus =
            $this->normalizeRoomStatus(
                $newStatus
            );

        $room =
            $existingRoom
            ?? $this->getRoom($roomId);

        $currentStatus =
            $this->getNormalizedRoomStatus(
                $room
            );

        if (
            $currentStatus === ''
        ) {
            throw new RuntimeException(
                'Current room status is unavailable.'
            );
        }

        if (
            $currentStatus === $newStatus
        ) {
            return $room;
        }

        if (
            !$this->canTransitionStatus(
                $currentStatus,
                $newStatus
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Room cannot change from "%s" to "%s".',
                    $currentStatus,
                    $newStatus
                )
            );
        }

        /*
         * Business rule:
         *
         * A room with an active reservation should not be made
         * available manually unless the booking is handled by
         * the booking workflow.
         */
        if (
            $currentStatus === 'reserved' &&
            $newStatus === 'available'
        ) {
            throw new InvalidArgumentException(
                'Reserved rooms must be released through the booking workflow.'
            );
        }

        /*
         * Occupied rooms should normally be released through
         * the checkout workflow.
         */
        if (
            $currentStatus === 'occupied' &&
            $newStatus === 'available'
        ) {
            throw new InvalidArgumentException(
                'Occupied rooms must be released through the checkout workflow.'
            );
        }

        $this->roomModel->updateStatus(
            $roomId,
            $newStatus
        );

        return $this->getRoom(
            $roomId
        );
    }

    // =====================================================
    // Delete Room
    // =====================================================

    /**
     * Delete a room.
     */
    public function deleteRoom(
        int $roomId
    ): bool {

        $this->validateRoomId(
            $roomId
        );

        $room =
            $this->getRoom(
                $roomId
            );

        $status =
            strtolower(
                trim(
                    (string) (
                        $room['status']
                        ?? ''
                    )
                )
            );

        /*
         * Operational rooms should not be deleted casually.
         * Prefer marking them out_of_order for historical records.
         */
        $this->assertRoomIsDeleteSafe($status);

        return $this->roomModel->delete(
            $roomId
        );
    }

    // =====================================================
    // Statistics
    // =====================================================

    /**
     * Get room statistics.
     */
    public function getStatistics(): array
    {
        $statistics =
            $this->roomModel->getStatistics();

        $total =
            (int) (
                $statistics['total_rooms']
                ?? 0
            );

        $available =
            (int) (
                $statistics['available_rooms']
                ?? 0
            );

        $occupied =
            (int) (
                $statistics['occupied_rooms']
                ?? 0
            );

        $reserved =
            (int) (
                $statistics['reserved_rooms']
                ?? 0
            );

        $maintenance =
            (int) (
                $statistics['maintenance_rooms']
                ?? 0
            );

        $cleaning =
            (int) (
                $statistics['cleaning_rooms']
                ?? 0
            );

        $outOfOrder =
            (int) (
                $statistics['out_of_order_rooms']
                ?? 0
            );

        /*
         * Operational rooms are rooms that are not marked
         * out-of-order or under maintenance.
         */
        $operational =
            max(
                0,
                $total -
                $maintenance -
                $outOfOrder
            );

        $occupancyRate =
            $operational > 0
                ? round(
                    (
                        $occupied /
                        $operational
                    ) * 100,
                    2
                )
                : 0;

        $availabilityRate =
            $operational > 0
                ? round(
                    (
                        $available /
                        $operational
                    ) * 100,
                    2
                )
                : 0;

        return array_merge(
            $statistics,
            [
                'operational_rooms' =>
                    $operational,

                'occupancy_rate' =>
                    $occupancyRate,

                'availability_rate' =>
                    $availabilityRate,

                'status_totals' => [
                    'available' =>
                        $available,

                    'occupied' =>
                        $occupied,

                    'reserved' =>
                        $reserved,

                    'maintenance' =>
                        $maintenance,

                    'cleaning' =>
                        $cleaning,

                    'out_of_order' =>
                        $outOfOrder,
                ],
            ]
        );
    }

    // =====================================================
    // Counts By Type
    // =====================================================

    /**
     * Get room counts grouped by room type.
     */
    public function getCountsByType(): array
    {
        return $this->roomModel->getCountsByType();
    }

    // =====================================================
    // Counts By Status
    // =====================================================

    /**
     * Get room counts grouped by status.
     */
    public function getCountsByStatus(): array
    {
        return $this->roomModel->getCountsByStatus();
    }

    // =====================================================
    // Room Type Validation
    // =====================================================

    /**
     * Validate and normalize room type.
     */
    private function normalizeRoomType(
        string $type
    ): string {

        $type =
            strtolower(
                trim($type)
            );

        if (
            !in_array(
                $type,
                self::ROOM_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid room type. Allowed types: ' .
                implode(', ', self::ROOM_TYPES)
            );
        }


        return $type;
    }

    // =====================================================
    // Room Status Validation
    // =====================================================

    /**
     * Validate and normalize room status.
     */
    private function normalizeRoomStatus(
        string $status
    ): string {

        $status =
            strtolower(
                trim($status)
            );

        if (
            !in_array(
                $status,
                self::ROOM_STATUSES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid room status. Allowed statuses: ' .
                implode(', ', self::ROOM_STATUSES)
            );
        }

        return $status;
    }

    /**
     * Safely read and normalize a room status from a room array.
     */
    private function getNormalizedRoomStatus(
        array $room
    ): string {
        return strtolower(
            trim(
                (string) (
                    $room['status']
                    ?? ''
                )
            )
        );
    }

    /**
     * Ensure that rooms in active use are not deleted casually.
     */
    private function assertRoomIsDeleteSafe(
        string $status
    ): void {
        if (
            in_array(
                $status,
                ['occupied', 'reserved', 'cleaning', 'maintenance'],
                true
            )
        ) {
            throw new RuntimeException(
                'Occupied, reserved, cleaning, or maintenance rooms cannot be deleted.'
            );
        }
    }

    // =====================================================
    // Status Transition
    // =====================================================

    /**
     * Check whether a status transition is allowed.
     */
    public function canTransitionStatus(
        string $from,
        string $to
    ): bool {

        $from =
            strtolower(
                trim($from)
            );

        $to =
            strtolower(
                trim($to)
            );

        if (
            $from === $to
        ) {
            return true;
        }

        if (
            !isset(
                self::STATUS_TRANSITIONS[$from]
            )
        ) {
            return false;
        }

        return in_array(
            $to,
            self::STATUS_TRANSITIONS[$from],
            true
        );
    }

    // =====================================================
    // Create Validation
    // =====================================================

    /**
     * Validate data used to create a room.
     */
    private function validateCreateData(
        array $data
    ): array {

        $roomNumber =
            trim(
                (string) (
                    $data['room_number']
                    ?? ''
                )
            );

        if (
            $roomNumber === ''
        ) {
            throw new InvalidArgumentException(
                'Room number is required.'
            );
        }

        if (
            strlen($roomNumber) > 50
        ) {
            throw new InvalidArgumentException(
                'Room number cannot exceed 50 characters.'
            );
        }

        if (
            $this->roomModel->roomNumberExists(
                $roomNumber
            )
        ) {
            throw new RuntimeException(
                'A room with this room number already exists.'
            );
        }

        $type =
            $this->normalizeRoomType(
                (string) (
                    $data['type']
                    ?? ''
                )
            );

        if (
            !array_key_exists(
                'price',
                $data
            )
        ) {
            throw new InvalidArgumentException(
                'Room price is required.'
            );
        }

        $price =
            $this->validatePrice(
                $data['price']
            );

        $floor =
            $this->validateFloor(
                $data['floor']
                ?? null
            );

        $capacity =
            $this->validateCapacity(
                $data['capacity']
                ?? 1
            );

        $status =
            $this->normalizeRoomStatus(
                (string) (
                    $data['status']
                    ?? 'available'
                )
            );

        $description =
            $this->normalizeDescription(
                $data['description']
                ?? null
            );

        $amenities =
            $this->normalizeAmenities(
                $data['amenities']
                ?? null
            );

        $image =
            $this->normalizeImage(
                $data['image']
                ?? null
            );

        return [
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
                $description,

            'amenities' =>
                $amenities,

            'image' =>
                $image,
        ];
    }

    // =====================================================
    // Update Validation
    // =====================================================

    /**
     * Validate partial room updates.
     */
    private function validateUpdateData(
        array $data,
        array $existing
    ): array {

        if (
            empty($data)
        ) {
            throw new InvalidArgumentException(
                'No update data was provided.'
            );
        }

        $validated = [];

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
                throw new InvalidArgumentException(
                    'Room number cannot be empty.'
                );
            }

            if (
                strlen($roomNumber) > 50
            ) {
                throw new InvalidArgumentException(
                    'Room number cannot exceed 50 characters.'
                );
            }

            if (
                $this->roomModel->roomNumberExists(
                    $roomNumber,
                    (int) $existing['id']
                )
            ) {
                throw new RuntimeException(
                    'A room with this room number already exists.'
                );
            }

            $validated['room_number'] =
                $roomNumber;
        }

        // -------------------------------------------------
        // Type
        // -------------------------------------------------

        if (
            array_key_exists(
                'type',
                $data
            )
        ) {

            $validated['type'] =
                $this->normalizeRoomType(
                    (string) $data['type']
                );
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

            $validated['price'] =
                $this->validatePrice(
                    $data['price']
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

            $validated['floor'] =
                $this->validateFloor(
                    $data['floor']
                );
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

            $validated['capacity'] =
                $this->validateCapacity(
                    $data['capacity']
                );
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

            $validated['status'] =
                $this->normalizeRoomStatus(
                    (string) $data['status']
                );
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

            $validated['description'] =
                $this->normalizeDescription(
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

            $validated['amenities'] =
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

            $validated['image'] =
                $this->normalizeImage(
                    $data['image']
                );
        }


        return $validated;
    }

    // =====================================================
    // Filters
    // =====================================================

    /**
     * Validate room-list filters.
     */
    private function validateFilters(
        array $filters
    ): array {

        $validated = [];

        if (
            isset($filters['search'])
        ) {

            $search =
                trim(
                    (string) $filters['search']
                );

            if (
                strlen($search) > 100
            ) {
                throw new InvalidArgumentException(
                    'Search text cannot exceed 100 characters.'
                );
            }

            if (
                $search !== ''
            ) {
                $validated['search'] =
                    $search;
            }
        }

        if (
            isset($filters['type'])
        ) {

            $validated['type'] =
                $this->normalizeRoomType(
                    (string) $filters['type']
                );
        }

        if (
            isset($filters['status'])
        ) {

            $validated['status'] =
                $this->normalizeRoomStatus(
                    (string) $filters['status']
                );
        }

        if (
            array_key_exists(
                'floor',
                $filters
            )
        ) {

            $validated['floor'] =
                $this->validateFloor(
                    $filters['floor']
                );
        }

        if (
            array_key_exists(
                'min_price',
                $filters
            )
        ) {

            $validated['min_price'] =
                $this->validatePrice(
                    $filters['min_price']
                );
        }

        if (
            array_key_exists(
                'max_price',
                $filters
            )
        ) {

            $validated['max_price'] =
                $this->validatePrice(
                    $filters['max_price']
                );
        }

        if (
            isset(
                $validated['min_price'],
                $validated['max_price']
            ) &&
            $validated['min_price'] >
            $validated['max_price']
        ) {
            throw new InvalidArgumentException(
                'Minimum price cannot exceed maximum price.'
            );
        }

        return $validated;
    }

    // =====================================================
    // Price Validation
    // =====================================================

    /**
     * Validate room price.
     */
    private function validatePrice(
        mixed $price
    ): float {

        if (
            $price === null ||
            $price === '' ||
            !is_numeric($price)
        ) {
            throw new InvalidArgumentException(
                'Room price must be a valid number.'
            );
        }

        $price =
            (float) $price;

        if (
            !is_finite($price) ||
            $price < 0
        ) {
            throw new InvalidArgumentException(
                'Room price cannot be negative.'
            );
        }

        /*
         * Adjust this limit if your hotel supports very
         * expensive room rates.
         */
        if (
            $price > 1000000
        ) {
            throw new InvalidArgumentException(
                'Room price is outside the allowed range.'
            );
        }

        return round(
            $price,
            2
        );
    }

    // =====================================================
    // Floor Validation
    // =====================================================

    /**
     * Validate room floor.
     */
    private function validateFloor(
        mixed $floor
    ): ?int {

        if (
            $floor === null ||
            $floor === ''
        ) {
            return null;
        }

        if (
            !is_numeric($floor)
        ) {
            throw new InvalidArgumentException(
                'Floor must be a valid number.'
            );
        }

        $floor =
            (int) $floor;

        if (
            $floor < 0
        ) {
            throw new InvalidArgumentException(
                'Floor cannot be negative.'
            );
        }

        if (
            $floor > 200
        ) {
            throw new InvalidArgumentException(
                'Floor is outside the allowed range.'
            );
        }

        return $floor;
    }

    // =====================================================
    // Capacity Validation
    // =====================================================

    /**
     * Validate room capacity.
     */
    private function validateCapacity(
        mixed $capacity
    ): int {

        if (
            !is_numeric($capacity)
        ) {
            throw new InvalidArgumentException(
                'Room capacity must be a valid number.'
            );
        }

        $capacity =
            (int) $capacity;

        if (
            $capacity < 1
        ) {
            throw new InvalidArgumentException(
                'Room capacity must be at least 1.'
            );
        }

        if (
            $capacity > 100
        ) {
            throw new InvalidArgumentException(
                'Room capacity is outside the allowed range.'
            );
        }

        return $capacity;
    }

    // =====================================================
    // Description Validation
    // =====================================================

    /**
     * Validate room description.
     */
    private function normalizeDescription(
        mixed $description
    ): ?string {

        if (
            $description === null
        ) {
            return null;
        }

        $description =
            trim(
                (string) $description
            );

        if (
            $description === ''
        ) {
            return null;
        }

        if (
            mb_strlen($description) > 5000
        ) {
            throw new InvalidArgumentException(
                'Room description cannot exceed 5000 characters.'
            );
        }

        return $description;
    }

    // =====================================================
    // Amenities
    // =====================================================

    /**
     * Normalize room amenities.
     *
     * The returned value is JSON because Room.php stores
     * amenities in the database as JSON text.
     */
    private function normalizeAmenities(
        mixed $amenities
    ): ?string {

        if (
            $amenities === null ||
            $amenities === ''
        ) {
            return null;
        }

        if (
            is_string($amenities)
        ) {

            $decoded =
                json_decode(
                    $amenities,
                    true
                );

            if (
                json_last_error() === JSON_ERROR_NONE &&
                is_array($decoded)
            ) {
                $amenities =
                    $decoded;
            } else {
                $amenities =
                    explode(
                        ',',
                        $amenities
                    );
            }
        }

        if (
            !is_array($amenities)
        ) {
            throw new InvalidArgumentException(
                'Amenities must be an array or comma-separated list.'
            );
        }

        $clean = [];

        foreach (
            $amenities as $amenity
        ) {

            if (
                !is_string($amenity) &&
                !is_numeric($amenity)
            ) {
                continue;
            }

            $amenity =
                trim(
                    (string) $amenity
                );

            if (
                $amenity === ''
            ) {
                continue;
            }

            if (
                mb_strlen($amenity) > 100
            ) {
                throw new InvalidArgumentException(
                    'Each amenity cannot exceed 100 characters.'
                );
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
            count($clean) > 50
        ) {
            throw new InvalidArgumentException(
                'A room cannot have more than 50 amenities.'
            );
        }

        if (
            empty($clean)
        ) {
            return null;
        }

        $json =
            json_encode(
                $clean,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

        if (
            $json === false
        ) {
            throw new RuntimeException(
                'Unable to encode room amenities.'
            );
        }

        return $json;
    }

    // =====================================================
    // Image Validation
    // =====================================================

    /**
     * Validate room image reference.
     *
     * This accepts a relative path or URL. Actual file
     * upload handling should live in a dedicated upload service.
     */
    private function normalizeImage(
        mixed $image
    ): ?string {

        if (
            $image === null
        ) {
            return null;
        }

        $image =
            trim(
                (string) $image
            );

        if (
            $image === ''
        ) {
            return null;
        }

        if (
            mb_strlen($image) > 1000
        ) {
            throw new InvalidArgumentException(
                'Room image path cannot exceed 1000 characters.'
            );
        }

        /*
         * Reject javascript/data URI schemes.
         */
        if (
            preg_match(
                '#^(javascript|data|vbscript):#i',
                $image
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'Invalid room image reference.'
            );
        }

        return $image;
    }

    // =====================================================
    // Date Validation
    // =====================================================

    /**
     * Normalize YYYY-MM-DD date.
     */
    private function normalizeDate(
        ?string $date,
        string $fieldName
    ): ?string {

        if (
            $date === null ||
            trim($date) === ''
        ) {
            return null;
        }

        $date =
            trim($date);

        $parsed =
            DateTime::createFromFormat(
                'Y-m-d',
                $date
            );

        if (
            $parsed === false ||
            $parsed->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                ucfirst($fieldName) .
                ' must use YYYY-MM-DD format.'
            );
        }

        return $date;
    }

    /**
     * Validate stay length.
     */
    private function validateStayLength(
        string $checkIn,
        string $checkOut
    ): void {

        try {

            $start =
                new DateTimeImmutable(
                    $checkIn
                );

            $end =
                new DateTimeImmutable(
                    $checkOut
                );

            $days =
                (int) $start->diff($end)->days;

            /*
             * Prevent accidentally requesting an enormous
             * booking window.
             */
            if (
                $days > 365
            ) {
                throw new InvalidArgumentException(
                    'Booking period cannot exceed 365 nights.'
                );
            }

        } catch (
            InvalidArgumentException $exception
        ) {
            throw $exception;

        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Invalid booking date range.'
            );
        }
    }

    // =====================================================
    // Pagination
    // =====================================================

    private function normalizePage(
        int $page
    ): int {

        if (
            $page < 1
        ) {
            return 1;
        }

        return min(
            $page,
            1000000
        );
    }

    private function normalizePerPage(
        int $perPage
    ): int {

        if (
            $perPage < 1
        ) {
            return 20;
        }

        return min(
            $perPage,
            100
        );
    }

    // =====================================================
    // Room ID
    // =====================================================

    private function validateRoomId(
        int $roomId
    ): void {

        if (
            $roomId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid room ID.'
            );
        }
    }

    // =====================================================
    // Public Validation Helpers
    // =====================================================

    /**
     * Return supported room types.
     */
    public function getRoomTypes(): array
    {
        return self::ROOM_TYPES;
    }

    /**
     * Return supported room statuses.
     */
    public function getRoomStatuses(): array
    {
        return self::ROOM_STATUSES;
    }

    /**
     * Determine whether a room is currently bookable.
     */
    public function isBookable(
        array $room
    ): bool {

        $status =
            strtolower(
                trim(
                    (string) (
                        $room['status']
                        ?? ''
                    )
                )
            );

        return $status === 'available';
    }
}
