<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/models/Staff.php
 *
 * Database model for staff members.
 */

require_once __DIR__ . '/../config/database.php';

class Staff
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->getDatabaseConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL STAFF
    |--------------------------------------------------------------------------
    */

    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        $this->buildFilters(
            $filters,
            $where,
            $params
        );

        $whereSql = '';

        if (!empty($where)) {
            $whereSql =
                'WHERE ' . implode(' AND ', $where);
        }

        $sql = "
            SELECT *
            FROM staff
            {$whereSql}
            ORDER BY created_at DESC
        ";

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAGINATE STAFF
    |--------------------------------------------------------------------------
    */

    public function paginate(
        array $filters = [],
        int $page = 1,
        int $limit = 20
    ): array {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));

        $where = [];
        $params = [];

        $this->buildFilters(
            $filters,
            $where,
            $params
        );

        $whereSql = '';

        if (!empty($where)) {
            $whereSql =
                'WHERE ' . implode(' AND ', $where);
        }

        $countSql = "
            SELECT COUNT(*)
            FROM staff
            {$whereSql}
        ";

        $countStatement =
            $this->db->prepare($countSql);

        $countStatement->execute($params);

        $total = (int) $countStatement->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT *
            FROM staff
            {$whereSql}
            ORDER BY created_at DESC
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
            $limit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        $items =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return [
            'items' => $items,

            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $total > 0
                    ? (int) ceil(
                        $total / $limit
                    )
                    : 0,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FIND STAFF MEMBER
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM staff
            WHERE id = :id
            LIMIT 1
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        $staff =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $staff ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY EMAIL
    |--------------------------------------------------------------------------
    */

    public function findByEmail(
        string $email
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM staff
            WHERE email = :email
            LIMIT 1
        ");

        $statement->execute([
            ':email' =>
                strtolower(
                    trim($email)
                ),
        ]);

        $staff =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $staff ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY EMPLOYEE NUMBER
    |--------------------------------------------------------------------------
    */

    public function findByEmployeeNumber(
        string $employeeNumber
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM staff
            WHERE employee_number = :employee_number
            LIMIT 1
        ");

        $statement->execute([
            ':employee_number' =>
                trim($employeeNumber),
        ]);

        $staff =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $staff ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE STAFF
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): int {
        $data = $this->normalizeStaffData($data);
        $this->validateStaffData(
            $data,
            true
        );

        $employeeNumber =
            $data['employee_number']
            ?? $this->generateEmployeeNumber();

        $sql = "
            INSERT INTO staff (
                employee_number,
                first_name,
                last_name,
                email,
                phone,
                department,
                position,
                employment_type,
                hire_date,
                salary,
                status,
                address,
                emergency_contact_name,
                emergency_contact_phone,
                notes
            )
            VALUES (
                :employee_number,
                :first_name,
                :last_name,
                :email,
                :phone,
                :department,
                :position,
                :employment_type,
                :hire_date,
                :salary,
                :status,
                :address,
                :emergency_contact_name,
                :emergency_contact_phone,
                :notes
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            ':employee_number' =>
                $employeeNumber,

            ':first_name' =>
                trim(
                    (string) $data['first_name']
                ),

            ':last_name' =>
                trim(
                    (string) $data['last_name']
                ),

            ':email' =>
                strtolower(
                    trim(
                        (string) $data['email']
                    )
                ),

            ':phone' =>
                $this->nullable(
                    $data['phone'] ?? null
                ),

            ':department' =>
                $this->nullable(
                    $data['department'] ?? null
                ),

            ':position' =>
                trim(
                    (string) $data['position']
                ),

            ':employment_type' =>
                strtolower(
                    trim(
                        (string) (
                            $data['employment_type']
                            ?? 'full_time'
                        )
                    )
                ) ?: 'full_time',

            ':hire_date' =>
                $this->nullable(
                    $data['hire_date'] ?? null
                ),

            ':salary' =>
                $this->nullableNumber(
                    $data['salary'] ?? null
                ),

            ':status' =>
                strtolower(
                    trim(
                        (string) (
                            $data['status']
                            ?? 'active'
                        )
                    )
                ) ?: 'active',

            ':address' =>
                $this->nullable(
                    $data['address'] ?? null
                ),

            ':emergency_contact_name' =>
                $this->nullable(
                    $data[
                        'emergency_contact_name'
                    ] ?? null
                ),

            ':emergency_contact_phone' =>
                $this->nullable(
                    $data[
                        'emergency_contact_phone'
                    ] ?? null
                ),

            ':notes' =>
                $this->nullable(
                    $data['notes'] ?? null
                ),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STAFF
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): bool {
        $data = $this->normalizeStaffData($data);
        $this->validateStaffData(
            $data,
            false,
            $id
        );

        $allowedFields = [
            'employee_number',
            'first_name',
            'last_name',
            'email',
            'phone',
            'department',
            'position',
            'employment_type',
            'hire_date',
            'salary',
            'status',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ];

        $updates = [];
        $params = [
            ':id' => $id,
        ];

        foreach ($allowedFields as $field) {
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

            $value = $data[$field];

            if (
                in_array(
                    $field,
                    [
                        'phone',
                        'department',
                        'hire_date',
                        'address',
                        'emergency_contact_name',
                        'emergency_contact_phone',
                        'notes',
                    ],
                    true
                )
            ) {
                $value = $this->nullable($value);
            }

            if ($field === 'salary') {
                $value =
                    $this->nullableNumber($value);
            }

            if ($field === 'email') {
                $value =
                    strtolower(
                        trim(
                            (string) $value
                        )
                    );
            }

            if (
                in_array(
                    $field,
                    [
                        'first_name',
                        'last_name',
                        'position',
                        'employee_number',
                    ],
                    true
                )
            ) {
                $value =
                    trim(
                        (string) $value
                    );
            }

            $params[":{$field}"] = $value;
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "
            UPDATE staff
            SET
                " . implode(
                    ', ',
                    $updates
                ) . ",
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $statement =
            $this->db->prepare($sql);

        return $statement->execute($params);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE STAFF
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $id
    ): bool {
        /*
         * If the database contains dependent records,
         * deactivation is safer than physical deletion.
         */
        $statement = $this->db->prepare("
            UPDATE staff
            SET
                status = 'inactive',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        return $statement->execute([
            ':id' => $id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE STAFF
    |--------------------------------------------------------------------------
    */

    public function getActive(): array
    {
        $statement = $this->db->query("
            SELECT *
            FROM staff
            WHERE status = 'active'
            ORDER BY first_name ASC, last_name ASC
        ");

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET DEPARTMENTS
    |--------------------------------------------------------------------------
    */

    public function getDepartments(): array
    {
        $statement = $this->db->query("
            SELECT DISTINCT department
            FROM staff
            WHERE department IS NOT NULL
              AND department <> ''
            ORDER BY department ASC
        ");

        $rows =
            $statement->fetchAll(
                PDO::FETCH_COLUMN
            );

        return array_values($rows);
    }

    /*
    |--------------------------------------------------------------------------
    | GET POSITIONS
    |--------------------------------------------------------------------------
    */

    public function getPositions(): array
    {
        $statement = $this->db->query("
            SELECT DISTINCT position
            FROM staff
            WHERE position IS NOT NULL
              AND position <> ''
            ORDER BY position ASC
        ");

        $rows =
            $statement->fetchAll(
                PDO::FETCH_COLUMN
            );

        return array_values($rows);
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
        $allowedStatuses = [
            'active',
            'inactive',
            'on_leave',
            'suspended',
            'terminated',
        ];

        $status = strtolower(
            trim($status)
        );

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid staff status.'
            );
        }

        $statement = $this->db->prepare("
            UPDATE staff
            SET
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        return $statement->execute([
            ':status' => $status,
            ':id' => $id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE
    |--------------------------------------------------------------------------
    */

    public function activate(
        int $id
    ): bool {
        return $this->updateStatus(
            $id,
            'active'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DEACTIVATE
    |--------------------------------------------------------------------------
    */

    public function deactivate(
        int $id
    ): bool {
        return $this->updateStatus(
            $id,
            'inactive'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    public function search(
        string $query
    ): array {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $search = '%' . $query . '%';

        $statement = $this->db->prepare("
            SELECT *
            FROM staff
            WHERE
                employee_number LIKE :search
                OR first_name LIKE :search
                OR last_name LIKE :search
                OR email LIKE :search
                OR phone LIKE :search
                OR department LIKE :search
                OR position LIKE :search
            ORDER BY
                first_name ASC,
                last_name ASC
            LIMIT 50
        ");

        $statement->execute([
            ':search' => $search,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function getSchedule(
        int $staffId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        /*
         * The current staff migration may not contain
         * a schedules table. Return an empty collection
         * until a dedicated staff_schedules table exists.
         */
        if (!$this->tableExists('staff_schedules')) {
            return [];
        }

        $where = [
            'staff_id = :staff_id',
        ];

        $params = [
            ':staff_id' => $staffId,
        ];

        if ($dateFrom !== null) {
            $where[] =
                'schedule_date >= :date_from';

            $params[':date_from'] =
                $dateFrom;
        }

        if ($dateTo !== null) {
            $where[] =
                'schedule_date <= :date_to';

            $params[':date_to'] =
                $dateTo;
        }

        $sql = "
            SELECT *
            FROM staff_schedules
            WHERE " . implode(
                ' AND ',
                $where
            ) . "
            ORDER BY schedule_date ASC,
                     start_time ASC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET ATTENDANCE
    |--------------------------------------------------------------------------
    */

    public function getAttendance(
        int $staffId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        if (!$this->tableExists('staff_attendance')) {
            return [];
        }

        $where = [
            'staff_id = :staff_id',
        ];

        $params = [
            ':staff_id' => $staffId,
        ];

        if ($dateFrom !== null) {
            $where[] =
                'attendance_date >= :date_from';

            $params[':date_from'] =
                $dateFrom;
        }

        if ($dateTo !== null) {
            $where[] =
                'attendance_date <= :date_to';

            $params[':date_to'] =
                $dateTo;
        }

        $sql = "
            SELECT *
            FROM staff_attendance
            WHERE " . implode(
                ' AND ',
                $where
            ) . "
            ORDER BY attendance_date DESC
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RECORD ATTENDANCE
    |--------------------------------------------------------------------------
    */

    public function recordAttendance(
        int $staffId,
        array $data
    ): int {
        if (!$this->tableExists('staff_attendance')) {
            throw new RuntimeException(
                'Staff attendance table does not exist.'
            );
        }

        $sql = "
            INSERT INTO staff_attendance (
                staff_id,
                attendance_date,
                status,
                check_in,
                check_out,
                notes
            )
            VALUES (
                :staff_id,
                :attendance_date,
                :status,
                :check_in,
                :check_out,
                :notes
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            ':staff_id' =>
                $staffId,

            ':attendance_date' =>
                $data['date'],

            ':status' =>
                $data['status'],

            ':check_in' =>
                $this->nullable(
                    $data['check_in']
                    ?? null
                ),

            ':check_out' =>
                $this->nullable(
                    $data['check_out']
                    ?? null
                ),

            ':notes' =>
                $this->nullable(
                    $data['notes']
                    ?? null
                ),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | STAFF STATISTICS
    |--------------------------------------------------------------------------
    */

    public function statistics(
        array $filters = []
    ): array {
        $where = [];
        $params = [];

        $this->buildFilters(
            $filters,
            $where,
            $params
        );

        $whereSql = '';

        if (!empty($where)) {
            $whereSql =
                'WHERE ' . implode(
                    ' AND ',
                    $where
                );
        }

        $sql = "
            SELECT
                COUNT(*) AS total,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'active'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS active,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'inactive'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS inactive,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'on_leave'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS on_leave,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'suspended'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS suspended,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'terminated'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS terminated,

                COALESCE(
                    SUM(
                        CASE
                            WHEN employment_type = 'full_time'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS full_time,

                COALESCE(
                    SUM(
                        CASE
                            WHEN employment_type = 'part_time'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS part_time

            FROM staff
            {$whereSql}
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute($params);

        $result =
            $statement->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        return [
            'total' =>
                (int) ($result['total'] ?? 0),

            'active' =>
                (int) ($result['active'] ?? 0),

            'inactive' =>
                (int) ($result['inactive'] ?? 0),

            'on_leave' =>
                (int) ($result['on_leave'] ?? 0),

            'suspended' =>
                (int) ($result['suspended'] ?? 0),

            'terminated' =>
                (int) ($result['terminated'] ?? 0),

            'full_time' =>
                (int) ($result['full_time'] ?? 0),

            'part_time' =>
                (int) ($result['part_time'] ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | COUNT STAFF
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int {
        $where = [];
        $params = [];

        $this->buildFilters(
            $filters,
            $where,
            $params
        );

        $whereSql = '';

        if (!empty($where)) {
            $whereSql =
                'WHERE ' . implode(
                    ' AND ',
                    $where
                );
        }

        $statement = $this->db->prepare("
            SELECT COUNT(*)
            FROM staff
            {$whereSql}
        ");

        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE STAFF DATA
    |--------------------------------------------------------------------------
    */

    private function normalizeStaffData(
        array $data
    ): array {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        $fieldMap = [
            'employee_number',
            'first_name',
            'last_name',
            'email',
            'phone',
            'department',
            'position',
            'employment_type',
            'hire_date',
            'salary',
            'status',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'notes',
        ];

        foreach ($fieldMap as $field) {
            if (!array_key_exists($field, $normalized)) {
                continue;
            }

            $value = $normalized[$field];

            if ($field === 'employee_number') {
                $normalized[$field] = trim((string) $value);
                continue;
            }

            if (in_array($field, ['first_name', 'last_name', 'position'], true)) {
                $normalized[$field] = trim((string) $value);
                continue;
            }

            if ($field === 'email') {
                $normalized[$field] = strtolower(trim((string) $value));
                continue;
            }

            if ($field === 'status') {
                $normalized[$field] = strtolower(trim((string) ($value ?? '')));
                continue;
            }

            if ($field === 'employment_type') {
                $normalized[$field] = strtolower(trim((string) ($value ?? '')));
                continue;
            }

            if ($field === 'salary') {
                if ($value === null || $value === '') {
                    $normalized[$field] = null;
                    continue;
                }

                if (is_numeric($value)) {
                    $normalized[$field] = round((float) $value, 2);
                }

                continue;
            }

            if (in_array($field, ['phone', 'department', 'hire_date', 'address', 'emergency_contact_name', 'emergency_contact_phone', 'notes'], true)) {
                $normalized[$field] = $this->nullable($value);
            }
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE STAFF DATA
    |--------------------------------------------------------------------------
    */

    private function validateStaffData(
        array $data,
        bool $isCreate,
        ?int $id = null
    ): void {
        $required = ['first_name', 'last_name', 'email', 'position'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                if ($isCreate) {
                    throw new InvalidArgumentException(
                        ucfirst(str_replace('_', ' ', $field)) . ' is required.'
                    );
                }

                continue;
            }

            $value = trim((string) $data[$field]);

            if ($value === '') {
                throw new InvalidArgumentException(
                    ucfirst(str_replace('_', ' ', $field)) . ' is required.'
                );
            }
        }

        if (isset($data['email']) && $data['email'] !== '') {
            $email = strtolower(trim((string) $data['email']));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException(
                    'Valid email address is required.'
                );
            }

            $existing = $this->findByEmail($email);

            if ($existing !== null && ($isCreate || (int) $existing['id'] !== $id)) {
                throw new InvalidArgumentException(
                    'Staff email already exists.'
                );
            }
        }

        if (isset($data['employee_number']) && trim((string) $data['employee_number']) !== '') {
            $employeeNumber = trim((string) $data['employee_number']);
            $existing = $this->findByEmployeeNumber($employeeNumber);

            if ($existing !== null && ($isCreate || (int) $existing['id'] !== $id)) {
                throw new InvalidArgumentException(
                    'Employee number already exists.'
                );
            }
        }

        if (isset($data['status']) && trim((string) $data['status']) !== '') {
            $status = strtolower(trim((string) $data['status']));
            $allowedStatuses = [
                'active',
                'inactive',
                'on_leave',
                'suspended',
                'terminated',
            ];

            if (!in_array($status, $allowedStatuses, true)) {
                throw new InvalidArgumentException(
                    'Invalid staff status.'
                );
            }
        }

        if (isset($data['employment_type']) && trim((string) $data['employment_type']) !== '') {
            $employmentType = strtolower(trim((string) $data['employment_type']));
            $allowedTypes = ['full_time', 'part_time', 'contract', 'intern'];

            if (!in_array($employmentType, $allowedTypes, true)) {
                throw new InvalidArgumentException(
                    'Invalid employment type.'
                );
            }
        }

        if (isset($data['salary']) && $data['salary'] !== null && $data['salary'] !== '') {
            if (!is_numeric($data['salary'])) {
                throw new InvalidArgumentException(
                    'Salary must be numeric.'
                );
            }
        }

        if (isset($data['hire_date']) && $data['hire_date'] !== null && $data['hire_date'] !== '') {
            $hireDate = trim((string) $data['hire_date']);

            if ($hireDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hireDate)) {
                throw new InvalidArgumentException(
                    'Hire date must be in YYYY-MM-DD format.'
                );
            }
        }
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
            isset($filters['search'])
            && trim((string) $filters['search']) !== ''
        ) {
            $search = trim((string) $filters['search']);

            $where[] = "
                (
                    employee_number LIKE :search
                    OR first_name LIKE :search
                    OR last_name LIKE :search
                    OR email LIKE :search
                    OR phone LIKE :search
                    OR department LIKE :search
                    OR position LIKE :search
                )
            ";

            $params[':search'] =
                '%' . $search . '%';
        }

        if (
            isset($filters['department'])
            && trim((string) $filters['department']) !== ''
        ) {
            $where[] =
                'department = :department';

            $params[':department'] =
                trim((string) $filters['department']);
        }

        if (
            isset($filters['position'])
            && trim((string) $filters['position']) !== ''
        ) {
            $where[] =
                'position = :position';

            $params[':position'] =
                trim((string) $filters['position']);
        }

        if (
            isset($filters['status'])
            && trim((string) $filters['status']) !== ''
        ) {
            $where[] =
                'status = :status';

            $params[':status'] =
                strtolower(trim((string) $filters['status']));
        }

        if (
            isset($filters['employment_type'])
            && trim((string) $filters['employment_type']) !== ''
        ) {
            $where[] =
                'employment_type = :employment_type';

            $params[':employment_type'] =
                strtolower(trim((string) $filters['employment_type']));
        }

        if (
            isset($filters['hire_date_from'])
            && trim((string) $filters['hire_date_from']) !== ''
        ) {
            $where[] =
                'hire_date >= :hire_date_from';

            $params[':hire_date_from'] =
                trim((string) $filters['hire_date_from']);
        }

        if (
            isset($filters['hire_date_to'])
            && trim((string) $filters['hire_date_to']) !== ''
        ) {
            $where[] =
                'hire_date <= :hire_date_to';

            $params[':hire_date_to'] =
                trim((string) $filters['hire_date_to']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE EMPLOYEE NUMBER
    |--------------------------------------------------------------------------
    */

    private function generateEmployeeNumber(): string
    {
        $prefix = 'EMP';

        do {
            $number =
                $prefix
                . '-'
                . date('Y')
                . '-'
                . str_pad(
                    (string) random_int(
                        1,
                        999999
                    ),
                    6,
                    '0',
                    STR_PAD_LEFT
                );

            $existing =
                $this->findByEmployeeNumber(
                    $number
                );
        } while ($existing !== null);

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | NULL HELPERS
    |--------------------------------------------------------------------------
    */

    private function nullable(
        mixed $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value === ''
                ? null
                : $value;
        }

        return $value;
    }

    private function nullableNumber(
        mixed $value
    ): ?float {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Invalid numeric value.'
            );
        }

        return round(
            (float) $value,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE EXISTS
    |--------------------------------------------------------------------------
    */

    private function tableExists(
        string $table
    ): bool {
        /*
         * Table names are internal constants in this
         * model and never come directly from user input.
         */
        try {
            $statement =
                $this->db->prepare("
                    SELECT COUNT(*)
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                      AND table_name = :table
                ");

            $statement->execute([
                ':table' => $table,
            ]);

            return (int) $statement->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    private function getDatabaseConnection(): PDO
    {
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

        global $pdo;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        throw new RuntimeException(
            'Database connection could not be initialized.'
        );
    }
}
