<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Room Model
 *
 * File:
 * backend/models/Room.php
 *
 * Responsibilities:
 * - Retrieve rooms
 * - Search/filter rooms
 * - Find individual rooms
 * - Find available rooms
 * - Create rooms
 * - Update rooms
 * - Update room status
 * - Delete rooms
 * - Generate room statistics
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';

class Room
{
    private PDO $db;

    private string $table = 'rooms';

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->getDatabaseConnection();
    }

    // =====================================================
    // Database Connection
    // =====================================================

    private function getDatabaseConnection(): PDO
    {
        /*
         * Supports common database.php implementations:
         *
         * 1. getDatabase()
         * 2. Database::getConnection()
         * 3. $pdo
         */

        if (function_exists('getDatabase')) {
            $connection = getDatabase();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        if (class_exists('Database') && method_exists('Database', 'getConnection')) {
            $connection = Database::getConnection();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }

        throw new RuntimeException(
            'Database connection could not be initialized.'
        );
    }

    // =====================================================
    // GET ALL ROOMS
    // =====================================================

    /**
     * Retrieve rooms with filtering and pagination.
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAll(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {
        $page = max(1, $page);
        $perPage = min(max(1, $perPage), 100);

        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        // -------------------------------------------------
        // Search
        // -------------------------------------------------

        if (
            isset($filters['search']) &&
            trim((string) $filters['search']) !== ''
        ) {
            $where[] = '(
                room_number LIKE :search
                OR type LIKE :search_type
                OR description LIKE :search_description
            )';

            $search = '%' . trim((string) $filters['search']) . '%';

            $params[':search'] = $search;
            $params[':search_type'] = $search;
            $params[':search_description'] = $search;
        }

        // -------------------------------------------------
        // Room Type
        // -------------------------------------------------

        if (
            isset($filters['type']) &&
            trim((string) $filters['type']) !== ''
        ) {
            $where[] = 'type = :type';
            $params[':type'] = strtolower(
                trim((string) $filters['type'])
            );
        }

        // -------------------------------------------------
        // Status
        // -------------------------------------------------

        if (
            isset($filters['status']) &&
            trim((string) $filters['status']) !== ''
        ) {
            $where[] = 'status = :status';
            $params[':status'] = strtolower(
                trim((string) $filters['status'])
            );
        }

        // -------------------------------------------------
        // Floor
        // -------------------------------------------------

        if (
            array_key_exists('floor', $filters) &&
            $filters['floor'] !== null
        ) {
            $where[] = 'floor = :floor';
            $params[':floor'] = (int) $filters['floor'];
        }

        // -------------------------------------------------
        // Minimum Price
        // -------------------------------------------------

        if (
            array_key_exists('min_price', $filters) &&
            $filters['min_price'] !== null
        ) {
            $where[] = 'price >= :min_price';
            $params[':min_price'] = (float) $filters['min_price'];
        }

        // -------------------------------------------------
        // Maximum Price
        // -------------------------------------------------

        if (
            array_key_exists('max_price', $filters) &&
            $filters['max_price'] !== null
        ) {
            $where[] = 'price <= :max_price';
            $params[':max_price'] = (float) $filters['max_price'];
        }

        $whereSql = '';

        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        // -------------------------------------------------
        // Total Count
        // -------------------------------------------------

        $countSql = "
            SELECT COUNT(*)
            FROM {$this->table}
            {$whereSql}
        ";

        $countStatement = $this->db->prepare($countSql);

        foreach ($params as $key => $value) {
            $countStatement->bindValue(
                $key,
                $value
            );
        }

        $countStatement->execute();

        $total = (int) $countStatement->fetchColumn();

        // -------------------------------------------------
        // Room Query
        // -------------------------------------------------

        $sql = "
            SELECT
                *
            FROM {$this->table}
            {$whereSql}
            ORDER BY
                floor ASC,
                room_number ASC
            LIMIT :limit
            OFFSET :offset
        ";

        $statement = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue(
                $key,
                $value
            );
        }

        $statement->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        $rooms = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );


        $totalPages = $total > 0
            ? (int) ceil($total / $perPage)
            : 0;

        return [
            'data' => array_map(
                [$this, 'formatRoom'],
                $rooms
            ),

            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next_page' =>
                    $page < $totalPages,
                'has_previous_page' =>
                    $page > 1 && $total > 0,
            ],
        ];
    }

    // =====================================================
    // FIND ROOM BY ID
    // =====================================================

    /**
     * Find a room by primary key.
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $statement->execute();

        $room = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        if ($room === false) {
            return null;
        }

        return $this->formatRoom($room);
    }

    // =====================================================
    // FIND ROOM BY ROOM NUMBER
    // =====================================================

    /**
     * Check whether a room number already exists.
     *
     * @param string $roomNumber
     * @param int|null $excludeId
     */
    public function roomNumberExists(
        string $roomNumber,
        ?int $excludeId = null
    ): bool {
        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE room_number = :room_number
        ";

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $sql .= " LIMIT 1";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':room_number',
            trim($roomNumber),
            PDO::PARAM_STR
        );

        if ($excludeId !== null) {
            $statement->bindValue(
                ':exclude_id',
                $excludeId,
                PDO::PARAM_INT
            );
        }

        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    // =====================================================
    // CREATE ROOM
    // =====================================================

    /**
     * Create a new room.
     *
     * @return int Newly created room ID.
     */
    public function create(array $data): int
    {
        $roomNumber = trim(
            (string) ($data['room_number'] ?? '')
        );

        $type = strtolower(
            trim(
                (string) ($data['type'] ?? '')
            )
        );

        $price = $data['price'] ?? null;

        if (
            $roomNumber === '' ||
            $type === '' ||
            $price === null
        ) {
            throw new InvalidArgumentException(
                'Room number, type and price are required.'
            );
        }


        if ($this->roomNumberExists($roomNumber)) {
            throw new RuntimeException(
                'A room with this room number already exists.'
            );
        }

        $sql = "
            INSERT INTO {$this->table}
            (
                room_number,
                type,
                price,
                floor,
                capacity,
                status,
                description,
                amenities,
                image
            )
            VALUES
            (
                :room_number,
                :type,
                :price,
                :floor,
                :capacity,
                :status,
                :description,
                :amenities,
                :image
            )
        ";

        $statement = $this->db->prepare($sql);


        $statement->bindValue(
            ':room_number',
            $roomNumber,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':type',
            $type,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':price',
            (float) $price
        );

        $floor = $data['floor'] ?? null;

        if ($floor === null) {
            $statement->bindValue(
                ':floor',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $statement->bindValue(
                ':floor',
                (int) $floor,
                PDO::PARAM_INT
            );
        }

        $capacity = max(
            1,
            (int) ($data['capacity'] ?? 1)
        );

        $statement->bindValue(
            ':capacity',
            $capacity,
            PDO::PARAM_INT
        );

        $status = strtolower(
            trim(
                (string) (
                    $data['status']
                    ?? 'available'
                )
            )
        );

        $statement->bindValue(
            ':status',
            $status,
            PDO::PARAM_STR
        );

        $this->bindNullableString(
            $statement,
            ':description',
            $data['description'] ?? null
        );

        $this->bindNullableString(
            $statement,
            ':amenities',
            $data['amenities'] ?? null
        );

        $this->bindNullableString(
            $statement,
            ':image',
            $data['image'] ?? null
        );

        try {
            $statement->execute();
        } catch (PDOException $exception) {

            /*
             * MySQL duplicate-key protection in case another
             * request created the same room simultaneously.
             */
            if ((int) $exception->errorInfo[1] === 1062) {
                throw new RuntimeException(
                    'A room with this room number already exists.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        return (int) $this->db->lastInsertId();
    }

    // =====================================================
    // UPDATE ROOM
    // =====================================================

    /**
     * Update a room.
     */
    public function update(
        int $id,
        array $data
    ): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Invalid room ID.'
            );
        }

        if (empty($data)) {
            throw new InvalidArgumentException(
                'No room data was provided.'
            );
        }

        /*
         * Only these columns can be updated through this model.
         * This prevents accidental SQL column injection.
         */
        $allowedColumns = [
            'room_number',
            'type',
            'price',
            'floor',
            'capacity',
            'status',
            'description',
            'amenities',
            'image',
        ];

        $fields = [];
        $values = [];

        foreach ($allowedColumns as $column) {

            if (!array_key_exists($column, $data)) {
                continue;
            }

            $fields[] =
                "{$column} = :{$column}";

            $values[$column] =
                $data[$column];
        }

        if (empty($fields)) {
            throw new InvalidArgumentException(
                'No valid room fields were provided.'
            );
        }

        if (isset($data['room_number'])) {

            $roomNumber = trim(
                (string) $data['room_number']
            );

            if (
                $this->roomNumberExists(
                    $roomNumber,
                    $id
                )
            ) {
                throw new RuntimeException(
                    'A room with this room number already exists.'
                );
            }

            $values['room_number'] =
                $roomNumber;
        }

        $sql = "
            UPDATE {$this->table}
            SET
                " . implode(', ', $fields) . "
            WHERE id = :id
        ";

        $statement = $this->db->prepare($sql);

        foreach ($values as $column => $value) {

            $parameter = ':' . $column;

            if ($value === null) {

                $statement->bindValue(
                    $parameter,
                    null,
                    PDO::PARAM_NULL
                );

            } elseif (
                in_array(
                    $column,
                    ['floor', 'capacity'],
                    true
                )
            ) {

                $statement->bindValue(
                    $parameter,
                    (int) $value,
                    PDO::PARAM_INT
                );

            } elseif ($column === 'price') {

                $statement->bindValue(
                    $parameter,
                    (float) $value
                );

            } else {

                $statement->bindValue(
                    $parameter,
                    (string) $value,
                    PDO::PARAM_STR
                );
            }
        }

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );


        return $statement->execute();
    }

    // =====================================================
    // UPDATE ROOM STATUS
    // =====================================================

    /**
     * Update only room status.
     */
    public function updateStatus(
        int $id,
        string $status
    ): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Invalid room ID.'
            );
        }

        $status = strtolower(
            trim($status)
        );

        if ($status === '') {
            throw new InvalidArgumentException(
                'Room status is required.'
            );
        }

        $sql = "
            UPDATE {$this->table}
            SET status = :status
            WHERE id = :id
        ";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':status',
            $status,
            PDO::PARAM_STR
        );

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        return $statement->execute();
    }

    // =====================================================
    // GET AVAILABLE ROOMS
    // =====================================================

    /**
     * Retrieve rooms available for booking.
     *
     * When dates are supplied, existing bookings are checked
     * for date overlap.
     */
    public function getAvailable(
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?string $type = null
    ): array {

        $params = [];

        /*
         * Base availability:
         *
         * available = room is operationally available.
         *
         * If dates are provided, also exclude rooms having
         * overlapping active bookings.
         */
        $sql = "
            SELECT
                r.*
            FROM {$this->table} r
            WHERE r.status = 'available'
        ";

        if (
            $type !== null &&
            trim($type) !== ''
        ) {
            $sql .= "
                AND r.type = :type
            ";

            $params[':type'] =
                strtolower(
                    trim($type)
                );
        }

        if (
            $checkIn !== null &&
            $checkOut !== null
        ) {

            /*
             * Booking overlap rule:
             *
             * existing.check_in < requested.check_out
             * AND
             * existing.check_out > requested.check_in
             *
             * This allows a room to be checked out on the same
             * date another guest checks in.
             */
            $sql .= "
                AND NOT EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.room_id = r.id

                    AND b.check_in < :check_out

                    AND b.check_out > :check_in

                    AND b.status NOT IN (
                        'cancelled',
                        'completed',
                        'no_show'
                    )
                )
            ";

            $params[':check_in'] =
                $checkIn;

            $params[':check_out'] =
                $checkOut;
        }


        $sql .= "
            ORDER BY
                r.floor ASC,
                r.room_number ASC
        ";

        $statement = $this->db->prepare($sql);


        foreach ($params as $key => $value) {
            $statement->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $statement->execute();

        $rooms = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        return array_map(
            [$this, 'formatRoom'],
            $rooms
        );
    }

    // =====================================================
    // DELETE ROOM
    // =====================================================

    /**
     * Delete a room.
     *
     * Prevents deletion when active/future bookings exist.
     */
    public function delete(
        int $id
    ): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Invalid room ID.'
            );
        }

        // -------------------------------------------------
        // Check active bookings
        // -------------------------------------------------

        if ($this->hasActiveBookings($id)) {
            throw new RuntimeException(
                'Room cannot be deleted because it has active or future bookings.'
            );
        }

        $sql = "
            DELETE FROM {$this->table}
            WHERE id = :id
        ";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }

    // =====================================================
    // ACTIVE BOOKINGS
    // =====================================================

    /**
     * Determine whether a room has active/future bookings.
     */
    private function hasActiveBookings(
        int $roomId
    ): bool {

        $sql = "
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = :room_id
            AND status NOT IN (
                'cancelled',
                'completed',
                'no_show'
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->bindValue(
            ':room_id',
            $roomId,
            PDO::PARAM_INT
        );

        $statement->execute();

        return (int) $statement->fetchColumn() > 0;
    }

    // =====================================================
    // ROOM STATISTICS
    // =====================================================

    /**
     * Retrieve room statistics.
     */
    public function getStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_rooms,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'available'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS available_rooms,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'occupied'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS occupied_rooms,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'reserved'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS reserved_rooms,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'maintenance'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS maintenance_rooms,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'cleaning'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS cleaning_rooms,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'out_of_order'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS out_of_order_rooms,

                COALESCE(
                    AVG(price),
                    0
                ) AS average_room_price

            FROM {$this->table}
        ";

        $statement = $this->db->query(
            $sql
        );

        $statistics =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        if ($statistics === false) {
            return [
                'total_rooms' => 0,
                'available_rooms' => 0,
                'occupied_rooms' => 0,
                'reserved_rooms' => 0,
                'maintenance_rooms' => 0,
                'cleaning_rooms' => 0,
                'out_of_order_rooms' => 0,
                'average_room_price' => 0,
            ];
        }

        return [
            'total_rooms' =>
                (int) $statistics['total_rooms'],

            'available_rooms' =>
                (int) $statistics['available_rooms'],

            'occupied_rooms' =>
                (int) $statistics['occupied_rooms'],

            'reserved_rooms' =>
                (int) $statistics['reserved_rooms'],

            'maintenance_rooms' =>
                (int) $statistics['maintenance_rooms'],

            'cleaning_rooms' =>
                (int) $statistics['cleaning_rooms'],

            'out_of_order_rooms' =>
                (int) $statistics['out_of_order_rooms'],

            'average_room_price' =>
                round(
                    (float) $statistics['average_room_price'],
                    2
                ),
        ];
    }

    // =====================================================
    // ROOM COUNTS BY TYPE
    // =====================================================

    /**
     * Return room count grouped by type.
     */
    public function getCountsByType(): array
    {
        $sql = "
            SELECT
                type,
                COUNT(*) AS total
            FROM {$this->table}
            GROUP BY type
            ORDER BY total DESC
        ";


        $statement = $this->db->query(
            $sql
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        return array_map(
            static function (array $row): array {
                return [
                    'type' =>
                        $row['type'],

                    'total' =>
                        (int) $row['total'],
                ];
            },
            $rows
        );
    }

    // =====================================================
    // ROOM COUNTS BY STATUS
    // =====================================================

    /**
     * Return room count grouped by status.
     */
    public function getCountsByStatus(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) AS total
            FROM {$this->table}
            GROUP BY status
            ORDER BY total DESC
        ";

        $statement = $this->db->query(
            $sql
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        return array_map(
            static function (array $row): array {
                return [
                    'status' =>
                        $row['status'],

                    'total' =>
                        (int) $row['total'],
                ];
            },
            $rows
        );
    }

    // =====================================================
    // FORMAT ROOM
    // =====================================================

    /**
     * Normalize database room data for API responses.
     */
    private function formatRoom(
        array $room
    ): array {

        if (isset($room['id'])) {
            $room['id'] =
                (int) $room['id'];
        }

        if (isset($room['price'])) {
            $room['price'] =
                (float) $room['price'];
        }

        if (isset($room['floor'])) {
            $room['floor'] =
                $room['floor'] === null
                    ? null
                    : (int) $room['floor'];
        }

        if (isset($room['capacity'])) {
            $room['capacity'] =
                (int) $room['capacity'];
        }

        /*
         * Amenities are stored as JSON in the database.
         * Decode them for API consumers.
         */
        if (
            isset($room['amenities']) &&
            is_string($room['amenities'])
        ) {

            $decoded =
                json_decode(
                    $room['amenities'],
                    true
                );

            if (
                json_last_error() === JSON_ERROR_NONE &&
                is_array($decoded)
            ) {
                $room['amenities'] =
                    $decoded;
            }
        }

        return $room;
    }

    // =====================================================
    // PDO Helpers
    // =====================================================

    /**
     * Bind nullable string.
     */
    private function bindNullableString(
        PDOStatement $statement,
        string $parameter,
        mixed $value
    ): void {

        if ($value === null) {
            $statement->bindValue(
                $parameter,
                null,
                PDO::PARAM_NULL
            );

            return;
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value);

            if ($encoded === false) {
                throw new InvalidArgumentException(
                    'Invalid JSON value provided for room field.'
                );
            }

            $statement->bindValue(
                $parameter,
                $encoded,
                PDO::PARAM_STR
            );

            return;
        }

        if (trim((string) $value) === '') {
            $statement->bindValue(
                $parameter,
                null,
                PDO::PARAM_NULL
            );

            return;
        }

        $statement->bindValue(
            $parameter,
            (string) $value,
            PDO::PARAM_STR
        );
    }
}
