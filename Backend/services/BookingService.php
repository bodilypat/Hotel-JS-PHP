<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Booking Service
 *
 * File:
 * backend/services/BookingService.php
 *
 * Responsibilities:
 * - Booking business rules
 * - Availability checks
 * - Date validation
 * - Booking price calculation
 * - Booking status management
 * - Guest/room validation
 * - Booking creation, update and cancellation
 *
 * This class should NOT contain HTTP-specific logic.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/models/Booking.php';
require_once BASE_PATH . '/models/Room.php';

if (file_exists(BASE_PATH . '/models/Guest.php')) {
    require_once BASE_PATH . '/models/Guest.php';
}

class BookingService
{
    private mixed $bookingModel;

    private mixed $roomModel;

    private mixed $guestModel;

    /**
     * Constructor.
     *
     * Dependencies can be injected for testing.
     */
    public function __construct(
        mixed $bookingModel = null,
        mixed $roomModel = null,
        mixed $guestModel = null
    ) {
        $bookingClass =
            class_exists('Booking')
                ? 'Booking'
                : null;

        $roomClass =
            class_exists('Room')
                ? 'Room'
                : null;

        $this->bookingModel =
            $bookingModel ?? (
                $bookingClass !== null
                    ? new $bookingClass()
                    : throw new RuntimeException(
                        'Booking model class not found.'
                    )
            );

        $this->roomModel =
            $roomModel ?? (
                $roomClass !== null
                    ? new $roomClass()
                    : throw new RuntimeException(
                        'Room model class not found.'
                    )
            );

        $this->guestModel =
            $guestModel;

        if (
            $this->guestModel === null &&
            class_exists('Guest')
        ) {
            $this->guestModel = new Guest();
        }
    }

    // =====================================================
    // LIST BOOKINGS
    // =====================================================

    /**
     * Get bookings with filters and pagination.
     *
     * Supported filters:
     *
     * search
     * guest_id
     * room_id
     * status
     * payment_status
     * check_in
     * check_out
     * date_from
     * date_to
     */
    public function listBookings(
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

        $filters =
            $this->sanitizeFilters(
                $filters
            );

        if (
            method_exists(
                $this->bookingModel,
                'getAll'
            )
        ) {

            $result =
                $this->bookingModel->getAll(
                    $filters,
                    $page,
                    $perPage
                );

            return $this->normalizeListResult(
                $result,
                $page,
                $perPage
            );
        }

        throw new RuntimeException(
            'Booking model does not implement getAll().'
        );
    }

    /**
     * Alias.
     */
    public function getBookings(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        return $this->listBookings(
            $filters,
            $page,
            $perPage
        );
    }

    // =====================================================
    // GET SINGLE BOOKING
    // =====================================================

    /**
     * Retrieve one booking.
     */
    public function getBooking(
        int $bookingId
    ): array {

        $bookingId =
            $this->validateId(
                $bookingId,
                'booking'
            );

        $booking =
            $this->findBooking(
                $bookingId
            );

        if (
            $booking === null
        ) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        return $booking;
    }

    /**
     * Alias.
     */
    public function findBooking(
        int $bookingId
    ): ?array {

        $bookingId =
            $this->validateId(
                $bookingId,
                'booking'
            );

        if (
            method_exists(
                $this->bookingModel,
                'findById'
            )
        ) {

            $booking =
                $this->bookingModel->findById(
                    $bookingId
                );

            return $this->toArray(
                $booking
            );
        }

        if (
            method_exists(
                $this->bookingModel,
                'find'
            )
        ) {

            $booking =
                $this->bookingModel->find(
                    $bookingId
                );

            return $this->toArray(
                $booking
            );
        }

        throw new RuntimeException(
            'Booking model does not implement a lookup method.'
        );
    }

    // =====================================================
    // CREATE BOOKING
    // =====================================================

