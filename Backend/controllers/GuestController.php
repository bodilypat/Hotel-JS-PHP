<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Guest Controller
 *
 * File:
 * backend/controllers/GuestController.php
 *
 * Responsibilities:
 * - Handle guest HTTP requests
 * - Validate request payload structure
 * - Call GuestService / Guest model
 * - Return consistent JSON responses
 *
 * Architecture:
 *
 * Route
 *   ↓
 * GuestController
 *   ↓
 * GuestService
 *   ↓
 * Guest Model
 *   ↓
 * Database
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/models/Guest.php';

/*
 * GuestService is preferred. The fallback to Guest.php keeps
 * this controller usable if the service has not been created yet.
 */
$guestServicePath = BASE_PATH . '/services/GuestService.php';

if (file_exists($guestServicePath)) {
    require_once $guestServicePath;
}

class GuestController
{
    /**
     * Guest service instance.
     */
    private mixed $guestService = null;

    /**
     * Guest model fallback.
     *
     * Kept as a generic object to avoid static-analysis errors
     * when the Guest model class is not available to the IDE.
     */
    private object $guestModel;

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(
        mixed $guestService = null,
        ?object $guestModel = null
    ) {
        $guestModelClass = class_exists('Guest') ? 'Guest' : 'stdClass';

        $this->guestModel =
            $guestModel ?? new $guestModelClass();

        /*
         * Use dependency injection when available.
         */
        if ($guestService !== null) {
            $this->guestService =
                $guestService;

            return;
        }

        /*
         * Automatically instantiate GuestService if it
         * exists in the project.
         */
        if (
            class_exists('GuestService')
        ) {
            $this->guestService =
                new GuestService(
                    $this->guestModel
                );
        }
    }

    // =====================================================
    // GET /api/guests
    // =====================================================

