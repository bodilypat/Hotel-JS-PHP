<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/controllers/StaffController.php
 *
 * Handles HTTP requests for staff operations.
 */

require_once __DIR__ . '/../models/Staff.php';

class StaffController
{
    private object $staffModel;

    public function __construct(
        ?object $staffModel = null
    ) {
        $this->staffModel =
            $staffModel ?? new Staff();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        try {
            $page = $this->getIntQuery(
                'page',
                1
            );

            $limit = $this->getIntQuery(
                'limit',
                20
            );

            if ($limit <= 0) {
                $limit = 20;
            }

            $filters = [
                'search' => $this->getQuery(
                    'search'
                ),

                'department' => $this->getQuery(
                    'department'
                ),

                'position' => $this->getQuery(
                    'position'
                ),

                'status' => $this->getQuery(
                    'status'
                ),

                'employment_type' =>
                    $this->getQuery(
                        'employment_type'
                    ),
            ];

            $filters = array_filter(
                $filters,
                static fn ($value) =>
                    $value !== null
                    && $value !== ''
            );

            /*
             * Use model pagination when available.
             */
            if (
                method_exists(
                    $this->staffModel,
                    'paginate'
                )
            ) {
                $result =
                    $this->staffModel->paginate(
                        $filters,
                        $page,
                        $limit
                    );
            } else {
                $staff =
                    $this->staffModel->all(
                        $filters
                    );

                $total = count($staff);

                $offset =
                    ($page - 1)
                    * $limit;

                $items = array_slice(
                    $staff,
                    $offset,
                    $limit
                );

                $result = [
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

            $this->success(
                $result,
                'Staff members retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/{id}
    |--------------------------------------------------------------------------
    */

    public function show(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $staff = $this->requireStaffExists($id);
            if ($staff === null) {
                return;
            }

            $this->success(
                $staff,
                'Staff member retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/staff
    |--------------------------------------------------------------------------
    */

    public function store(): void
    {
        try {
            $data =
                $this->getJsonInput();

            $this->validateRequired(
                $data,
                [
                    'first_name',
                    'last_name',
                    'email',
                    'position',
                ]
            );

            $this->validateEmail(
                $data['email']
            );

            if (
                isset($data['phone'])
                && $data['phone'] !== ''
            ) {
                $data['phone'] =
                    trim(
                        (string) $data['phone']
                    );
            }

            if (
                isset($data['salary'])
                && $data['salary'] !== ''
            ) {
                if (
                    !is_numeric(
                        $data['salary']
                    )
                    || (float) $data['salary'] < 0
                ) {
                    $this->error(
                        'Salary must be a valid non-negative number.',
                        422
                    );

                    return;
                }

                $data['salary'] =
                    round(
                        (float) $data['salary'],
                        2
                    );
            }

            $status =
                $this->normalizeStatus(
                    (string) ($data['status'] ?? 'active')
                );
            $data['status'] = $status;

            if (
                method_exists(
                    $this->staffModel,
                    'create'
                )
            ) {
                $staffId =
                    $this->staffModel->create(
                        $data
                    );
            } else {
                throw new RuntimeException(
                    'Staff create operation is not available.'
                );
            }

            $staff =
                $this->staffModel->find(
                    (int) $staffId
                );

            $this->success(
                $staff,
                'Staff member created successfully.',
                201
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (PDOException $e) {
            $this->handleDatabaseException(
                $e
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT/PATCH /api/staff/{id}
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            $data =
                $this->getJsonInput();

            if (empty($data)) {
                $this->error(
                    'No staff data was provided.',
                    422
                );

                return;
            }

            if (
                isset($data['email'])
                && $data['email'] !== ''
            ) {
                $this->validateEmail(
                    $data['email']
                );
            }

            if (
                isset($data['salary'])
                && $data['salary'] !== ''
            ) {
                if (
                    !is_numeric(
                        $data['salary']
                    )
                    || (float) $data['salary'] < 0
                ) {
                    $this->error(
                        'Salary must be a valid non-negative number.',
                        422
                    );

                    return;
                }

                $data['salary'] =
                    round(
                        (float) $data['salary'],
                        2
                    );
            }

            if (
                isset($data['status'])
            ) {
                $data['status'] =
                    $this->normalizeStatus(
                        (string) $data['status']
                    );
            }

            $updated =
                $this->staffModel->update(
                    $id,
                    $data
                );

            if (!$updated) {
                $this->error(
                    'No changes were made.',
                    400
                );

                return;
            }

            $staff =
                $this->staffModel->find($id);

            $this->success(
                $staff,
                'Staff member updated successfully.'
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (PDOException $e) {
            $this->handleDatabaseException(
                $e
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/staff/{id}
    |--------------------------------------------------------------------------
    */

    public function destroy(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            /*
             * Prefer soft-delete/deactivation when
             * supported by the model.
             */
            if (
                method_exists(
                    $this->staffModel,
                    'delete'
                )
            ) {
                $deleted =
                    $this->staffModel->delete(
                        $id
                    );
            } else {
                throw new RuntimeException(
                    'Staff delete operation is not available.'
                );
            }

            if (!$deleted) {
                $this->error(
                    'Staff member could not be deleted.',
                    400
                );

                return;
            }

            $this->success(
                [
                    'id' => $id,
                    'deleted' => true,
                ],
                'Staff member deleted successfully.'
            );
        } catch (PDOException $e) {
            $this->handleDatabaseException(
                $e
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/active
    |--------------------------------------------------------------------------
    */

    public function active(): void
    {
        try {
            if (
                method_exists(
                    $this->staffModel,
                    'getActive'
                )
            ) {
                $staff =
                    $this->staffModel
                        ->getActive();
            } else {
                $staff =
                    $this->staffModel->all([
                        'status' => 'active',
                    ]);
            }

            $this->success(
                $staff,
                'Active staff retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/departments
    |--------------------------------------------------------------------------
    */

    public function departments(): void
    {
        try {
            if (
                method_exists(
                    $this->staffModel,
                    'getDepartments'
                )
            ) {
                $departments =
                    $this->staffModel
                        ->getDepartments();
            } else {
                $departments = [
                    'management',
                    'reception',
                    'housekeeping',
                    'food_and_beverage',
                    'maintenance',
                    'security',
                    'accounting',
                    'human_resources',
                    'sales',
                    'other',
                ];
            }

            $this->success(
                $departments,
                'Staff departments retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/positions
    |--------------------------------------------------------------------------
    */

    public function positions(): void
    {
        try {
            if (
                method_exists(
                    $this->staffModel,
                    'getPositions'
                )
            ) {
                $positions =
                    $this->staffModel
                        ->getPositions();
            } else {
                $positions = [
                    'general_manager',
                    'manager',
                    'receptionist',
                    'front_desk_agent',
                    'housekeeping_manager',
                    'housekeeper',
                    'maintenance_worker',
                    'chef',
                    'waiter',
                    'accountant',
                    'security_officer',
                    'hr_manager',
                    'sales_manager',
                    'other',
                ];
            }

            $this->success(
                $positions,
                'Staff positions retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PATCH /api/staff/{id}/status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            $data =
                $this->getJsonInput();

            if (
                empty($data['status'])
            ) {
                $this->error(
                    'Status is required.',
                    422
                );

                return;
            }

            $status =
                $this->normalizeStatus(
                    (string) $data['status']
                );

            if (
                method_exists(
                    $this->staffModel,
                    'updateStatus'
                )
            ) {
                $result =
                    $this->staffModel
                        ->updateStatus(
                            $id,
                            $status
                        );
            } else {
                $result =
                    $this->staffModel->update(
                        $id,
                        [
                            'status' => $status,
                        ]
                    );
            }

            if (!$result) {
                $this->error(
                    'Staff status could not be updated.',
                    400
                );

                return;
            }

            $staff =
                $this->staffModel->find($id);

            $this->success(
                $staff,
                'Staff status updated successfully.'
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/staff/{id}/activate
    |--------------------------------------------------------------------------
    */

    public function activate(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            if (
                method_exists(
                    $this->staffModel,
                    'activate'
                )
            ) {
                $result =
                    $this->staffModel
                        ->activate($id);
            } else {
                $result =
                    $this->staffModel->update(
                        $id,
                        [
                            'status' => 'active',
                        ]
                    );
            }

            if (!$result) {
                $this->error(
                    'Staff member could not be activated.',
                    400
                );

                return;
            }

            $staff =
                $this->staffModel->find($id);

            $this->success(
                $staff,
                'Staff member activated successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/staff/{id}/deactivate
    |--------------------------------------------------------------------------
    */

    public function deactivate(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            if (
                method_exists(
                    $this->staffModel,
                    'deactivate'
                )
            ) {
                $result =
                    $this->staffModel
                        ->deactivate($id);
            } else {
                $result =
                    $this->staffModel->update(
                        $id,
                        [
                            'status' => 'inactive',
                        ]
                    );
            }

            if (!$result) {
                $this->error(
                    'Staff member could not be deactivated.',
                    400
                );

                return;
            }

            $staff =
                $this->staffModel->find($id);

            $this->success(
                $staff,
                'Staff member deactivated successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/{id}/schedule
    |--------------------------------------------------------------------------
    */

    public function schedule(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            $dateFrom =
                $this->getQuery(
                    'date_from'
                );

            $dateTo =
                $this->getQuery(
                    'date_to'
                );

            if (
                method_exists(
                    $this->staffModel,
                    'getSchedule'
                )
            ) {
                $schedule =
                    $this->staffModel
                        ->getSchedule(
                            $id,
                            $dateFrom,
                            $dateTo
                        );
            } else {
                $schedule = [];
            }

            $this->success(
                $schedule,
                'Staff schedule retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/{id}/attendance
    |--------------------------------------------------------------------------
    */

    public function attendance(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            $dateFrom =
                $this->getQuery(
                    'date_from'
                );

            $dateTo =
                $this->getQuery(
                    'date_to'
                );

            if (
                method_exists(
                    $this->staffModel,
                    'getAttendance'
                )
            ) {
                $attendance =
                    $this->staffModel
                        ->getAttendance(
                            $id,
                            $dateFrom,
                            $dateTo
                        );
            } else {
                $attendance = [];
            }

            $this->success(
                $attendance,
                'Staff attendance retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/staff/{id}/attendance
    |--------------------------------------------------------------------------
    */

    public function recordAttendance(
        int $id
    ): void {
        try {
            $this->validateId($id);

            $existing = $this->requireStaffExists($id);
            if ($existing === null) {
                return;
            }

            $data =
                $this->getJsonInput();

            $this->validateRequired(
                $data,
                [
                    'date',
                    'status',
                ]
            );

            $allowedStatuses = [
                'present',
                'absent',
                'late',
                'half_day',
                'leave',
                'holiday',
            ];

            $attendanceStatus =
                strtolower(
                    trim(
                        (string) $data['status']
                    )
                );

            if (
                !in_array(
                    $attendanceStatus,
                    $allowedStatuses,
                    true
                )
            ) {
                $this->error(
                    'Invalid attendance status.',
                    422
                );

                return;
            }

            if (
                method_exists(
                    $this->staffModel,
                    'recordAttendance'
                )
            ) {
                $result =
                    $this->staffModel
                        ->recordAttendance(
                            $id,
                            $data
                        );
            } else {
                $this->error(
                    'Attendance operation is not available.',
                    501
                );

                return;
            }

            $this->success(
                $result,
                'Staff attendance recorded successfully.',
                201
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/statistics
    |--------------------------------------------------------------------------
    */

    public function statistics(): void
    {
        try {
            $filters = [
                'department' =>
                    $this->getQuery(
                        'department'
                    ),

                'position' =>
                    $this->getQuery(
                        'position'
                    ),

                'status' =>
                    $this->getQuery(
                        'status'
                    ),
            ];

            $filters = array_filter(
                $filters,
                static fn ($value) =>
                    $value !== null
                    && $value !== ''
            );

            if (
                method_exists(
                    $this->staffModel,
                    'statistics'
                )
            ) {
                $statistics =
                    $this->staffModel
                        ->statistics(
                            $filters
                        );
            } else {
                $staff =
                    $this->staffModel->all(
                        $filters
                    );

                $statistics = [
                    'total' => count($staff),
                    'active' => 0,
                    'inactive' => 0,
                    'on_leave' => 0,
                ];

                foreach ($staff as $member) {
                    $status =
                        $member['status']
                        ?? 'active';

                    if (
                        $status === 'active'
                    ) {
                        $statistics['active']++;
                    } elseif (
                        $status === 'inactive'
                    ) {
                        $statistics['inactive']++;
                    } elseif (
                        $status === 'on_leave'
                    ) {
                        $statistics['on_leave']++;
                    }
                }
            }

            $this->success(
                $statistics,
                'Staff statistics retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/staff/search
    |--------------------------------------------------------------------------
    */

    public function search(): void
    {
        try {
            $query =
                trim(
                    (string) (
                        $this->getQuery(
                            'q'
                        )
                        ?? $this->getQuery(
                            'search'
                        )
                        ?? ''
                    )
                );

            if ($query === '') {
                $this->error(
                    'Search query is required.',
                    422
                );

                return;
            }

            if (
                method_exists(
                    $this->staffModel,
                    'search'
                )
            ) {
                $result =
                    $this->staffModel->search(
                        $query
                    );
            } else {
                $result =
                    $this->staffModel->all([
                        'search' => $query,
                    ]);
            }

            $this->success(
                $result,
                'Staff search completed successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    private function validateId(
        int $id
    ): void {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Invalid staff ID.'
            );
        }
    }

    private function requireStaffExists(
        int $id
    ): ?array {
        $staff = $this->staffModel->find($id);

        if (!$staff) {
            $this->error(
                'Staff member not found.',
                404
            );

            return null;
        }

        return $staff;
    }

    private function validateRequired(
        array $data,
        array $fields
    ): void {
        $missing = [];

        foreach ($fields as $field) {
            if (
                !array_key_exists(
                    $field,
                    $data
                )
                || $data[$field] === null
                || trim(
                    (string) $data[$field]
                ) === ''
            ) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Required fields are missing: '
                . implode(
                    ', ',
                    $missing
                )
            );
        }
    }

    private function validateEmail(
        mixed $email
    ): void {
        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid email address.'
            );
        }
    }

    private function normalizeStatus(
        string $status,
        ?array $allowedStatuses = null
    ): string {
        $normalized =
            strtolower(
                trim($status)
            );

        $allowed = $allowedStatuses ?? [
            'active',
            'inactive',
            'on_leave',
            'suspended',
            'terminated',
        ];

        if (
            !in_array(
                $normalized,
                $allowed,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid staff status.'
            );
        }

        return $normalized;
    }

    private function validateStatus(
        string $status
    ): void {
        $this->normalizeStatus($status);
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST HELPERS
    |--------------------------------------------------------------------------
    */

    private function getJsonInput(): array
    {
        $rawInput =
            file_get_contents(
                'php://input'
            );

        if (
            $rawInput === false
            || trim($rawInput) === ''
        ) {
            return [];
        }

        $data =
            json_decode(
                $rawInput,
                true
            );

        if (
            json_last_error()
            !== JSON_ERROR_NONE
        ) {
            throw new InvalidArgumentException(
                'Invalid JSON request body.'
            );
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(
                'Request body must be a JSON object.'
            );
        }

        return $data;
    }

    private function getQuery(
        string $key,
        ?string $default = null
    ): ?string {
        if (
            !isset($_GET[$key])
        ) {
            return $default;
        }

        $value =
            trim(
                (string) $_GET[$key]
            );

        return $value === ''
            ? $default
            : $value;
    }

    private function getIntQuery(
        string $key,
        int $default = 0
    ): int {
        if (
            !isset($_GET[$key])
            || !is_numeric(
                $_GET[$key]
            )
        ) {
            return $default;
        }

        return max(
            1,
            (int) $_GET[$key]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSE HELPERS
    |--------------------------------------------------------------------------
    */

    private function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): void {
        $this->respond(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ],
            $statusCode
        );
    }

    private function error(
        string $message,
        int $statusCode = 400,
        mixed $errors = null
    ): void {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->respond(
            $response,
            $statusCode
        );
    }

    private function respond(
        array $response,
        int $statusCode
    ): void {
        http_response_code(
            $statusCode
        );

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | EXCEPTION HANDLING
    |--------------------------------------------------------------------------
    */

    private function handleException(
        Throwable $e
    ): void {
        if (
            $e instanceof InvalidArgumentException
        ) {
            $this->error(
                $e->getMessage(),
                422
            );

            return;
        }

        if (
            $e instanceof RuntimeException
        ) {
            $this->error(
                $e->getMessage(),
                400
            );

            return;
        }

        $this->error(
            'An unexpected server error occurred.',
            500
        );
    }

    private function handleDatabaseException(
        PDOException $e
    ): void {
        /*
         * Avoid exposing database credentials,
         * SQL statements, or internal details.
         */
        $message =
            'A database error occurred.';

        /*
         * Duplicate email / unique constraint.
         */
        if (
            $e->getCode() === '23000'
            || (is_array($e->errorInfo ?? null)
                && ($e->errorInfo[1] ?? null) === 1062)
        ) {
            $message =
                'The staff information conflicts with an existing record.';
        }

        $this->error(
            $message,
            409
        );
    }
}