    /**
     * Create a new booking.
     *
     * Business rules:
     *
     * 1. Guest must exist.
     * 2. Room must exist.
     * 3. Dates must be valid.
     * 4. Check-out must be after check-in.
     * 5. Room must be available.
     * 6. Number of guests must be valid.
     * 7. Room capacity must not be exceeded.
     * 8. Price is calculated server-side.
     */
    public function createBooking(
        array $data
    ): array {

        $data =
            $this->validateBookingData(
                $data,
                false
            );

        $guestId =
            $this->extractGuestId(
                $data
            );

        $roomId =
            $this->extractRoomId(
                $data
            );

        $checkIn =
            $this->parseDate(
                $data['check_in']
            );

        $checkOut =
            $this->parseDate(
                $data['check_out']
            );

        $this->validateDateRange(
            $checkIn,
            $checkOut
        );

        /*
         * Validate guest.
         */
        $guest =
            $this->findGuest(
                $guestId
            );

        if (
            $guest === null
        ) {

            throw new RuntimeException(
                'Guest not found.'
            );
        }

        /*
         * Validate room.
         */
        $room =
            $this->findRoom(
                $roomId
            );

        if (
            $room === null
        ) {

            throw new RuntimeException(
                'Room not found.'
            );
        }

        /*
         * Do not allow bookings for maintenance,
         * inactive or unavailable rooms.
         */
        $this->validateRoomBookable(
            $room
        );

        /*
         * Validate number of guests against room capacity.
         */
        $numberOfGuests =
            $this->getGuestCount(
                $data
            );

        $this->validateRoomCapacity(
            $room,
            $numberOfGuests
        );

        /*
         * Check for overlapping bookings.
         */
        $this->assertRoomAvailable(
            $roomId,
            $checkIn,
            $checkOut
        );

        /*
         * Calculate nights.
         */
        $nights =
            $this->calculateNights(
                $checkIn,
                $checkOut
            );

        /*
         * Calculate room charges.
         */
        $roomRate =
            $this->getRoomRate(
                $room
            );

        $subtotal =
            round(
                $roomRate * $nights,
                2
            );

        /*
         * Optional discount.
         */
        $discount =
            $this->calculateDiscount(
                $data,
                $subtotal
            );

        /*
         * Optional tax.
         */
        $tax =
            $this->calculateTax(
                $data,
                $subtotal - $discount
            );

        $total =
            round(
                ($subtotal - $discount) + $tax,
                2
            );

        /*
         * Prepare database record.
         */
        $bookingData = [
            'guest_id' =>
                $guestId,

            'room_id' =>
                $roomId,

            'check_in' =>
                $checkIn->format('Y-m-d'),

            'check_out' =>
                $checkOut->format('Y-m-d'),

            'number_of_guests' =>
                $numberOfGuests,

            'number_of_nights' =>
                $nights,

            'room_rate' =>
                $roomRate,

            'subtotal' =>
                $subtotal,

            'discount' =>
                $discount,

            'tax' =>
                $tax,

            'total_amount' =>
                $total,

            'status' =>
                $this->validateStatus(
                    $data['status']
                    ?? 'pending'
                ),

            'payment_status' =>
                $this->validatePaymentStatus(
                    $data['payment_status']
                    ?? 'unpaid'
                ),

            'special_requests' =>
                array_key_exists(
                    'special_requests',
                    $data
                )
                    ? $this->nullableString(
                        $data['special_requests'],
                        5000
                    )
                    : null,

            'notes' =>
                array_key_exists(
                    'notes',
                    $data
                )
                    ? $this->nullableString(
                        $data['notes'],
                        5000
                    )
                    : null,
        ];

        /*
         * Preserve optional booking fields.
         */
        $optionalFields = [
            'booking_reference' => 100,
            'source' => 255,
            'adults' => null,
            'children' => null,
            'currency' => 10,
        ];

        foreach (
            $optionalFields as $field => $maxLength
        ) {

            if (
                !array_key_exists(
                    $field,
                    $data
                )
            ) {
                continue;
            }

            if (
                $field === 'adults' ||
                $field === 'children'
            ) {
                $bookingData[$field] =
                    max(
                        0,
                        (int) $data[$field]
                    );

                continue;
            }

            $bookingData[$field] =
                $maxLength !== null
                    ? $this->nullableString(
                        $data[$field],
                        $maxLength
                    )
                    : $this->nullableString(
                        $data[$field],
                        255
                    );
        }

        /*
         * Persist booking.
         */
        $bookingId =
            $this->createBookingRecord(
                $bookingData
            );

        /*
         * Retrieve newly-created booking.
         */
        $booking =
            $this->findBooking(
                (int) $bookingId
            );

        if (
            $booking === null
        ) {

            /*
             * Some models return the created record
             * directly rather than exposing findById().
             */
            $booking =
                $bookingData;

            $booking['id'] =
                (int) $bookingId;
        }

        return $booking;
    }

    // =====================================================
    // UPDATE BOOKING
    // =====================================================

