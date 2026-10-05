<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/controllers/PaymentController.php
 *
 * Handles HTTP requests for payment operations.
 */

$paymentServiceClass = 'PaymentService';

if (
    !class_exists($paymentServiceClass, false)
    && file_exists(__DIR__ . '/../services/PaymentService.php')
) {
    require_once __DIR__ . '/../services/PaymentService.php';
}

class PaymentController
{
    private object $paymentService;

    public function __construct(
        ?object $paymentService = null
    ) {
        global $paymentServiceClass;

        $this->paymentService =
            $paymentService ?? new $paymentServiceClass();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        try {
            $filters = $this->normalizeFilters([
                'page' => $this->getIntQuery(
                    'page',
                    1
                ),
                'limit' => $this->getIntQuery(
                    'limit',
                    20
                ),
                'booking_id' => $this->getIntQuery(
                    'booking_id'
                ),
                'guest_id' => $this->getIntQuery(
                    'guest_id'
                ),
                'status' => $this->getQuery(
                    'status'
                ),
                'payment_method' => $this->getQuery(
                    'payment_method'
                ),
                'date_from' => $this->getQuery(
                    'date_from'
                ),
                'date_to' => $this->getQuery(
                    'date_to'
                ),
                'search' => $this->getQuery(
                    'search'
                ),
            ]);

            $result = $this->paymentService->getAll(
                $filters
            );

            $this->success(
                $result,
                'Payments retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/{id}
    |--------------------------------------------------------------------------
    */

    public function show(
        int $id
    ): void {
        try {
            if (!$this->isValidId($id)) {
                $this->error(
                    'Invalid payment ID.',
                    422
                );

                return;
            }

            $payment =
                $this->paymentService->getById($id);

            if (!$payment) {
                $this->error(
                    'Payment not found.',
                    404
                );

                return;
            }

            $this->success(
                $payment,
                'Payment retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/payments
    |--------------------------------------------------------------------------
    */

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();

            $this->validateRequired(
                $data,
                [
                    'booking_id',
                    'amount',
                    'payment_method',
                ]
            );

            $payment =
                $this->paymentService->create($data);

            $this->success(
                $payment,
                'Payment created successfully.',
                201
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT/PATCH /api/payments/{id}
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id
    ): void {
        try {
            if (!$this->isValidId($id)) {
                $this->error(
                    'Invalid payment ID.',
                    422
                );

                return;
            }

            $data = $this->getJsonInput();

            if (empty($data)) {
                $this->error(
                    'No payment data was provided.',
                    422
                );

                return;
            }

            $payment =
                $this->paymentService->update(
                    $id,
                    $data
                );

            $this->success(
                $payment,
                'Payment updated successfully.'
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/payments/{id}
    |--------------------------------------------------------------------------
    */

    public function destroy(
        int $id
    ): void {
        try {
            if (!$this->isValidId($id)) {
                $this->error(
                    'Invalid payment ID.',
                    422
                );

                return;
            }

            $result =
                $this->paymentService->delete($id);

            $this->success(
                $result,
                'Payment deleted successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/payments/{id}/refund
    |--------------------------------------------------------------------------
    */

    public function refund(
        int $id
    ): void {
        try {
            if (!$this->isValidId($id)) {
                $this->error(
                    'Invalid payment ID.',
                    422
                );

                return;
            }

            $data = $this->getJsonInput();

            $result =
                $this->paymentService->refund(
                    $id,
                    $data
                );

            $this->success(
                $result,
                'Payment refunded successfully.'
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/payments/{id}/void
    |--------------------------------------------------------------------------
    */

    public function void(
        int $id
    ): void {
        try {
            if (!$this->isValidId($id)) {
                $this->error(
                    'Invalid payment ID.',
                    422
                );

                return;
            }

            $data = $this->getJsonInput();

            $result =
                $this->paymentService->void(
                    $id,
                    $data
                );

            $this->success(
                $result,
                'Payment voided successfully.'
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/payments/{id}/complete
    |--------------------------------------------------------------------------
    */

    public function complete(
        int $id
    ): void {
        try {
            if (!$this->isValidId($id)) {
                $this->error(
                    'Invalid payment ID.',
                    422
                );

                return;
            }

            $result =
                $this->paymentService->complete(
                    $id
                );

            $this->success(
                $result,
                'Payment completed successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/booking/{bookingId}
    |--------------------------------------------------------------------------
    */

    public function byBooking(
        int $bookingId
    ): void {
        try {
            if (!$this->isValidId($bookingId)) {
                $this->error(
                    'Invalid booking ID.',
                    422
                );

                return;
            }

            $payments =
                $this->paymentService->getByBooking(
                    $bookingId
                );

            $this->success(
                $payments,
                'Booking payments retrieved successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/guest/{guestId}
    |--------------------------------------------------------------------------
    */

    public function byGuest(
        int $guestId
    ): void {
        try {
            if (!$this->isValidId($guestId)) {
                $this->error(
                    'Invalid guest ID.',
                    422
                );

                return;
            }

            $payments =
                $this->paymentService->getByGuest(
                    $guestId
                );

            $this->success(
                $payments,
                'Guest payments retrieved successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/summary
    |--------------------------------------------------------------------------
    */

    public function summary(): void
    {
        try {
            $filters = $this->normalizeFilters([
                'date_from' => $this->getQuery(
                    'date_from'
                ),
                'date_to' => $this->getQuery(
                    'date_to'
                ),
                'booking_id' => $this->getIntQuery(
                    'booking_id'
                ),
                'payment_method' => $this->getQuery(
                    'payment_method'
                ),
            ]);

            $summary =
                $this->paymentService->getSummary(
                    $filters
                );

            $this->success(
                $summary,
                'Payment summary retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/methods
    |--------------------------------------------------------------------------
    */

    public function methods(): void
    {
        try {
            if (
                method_exists(
                    $this->paymentService,
                    'getPaymentMethods'
                )
            ) {
                $methods =
                    $this->paymentService
                        ->getPaymentMethods();
            } else {
                $methods = [
                    'cash',
                    'card',
                    'bank_transfer',
                    'online',
                    'mobile_payment',
                ];
            }

            $this->success(
                $methods,
                'Payment methods retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST BODY
    |--------------------------------------------------------------------------
    */

    private function getJsonInput(): array
    {
        $rawInput = file_get_contents(
            'php://input'
        );

        if (
            $rawInput === false
            || trim($rawInput) === ''
        ) {
            return [];
        }

        $data = json_decode(
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

    /*
    |--------------------------------------------------------------------------
    | REQUIRED FIELD VALIDATION
    |--------------------------------------------------------------------------
    */

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
                || $this->isBlankValue(
                    $data[$field]
                )
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

    private function isBlankValue(
        mixed $value
    ): bool {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY STRING HELPERS
    |--------------------------------------------------------------------------
    */

    private function normalizeFilters(
        array $filters
    ): array {
        foreach ($filters as $key => $value) {
            if (
                $value === null
                || (is_string($value) && trim($value) === '')
                || (is_array($value) && $value === [])
            ) {
                unset($filters[$key]);
            }
        }

        return $filters;
    }

    private function isValidId(
        int $id
    ): bool {
        return $id > 0;
    }

    private function getQuery(
        string $key,
        ?string $default = null
    ): ?string {
        if (!isset($_GET[$key])) {
            return $default;
        }

        $value = $_GET[$key];

        if (
            is_array($value)
            || is_object($value)
        ) {
            return $default;
        }

        $value = trim(
            (string) $value
        );

        return $value === ''
            ? $default
            : $value;
    }

    private function getIntQuery(
        string $key,
        ?int $default = null
    ): ?int {
        if (!isset($_GET[$key])) {
            return $default;
        }

        $value = $_GET[$key];

        if (
            is_array($value)
            || is_bool($value)
            || is_object($value)
        ) {
            return $default;
        }

        $numeric = trim(
            (string) $value
        );

        if (
            $numeric === ''
            || !preg_match(
                '/^-?\d+$/',
                $numeric
            )
        ) {
            return $default;
        }

        return (int) $numeric;
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
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

    /*
    |--------------------------------------------------------------------------
    | ERROR RESPONSE
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | EXCEPTION HANDLER
    |--------------------------------------------------------------------------
    */

    private function handleException(
        Throwable $e
    ): void {
        $statusCode = match (true) {
            $e instanceof InvalidArgumentException => 422,
            $e instanceof RuntimeException => 400,
            default => 500,
        };

        $this->error(
            $e->getMessage(),
            $statusCode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

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
}
