<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/controllers/BookingController.php
 */

require_once __DIR__ . '/../services/BookingService.php';

class BookingController
{
    /** @var object|null */
    private ?object $bookingService;

    /**
     * @param object|null $bookingService
     */
    public function __construct(?object $bookingService = null)
    {
        if ($bookingService !== null) {
            $this->bookingService = $bookingService;
            return;
        }

        $serviceClass = 'BookingService';

        if (!class_exists($serviceClass, false)) {
            require_once __DIR__ . '/../services/BookingService.php';
        }

        $this->bookingService = new $serviceClass();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        try {
            $filters = [
                'page' => $this->getIntQuery('page', 1),
                'limit' => $this->getIntQuery('limit', 20),
                'status' => $this->getQuery('status'),
                'payment_status' => $this->getQuery('payment_status'),
                'guest_id' => $this->getIntQuery('guest_id', null),
                'room_id' => $this->getIntQuery('room_id', null),
                'check_in' => $this->getQuery('check_in'),
                'check_out' => $this->getQuery('check_out'),
                'search' => $this->getQuery('search'),
            ];

            $filters = array_filter(
                $filters,
                static fn ($value) => $value !== null && $value !== ''
            );

            $result = $this->bookingService->getAll($filters);

            $this->success(
                $result,
                'Bookings retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/{id}
    |--------------------------------------------------------------------------
    */

    public function show(int $id): void
    {
        try {
            $booking = $this->bookingService->getById($id);

            if (!$booking) {
                $this->error('Booking not found.', 404);
                return;
            }

            $this->success(
                $booking,
                'Booking retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings
    |--------------------------------------------------------------------------
    */

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();

            $this->validateRequired(
                $data,
                [
                    'guest_id',
                    'room_id',
                    'check_in',
                    'check_out',
                ]
            );

            $this->validateDateRange(
                (string) $data['check_in'],
                (string) $data['check_out']
            );

            $booking = $this->bookingService->create($data);

            $this->success(
                $booking,
                'Booking created successfully.',
                201
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT/PATCH /api/bookings/{id}
    |--------------------------------------------------------------------------
    */

    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();

            if (empty($data)) {
                $this->error(
                    'No booking data was provided.',
                    422
                );
                return;
            }

            $booking = $this->bookingService->update(
                $id,
                $data
            );

            $this->success(
                $booking,
                'Booking updated successfully.'
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
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/bookings/{id}
    |--------------------------------------------------------------------------
    */

    public function destroy(int $id): void
    {
        try {
            $result = $this->bookingService->delete($id);

            $this->success(
                $result,
                'Booking deleted successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                404
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings/{id}/confirm
    |--------------------------------------------------------------------------
    */

    public function confirm(int $id): void
    {
        try {
            $booking = $this->bookingService->confirm($id);

            $this->success(
                $booking,
                'Booking confirmed successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings/{id}/check-in
    |--------------------------------------------------------------------------
    */

    public function checkIn(int $id): void
    {
        try {
            $data = $this->getJsonInput();

            $booking = $this->bookingService->checkIn(
                $id,
                $data
            );

            $this->success(
                $booking,
                'Guest checked in successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings/{id}/check-out
    |--------------------------------------------------------------------------
    */

    public function checkOut(int $id): void
    {
        try {
            $data = $this->getJsonInput();

            $booking = $this->bookingService->checkOut(
                $id,
                $data
            );

            $this->success(
                $booking,
                'Guest checked out successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/bookings/{id}/cancel
    |--------------------------------------------------------------------------
    */

    public function cancel(int $id): void
    {
        try {
            $data = $this->getJsonInput();

            $reason = $data['reason'] ?? null;

            $booking = $this->bookingService->cancel(
                $id,
                $reason
            );

            $this->success(
                $booking,
                'Booking cancelled successfully.'
            );
        } catch (RuntimeException $e) {
            $this->error(
                $e->getMessage(),
                400
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/availability
    |--------------------------------------------------------------------------
    */

    public function availability(): void
    {
        try {
            $roomId = $this->getIntQuery('room_id');
            $checkIn = $this->getQuery('check_in');
            $checkOut = $this->getQuery('check_out');

            if (!$roomId || !$checkIn || !$checkOut) {
                $this->error(
                    'room_id, check_in and check_out are required.',
                    422
                );
                return;
            }

            $result = $this->bookingService->checkAvailability(
                $roomId,
                $checkIn,
                $checkOut
            );

            $this->success(
                $result,
                'Room availability checked successfully.'
            );
        } catch (InvalidArgumentException $e) {
            $this->error(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/calendar
    |--------------------------------------------------------------------------
    */

    public function calendar(): void
    {
        try {
            $start = $this->getQuery('start');
            $end = $this->getQuery('end');

            $result = $this->bookingService->getCalendarBookings(
                $start,
                $end
            );

            $this->success(
                $result,
                'Calendar bookings retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/{id}/payments
    |--------------------------------------------------------------------------
    */

    public function payments(int $id): void
    {
        try {
            $booking = $this->bookingService->getById($id);

            if (!$booking) {
                $this->error(
                    'Booking not found.',
                    404
                );
                return;
            }

            if (method_exists($this->bookingService, 'getPayments')) {
                $payments = $this->bookingService->getPayments($id);
            } else {
                $payments = [];
            }

            $this->success(
                $payments,
                'Booking payments retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/bookings/{id}/invoice
    |--------------------------------------------------------------------------
    */

    public function invoice(int $id): void
    {
        try {
            $booking = $this->bookingService->getById($id);

            if (!$booking) {
                $this->error(
                    'Booking not found.',
                    404
                );
                return;
            }

            if (method_exists($this->bookingService, 'getInvoice')) {
                $invoice = $this->bookingService->getInvoice($id);
            } else {
                $invoice = [
                    'booking' => $booking,
                    'invoice_number' => null,
                    'payments' => [],
                ];
            }

            $this->success(
                $invoice,
                'Booking invoice retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage(),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | JSON input
    |--------------------------------------------------------------------------
    */

    private function getJsonInput(): array
    {
        $rawInput = file_get_contents('php://input');

        if ($rawInput === false || trim($rawInput) === '') {
            return [];
        }

        $data = json_decode(
            $rawInput,
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                'Invalid JSON request body.'
            );
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(
                'Request body must contain a JSON object.'
            );
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Query helpers
    |--------------------------------------------------------------------------
    */

    private function getQuery(
        string $key,
        ?string $default = null
    ): ?string {
        if (!isset($_GET[$key])) {
            return $default;
        }

        $value = trim((string) $_GET[$key]);

        return $value === '' ? $default : $value;
    }

    private function getIntQuery(
        string $key,
        ?int $default = null
    ): ?int {
        if (!isset($_GET[$key])) {
            return $default;
        }

        if (!is_numeric($_GET[$key])) {
            return $default;
        }

        return (int) $_GET[$key];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validateRequired(
        array $data,
        array $fields
    ): void {
        $missing = [];

        foreach ($fields as $field) {
            if (
                !array_key_exists($field, $data)
                || $data[$field] === null
                || $data[$field] === ''
            ) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Required fields are missing: '
                . implode(', ', $missing)
            );
        }
    }

    private function validateDateRange(
        string $checkIn,
        string $checkOut
    ): void {
        $format = 'Y-m-d';

        $checkInDate = DateTimeImmutable::createFromFormat(
            $format,
            $checkIn
        );
        $checkOutDate = DateTimeImmutable::createFromFormat(
            $format,
            $checkOut
        );

        if (
            $checkInDate === false
            || $checkOutDate === false
            || $checkInDate->format($format) !== $checkIn
            || $checkOutDate->format($format) !== $checkOut
        ) {
            throw new InvalidArgumentException(
                'check_in and check_out must be valid dates in YYYY-MM-DD format.'
            );
        }

        if ($checkOutDate <= $checkInDate) {
            throw new InvalidArgumentException(
                'check_out date must be after check_in date.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Success response
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
    | Error response
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
    | JSON response
    |--------------------------------------------------------------------------
    */

    private function respond(
        array $response,
        int $statusCode
    ): void {
        http_response_code($statusCode);

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