    /**
     * Update an existing booking.
     *
     * If room or dates change, availability is checked again.
     */
    public function updateBooking(
        int $bookingId,
        array $data
    ): array {

        $bookingId =
            $this->validateId(
                $bookingId,
                'booking'
            );

        $existing =
            $this->getBooking(
                $bookingId
            );

        $currentStatus =
            strtolower(
                (string) (
                    $existing['status']
                    ?? 'pending'
                )
            );

        /*
         * Completed or cancelled bookings should not be
         * freely modified.
         */
        if (
            in_array(
                $currentStatus,
                [
                    'completed',
                    'cancelled',
                ],
                true
            )
        ) {

            throw new DomainException(
                'Completed or cancelled bookings cannot be modified.'
            );
        }

        $updateData = [];

        /*
         * Determine room.
         */
        $roomId =
            isset($data['room_id'])
                ? $this->validateId(
                    (int) $data['room_id'],
                    'room'
                )
                : (int) (
                    $existing['room_id']
                    ?? 0
                );

        /*
         * Determine dates.
         */
        $checkInString =
            $data['check_in']
            ?? $existing['check_in']
            ?? null;

        $checkOutString =
            $data['check_out']
            ?? $existing['check_out']
            ?? null;

        if (
            !$checkInString ||
            !$checkOutString
        ) {

            throw new InvalidArgumentException(
                'Booking dates are required.'
            );
        }

        $checkIn =
            $this->parseDate(
                $checkInString
            );

        $checkOut =
            $this->parseDate(
                $checkOutString
            );

        $this->validateDateRange(
            $checkIn,
            $checkOut
        );

        /*
         * Check availability whenever the room or dates
         * are being changed.
         */
        $oldRoomId =
            (int) (
                $existing['room_id']
                ?? 0
            );

        $oldCheckIn =
            $existing['check_in']
            ?? null;

        $oldCheckOut =
            $existing['check_out']
            ?? null;

        $bookingPeriodChanged =
            $roomId !== $oldRoomId ||
            $checkIn->format('Y-m-d') !==
                (string) $oldCheckIn ||
            $checkOut->format('Y-m-d') !==
                (string) $oldCheckOut;

        if (
            $bookingPeriodChanged
        ) {

            $room =
                $this->findRoom(
                    $roomId
                );

            if (
                $room === null
            ) {

                throw new RuntimeException(
                    'Room not found.'
                );
            }

            $this->validateRoomBookable(
                $room
            );

            $guestCount =
                isset($data['number_of_guests'])
                    ? (int) $data['number_of_guests']
                    : (int) (
                        $existing['number_of_guests']
                        ?? 1
                    );


            $this->validateRoomCapacity(
                $room,
                $guestCount
            );

            $this->assertRoomAvailable(
                $roomId,
                $checkIn,
                $checkOut,
                $bookingId
            );

            $nights =
                $this->calculateNights(
                    $checkIn,
                    $checkOut
                );

            $roomRate =
                $this->getRoomRate(
                    $room
                );

            $subtotal =
                round(
                    $roomRate * $nights,
                    2
                );

            $discount =
                isset($data['discount'])
                    ? $this->normalizeMoney(
                        $data['discount']
                    )
                    : $this->normalizeMoney(
                        $existing['discount']
                        ?? 0
                    );

            $tax =
                isset($data['tax'])
                    ? $this->normalizeMoney(
                        $data['tax']
                    )
                    : $this->normalizeMoney(
                        $existing['tax']
                        ?? 0
                    );

            $total =
                round(
                    max(
                        0,
                        $subtotal - $discount
                    ) + $tax,
                    2
                );

            $updateData[
                'room_id'
            ] = $roomId;

            $updateData[
                'check_in'
            ] = $checkIn->format('Y-m-d');

            $updateData[
                'check_out'
            ] = $checkOut->format('Y-m-d');

            $updateData[
                'number_of_nights'
            ] = $nights;

            $updateData[
                'room_rate'
            ] = $roomRate;

            $updateData[
                'subtotal'
            ] = $subtotal;

            $updateData[
                'discount'
            ] = $discount;

            $updateData[
                'tax'
            ] = $tax;

            $updateData[
                'total_amount'
            ] = $total;
        }

        /*
         * Guest change.
         */
        if (
            array_key_exists(
                'guest_id',
                $data
            )
        ) {

            $guestId =
                $this->validateId(
                    (int) $data['guest_id'],
                    'guest'
                );

            if (
                $this->findGuest(
                    $guestId
                ) === null
            ) {

                throw new RuntimeException(
                    'Guest not found.'
                );
            }

            $updateData['guest_id'] =
                $guestId;
        }

        /*
         * Guest count.
         */
        if (
            array_key_exists(
                'number_of_guests',
                $data
            )
        ) {

            $guestCount =
                $this->validateGuestCount(
                    $data['number_of_guests']
                );

            $room =
                $this->findRoom(
                    $roomId
                );

            if (
                $room !== null
            ) {

                $this->validateRoomCapacity(
                    $room,
                    $guestCount
                );
            }

            $updateData[
                'number_of_guests'
            ] = $guestCount;
        }

        /*
         * Status.
         */
        if (
            array_key_exists(
                'status',
                $data
            )
        ) {

            $updateData['status'] =
                $this->validateStatus(
                    $data['status']
                );
        }

        /*
         * Payment status.
         */
        if (
            array_key_exists(
                'payment_status',
                $data
            )
        ) {

            $updateData[
                'payment_status'
            ] =
                $this->validatePaymentStatus(
                    $data['payment_status']
                );
        }

        /*
         * Optional text fields.
         */
        foreach (
            [
                'special_requests',
                'notes',
                'source',
            ] as $field
        ) {

            if (
                array_key_exists(
                    $field,
                    $data
                )
            ) {

                $updateData[$field] =
                    $this->nullableString(
                        $data[$field],
                        5000
                    );
            }
        }

        /*
         * Nothing to update.
         */
        if (
            empty($updateData)
        ) {

            throw new InvalidArgumentException(
                'No booking fields were provided for update.'
            );
        }

        $this->updateBookingRecord(
            $bookingId,
            $updateData
        );

        return $this->getBooking(
            $bookingId
        );
    }

    /**
     * Alias.
     */
    public function update(
        int $bookingId,
        array $data
    ): array {

        return $this->updateBooking(
            $bookingId,
            $data
        );
    }

    // =====================================================
    // CANCEL BOOKING
    // =====================================================

