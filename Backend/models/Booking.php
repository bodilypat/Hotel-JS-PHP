<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/models/Booking.php
 *
 * Database model for the bookings table.
 */

require_once __DIR__ . '/../config/database.php';

class Booking
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->getDatabaseConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | FIND ALL BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function all(
        array $filters = [],
        int $page = 1,
        int $limit = 20
    ): array {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        $this->buildFilters(
            $filters,
            $where,
            $params
        );

        $whereSql = '';

        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(
                ' AND ',
                $where
            );
        }

        /*
         * Total count.
         */
        $countSql = "
            SELECT COUNT(*)
            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            {$whereSql}
        ";

        $countStatement = $this->db->prepare(
            $countSql
        );

        $countStatement->execute(
            $params
        );

        $total = (int) $countStatement->fetchColumn();

        /*
         * Booking records.
         */
        $sql = "
            SELECT
                b.*,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                g.email AS guest_email,
                g.phone AS guest_phone,

                r.room_number,
                r.room_type,
                r.name AS room_name

            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            {$whereSql}

            ORDER BY b.created_at DESC

            LIMIT :limit
            OFFSET :offset
        ";

        $statement = $this->db->prepare(
            $sql
        );

        foreach ($params as $key => $value) {
            $statement->bindValue(
                $key,
                $value
            );
        }

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        return [
            'items' => $statement->fetchAll(
                PDO::FETCH_ASSOC
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $total > 0
                    ? (int) ceil($total / $limit)
                    : 0,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BOOKING BY ID
    |--------------------------------------------------------------------------
    */

    public function find(int $id): ?array
    {
        $sql = "
            SELECT
                b.*,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                g.first_name AS guest_first_name,
                g.last_name AS guest_last_name,
                g.email AS guest_email,
                g.phone AS guest_phone,
                g.nationality AS guest_nationality,

                r.room_number,
                r.room_type,
                r.name AS room_name,
                r.price_per_night AS current_room_rate

            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            WHERE b.id = :id

            LIMIT 1
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute([
            ':id' => $id,
        ]);

        $booking = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $booking ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY BOOKING REFERENCE
    |--------------------------------------------------------------------------
    */

    public function findByReference(
        string $reference
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM bookings
            WHERE booking_reference = :reference
            LIMIT 1
        ");

        $statement->execute([
            ':reference' => $reference,
        ]);

        $booking = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $booking ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY GUEST
    |--------------------------------------------------------------------------
    */

    public function findByGuest(
        int $guestId,
        array $filters = []
    ): array {
        $filters['guest_id'] = $guestId;

        return $this->all(
            $filters,
            (int) ($filters['page'] ?? 1),
            (int) ($filters['limit'] ?? 20)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY ROOM
    |--------------------------------------------------------------------------
    */

    public function findByRoom(
        int $roomId,
        ?string $from = null,
        ?string $to = null
    ): array {
        $where = [
            'room_id = :room_id',
        ];

        $params = [
            ':room_id' => $roomId,
        ];

        if ($from !== null) {
            $where[] = 'check_out >= :from_date';
            $params[':from_date'] = $from;
        }

        if ($to !== null) {
            $where[] = 'check_in <= :to_date';
            $params[':to_date'] = $to;
        }

        $sql = "
            SELECT *
            FROM bookings
            WHERE " . implode(
                ' AND ',
                $where
            ) . "
            ORDER BY check_in ASC
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): int {
        $fields = [
            'booking_reference',
            'guest_id',
            'room_id',
            'check_in',
            'check_out',
            'number_of_guests',
            'adults',
            'children',
            'number_of_nights',
            'room_rate',
            'subtotal',
            'discount',
            'tax',
            'total_amount',
            'currency',
            'status',
            'payment_status',
            'source',
            'special_requests',
            'notes',
        ];

        $insertFields = [];
        $placeholders = [];
        $params = [];

        foreach ($fields as $field) {
            if (array_key_exists(
                $field,
                $data
            )) {
                $insertFields[] = "`{$field}`";
                $placeholders[] = ":{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($insertFields)) {
            throw new InvalidArgumentException(
                'No booking data was provided.'
            );
        }

        $sql = "
            INSERT INTO bookings (
                " . implode(
                    ', ',
                    $insertFields
                ) . "
            )
            VALUES (
                " . implode(
                    ', ',
                    $placeholders
                ) . "
            )
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        return (int) $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): bool {
        $allowedFields = [
            'guest_id',
            'room_id',
            'check_in',
            'check_out',
            'number_of_guests',
            'adults',
            'children',
            'number_of_nights',
            'room_rate',
            'subtotal',
            'discount',
            'tax',
            'total_amount',
            'currency',
            'status',
            'payment_status',
            'source',
            'special_requests',
            'notes',
            'cancellation_reason',
            'cancelled_at',
        ];

        $setParts = [];
        $params = [
            ':id' => $id,
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists(
                $field,
                $data
            )) {
                $setParts[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($setParts)) {
            return false;
        }

        $sql = "
            UPDATE bookings
            SET " . implode(
                ', ',
                $setParts
            ) . "
            WHERE id = :id
        ";

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $id
    ): bool {
        $statement = $this->db->prepare("
            DELETE FROM bookings
            WHERE id = :id
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        int $id,
        string $status
    ): bool {
        $status = trim($status);

        if (!$this->isValidBookingStatus(
            $status
        )) {
            throw new InvalidArgumentException(
                'Invalid booking status.'
            );
        }

        $statement = $this->db->prepare("
            UPDATE bookings
            SET status = :status
            WHERE id = :id
        ");

        $statement->execute([
            ':status' => $status,
            ':id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PAYMENT STATUS
    |--------------------------------------------------------------------------
    */

    public function updatePaymentStatus(
        int $id,
        string $paymentStatus
    ): bool {
        $paymentStatus = trim($paymentStatus);

        if (!$this->isValidPaymentStatus(
            $paymentStatus
        )) {
            throw new InvalidArgumentException(
                'Invalid payment status.'
            );
        }

        $statement = $this->db->prepare("
            UPDATE bookings
            SET payment_status = :payment_status
            WHERE id = :id
        ");

        $statement->execute([
            ':payment_status' => $paymentStatus,
            ':id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    public function cancel(
        int $id,
        ?string $reason = null
    ): bool {
        $statement = $this->db->prepare("
            UPDATE bookings
            SET
                status = 'cancelled',
                cancellation_reason = :reason,
                cancelled_at = NOW()
            WHERE id = :id
        ");

        $statement->execute([
            ':id' => $id,
            ':reason' => $reason,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM
    |--------------------------------------------------------------------------
    */

    public function confirm(
        int $id
    ): bool {
        $statement = $this->db->prepare("
            UPDATE bookings
            SET status = 'confirmed'
            WHERE id = :id
              AND status = 'pending'
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK IN
    |--------------------------------------------------------------------------
    */

    public function checkIn(
        int $id
    ): bool {
        $statement = $this->db->prepare("
            UPDATE bookings
            SET status = 'checked_in'
            WHERE id = :id
              AND status IN (
                  'pending',
                  'confirmed'
              )
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK OUT
    |--------------------------------------------------------------------------
    */

    public function checkOut(
        int $id
    ): bool {
        $statement = $this->db->prepare("
            UPDATE bookings
            SET status = 'checked_out'
            WHERE id = :id
              AND status = 'checked_in'
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK OVERLAPPING BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function hasOverlap(
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $excludeBookingId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)
            FROM bookings

            WHERE room_id = :room_id

              AND status IN (
                  'pending',
                  'confirmed',
                  'checked_in'
              )

              AND check_in < :check_out

              AND check_out > :check_in
        ";

        $params = [
            ':room_id' => $roomId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut,
        ];

        if ($excludeBookingId !== null) {
            $sql .= "
                AND id <> :exclude_id
            ";

            $params[':exclude_id'] = $excludeBookingId;
        }

        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        return (int) $statement->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | ROOM AVAILABILITY
    |--------------------------------------------------------------------------
    */

    public function isRoomAvailable(
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $excludeBookingId = null
    ): bool {
        return !$this->hasOverlap(
            $roomId,
            $checkIn,
            $checkOut,
            $excludeBookingId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function getActive(
        ?string $date = null
    ): array {
        $date = $date ?? date('Y-m-d');

        $statement = $this->db->prepare("
            SELECT
                b.*,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                r.room_number,
                r.room_type

            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            WHERE b.status IN (
                'pending',
                'confirmed',
                'checked_in'
            )

            AND b.check_in <= :date

            AND b.check_out > :date

            ORDER BY b.check_in ASC
        ");

        $statement->execute([
            ':date' => $date,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPCOMING BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function getUpcoming(
        int $limit = 10
    ): array {
        $limit = min(
            100,
            max(1, $limit)
        );

        $statement = $this->db->prepare("
            SELECT
                b.*,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                r.room_number,
                r.room_type

            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            WHERE b.check_in >= CURDATE()

              AND b.status IN (
                  'pending',
                  'confirmed'
              )

            ORDER BY b.check_in ASC

            LIMIT :limit
        ");

        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TODAY'S CHECK-INS
    |--------------------------------------------------------------------------
    */

    public function getTodayCheckIns(): array
    {
        $statement = $this->db->query("
            SELECT
                b.*,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                g.phone AS guest_phone,
                r.room_number,
                r.room_type

            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            WHERE DATE(b.check_in) = CURDATE()

              AND b.status IN (
                  'pending',
                  'confirmed'
              )

            ORDER BY b.check_in ASC
        ");

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TODAY'S CHECK-OUTS
    |--------------------------------------------------------------------------
    */

    public function getTodayCheckOuts(): array
    {
        $statement = $this->db->query("
            SELECT
                b.*,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                g.phone AS guest_phone,
                r.room_number,
                r.room_type

            FROM bookings b

            LEFT JOIN guests g
                ON g.id = b.guest_id

            LEFT JOIN rooms r
                ON r.id = b.room_id

            WHERE DATE(b.check_out) = CURDATE()

              AND b.status IN (
                  'confirmed',
                  'checked_in'
              )

            ORDER BY b.check_out ASC
        ");

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT SUMMARY
    |--------------------------------------------------------------------------
    */

    public function paymentSummary(
        int $bookingId
    ): array {
        $statement = $this->db->prepare("
            SELECT
                COALESCE(
                    SUM(
                        CASE
                            WHEN status IN (
                                'completed',
                                'partially_refunded'
                            )
                            THEN amount - refunded_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS paid_amount,

                COALESCE(
                    SUM(refunded_amount),
                    0
                ) AS refunded_amount,

                COUNT(*) AS payment_count

            FROM payments

            WHERE booking_id = :booking_id
        ");

        $statement->execute([
            ':booking_id' => $bookingId,
        ]);

        $result = $statement->fetch(
            PDO::FETCH_ASSOC
        ) ?: [];

        $booking = $this->find(
            $bookingId
        );

        $total = (float) (
            $booking['total_amount'] ?? 0
        );

        $paid = (float) (
            $result['paid_amount'] ?? 0
        );

        return [
            'payment_count' => (int) (
                $result['payment_count'] ?? 0
            ),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'refunded_amount' => (float) (
                $result['refunded_amount'] ?? 0
            ),
            'remaining_amount' => max(
                0,
                round(
                    $total - $paid,
                    2
                )
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */

    private function isValidBookingStatus(
        string $status
    ): bool {
        $validStatuses = [
            'pending',
            'confirmed',
            'checked_in',
            'checked_out',
            'cancelled',
        ];

        return in_array(
            strtolower(trim($status)),
            $validStatuses,
            true
        );
    }

    private function isValidPaymentStatus(
        string $status
    ): bool {
        $validStatuses = [
            'pending',
            'partial',
            'paid',
            'partially_refunded',
            'refunded',
            'failed',
            'cancelled',
            'completed',
        ];

        return in_array(
            strtolower(trim($status)),
            $validStatuses,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD FILTERS
    |--------------------------------------------------------------------------
    */

    private function buildFilters(
        array $filters,
        array &$where,
        array &$params
    ): void {
        if (
            isset($filters['status'])
            && $filters['status'] !== ''
        ) {
            $where[] = 'b.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (
            isset($filters['payment_status'])
            && $filters['payment_status'] !== ''
        ) {
            $where[] =
                'b.payment_status = :payment_status';

            $params[':payment_status'] =
                $filters['payment_status'];
        }

        if (
            isset($filters['guest_id'])
            && $filters['guest_id'] !== ''
        ) {
            $where[] = 'b.guest_id = :guest_id';
            $params[':guest_id'] =
                (int) $filters['guest_id'];
        }

        if (
            isset($filters['room_id'])
            && $filters['room_id'] !== ''
        ) {
            $where[] = 'b.room_id = :room_id';
            $params[':room_id'] =
                (int) $filters['room_id'];
        }

        if (
            !empty($filters['check_in'])
        ) {
            $where[] =
                'b.check_in >= :check_in';

            $params[':check_in'] =
                $filters['check_in'];
        }

        if (
            !empty($filters['check_out'])
        ) {
            $where[] =
                'b.check_out <= :check_out';

            $params[':check_out'] =
                $filters['check_out'];
        }

        if (
            !empty($filters['search'])
        ) {
            $where[] = "
                (
                    b.booking_reference LIKE :search

                    OR g.first_name LIKE :search

                    OR g.last_name LIKE :search

                    OR g.email LIKE :search

                    OR r.room_number LIKE :search
                )
            ";

            $params[':search'] =
                '%' . $filters['search'] . '%';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    private function getDatabaseConnection(): PDO
    {
        /*
         * Supports:
         *
         * Database::getConnection()
         */
        if (
            class_exists('Database')
            && method_exists(
                'Database',
                'getConnection'
            )
        ) {
            $connection =
                Database::getConnection();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        /*
         * Supports:
         *
         * getDatabaseConnection()
         */
        if (
            function_exists(
                'getDatabaseConnection'
            )
        ) {
            $connection =
                getDatabaseConnection();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        /*
         * Supports a global $pdo.
         */
        global $pdo;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        throw new RuntimeException(
            'Database connection could not be initialized.'
        );
    }
}