    /**
     * List guests.
     *
     * Query parameters:
     *
     * ?page=1
     * ?per_page=20
     * ?search=john
     * ?email=john@example.com
     * ?phone=5551234
     * ?status=active
     */
    public function index(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        try {

            if ($page < 1) {
                throw new InvalidArgumentException(
                    'Page must be greater than zero.'
                );
            }

            if ($perPage < 1 || $perPage > 100) {
                throw new InvalidArgumentException(
                    'Per-page value must be between 1 and 100.'
                );
            }
            if (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'listGuests'
                )
            ) {

                $result = $this->guestService->listGuests(
                    $filters,
                    $page,
                    $perPage
                );
            }

            /*
             * Fallback to model.
             */
            elseif (
                method_exists(
                    $this->guestModel,
                    'getAll'
                )
            ) {

                $result = $this->guestModel->getAll(
                    $filters,
                    $page,
                    $perPage
                );
            } else {
                throw new RuntimeException(
                    'Guest listing method is not implemented.'
                );
            }


            // Preserve an existing response envelope from the service/model.
            if (
                is_array($result) &&
                array_key_exists('success', $result) &&
                array_key_exists('status', $result)
            ) {
                return $result;
            }

            return $this->successResponse($result);

        } catch (Throwable $exception) {

            return $this->errorResponse(
                $exception
            );
        }
    }

    /**
     * Alias used by routes.
     */
    public function getAll(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        return $this->index(
            $filters,
            $page,
            $perPage
        );
    }

    /**
     * Alias used by routes.
     */
    public function getGuests(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        return $this->index(
            $filters,
            $page,
            $perPage
        );
    }

    /**
     * Alias used by routes.
     */
    public function list(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        return $this->index(
            $filters,
            $page,
            $perPage
        );
    }

    // =====================================================
    // GET /api/guests/{id}
    // =====================================================

    /**
     * Get a single guest.
     */
    public function show(
        int $id
    ): array {

        try {

            $id =
                $this->validateId(
                    $id
                );

            if (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'getGuest'
                )
            ) {

                $guest =
                    $this->guestService->getGuest(
                        $id
                    );

            } elseif (
                method_exists(
                    $this->guestModel,
                    'findById'
                )
            ) {

                $guest =
                    $this->guestModel->findById(
                        $id
                    );

            } else {

                throw new RuntimeException(
                    'Guest lookup method is not implemented.'
                );
            }

            if (
                $guest === null ||
                $guest === false
            ) {

                throw new RuntimeException(
                    'Guest not found.'
                );
            }

            return $this->successResponse(
                $guest
            );

        } catch (Throwable $exception) {

            return $this->errorResponse(
                $exception
            );
        }
    }

    /**
     * Alias used by routes.
     */
    public function get(
        int $id
    ): array {

        return $this->show(
            $id
        );
    }

    /**
     * Alias used by routes.
     */
    public function getGuest(
        int $id
    ): array {

        return $this->show(
            $id
        );
    }

    /**
     * Alias used by routes.
     */
    public function find(
        int $id
    ): array {

        return $this->show(
            $id
        );
    }

    // =====================================================
    // POST /api/guests
    // =====================================================

    /**
     * Create a guest.
     */
    public function store(
        array $data
    ): array {

        try {

            $data =
                $this->validateGuestData(
                    $data,
                    false
                );

            if (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'createGuest'
                )
            ) {

                $guest =
                    $this->guestService->createGuest(
                        $data
                    );

            } elseif (
                method_exists(
                    $this->guestModel,
                    'create'
                )
            ) {

                $guestId =
                    $this->guestModel->create(
                        $data
                    );


                $guest =
                    $this->guestModel->findById(
                        $guestId
                    );

            } else {

                throw new RuntimeException(
                    'Guest creation method is not implemented.'
                );
            }

            return $this->successResponse(
                $guest,
                201,
                'Guest created successfully.'
            );

        } catch (Throwable $exception) {

            return $this->errorResponse(
                $exception
            );
        }
    }

    /**
     * Alias used by routes.
     */
    public function create(
        array $data
    ): array {

        return $this->store(
            $data
        );
    }

    /**
     * Alias used by routes.
     */
    public function createGuest(
        array $data
    ): array {

        return $this->store(
            $data
        );
    }

    // =====================================================
    // PUT /api/guests/{id}
    // =====================================================

    /**
     * Update guest.
     */
    public function update(
        int $id,
        array $data
    ): array {

        try {

            $id =
                $this->validateId(
                    $id
                );

            /*
             * Confirm the guest exists before updating.
             */
            $this->getExistingGuest(
                $id
            );

            $data =
                $this->validateGuestData(
                    $data,
                    true
                );

            if (
                empty($data)
            ) {

                throw new InvalidArgumentException(
                    'No guest fields were provided for update.'
                );
            }

            if (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'updateGuest'
                )
            ) {

                $guest =
                    $this->guestService->updateGuest(
                        $id,
                        $data
                    );

            } elseif (
                method_exists(
                    $this->guestModel,
                    'update'
                )
            ) {

                $this->guestModel->update(
                    $id,
                    $data
                );

                $guest =
                    $this->guestModel->findById(
                        $id
                    );

            } else {

                throw new RuntimeException(
                    'Guest update method is not implemented.'
                );
            }

            return $this->successResponse(
                $guest,
                200,
                'Guest updated successfully.'
            );

        } catch (Throwable $exception) {

            return $this->errorResponse(
                $exception
            );
        }
    }

    /**
     * Alias used by routes.
     */
    public function updateGuest(
        int $id,
        array $data
    ): array {

        return $this->update(
            $id,
            $data
        );
    }

    /**
     * PATCH alias.
     */
    public function patch(
        int $id,
        array $data
    ): array {

        return $this->update(
            $id,
            $data
        );
    }

    // =====================================================
    // DELETE /api/guests/{id}
    // =====================================================

    /**
     * Delete guest.
     */
    public function destroy(
        int $id
    ): array {

        try {

            $id =
                $this->validateId(
                    $id
                );


            /*
             * Confirm guest exists.
             */
            $this->getExistingGuest(
                $id
            );


            if (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'deleteGuest'
                )
            ) {

                $deleted =
                    $this->guestService->deleteGuest(
                        $id
                    );

            } elseif (
                method_exists(
                    $this->guestModel,
                    'delete'
                )
            ) {

                $deleted =
                    $this->guestModel->delete(
                        $id
                    );

            } else {

                throw new RuntimeException(
                    'Guest deletion method is not implemented.'
                );
            }

            if (
                $deleted === false
            ) {

                throw new RuntimeException(
                    'Guest could not be deleted.'
                );
            }

            return $this->successResponse(
                null,
                200,
                'Guest deleted successfully.'
            );

        } catch (Throwable $exception) {

            return $this->errorResponse(
                $exception
            );
        }
    }

    /**
     * Alias used by routes.
     */
    public function delete(
        int $id
    ): array {

        return $this->destroy(
            $id
        );
    }

    /**
     * Alias used by routes.
     */
    public function deleteGuest(
        int $id
    ): array {

        return $this->destroy(
            $id
        );
    }

    // =====================================================
    // GET /api/guests/search
    // =====================================================

    /**
     * Search guests.
     */
    public function search(
        array $query = []
    ): array {

        try {

            $search =
                trim(
                    (string) (
                        $query['q']
                        ?? $query['search']
                        ?? ''
                    )
                );


            if (
                $search === ''
            ) {

                throw new InvalidArgumentException(
                    'Search query is required.'
                );
            }

            if (
                mb_strlen($search) > 100
            ) {

                throw new InvalidArgumentException(
                    'Search query cannot exceed 100 characters.'
                );
            }

            /*
             * Prefer a dedicated service method.
             */
            if (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'searchGuests'
                )
            ) {

                $result =
                    $this->guestService->searchGuests(
                        $search,
                        $query
                    );

            } elseif (
                $this->guestService !== null &&
                method_exists(
                    $this->guestService,
                    'search'
                )
            ) {

                $result =
                    $this->guestService->search(
                        $search
                    );

            } elseif (
                method_exists(
                    $this->guestModel,
                    'search'
                )
            ) {

                $result =
                    $this->guestModel->search(
                        $search
                    );

            } elseif (
                method_exists(
                    $this->guestModel,
                    'getAll'
                )
            ) {

                /*
                 * Fallback to model filtering.
                 */
                $result =
                    $this->guestModel->getAll(
                        [
                            'search' => $search,
                        ],
                        1,
                        20
                    );

            } else {

                throw new RuntimeException(
                    'Guest search method is not implemented.'
                );
            }

            return $this->successResponse(
                $result
            );

        } catch (Throwable $exception) {

            return $this->errorResponse(
                $exception
            );
        }
    }

    /**
     * Alias used by routes.
     */
    public function searchGuests(
        array $query = []
    ): array {

        return $this->search(
            $query
        );
    }

    // =====================================================
    // Validation
    // =====================================================

    /**
     * Normalize common payload variations before validation.
     *
     * This keeps the controller tolerant to route payloads and client-side
     * naming conventions without weakening the core validation rules.
     */
    private function normalizeGuestData(
        array $data
    ): array {

        $normalized = $data;

        foreach (
            [
                'guest' => null,
                'data' => null,
            ] as $nestedKey => $_
        ) {
            if (
                isset($normalized[$nestedKey]) &&
                is_array($normalized[$nestedKey])
            ) {
                foreach (
                    $normalized[$nestedKey] as $key => $value
                ) {
                    if (
                        !array_key_exists(
                            $key,
                            $normalized
                        )
                    ) {
                        $normalized[$key] = $value;
                    }
                }

                unset($normalized[$nestedKey]);
            }
        }

        $aliases = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'postalCode' => 'postal_code',
            'dateOfBirth' => 'date_of_birth',
            'idType' => 'id_type',
            'idNumber' => 'id_number',
            'phoneNumber' => 'phone',
            'guestStatus' => 'status',
        ];

        foreach (
            $aliases as $alias => $canonical
        ) {
            if (
                array_key_exists(
                    $alias,
                    $normalized
                ) &&
                !array_key_exists(
                    $canonical,
                    $normalized
                )
            ) {
                $normalized[$canonical] =
                    $normalized[$alias];
            }
        }

        return $normalized;
    }

    /**
     * Validate guest request data.
     *
     * The exact database fields should match Guest.php.
     *
     * Supported common fields:
     *
     * first_name
     * last_name
     * email
     * phone
     * address
     * city
     * state
     * country
     * postal_code
     * date_of_birth
     * gender
     * nationality
     * id_type
     * id_number
     * notes
     * status
     */
    private function validateGuestData(
        array $data,
        bool $isUpdate
    ): array {

        $data =
            $this->normalizeGuestData(
                $data
            );


        if (
            !$isUpdate &&
            !array_key_exists(
                'status',
                $data
            )
        ) {
            $data['status'] = 'active';
        }

        $validated = [];

        // -------------------------------------------------
        // First Name
        // -------------------------------------------------

        if (
            !$isUpdate ||
            array_key_exists(
                'first_name',
                $data
            )
        ) {

            $firstName =
                trim(
                    (string) (
                        $data['first_name']
                        ?? ''
                    )
                );

            if (
                $firstName === ''
            ) {

                throw new InvalidArgumentException(
                    'First name is required.'
                );
            }

            if (
                mb_strlen($firstName) > 100
            ) {

                throw new InvalidArgumentException(
                    'First name cannot exceed 100 characters.'
                );
            }

            $validated['first_name'] =
                $firstName;
        }


        // -------------------------------------------------
        // Last Name
        // -------------------------------------------------

        if (
            !$isUpdate ||
            array_key_exists(
                'last_name',
                $data
            )
        ) {

            $lastName =
                trim(
                    (string) (
                        $data['last_name']
                        ?? ''
                    )
                );

            if (
                $lastName === ''
            ) {

                throw new InvalidArgumentException(
                    'Last name is required.'
                );
            }

            if (
                mb_strlen($lastName) > 100
            ) {

                throw new InvalidArgumentException(
                    'Last name cannot exceed 100 characters.'
                );
            }

            $validated['last_name'] =
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
                trim(
                    strtolower(
                        (string) $data['email']
                    )
                );

            if (
                $email !== '' &&
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                throw new InvalidArgumentException(
                    'Invalid email address.'
                );
            }

            $validated['email'] =
                $email !== ''
                    ? $email
                    : null;
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

            $phone =
                trim(
                    (string) $data['phone']
                );

            if (
                $phone !== '' &&
                !preg_match(
                    '/^[0-9+\-\s().]{7,30}$/',
                    $phone
                )
            ) {

                throw new InvalidArgumentException(
                    'Invalid phone number.'
                );
            }

            $validated['phone'] =
                $phone !== ''
                    ? $phone
                    : null;
        }

        // -------------------------------------------------
        // Address
        // -------------------------------------------------

        if (
            array_key_exists(
                'address',
                $data
            )
        ) {

            $validated['address'] =
                $this->nullableText(
                    $data['address'],
                    500
                );
        }

        // -------------------------------------------------
        // City
        // -------------------------------------------------

        if (
            array_key_exists(
                'city',
                $data
            )
        ) {

            $validated['city'] =
                $this->nullableText(
                    $data['city'],
                    100
                );
        }

        // -------------------------------------------------
        // State
        // -------------------------------------------------

        if (
            array_key_exists(
                'state',
                $data
            )
        ) {

            $validated['state'] =
                $this->nullableText(
                    $data['state'],
                    100
                );
        }

        // -------------------------------------------------
        // Country
        // -------------------------------------------------

        if (
            array_key_exists(
                'country',
                $data
            )
        ) {

            $validated['country'] =
                $this->nullableText(
                    $data['country'],
                    100
                );
        }

        // -------------------------------------------------
        // Postal Code
        // -------------------------------------------------

        if (
            array_key_exists(
                'postal_code',
                $data
            )
        ) {

            $postalCode =
                trim(
                    (string) $data['postal_code']
                );


            if (
                $postalCode !== '' &&
                mb_strlen($postalCode) > 30
            ) {

                throw new InvalidArgumentException(
                    'Postal code cannot exceed 30 characters.'
                );
            }

            $validated['postal_code'] =
                $postalCode !== ''
                    ? $postalCode
                    : null;
        }

        // -------------------------------------------------
        // Date of Birth
        // -------------------------------------------------

        if (
            array_key_exists(
                'date_of_birth',
                $data
            )
        ) {

            $validated['date_of_birth'] =
                $this->validateDate(
                    $data['date_of_birth'],
                    'Date of birth'
                );
        }

        // -------------------------------------------------
        // Gender
        // -------------------------------------------------

        if (
            array_key_exists(
                'gender',
                $data
            )
        ) {

            $gender =
                strtolower(
                    trim(
                        (string) $data['gender']
                    )
                );

            $allowedGenderValues = [
                'male',
                'female',
                'other',
                'prefer_not_to_say',
            ];

            if (
                $gender !== '' &&
                !in_array(
                    $gender,
                    $allowedGenderValues,
                    true
                )
            ) {

                throw new InvalidArgumentException(
                    'Invalid gender value.'
                );
            }

            $validated['gender'] =
                $gender !== ''
                    ? $gender
                    : null;
        }

        // -------------------------------------------------
        // Nationality
        // -------------------------------------------------

        if (
            array_key_exists(
                'nationality',
                $data
            )
        ) {

            $validated['nationality'] =
                $this->nullableText(
                    $data['nationality'],
                    100
                );
        }

        // -------------------------------------------------
        // ID Type
        // -------------------------------------------------

        if (
            array_key_exists(
                'id_type',
                $data
            )
        ) {

            $idType =
                strtolower(
                    trim(
                        (string) $data['id_type']
                    )
                );

            $allowedIdTypes = [
                'passport',
                'national_id',
                'drivers_license',
                'driver_license',
                'identity_card',
                'other',
            ];

            if (
                $idType !== '' &&
                !in_array(
                    $idType,
                    $allowedIdTypes,
                    true
                )
            ) {

                throw new InvalidArgumentException(
                    'Invalid identification type.'
                );
            }

            $validated['id_type'] =
                $idType !== ''
                    ? $idType
                    : null;
        }

        // -------------------------------------------------
        // ID Number
        // -------------------------------------------------

        if (
            array_key_exists(
                'id_number',
                $data
            )
        ) {

            $idNumber =
                trim(
                    (string) $data['id_number']
                );

            if (
                $idNumber !== '' &&
                mb_strlen($idNumber) > 100
            ) {

                throw new InvalidArgumentException(
                    'Identification number cannot exceed 100 characters.'
                );
            }

            $validated['id_number'] =
                $idNumber !== ''
                    ? $idNumber
                    : null;
        }

        // -------------------------------------------------
        // Notes
        // -------------------------------------------------

        if (
            array_key_exists(
                'notes',
                $data
            )
        ) {

            $validated['notes'] =
                $this->nullableText(
                    $data['notes'],
                    5000
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

            $status =
                strtolower(
                    trim(
                        (string) $data['status']
                    )
                );

            $allowedStatuses = [
                'active',
                'inactive',
                'blacklisted',
            ];

            if (
                !in_array(
                    $status,
                    $allowedStatuses,
                    true
                )
            ) {

                throw new InvalidArgumentException(
                    'Invalid guest status.'
                );
            }

            $validated['status'] =
                $status;
        }

        return $validated;
    }

    // =====================================================
    // Existing Guest
    // =====================================================

    /**
     * Retrieve an existing guest or throw an exception.
     */
    private function getExistingGuest(
        int $id
    ): array {

        $result =
            $this->show(
                $id
            );

        /*
         * show() returns a response envelope.
         */
        if (
            isset(
                $result['success']
            ) &&
            $result['success'] === false
        ) {

            throw new RuntimeException(
                $result['message']
                ?? 'Guest not found.'
            );
        }

        if (
            isset(
                $result['data']
            ) &&
            is_array(
                $result['data']
            )
        ) {

            return $result['data'];
        }

        /*
         * Some models/services may return the guest
         * directly.
         */
        if (
            is_array($result)
        ) {

            return $result;
        }

        throw new RuntimeException(
            'Guest not found.'
        );
    }

    // =====================================================
    // Date Validation
    // =====================================================

    private function validateDate(
        mixed $value,
        string $fieldName
    ): ?string {

        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        $date =
            trim(
                (string) $value
            );

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
                $fieldName .
                ' must use YYYY-MM-DD format.'
            );
        }

        /*
         * A guest's date of birth cannot be in the future.
         */
        if (
            $fieldName === 'Date of birth' &&
            $date > date('Y-m-d')
        ) {

            throw new InvalidArgumentException(
                'Date of birth cannot be in the future.'
            );
        }

        return $date;
    }

    // =====================================================
    // Nullable Text
    // =====================================================

    private function nullableText(
        mixed $value,
        int $maxLength
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

        if (
            $value === ''
        ) {
            return null;
        }

        if (
            mb_strlen($value) > $maxLength
        ) {

            throw new InvalidArgumentException(
                "Text cannot exceed {$maxLength} characters."
            );
        }

        return $value;
    }

    // =====================================================
    // ID Validation
    // =====================================================

    private function validateId(
        int $id
    ): int {

        if (
            $id <= 0
        ) {

            throw new InvalidArgumentException(
                'Invalid guest ID.'
            );
        }

        return $id;
    }

    // =====================================================
    // Success Response
    // =====================================================

    /**
     * Return a consistent controller response.
     *
     * The route layer can directly JSON-encode this result.
     */
    private function successResponse(
        mixed $data,
        int $status = 200,
        string $message = ''
    ): array {

        $response = [
            'success' => true,
            'status' => $status,
            'data' => $data,
        ];

        if (
            $message !== ''
        ) {

            $response['message'] =
                $message;
        }

        return $response;
    }

    // =====================================================
    // Error Response
    // =====================================================

    /**
     * Convert exceptions into a safe API response.
     *
     * Internal exception messages are not exposed for
     * unexpected server errors.
     */
    private function errorResponse(
        Throwable $exception
    ): array {

        $status =
            $this->exceptionStatusCode(
                $exception
            );

        $clientMessage =
            $this->clientErrorMessage(
                $exception,
                $status
            );

        /*
         * Log unexpected errors when a logger exists.
         */
        $this->logException(
            $exception,
            $status
        );

        return [
            'success' => false,
            'status' => $status,
            'message' => $clientMessage,
        ];
    }

    // =====================================================
    // Exception Status
    // =====================================================

    private function exceptionStatusCode(
        Throwable $exception
    ): int {

        if (
            $exception instanceof InvalidArgumentException
        ) {
            return 422;
        }

        if (
            $exception instanceof DomainException
        ) {
            return 422;
        }


        $message =
            strtolower(
                $exception->getMessage()
            );

        if (
            str_contains(
                $message,
                'not found'
            )
        ) {
            return 404;
        }

        if (
            str_contains(
                $message,
                'already exists'
            ) ||
            str_contains(
                $message,
                'duplicate'
            )
        ) {
            return 409;
        }

        if (
            $exception instanceof RuntimeException
        ) {

            /*
             * Business-rule RuntimeExceptions generally map
             * to a conflict or unprocessable request.
             */
            return 409;
        }

        return 500;
    }

    // =====================================================
    // Safe Error Message
    // =====================================================

    private function clientErrorMessage(
        Throwable $exception,
        int $status
    ): string {

        if (
            $status < 500
        ) {

            return $exception->getMessage()
                ?: 'The request could not be processed.';
        }

        return 'An internal server error occurred.';
    }

    // =====================================================
    // Logging
    // =====================================================

    private function logException(
        Throwable $exception,
        int $status
    ): void {

        /*
         * Never expose sensitive exception details to the
         * client. Log them server-side instead.
         */

        $logMessage = sprintf(
            '[GuestController] HTTP %d: %s in %s:%d',
            $status,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        /*
         * Use project logger if available.
         */
        $loggerPath =
            BASE_PATH .
            '/helpers/logger.php';

        if (
            file_exists($loggerPath)
        ) {

            require_once $loggerPath;

            if (
                function_exists('logError')
            ) {

                try {
                    logError(
                        $logMessage
                    );

                    return;
                } catch (Throwable) {
                    // Fall through to error_log().
                }
            }
        }

        error_log(
            $logMessage
        );
    }
}