    /**
     * Cancel a booking.
     */
    public function cancelBooking(
        int $bookingId,
        ?string $reason = null
    ): array {

        $booking =
            $this->getBooking(
                $bookingId
            );

        $status =
            strtolower(
                (string) (
                    $booking['status']
                    ?? 'pending'
                )
            );

        if (
            $status === 'cancelled'
        ) {

            throw new DomainException(
                'Booking is already cancelled.'
            );
        }

        if (
            $status === 'completed'
        ) {

            throw new DomainException(
                'Completed bookings cannot be cancelled.'
            );
        }

        $update = [
            'status' => 'cancelled',
        ];

        if (
            $reason !== null &&
            trim($reason) !== ''
        ) {

            $update[
                'cancellation_reason'
            ] =
                $this->nullableString(
                    $reason,
                    2000
                );
        }

        $this->updateBookingRecord(
            $bookingId,
            $update
        );

        return $this->getBooking(
            $bookingId
        );
    }

    /**
     * Alias.
     */
    public function cancel(
        int $bookingId,
        ?string $reason = null
    ): array {

        return $this->cancelBooking(
            $bookingId,
            $reason
        );
    }

    // =====================================================
    // CONFIRM BOOKING
    // =====================================================

    /**
     * Confirm a pending booking.
     */
    public function confirmBooking(
        int $bookingId
    ): array {

        $booking =
            $this->getBooking(
                $bookingId
            );

        $status =
            strtolower(
                (string) (
                    $booking['status']
                    ?? 'pending'
                )
            );

        if (
            $status === 'confirmed'
        ) {

            return $booking;
        }

        if (
            $status !== 'pending'
        ) {

            throw new DomainException(
                'Only pending bookings can be confirmed.'
            );
        }

        $roomId =
            $this->validateId(
                (int) $booking['room_id'],
                'room'
            );

        $checkIn =
            $this->parseDate(
                $booking['check_in']
            );

        $checkOut =
            $this->parseDate(
                $booking['check_out']
            );

        /*
         * Re-check availability immediately before
         * confirmation.
         */
        $this->assertRoomAvailable(
            $roomId,
            $checkIn,
            $checkOut,
            $bookingId
        );

        $this->updateBookingRecord(
            $bookingId,
            [
                'status' => 'confirmed',
            ]
        );

        return $this->getBooking(
            $bookingId
        );
    }

    // =====================================================
    // CHECK IN
    // =====================================================

    /**
     * Check a guest into a confirmed booking.
     */
    public function checkIn(
        int $bookingId
    ): array {

        $booking =
            $this->getBooking(
                $bookingId
            );

        $status =
            strtolower(
                (string) (
                    $booking['status']
                    ?? ''
                )
            );

        if (
            $status !== 'confirmed'
        ) {

            throw new DomainException(
                'Only confirmed bookings can be checked in.'
            );
        }

        $today =
            new DateTimeImmutable(
                'today'
            );

        $checkIn =
            $this->parseDate(
                $booking['check_in']
            );

        if (
            $today < $checkIn
        ) {

            throw new DomainException(
                'Guest cannot check in before the booking check-in date.'
            );
        }

        $this->updateBookingRecord(
            $bookingId,
            [
                'status' => 'checked_in',
            ]
        );


        return $this->getBooking(
            $bookingId
        );
    }

    // =====================================================
    // CHECK OUT
    // =====================================================

    /**
     * Check a guest out.
     */
    public function checkOut(
        int $bookingId
    ): array {

        $booking =
            $this->getBooking(
                $bookingId
            );

        $status =
            strtolower(
                (string) (
                    $booking['status']
                    ?? ''
                )
            );


        if (
            $status !== 'checked_in'
        ) {

            throw new DomainException(
                'Only checked-in guests can be checked out.'
            );
        }

        $this->updateBookingRecord(
            $bookingId,
            [
                'status' => 'completed',
            ]
        );

        return $this->getBooking(
            $bookingId
        );
    }

    // =====================================================
    // AVAILABILITY
    // =====================================================

    /**
     * Check whether a room is available.
     */
    public function isRoomAvailable(
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $excludeBookingId = null
    ): bool {

        $roomId =
            $this->validateId(
                $roomId,
                'room'
            );

        $start =
            $this->parseDate(
                $checkIn
            );

        $end =
            $this->parseDate(
                $checkOut
            );

        $this->validateDateRange(
            $start,
            $end
        );

        /*
         * If the room itself is unavailable, immediately
         * return false.
         */
        $room =
            $this->findRoom(
                $roomId
            );


        if (
            $room === null
        ) {
            return false;
        }

        try {

            $this->validateRoomBookable(
                $room
            );

        } catch (Throwable) {

            return false;
        }

        return !$this->hasOverlappingBooking(
            $roomId,
            $start,
            $end,
            $excludeBookingId
        );
    }

    /**
     * Return availability information.
     */
    public function checkAvailability(
        int $roomId,
        string $checkIn,
        string $checkOut
    ): array {

        $available =
            $this->isRoomAvailable(
                $roomId,
                $checkIn,
                $checkOut
            );

        return [
            'room_id' =>
                $roomId,

            'check_in' =>
                $this->parseDate(
                    $checkIn
                )->format('Y-m-d'),

            'check_out' =>
                $this->parseDate(
                    $checkOut
                )->format('Y-m-d'),

            'available' =>
                $available,
        ];
    }

