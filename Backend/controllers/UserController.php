<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * User Controller
 *
 * File:
 * backend/controllers/UserController.php
 *
 * Responsibilities:
 * - User listing
 * - User details
 * - User creation
 * - User updates
 * - User deletion
 * - Current user profile
 * - User statistics
 *
 * Routes:
 *
 * GET    /api/users
 * GET    /api/users/{id}
 * POST   /api/users
 * PUT    /api/users/{id}
 * PATCH  /api/users/{id}
 * DELETE /api/users/{id}
 *
 * GET    /api/users/me
 * PUT    /api/users/me
 * PATCH  /api/users/me
 *
 * GET    /api/users/statistics
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

require_once BASE_PATH . '/models/User.php';

// ---------------------------------------------------------
// User Controller
// ---------------------------------------------------------

class UserController
{
    /**
     * @var object|null
     */
    private $userModel;

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        $userModel = null
    ) {
        $modelClass = 'User';

        $this->userModel =
            $userModel
            ?? (
                class_exists($modelClass, true)
                ? new $modelClass()
                : null
            );
    }

    // =====================================================
    // GET /api/users
    // =====================================================

    /**
     * List users.
     *
     * Supported query parameters:
     *
     * ?page=1
     * ?per_page=20
     * ?search=john
     * ?role=staff
     * ?status=active
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

            $filters = [];

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

            if (
                isset($query['role']) &&
                trim(
                    (string) $query['role']
                ) !== ''
            ) {
                $filters['role'] =
                    strtolower(
                        trim(
                            (string) $query['role']
                        )
                    );
            }

            if (
                isset($query['status']) &&
                trim(
                    (string) $query['status']
                ) !== ''
            ) {
                $filters['status'] =
                    strtolower(
                        trim(
                            (string) $query['status']
                        )
                    );
            }

            $result =
                $this->userModel->getAll(
                    $filters,
                    $page,
                    $perPage
                );

            $this->success(
                $result['data'],
                'Users retrieved successfully.',
                [
                    'pagination' =>
                        $result['pagination'],
                ]
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve users.'
            );
        }
    }

    // =====================================================
    // GET /api/users/{id}
    // =====================================================

    /**
     * Get one user.
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
                    'A valid user ID is required.'
                );
            }

            $user =
                $this->userModel->findById(
                    $id,
                    false
                );

            if (
                $user === null
            ) {
                $this->notFound(
                    'User not found.'
                );
            }

            $this->success(
                $user,
                'User retrieved successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve user.'
            );
        }
    }

    // =====================================================
    // POST /api/users
    // =====================================================

    /**
     * Create a new user.
     */
    public function store(
        array $request = []
    ): never {

        try {

            $request = is_array($request) ? $request : [];

            $data =
                $this->getRequestData();

            // -------------------------------------------------
            // Validate Required Fields
            // -------------------------------------------------

            $firstName =
                trim(
                    (string) (
                        $data['first_name']
                        ?? ''
                    )
                );

            $lastName =
                trim(
                    (string) (
                        $data['last_name']
                        ?? ''
                    )
                );

            $email =
                strtolower(
                    trim(
                        (string) (
                            $data['email']
                            ?? ''
                        )
                    )
                );

            $password =
                (string) (
                    $data['password']
                    ?? ''
                );

            if (
                $firstName === '' ||
                $lastName === '' ||
                $email === '' ||
                $password === ''
            ) {
                $this->badRequest(
                    'First name, last name, email and password are required.'
                );
            }

            // -------------------------------------------------
            // Validate Email
            // -------------------------------------------------

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $this->badRequest(
                    'Invalid email address.'
                );
            }


            // -------------------------------------------------
            // Check Duplicate Email
            // -------------------------------------------------

            if (
                $this->userModel->emailExists(
                    $email
                )
            ) {
                $this->conflict(
                    'A user with this email already exists.'
                );
            }

            // -------------------------------------------------
            // Password
            // -------------------------------------------------

            if (
                strlen($password) < 8
            ) {
                $this->badRequest(
                    'Password must contain at least 8 characters.'
                );
            }

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            if (
                $passwordHash === false
            ) {
                $this->serverError(
                    null,
                    'Unable to create password hash.'
                );
            }

            // -------------------------------------------------
            // Role
            // -------------------------------------------------

            $role =
                strtolower(
                    trim(
                        (string) (
                            $data['role']
                            ?? 'guest'
                        )
                    )
                );

            $allowedRoles = [
                'admin',
                'manager',
                'receptionist',
                'staff',
                'guest',
            ];

            if (
                !in_array(
                    $role,
                    $allowedRoles,
                    true
                )
            ) {
                $this->badRequest(
                    'Invalid user role.'
                );
            }

            // -------------------------------------------------
            // Status
            // -------------------------------------------------

            $status =
                strtolower(
                    trim(
                        (string) (
                            $data['status']
                            ?? 'active'
                        )
                    )
                );

            $allowedStatuses = [
                'active',
                'inactive',
                'suspended',
                'pending',
                'blocked',
            ];

            if (
                !in_array(
                    $status,
                    $allowedStatuses,
                    true
                )
            ) {
                $this->badRequest(
                    'Invalid user status.'
                );
            }

            // -------------------------------------------------
            // Create
            // -------------------------------------------------

            $userId =
                $this->userModel->create([
                    'first_name' =>
                        $firstName,

                    'last_name' =>
                        $lastName,

                    'email' =>
                        $email,

                    'phone' =>
                        $data['phone']
                        ?? null,

                    'password_hash' =>
                        $passwordHash,

                    'role' =>
                        $role,

                    'status' =>
                        $status,

                    'email_verified' =>
                        !empty(
                            $data['email_verified']
                        ),
                ]);

            $user =
                $this->userModel->findById(
                    $userId,
                    false
                );

            $this->success(
                $user,
                'User created successfully.',
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
                'Unable to create user.'
            );
        }
    }

    // =====================================================
    // PUT/PATCH /api/users/{id}
    // =====================================================

    /**
     * Update an existing user.
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
                    'A valid user ID is required.'
                );
            }

            $existing =
                $this->userModel->findById(
                    $id,
                    false
                );

            if (
                $existing === null
            ) {
                $this->notFound(
                    'User not found.'
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
            // First Name
            // -------------------------------------------------

            if (
                array_key_exists(
                    'first_name',
                    $data
                )
            ) {

                $firstName =
                    trim(
                        (string) $data['first_name']
                    );

                if (
                    $firstName === ''
                ) {
                    $this->badRequest(
                        'First name cannot be empty.'
                    );
                }

                $updateData['first_name'] =
                    $firstName;
            }

            // -------------------------------------------------
            // Last Name
            // -------------------------------------------------

            if (
                array_key_exists(
                    'last_name',
                    $data
                )
            ) {

                $lastName =
                    trim(
                        (string) $data['last_name']
                    );

                if (
                    $lastName === ''
                ) {
                    $this->badRequest(
                        'Last name cannot be empty.'
                    );
                }

                $updateData['last_name'] =
                    $lastName;
            }

            // -------------------------------------------------
            // Email
            // -------------------------------------------------

            if (
                array_key_exists(
                    'email',
                    $data
                )
            ) {

                $email =
                    strtolower(
                        trim(
                            (string) $data['email']
                        )
                    );


                if (
                    !filter_var(
                        $email,
                        FILTER_VALIDATE_EMAIL
                    )
                ) {
                    $this->badRequest(
                        'Invalid email address.'
                    );
                }

                if (
                    $this->userModel->emailExists(
                        $email,
                        $id
                    )
                ) {
                    $this->conflict(
                        'A user with this email already exists.'
                    );
                }

                $updateData['email'] =
                    $email;
            }

            // -------------------------------------------------
            // Phone
            // -------------------------------------------------

            if (
                array_key_exists(
                    'phone',
                    $data
                )
            ) {

                $updateData['phone'] =
                    trim(
                        (string) $data['phone']
                    );
            }

            // -------------------------------------------------
            // Role
            // -------------------------------------------------

            if (
                array_key_exists(
                    'role',
                    $data
                )
            ) {

                $role =
                    strtolower(
                        trim(
                            (string) $data['role']
                        )
                    );

                $allowedRoles = [
                    'admin',
                    'manager',
                    'receptionist',
                    'staff',
                    'guest',
                ];

                if (
                    !in_array(
                        $role,
                        $allowedRoles,
                        true
                    )
                ) {
                    $this->badRequest(
                        'Invalid user role.'
                    );
                }

                $updateData['role'] =
                    $role;
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

                $status =
                    strtolower(
                        trim(
                            (string) $data['status']
                        )
                    );

                $allowedStatuses = [
                    'active',
                    'inactive',
                    'suspended',
                    'pending',
                    'blocked',
                ];

                if (
                    !in_array(
                        $status,
                        $allowedStatuses,
                        true
                    )
                ) {
                    $this->badRequest(
                        'Invalid user status.'
                    );
                }

                $updateData['status'] =
                    $status;
            }

            // -------------------------------------------------
            // Email Verified
            // -------------------------------------------------

            if (
                array_key_exists(
                    'email_verified',
                    $data
                )
            ) {

                $updateData['email_verified'] =
                    !empty(
                        $data['email_verified']
                    )
                        ? 1
                        : 0;
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

            $this->userModel->update(
                $id,
                $updateData
            );

            $user =
                $this->userModel->findById(
                    $id,
                    false
                );

            $this->success(
                $user,
                'User updated successfully.'
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
                'Unable to update user.'
            );
        }
    }

    // =====================================================
    // DELETE /api/users/{id}
    // =====================================================

    /**
     * Delete a user.
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
                    'A valid user ID is required.'
                );
            }

            $existing =
                $this->userModel->findById(
                    $id,
                    false
                );

            if (
                $existing === null
            ) {
                $this->notFound(
                    'User not found.'
                );
            }

            // -------------------------------------------------
            // Prevent Self Deletion
            // -------------------------------------------------

            $authenticatedUserId =
                $this->getAuthenticatedUserId(
                    $request
                );

            if (
                $authenticatedUserId !== null &&
                $authenticatedUserId === $id
            ) {
                $this->badRequest(
                    'You cannot delete your own account.'
                );
            }

            $deleted =
                $this->userModel->delete(
                    $id
                );

            if (
                !$deleted
            ) {
                $this->serverError(
                    null,
                    'Unable to delete user.'
                );
            }

            $this->success(
                null,
                'User deleted successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to delete user.'
            );
        }
    }

    // =====================================================
    // GET /api/users/me
    // =====================================================

    /**
     * Get authenticated user's profile.
     */
    public function me(
        array $request = []
    ): never {

        try {

            $id =
                $this->getAuthenticatedUserId(
                    $request
                );


            if (
                $id === null
            ) {
                $this->unauthorized(
                    'Authenticated user could not be determined.'
                );
            }

            $user =
                $this->userModel->findById(
                    $id,
                    false
                );

            if (
                $user === null
            ) {
                $this->notFound(
                    'User account was not found.'
                );
            }

            $this->success(
                $user,
                'Profile retrieved successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve profile.'
            );
        }
    }

    // =====================================================
    // PUT/PATCH /api/users/me
    // =====================================================

    /**
     * Update authenticated user's profile.
     *
     * Only profile fields are accepted:
     * - first_name
     * - last_name
     * - phone
     */
    public function updateProfile(
        array $request = []
    ): never {

        try {

            $id =
                $this->getAuthenticatedUserId(
                    $request
                );

            if (
                $id === null
            ) {
                $this->unauthorized(
                    'Authenticated user could not be determined.'
                );
            }

            $data =
                $this->getRequestData();


            $profileData = [];

            if (
                array_key_exists(
                    'first_name',
                    $data
                )
            ) {

                $firstName =
                    trim(
                        (string) $data['first_name']
                    );

                if (
                    $firstName === ''
                ) {
                    $this->badRequest(
                        'First name cannot be empty.'
                    );
                }


                $profileData['first_name'] =
                    $firstName;
            }

            if (
                array_key_exists(
                    'last_name',
                    $data
                )
            ) {

                $lastName =
                    trim(
                        (string) $data['last_name']
                    );

                if (
                    $lastName === ''
                ) {
                    $this->badRequest(
                        'Last name cannot be empty.'
                    );
                }

                $profileData['last_name'] =
                    $lastName;
            }

            if (
                array_key_exists(
                    'phone',
                    $data
                )
            ) {

                $profileData['phone'] =
                    trim(
                        (string) $data['phone']
                    );
            }

            /*
             * Email, password, role and status are
             * intentionally not accepted here.
             *
             * Those operations should have their own
             * authenticated workflows.
             */

            if (
                empty($profileData)
            ) {
                $this->badRequest(
                    'No valid profile fields were provided.'
                );
            }

            $this->userModel->updateProfile(
                $id,
                $profileData
            );

            $user =
                $this->userModel->findById(
                    $id,
                    false
                );

            $this->success(
                $user,
                'Profile updated successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to update profile.'
            );
        }
    }

    // =====================================================
    // GET /api/users/statistics
    // =====================================================

    /**
     * Get user statistics.
     */
    public function statistics(
        array $request = []
    ): never {

        try {

            $request = is_array($request) ? $request : [];

            $statistics =
                $this->userModel->getStatistics();

            $this->success(
                $statistics,
                'User statistics retrieved successfully.'
            );

        } catch (Throwable $exception) {

            $this->serverError(
                $exception,
                'Unable to retrieve user statistics.'
            );
        }
    }

    // =====================================================
    // Request Data
    // =====================================================

    /**
     * Get JSON/form request data.
     */
    private function getRequestData(): array
    {
        /*
         * Prefer parsed request data if the router has
         * already provided it.
         */
        if (
            isset($_REQUEST['data']) &&
            is_array($_REQUEST['data'])
        ) {
            return $_REQUEST['data'];
        }

        /*
         * Standard POST form data.
         */
        if (
            !empty($_POST)
        ) {
            return $_POST;
        }

        /*
         * JSON request body.
         */
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
    // Request Helpers
    // =====================================================

    /**
     * Get user ID from route parameters.
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
     * Get authenticated user ID.
     */
    private function getAuthenticatedUserId(
        array $request
    ): ?int {

        if (
            isset(
                $request['user']['id']
            )
        ) {

            $id =
                (int) $request['user']['id'];


            return $id > 0
                ? $id
                : null;
        }

        if (
            isset(
                $request['auth']['user']['id']
            )
        ) {

            $id =
                (int) $request[
                    'auth'
                ]['user']['id'];


            return $id > 0
                ? $id
                : null;
        }

        return null;
    }


    /**
     * Convert a value to a positive integer.
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

        if (
            $value <= 0
        ) {
            return $default;
        }

        return $value;
    }

    // =====================================================
    // JSON Responses
    // =====================================================

    /**
     * Send successful JSON response.
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
     * Send 400 response.
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
     * Send 401 response.
     */
    private function unauthorized(
        string $message
    ): never {

        http_response_code(401);

        if (
            !headers_sent()
        ) {
            header(
                'WWW-Authenticate: Bearer'
            );
        }

        $this->sendJson([
            'success' => false,
            'message' => $message,
            'error' => 'UNAUTHORIZED',
        ]);
    }

    /**
     * Send 404 response.
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
     * Send 409 response.
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
     * Send 500 response.
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
     * Output JSON.
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
     * Log exception without exposing details to client.
     */
    private function logException(
        Throwable $exception
    ): void {

        if (
            function_exists('logMessage')
        ) {
            $logLevel = 3;

            if (
                defined('LOG_LEVEL_ERROR')
            ) {
                $logLevel = LOG_LEVEL_ERROR;
            }

            logMessage(
                $logLevel,
                'UserController error.',
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
                'UserController error: %s in %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            )
        );
    }
}
