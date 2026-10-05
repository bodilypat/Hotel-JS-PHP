<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Guest Model
 *
 * File:
 * backend/models/Guest.php
 *
 * Responsibilities:
 * - Guest database operations
 * - CRUD operations
 * - Guest lookup
 * - Search and filtering
 * - Pagination
 *
 * Business rules should remain in GuestService/Controller.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';

class Guest
{
    private PDO $db;

    private string $table = 'guests';

    /**
     * Constructor.
     *
     * Supports either:
     *
     * $guest = new Guest();
     *
     * or dependency injection:
     *
     * $guest = new Guest($pdo);
     */
    public function __construct(?PDO $db = null)
    {
        $this->db =
            $db ?? $this->getDatabaseConnection();
    }

    // =====================================================
    // CREATE
    // =====================================================

    /**
     * Create a new guest.
     *
     * Expected fields may include:
     *
     * first_name
     * last_name
     * email
     * phone
     * date_of_birth
     * gender
     * nationality
     * id_type
     * id_number
     * address
     * city
     * state
     * country
     * postal_code
     * emergency_contact_name
     * emergency_contact_phone
     * notes
     */
    public function create(array $data): int
    {
        $fields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'nationality',
            'id_type',
            'id_number',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ];

        $insertFields = [];
        $placeholders = [];
        $values = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $insertFields[] = "`{$field}`";
            $placeholders[] = ":{$field}";
            $values[":{$field}"] =
                $this->normalizeValue(
                    $field,
                    $data[$field]
                );
        }

        if (empty($insertFields)) {
            throw new InvalidArgumentException(
                'No guest data was provided.'
            );
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            throw new InvalidArgumentException(
                'First name and last name are required.'
            );
        }

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->table,
            implode(', ', $insertFields),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return (int) $this->db->lastInsertId();
    }

    // =====================================================
    // FIND
    // =====================================================

    /**
     * Find guest by primary key.
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "
            SELECT *
            FROM `{$this->table}`
            WHERE `id` = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
        ]);

        $guest =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $guest ?: null;
    }

    /**
     * Alias used by services/controllers.
     */
    public function find(int $id): ?array
    {
        return $this->findById($id);
    }


    /**
     * Find guest by email.
     */
    public function findByEmail(
        string $email
    ): ?array {

        $email =
            strtolower(
                trim($email)
            );

        if ($email === '') {
            return null;
        }

        $sql = "
            SELECT *
            FROM `{$this->table}`
            WHERE LOWER(`email`) = :email
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':email' => $email,
        ]);

        $guest =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $guest ?: null;
    }

    /**
     * Find guest by phone.
     */
    public function findByPhone(
        string $phone
    ): ?array {

        $phone =
            preg_replace(
                '/\D+/',
                '',
                trim($phone)
            ) ?? trim($phone);

        if ($phone === '') {
            return null;
        }

        $sql = "
            SELECT *
            FROM `{$this->table}`
            WHERE `phone` = :phone
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':phone' => $phone,
        ]);

        $guest =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $guest ?: null;
    }

    /**
     * Find by government ID.
     */
    public function findByIdNumber(
        string $idNumber
    ): ?array {

        $idNumber =
            strtoupper(
                trim($idNumber)
            );

        if ($idNumber === '') {
            return null;
        }

        $sql = "
            SELECT *
            FROM `{$this->table}`
            WHERE `id_number` = :id_number
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id_number' => $idNumber,
        ]);

        $guest =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $guest ?: null;
    }

    // =====================================================
    // GET ALL
    // =====================================================

    /**
     * Get guests with filters and pagination.
     *
     * Supported filters:
     *
     * search
     * email
     * phone
     * city
     * state
     * country
     * nationality
     * gender
     * id_type
     */
    public function getAll(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        $page =
            max(1, $page);

        $perPage =
            max(
                1,
                min(100, $perPage)
            );

        $where = [];
        $params = [];

        $this->applyFilters(
            $filters,
            $where,
            $params
        );

        $whereSql =
            empty($where)
                ? ''
                : 'WHERE ' .
                    implode(
                        ' AND ',
                        $where
                    );


        // -------------------------------------------------
        // Count
        // -------------------------------------------------

        $countSql = "
            SELECT COUNT(*)
            FROM `{$this->table}`
            {$whereSql}
        ";

        $countStmt =
            $this->db->prepare(
                $countSql
            );

        $countStmt->execute(
            $params
        );

        $total =
            (int) $countStmt->fetchColumn();


        // -------------------------------------------------
        // Data
        // -------------------------------------------------

        $offset =
            ($page - 1) * $perPage;

        $sql = "
            SELECT *
            FROM `{$this->table}`
            {$whereSql}
            ORDER BY `created_at` DESC, `id` DESC
            LIMIT :limit
            OFFSET :offset
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );


        foreach (
            $params as $key => $value
        ) {

            $stmt->bindValue(
                $key,
                $value
            );
        }

        $stmt->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();


        $items =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        return [
            'items' =>
                $items,

            'pagination' => [
                'page' =>
                    $page,

                'per_page' =>
                    $perPage,

                'total' =>
                    $total,

                'total_pages' =>
                    $perPage > 0
                        ? (int) ceil(
                            $total / $perPage
                        )
                        : 0,
            ],
        ];
    }

    /**
     * Search guests.
     */
    public function search(
        string $query,
        int $page = 1,
        int $perPage = 20
    ): array {

        return $this->getAll(
            [
                'search' => $query,
            ],
            $page,
            $perPage
        );
    }

    // =====================================================
    // UPDATE
    // =====================================================

    /**
     * Update guest.
     */
    public function update(
        int $id,
        array $data
    ): bool {

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Invalid guest ID.'
            );
        }

        $allowedFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'nationality',
            'id_type',
            'id_number',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ];

        $updates = [];
        $params = [
            ':id' => $id,
        ];

        foreach (
            $allowedFields as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $data
                )
            ) {
                continue;
            }

            $updates[] =
                "`{$field}` = :{$field}";

            $params[
                ":{$field}"
            ] =
                $this->normalizeValue(
                    $field,
                    $data[$field]
                );
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "
            UPDATE `{$this->table}`
            SET
                " .
                implode(
                    ",\n                ",
                    $updates
                ) .
            "
            WHERE `id` = :id
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        return $stmt->execute(
            $params
        );
    }

    // =====================================================
    // DELETE
    // =====================================================

    /**
     * Delete guest.
     *
     * For a production hotel system, soft deletion is
     * generally preferable when bookings reference guests.
     */
    public function delete(
        int $id
    ): bool {

        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Invalid guest ID.'
            );
        }

        $sql = "
            DELETE FROM `{$this->table}`
            WHERE `id` = :id
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    /**
     * Check whether a guest exists.
     */
    public function exists(
        int $id
    ): bool {

        if ($id <= 0) {
            return false;
        }

        $sql = "
            SELECT 1
            FROM `{$this->table}`
            WHERE `id` = :id
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':id' => $id,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    // =====================================================
    // BOOKING INFORMATION
    // =====================================================

    /**
     * Get bookings belonging to a guest.
     *
     * This method expects a bookings table with:
     *
     * guest_id
     * room_id
     * check_in
     * check_out
     */
    public function getBookings(
        int $guestId,
        int $page = 1,
        int $perPage = 20
    ): array {

        if ($guestId <= 0) {
            throw new InvalidArgumentException(
                'Invalid guest ID.'
            );
        }

        $page =
            max(1, $page);

        $perPage =
            max(
                1,
                min(100, $perPage)
            );

        $offset =
            ($page - 1) * $perPage;

        $countSql = "
            SELECT COUNT(*)
            FROM `bookings`
            WHERE `guest_id` = :guest_id
        ";

        $countStmt =
            $this->db->prepare(
                $countSql
            );

        $countStmt->execute([
            ':guest_id' => $guestId,
        ]);

        $total =
            (int) $countStmt->fetchColumn();

        $sql = "
            SELECT
                b.*,
                r.room_number,
                r.room_type
            FROM `bookings` b
            LEFT JOIN `rooms` r
                ON r.id = b.room_id
            WHERE b.`guest_id` = :guest_id
            ORDER BY b.`check_in` DESC, b.`id` DESC
            LIMIT :limit
            OFFSET :offset
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->bindValue(
            ':guest_id',
            $guestId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $items =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        return [
            'items' =>
                $items,

            'pagination' => [
                'page' =>
                    $page,

                'per_page' =>
                    $perPage,

                'total' =>
                    $total,

                'total_pages' =>
                    $perPage > 0
                        ? (int) ceil(
                            $total / $perPage
                        )
                        : 0,
            ],
        ];
    }

    /**
     * Get number of bookings for a guest.
     */
    public function getBookingCount(
        int $guestId
    ): int {

        if ($guestId <= 0) {
            return 0;
        }

        $sql = "
            SELECT COUNT(*)
            FROM `bookings`
            WHERE `guest_id` = :guest_id
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':guest_id' => $guestId,
        ]);

        return (int)
            $stmt->fetchColumn();
    }

    // =====================================================
    // GUEST STATISTICS
    // =====================================================

    /**
     * Return guest statistics.
     */
    public function getStatistics(
        int $guestId
    ): array {

        $guest =
            $this->findById(
                $guestId
            );

        if ($guest === null) {
            throw new RuntimeException(
                'Guest not found.'
            );
        }

        $sql = "
            SELECT
                COUNT(*) AS total_bookings,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'completed'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS completed_bookings,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'cancelled'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS cancelled_bookings,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status IN (
                                'confirmed',
                                'checked_in'
                            )
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS active_bookings,

                COALESCE(
                    SUM(
                        COALESCE(total_amount, 0)
                    ),
                    0
                ) AS total_spent

            FROM `bookings`
            WHERE `guest_id` = :guest_id
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':guest_id' => $guestId,
        ]);

        $stats =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return [
            'guest_id' =>
                $guestId,

            'total_bookings' =>
                (int) (
                    $stats['total_bookings']
                    ?? 0
                ),

            'completed_bookings' =>
                (int) (
                    $stats['completed_bookings']
                    ?? 0
                ),

            'cancelled_bookings' =>
                (int) (
                    $stats['cancelled_bookings']
                    ?? 0
                ),

            'active_bookings' =>
                (int) (
                    $stats['active_bookings']
                    ?? 0
                ),

            'total_spent' =>
                round(
                    (float) (
                        $stats['total_spent']
                        ?? 0
                    ),
                    2
                ),
        ];
    }

    // =====================================================
    // DUPLICATE DETECTION
    // =====================================================

    /**
     * Determine whether a guest already exists using
     * email, phone or ID number.
     *
     * $excludeId is useful during updates.
     */
    public function isDuplicate(
        array $data,
        ?int $excludeId = null
    ): bool {

        $conditions = [];
        $params = [];

        if (
            !empty($data['email'])
        ) {

            $conditions[] =
                'LOWER(`email`) = LOWER(:email)';

            $params[':email'] =
                trim(
                    (string) $data['email']
                );
        }

        if (
            !empty($data['phone'])
        ) {

            $conditions[] =
                '`phone` = :phone';

            $params[':phone'] =
                trim(
                    (string) $data['phone']
                );
        }

        if (
            !empty($data['id_number'])
        ) {

            $conditions[] =
                '`id_number` = :id_number';

            $params[':id_number'] =
                trim(
                    (string) $data['id_number']
                );
        }

        if (empty($conditions)) {
            return false;
        }

        $where =
            '(' .
            implode(
                ' OR ',
                $conditions
            ) .
            ')';

        if (
            $excludeId !== null &&
            $excludeId > 0
        ) {

            $where .=
                ' AND `id` != :exclude_id';

            $params[':exclude_id'] =
                $excludeId;
        }

        $sql = "
            SELECT 1
            FROM `{$this->table}`
            WHERE {$where}
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        return $stmt->fetchColumn() !== false;
    }

    // =====================================================
    // FILTER BUILDER
    // =====================================================

    /**
     * Apply GET/list filters.
     */
    private function applyFilters(
        array $filters,
        array &$where,
        array &$params
    ): void {

        if (
            !empty($filters['search'])
        ) {

            $where[] = "
                (
                    `first_name` LIKE :search
                    OR `last_name` LIKE :search
                    OR CONCAT(
                        `first_name`,
                        ' ',
                        `last_name`
                    ) LIKE :search
                    OR `email` LIKE :search
                    OR `phone` LIKE :search
                    OR `id_number` LIKE :search
                )
            ";

            $params[':search'] =
                '%' .
                trim(
                    (string) $filters['search']
                ) .
                '%';
        }

        $exactFilters = [
            'email',
            'phone',
            'city',
            'state',
            'country',
            'nationality',
            'gender',
            'id_type',
        ];

        foreach (
            $exactFilters as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $filters
                ) ||
                $filters[$field] === ''
            ) {
                continue;
            }

            $where[] =
                "`{$field}` = :{$field}";

            $params[
                ":{$field}"
            ] =
                trim(
                    (string) $filters[$field]
                );
        }
    }

    // =====================================================
    // NORMALIZATION
    // =====================================================

    /**
     * Normalize values before insertion/update.
     */
    private function normalizeValue(
        string $field,
        mixed $value
    ): mixed {

        if (
            $value === null
        ) {
            return null;
        }


        if (
            is_string($value)
        ) {

            $value =
                trim($value);
        }

        /*
         * Empty optional values become NULL.
         */
        $nullableFields = [
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'nationality',
            'id_type',
            'id_number',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ];

        if (
            in_array(
                $field,
                $nullableFields,
                true
            ) &&
            $value === ''
        ) {

            return null;
        }

        /*
         * Normalize email addresses.
         */
        if (
            $field === 'email'
        ) {

            return strtolower(
                (string) $value
            );
        }

        return $value;
    }

    // =====================================================
    // DATABASE CONNECTION
    // =====================================================

    /**
     * Resolve PDO connection from config/database.php.
     *
     * Supports common connection patterns so the model can
     * work with the database configuration already created
     * in this project.
     */
    private function getDatabaseConnection(): PDO
    {

        /*
         * Pattern 1:
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

            if (
                $connection instanceof PDO
            ) {

                return $connection;
            }
        }

        /*
         * Pattern 2:
         *
         * Database::getConnection()
         */
        if (
            class_exists('Database') &&
            method_exists(
                'Database',
                'getConnection'
            )
        ) {

            $connection =
                Database::getConnection();

            if (
                $connection instanceof PDO
            ) {

                return $connection;
            }
        }

        /*
         * Pattern 3:
         *
         * Database::connect()
         */
        if (
            class_exists('Database') &&
            method_exists(
                'Database',
                'connect'
            )
        ) {

            $connection =
                Database::connect();


            if (
                $connection instanceof PDO
            ) {

                return $connection;
            }
        }

        /*
         * Pattern 4:
         *
         * global $pdo
         */
        global $pdo;

        if (
            isset($pdo) &&
            $pdo instanceof PDO
        ) {

            return $pdo;
        }

        throw new RuntimeException(
            'Unable to establish database connection.'
        );
    }
}