    // =====================================================
    // PRICE CALCULATION
    // =====================================================

    /**
     * Calculate a booking quote without creating a booking.
     */
    public function calculateQuote(
        int $roomId,
        string $checkIn,
        string $checkOut,
        float $discount = 0.0,
        float $taxRate = 0.0
    ): array {

        $roomId =
            $this->validateId(
                $roomId,
                'room'
            );

        $room =
            $this->findRoom(
                $roomId
            );

        if (
            $room === null
        ) {

            throw new RuntimeException(
                'Room not found.'
            );
        }

        $start =
            $this->parseDate(
                $checkIn
            );

        $end =
            $this->parseDate(
                $checkOut
            );

        $this->validateDateRange(
            $start,
            $end
        );

        $this->validateRoomBookable(
            $room
        );

        $nights =
            $this->calculateNights(
                $start,
                $end
            );

        $rate =
            $this->getRoomRate(
                $room
            );

        $subtotal =
            round(
                $rate * $nights,
                2
            );

        $discount =
            max(
                0,
                min(
                    $subtotal,
                    $discount
                )
            );

        $taxableAmount =
            max(
                0,
                $subtotal - $discount
            );

        $tax =
            round(
                $taxableAmount *
                max(0, $taxRate) /
                100,
                2
            );

        $total =
            round(
                $taxableAmount + $tax,
                2
            );

        return [
            'room_id' =>
                $roomId,

            'check_in' =>
                $start->format('Y-m-d'),

            'check_out' =>
                $end->format('Y-m-d'),

            'nights' =>
                $nights,

            'room_rate' =>
                $rate,

            'subtotal' =>
                $subtotal,

            'discount' =>
                $discount,

            'tax_rate' =>
                $taxRate,

            'tax' =>
                $tax,

            'total' =>
                $total,
        ];
    }

    // =====================================================
    // BOOKING VALIDATION
    // =====================================================

    /**
     * Validate create-booking payload.
     */
    private function validateBookingData(
        array $data,
        bool $isUpdate
    ): array {

        if (
            !$isUpdate &&
            !isset($data['guest_id'])
        ) {

            throw new InvalidArgumentException(
                'Guest ID is required.'
            );
        }

        if (
            !$isUpdate &&
            !isset($data['room_id'])
        ) {

            throw new InvalidArgumentException(
                'Room ID is required.'
            );
        }

        if (
            !$isUpdate &&
            empty($data['check_in'])
        ) {

            throw new InvalidArgumentException(
                'Check-in date is required.'
            );
        }

        if (
            !$isUpdate &&
            empty($data['check_out'])
        ) {

            throw new InvalidArgumentException(
                'Check-out date is required.'
            );
        }

        return $data;
    }

    // =====================================================
    // GUEST VALIDATION
    // =====================================================

    private function extractGuestId(
        array $data
    ): int {

        $guestId =
            (int) (
                $data['guest_id']
                ?? 0
            );

        return $this->validateId(
            $guestId,
            'guest'
        );
    }

    private function findGuest(
        int $guestId
    ): ?array {

        if (
            $this->guestModel === null
        ) {
            /*
             * If the project has not yet added Guest.php,
             * skip model-level verification. Database foreign
             * keys should still protect integrity.
             */
            return [
                'id' => $guestId,
            ];
        }

        if (
            method_exists(
                $this->guestModel,
                'findById'
            )
        ) {

            return $this->toArray(
                $this->guestModel->findById(
                    $guestId
                )
            );
        }

        if (
            method_exists(
                $this->guestModel,
                'find'
            )
        ) {

            return $this->toArray(
                $this->guestModel->find(
                    $guestId
                )
            );
        }

        return [
            'id' => $guestId,
        ];
    }

    // =====================================================
    // ROOM VALIDATION
    // =====================================================

    private function extractRoomId(
        array $data
    ): int {

        $roomId =
            (int) (
                $data['room_id']
                ?? 0
            );

        return $this->validateId(
            $roomId,
            'room'
        );
    }

    private function findRoom(
        int $roomId
    ): ?array {

        if (
            method_exists(
                $this->roomModel,
                'findById'
            )
        ) {

            return $this->toArray(
                $this->roomModel->findById(
                    $roomId
                )
            );
        }

        if (
            method_exists(
                $this->roomModel,
                'find'
            )
        ) {

            return $this->toArray(
                $this->roomModel->find(
                    $roomId
                )
            );
        }

        throw new RuntimeException(
            'Room model does not implement a lookup method.'
        );
    }

    /**
     * Ensure room can accept a booking.
     */
    private function validateRoomBookable(
        array $room
    ): void {

        $status =
            strtolower(
                (string) (
                    $room['status']
                    ?? 'available'
                )
            );

        $allowedStatuses = [
            'available',
            'active',
        ];

        if (
            isset($room['is_available']) &&
            !$this->toBoolean(
                $room['is_available']
            )
        ) {

            throw new DomainException(
                'Room is currently unavailable.'
            );
        }

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            throw new DomainException(
                'Room is not available for booking.'
            );
        }
    }

    /**
     * Validate room capacity.
     */
    private function validateRoomCapacity(
        array $room,
        int $guestCount
    ): void {

        $guestCount =
            $this->validateGuestCount(
                $guestCount
            );

        $capacity =
            $this->extractRoomCapacity(
                $room
            );

        if (
            $capacity !== null &&
            $guestCount > $capacity
        ) {

            throw new DomainException(
                "Room capacity is {$capacity} guest(s)."
            );
        }
    }

    /**
     * Get room capacity.
     */
    private function extractRoomCapacity(
        array $room
    ): ?int {

        foreach (
            [
                'capacity',
                'max_guests',
                'max_occupancy',
            ] as $field
        ) {

            if (
                isset($room[$field])
            ) {

                $capacity =
                    (int) $room[$field];


                if (
                    $capacity > 0
                ) {
                    return $capacity;
                }
            }
        }

        return null;
    }

    /**
     * Get room nightly rate.
     */
    private function getRoomRate(
        array $room
    ): float {
        foreach (
            [
                'price_per_night',
                'nightly_rate',
                'rate',
                'price',
            ] as $field
        ) {

            if (
                isset($room[$field])
            ) {

                $rate =
                    $this->normalizeMoney(
                        $room[$field]
                    );

                if (
                    $rate >= 0
                ) {
                    return $rate;
                }
            }
        }

        throw new RuntimeException(
            'Room does not have a valid nightly rate.'
        );
    }

    // =====================================================
    // OVERLAP DETECTION
    // =====================================================

    /**
     * Prevent double booking.
     *
     * Overlap condition:
     *
     * Existing check-in < requested check-out
     * AND
     * Existing check-out > requested check-in
     */
    private function hasOverlappingBooking(
        int $roomId,
        DateTimeImmutable $checkIn,
        DateTimeImmutable $checkOut,
        ?int $excludeBookingId = null
    ): bool {

        /*
         * Prefer a model-level availability method when available.
         */
        if (
            method_exists(
                $this->bookingModel,
                'hasOverlap'
            )
        ) {

            return (bool)
                $this->bookingModel->hasOverlap(
                    $roomId,
                    $checkIn->format('Y-m-d'),
                    $checkOut->format('Y-m-d'),
                    $excludeBookingId
                );
        }

        if (
            method_exists(
                $this->bookingModel,
                'getOverlappingBookings'
            )
        ) {

            $bookings =
                $this->bookingModel->getOverlappingBookings(
                    $roomId,
                    $checkIn->format('Y-m-d'),
                    $checkOut->format('Y-m-d'),
                    $excludeBookingId
                );

            return !empty($bookings);
        }

        /*
         * Fallback to fetching bookings.
         */
        if (
            method_exists(
                $this->bookingModel,
                'getAll'
            )
        ) {

            $bookings =
                $this->bookingModel->getAll(
                    [
                        'room_id' =>
                            $roomId,

                        'exclude_booking_id' =>
                            $excludeBookingId,

                        'active_only' =>
                            true,
                    ],
                    1,
                    1000
                );

            $rows =
                $this->extractRows(
                    $bookings
                );

            foreach (
                $rows as $existing
            ) {

                $existingStatus =
                    strtolower(
                        (string) (
                            $existing['status']
                            ?? ''
                        )
                    );

                if (
                    in_array(
                        $existingStatus,
                        [
                            'cancelled',
                            'completed',
                        ],
                        true
                    )
                ) {
                    continue;
                }

                if (
                    $excludeBookingId !== null &&
                    (int) (
                        $existing['id']
                        ?? 0
                    ) === $excludeBookingId
                ) {
                    continue;
                }

                if (
                    empty($existing['check_in']) ||
                    empty($existing['check_out'])
                ) {
                    continue;
                }

                $existingStart =
                    $this->parseDate(
                        $existing['check_in']
                    );

                $existingEnd =
                    $this->parseDate(
                        $existing['check_out']
                    );

                if (
                    $existingStart < $checkOut &&
                    $existingEnd > $checkIn
                ) {

                    return true;
                }
            }

            return false;
        }

        throw new RuntimeException(
            'Unable to determine room availability.'
        );
    }

    /**
     * Throw when room is already booked.
     */
    private function assertRoomAvailable(
        int $roomId,
        DateTimeImmutable $checkIn,
        DateTimeImmutable $checkOut,
        ?int $excludeBookingId = null
    ): void {

        if (
            $this->hasOverlappingBooking(
                $roomId,
                $checkIn,
                $checkOut,
                $excludeBookingId
            )
        ) {

            throw new DomainException(
                'The selected room is already booked for the requested dates.'
            );
        }
    }

    // =====================================================
    // DATE HANDLING
    // =====================================================

    /**
     * Parse YYYY-MM-DD date.
     */
    private function parseDate(
        mixed $value
    ): DateTimeImmutable {

        $date =
            trim(
                (string) $value
            );

        $parsed =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $date
            );

        $errors =
            DateTimeImmutable::getLastErrors();

        if (
            $parsed === false ||
            (
                is_array($errors) &&
                (
                    $errors['warning_count'] > 0 ||
                    $errors['error_count'] > 0
                )
            ) ||
            $parsed->format('Y-m-d') !== $date
        ) {

            throw new InvalidArgumentException(
                'Invalid date. Expected YYYY-MM-DD.'
            );
        }

        return $parsed;
    }

    /**
     * Validate booking date range.
     */
    private function validateDateRange(
        DateTimeImmutable $checkIn,
        DateTimeImmutable $checkOut
    ): void {

        if (
            $checkOut <= $checkIn
        ) {

            throw new InvalidArgumentException(
                'Check-out date must be after check-in date.'
            );
        }

        /*
         * Prevent bookings from being created too far in
         * the past.
         */
        $today =
            new DateTimeImmutable(
                'today'
            );

        if (
            $checkIn < $today
        ) {

            throw new InvalidArgumentException(
                'Check-in date cannot be in the past.'
            );
        }
    }

    /**
     * Calculate number of nights.
     */
    private function calculateNights(
        DateTimeImmutable $checkIn,
        DateTimeImmutable $checkOut
    ): int {

        return (int)
            $checkIn->diff(
                $checkOut
            )->days;
    }

    // =====================================================
    // GUEST COUNT
    // =====================================================

    private function getGuestCount(
        array $data
    ): int {

        if (
            isset($data['number_of_guests'])
        ) {

            return $this->validateGuestCount(
                $data['number_of_guests']
            );
        }

        /*
         * Support adults + children.
         */
        $adults =
            isset($data['adults'])
                ? (int) $data['adults']
                : 0;

        $children =
            isset($data['children'])
                ? (int) $data['children']
                : 0;

        if (
            $adults > 0 ||
            $children > 0
        ) {

            return $this->validateGuestCount(
                $adults + $children
            );
        }

        return 1;
    }

    private function validateGuestCount(
        mixed $value
    ): int {

        $count =
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            );

        if (
            $count === false ||
            $count < 1 ||
            $count > 100
        ) {

            throw new InvalidArgumentException(
                'Number of guests must be between 1 and 100.'
            );
        }

        return (int) $count;
    }

    // =====================================================
    // DISCOUNTS AND TAX
    // =====================================================

    /**
     * Calculate discount.
     *
     * Supports:
     *
     * discount
     * discount_amount
     * discount_percent
     */
    private function calculateDiscount(
        array $data,
        float $subtotal
    ): float {

        if (
            isset($data['discount_percent'])
        ) {

            $percent =
                (float) $data['discount_percent'];

            if (
                $percent < 0 ||
                $percent > 100
            ) {

                throw new InvalidArgumentException(
                    'Discount percentage must be between 0 and 100.'
                );
            }

            return round(
                $subtotal * $percent / 100,
                2
            );
        }

        if (
            isset($data['discount_amount'])
        ) {

            return min(
                $subtotal,
                $this->normalizeMoney(
                    $data['discount_amount']
                )
            );
        }

        if (
            isset($data['discount'])
        ) {

            return min(
                $subtotal,
                $this->normalizeMoney(
                    $data['discount']
                )
            );
        }

        return 0.0;
    }

    /**
     * Calculate tax.
     *
     * Supports:
     *
     * tax
     * tax_amount
     * tax_rate
     */
    private function calculateTax(
        array $data,
        float $taxableAmount
    ): float {

        if (
            isset($data['tax_amount'])
        ) {

            return max(
                0,
                $this->normalizeMoney(
                    $data['tax_amount']
                )
            );
        }

        if (
            isset($data['tax'])
        ) {

            return max(
                0,
                $this->normalizeMoney(
                    $data['tax']
                )
            );
        }

        if (
            isset($data['tax_rate'])
        ) {

            $rate =
                (float) $data['tax_rate'];


            if (
                $rate < 0 ||
                $rate > 100
            ) {

                throw new InvalidArgumentException(
                    'Tax rate must be between 0 and 100.'
                );
            }

            return round(
                $taxableAmount *
                $rate /
                100,
                2
            );
        }

        return 0.0;
    }

    // =====================================================
    // STATUS
    // =====================================================

    private function validateStatus(
        mixed $value
    ): string {

        $status =
            strtolower(
                trim(
                    (string) $value
                )
            );

        $allowed = [
            'pending',
            'confirmed',
            'checked_in',
            'checked_out',
            'completed',
            'cancelled',
            'no_show',
        ];

        if (
            !in_array(
                $status,
                $allowed,
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Invalid booking status.'
            );
        }

        return $status;
    }

    private function validatePaymentStatus(
        mixed $value
    ): string {

        $status =
            strtolower(
                trim(
                    (string) $value
                )
            );

        $allowed = [
            'unpaid',
            'partial',
            'paid',
            'refunded',
            'failed',
        ];

        if (
            !in_array(
                $status,
                $allowed,
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Invalid payment status.'
            );
        }

        return $status;
    }


    // =====================================================
    // DATABASE OPERATIONS
    // =====================================================

    private function createBookingRecord(
        array $data
    ): int {

        if (
            method_exists(
                $this->bookingModel,
                'create'
            )
        ) {

            $result =
                $this->bookingModel->create(
                    $data
                );

            /*
             * Model may return:
             *
             * integer ID
             * array containing ID
             * created booking
             */
            if (
                is_int($result) ||
                (
                    is_numeric($result) &&
                    (int) $result > 0
                )
            ) {

                return (int) $result;
            }

            if (
                is_array($result)
            ) {

                foreach (
                    [
                        'id',
                        'booking_id',
                    ] as $key
                ) {

                    if (
                        isset($result[$key])
                    ) {

                        return (int) $result[$key];
                    }
                }
            }
        }

        throw new RuntimeException(
            'Booking model does not implement create().'
        );
    }

    private function updateBookingRecord(
        int $bookingId,
        array $data
    ): bool {

        if (
            method_exists(
                $this->bookingModel,
                'update'
            )
        ) {

            $result =
                $this->bookingModel->update(
                    $bookingId,
                    $data
                );

            return $result !== false;
        }

        throw new RuntimeException(
            'Booking model does not implement update().'
        );
    }

    // =====================================================
    // FILTERS
    // =====================================================

    private function sanitizeFilters(
        array $filters
    ): array {

        $allowed = [
            'search',
            'guest_id',
            'room_id',
            'status',
            'payment_status',
            'check_in',
            'check_out',
            'date_from',
            'date_to',
            'exclude_booking_id',
            'active_only',
        ];

        $result = [];

        foreach (
            $allowed as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $filters
                )
            ) {
                continue;
            }

            $value =
                $filters[$field];

            switch ($field) {

                case 'guest_id':
                case 'room_id':
                case 'exclude_booking_id':

                    $result[$field] =
                        $this->validateId(
                            (int) $value,
                            str_replace(
                                '_id',
                                '',
                                $field
                            )
                        );

                    break;

                case 'status':

                    $result[$field] =
                        $this->validateStatus(
                            $value
                        );

                    break;

                case 'payment_status':

                    $result[$field] =
                        $this->validatePaymentStatus(
                            $value
                        );

                    break;

                case 'check_in':
                case 'check_out':
                case 'date_from':
                case 'date_to':

                    $result[$field] =
                        $this->parseDate(
                            $value
                        )->format('Y-m-d');

                    break;

                case 'active_only':

                    $result[$field] =
                        $this->toBoolean(
                            $value
                        );

                    break;

                default:

                    $result[$field] =
                        trim(
                            (string) $value
                        );
            }
        }

        return $result;
    }


    // =====================================================
    // NORMALIZATION
    // =====================================================

    private function normalizeListResult(
        mixed $result,
        int $page,
        int $perPage
    ): array {

        if (
            !is_array($result)
        ) {

            return [
                'items' =>
                    [],

                'pagination' => [
                    'page' =>
                        $page,

                    'per_page' =>
                        $perPage,

                    'total' =>
                        0,

                    'total_pages' =>
                        0,
                ],
            ];
        }

        /*
         * If the model already returns a pagination envelope,
         * preserve it.
         */
        if (
            isset($result['data']) &&
            (
                isset($result['pagination']) ||
                isset($result['meta'])
            )
        ) {

            return $result;
        }


        $items =
            $this->extractRows(
                $result
            );

        $total =
            isset($result['total'])
                ? (int) $result['total']
                : count($items);

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

    private function extractRows(
        mixed $result
    ): array {

        if (
            !is_array($result)
        ) {
            return [];
        }


        if (
            isset($result['items']) &&
            is_array($result['items'])
        ) {

            return $result['items'];
        }

        if (
            isset($result['data']) &&
            is_array($result['data'])
        ) {

            /*
             * A single associative object is not a list.
             */
            if (
                $this->isSequentialArray(
                    $result['data']
                )
            ) {

                return $result['data'];
            }
        }

        if (
            $this->isSequentialArray(
                $result
            )
        ) {

            return $result;
        }

        return [];
    }

    private function isSequentialArray(
        array $array
    ): bool {

        if (
            $array === []
        ) {
            return true;
        }

        return array_keys($array) ===
            range(
                0,
                count($array) - 1
            );
    }

    private function toArray(
        mixed $value
    ): ?array {

        if (
            $value === null ||
            $value === false
        ) {
            return null;
        }

        if (
            is_array($value)
        ) {
            return $value;
        }

        if (
            is_object($value)
        ) {

            return get_object_vars(
                $value
            );
        }

        return null;
    }

    // =====================================================
    // COMMON HELPERS
    // =====================================================

    private function validateId(
        int $id,
        string $type = 'record'
    ): int {

        if (
            $id <= 0
        ) {

            throw new InvalidArgumentException(
                "Invalid {$type} ID."
            );
        }

        return $id;
    }

    private function normalizeMoney(
        mixed $value
    ): float {

        if (
            !is_numeric($value)
        ) {

            throw new InvalidArgumentException(
                'Invalid monetary value.'
            );
        }

        $amount =
            round(
                (float) $value,
                2
            );

        if (
            $amount < 0
        ) {

            throw new InvalidArgumentException(
                'Monetary value cannot be negative.'
            );
        }

        return $amount;
    }

    private function nullableString(
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
                "Value cannot exceed {$maxLength} characters."
            );
        }

        return $value;
    }

    private function toBoolean(
        mixed $value
    ): bool {

        if (
            is_bool($value)
        ) {
            return $value;
        }

        if (
            is_int($value)
        ) {
            return $value === 1;
        }

        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }
}
