<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/services/BookingService.php
 *
 * Business logic for:
 * - Creating bookings
 * - Updating bookings
 * - Room availability
 * - Pricing
 * - Confirmation
 * - Check-in / check-out
 * - Cancellation
 * - Calendar bookings
 * - Booking payments
 * - Invoices
 */

require_once __DIR__ . '/../config/database.php';

class BookingService
{
    private PDO $db;

    /**
     * Booking statuses that represent an active room reservation.
     */
    private const ACTIVE_BOOKING_STATUSES = [
        'pending',
        'confirmed',
        'checked_in',
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->getDatabaseConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function getAll(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(
            100,
            max(1, (int) ($filters['limit'] ?? 20))
        );

        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        $this->applyFilters(
            $filters,
            $where,
            $params
        );

        $whereSql = '';

        if (!empty($where)) {
            $whereSql = ' WHERE ' . implode(' AND ', $where);
        }

        /*
         * Count records.
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

        $countStatement = $this->db->prepare($countSql);
        $countStatement->execute($params);

        $total = (int) $countStatement->fetchColumn();

        /*
         * Get records.
         */
        $sql = "
            SELECT
                b.*,

                g.first_name AS guest_first_name,
                g.last_name AS guest_last_name,
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

            LIMIT :limit OFFSET :offset
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

        $items = $statement->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
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
    | GET BOOKING BY ID
    |--------------------------------------------------------------------------
    */

    public function getById(int $id): ?array
    {
        $sql = "
            SELECT
                b.*,

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

        $statement = $this->db->prepare($sql);

        $statement->execute([
            ':id' => $id,
        ]);

        $booking = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return null;
        }

        /*
         * Include payment summary.
         */
        $booking['payment_summary'] = $this->getPaymentSummary($id);

        return $booking;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE BOOKING
    |--------------------------------------------------------------------------
    */

    public function create(array $data): array
    {
        $this->validateBookingData($data);

        $guestId = (int) $data['guest_id'];
        $roomId = (int) $data['room_id'];

        $checkIn = trim((string) $data['check_in']);
        $checkOut = trim((string) $data['check_out']);

        $adults = max(
            1,
            (int) ($data['adults'] ?? $data['number_of_guests'] ?? 1)
        );

        $children = max(
            0,
            (int) ($data['children'] ?? 0)
        );

        $numberOfGuests = $adults + $children;

        $this->assertGuestExists($guestId);

        /*
         * Lock the room row during the transaction.
         * This reduces the chance of concurrent double bookings.
         */
        $this->db->beginTransaction();

        try {
            $room = $this->getRoomForUpdate($roomId);

            if (!$room) {
                throw new RuntimeException(
                    'Room not found.'
                );
            }

            if ($room['status'] === 'maintenance') {
                throw new RuntimeException(
                    'This room is currently under maintenance.'
                );
            }

            if ($room['status'] === 'inactive') {
                throw new RuntimeException(
                    'This room is inactive.'
                );
            }

            if ($numberOfGuests > (int) $room['max_guests']) {
                throw new InvalidArgumentException(
                    'The selected room cannot accommodate the requested number of guests.'
                );
            }

            if (!$this->isRoomAvailable(
                $roomId,
                $checkIn,
                $checkOut
            )) {
                throw new RuntimeException(
                    'The selected room is not available for the requested dates.'
                );
            }

            $numberOfNights = $this->calculateNights(
                $checkIn,
                $checkOut
            );

            $roomRate = (float) $room['price_per_night'];

            $subtotal = round(
                $roomRate * $numberOfNights,
                2
            );

            $discount = max(
                0,
                (float) ($data['discount'] ?? 0)
            );

            if ($discount > $subtotal) {
                $discount = $subtotal;
            }

            $taxRate = max(
                0,
                (float) ($data['tax_rate'] ?? 0)
            );

            /*
             * If a direct tax amount is supplied, use it.
             * Otherwise calculate tax from tax_rate.
             */
            if (array_key_exists('tax', $data)) {
                $tax = max(
                    0,
                    (float) $data['tax']
                );
            } else {
                $taxableAmount = $subtotal - $discount;

                $tax = round(
                    $taxableAmount * ($taxRate / 100),
                    2
                );
            }

            $totalAmount = round(
                ($subtotal - $discount) + $tax,
                2
            );

            $bookingReference = $this->generateBookingReference();

            $status = $data['status'] ?? 'pending';

            if (!in_array(
                $status,
                [
                    'pending',
                    'confirmed',
                ],
                true
            )) {
                $status = 'pending';
            }

            $paymentStatus = $data['payment_status'] ?? 'unpaid';

            if (!in_array(
                $paymentStatus,
                [
                    'unpaid',
                    'partial',
                    'paid',
                    'refunded',
                    'failed',
                ],
                true
            )) {
                $paymentStatus = 'unpaid';
            }

            $sql = "
                INSERT INTO bookings (
                    booking_reference,
                    guest_id,
                    room_id,
                    check_in,
                    check_out,
                    number_of_guests,
                    adults,
                    children,
                    number_of_nights,
                    room_rate,
                    subtotal,
                    discount,
                    tax,
                    total_amount,
                    currency,
                    status,
                    payment_status,
                    source,
                    special_requests,
                    notes
                ) VALUES (
                    :booking_reference,
                    :guest_id,
                    :room_id,
                    :check_in,
                    :check_out,
                    :number_of_guests,
                    :adults,
                    :children,
                    :number_of_nights,
                    :room_rate,
                    :subtotal,
                    :discount,
                    :tax,
                    :total_amount,
                    :currency,
                    :status,
                    :payment_status,
                    :source,
                    :special_requests,
                    :notes
                )
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ':booking_reference' => $bookingReference,
                ':guest_id' => $guestId,
                ':room_id' => $roomId,
                ':check_in' => $checkIn,
                ':check_out' => $checkOut,
                ':number_of_guests' => $numberOfGuests,
                ':adults' => $adults,
                ':children' => $children,
                ':number_of_nights' => $numberOfNights,
                ':room_rate' => $roomRate,
                ':subtotal' => $subtotal,
                ':discount' => $discount,
                ':tax' => $tax,
                ':total_amount' => $totalAmount,
                ':currency' => $data['currency'] ?? $room['currency'] ?? 'USD',
                ':status' => $status,
                ':payment_status' => $paymentStatus,
                ':source' => $data['source'] ?? null,
                ':special_requests' => $data['special_requests'] ?? null,
                ':notes' => $data['notes'] ?? null,
            ]);

            $bookingId = (int) $this->db->lastInsertId();

            $this->db->commit();

            $booking = $this->getById($bookingId);

            if (!$booking) {
                throw new RuntimeException(
                    'Booking was created but could not be retrieved.'
                );
            }

            return $booking;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE BOOKING
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): array {
        $existing = $this->getById($id);

        if (!$existing) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        if (in_array(
            $existing['status'],
            [
                'cancelled',
                'completed',
                'checked_out',
                'no_show',
            ],
            true
        )) {
            throw new RuntimeException(
                'This booking cannot be modified in its current status.'
            );
        }

        $guestId = isset($data['guest_id'])
            ? (int) $data['guest_id']
            : (int) $existing['guest_id'];

        $roomId = isset($data['room_id'])
            ? (int) $data['room_id']
            : (int) $existing['room_id'];

        $checkIn = $data['check_in']
            ?? $existing['check_in'];

        $checkOut = $data['check_out']
            ?? $existing['check_out'];

        $this->validateDates(
            $checkIn,
            $checkOut
        );

        $this->assertGuestExists($guestId);

        $this->db->beginTransaction();

        try {
            $room = $this->getRoomForUpdate($roomId);

            if (!$room) {
                throw new RuntimeException(
                    'Room not found.'
                );
            }

            if ($room['status'] === 'maintenance') {
                throw new RuntimeException(
                    'This room is under maintenance.'
                );
            }

            if ($this->hasOverlappingBooking(
                $roomId,
                $checkIn,
                $checkOut,
                $id
            )) {
                throw new RuntimeException(
                    'The selected room is not available for the requested dates.'
                );
            }

            $adults = max(
                1,
                (int) (
                    $data['adults']
                    ?? $existing['adults']
                )
            );

            $children = max(
                0,
                (int) (
                    $data['children']
                    ?? $existing['children']
                )
            );

            $numberOfGuests = $adults + $children;

            if ($numberOfGuests > (int) $room['max_guests']) {
                throw new InvalidArgumentException(
                    'The selected room cannot accommodate the requested number of guests.'
                );
            }

            $numberOfNights = $this->calculateNights(
                $checkIn,
                $checkOut
            );

            $roomRate = isset($data['room_rate'])
                ? max(0, (float) $data['room_rate'])
                : (float) $room['price_per_night'];

            $subtotal = round(
                $roomRate * $numberOfNights,
                2
            );

            $discount = array_key_exists(
                'discount',
                $data
            )
                ? max(0, (float) $data['discount'])
                : (float) $existing['discount'];

            $discount = min(
                $discount,
                $subtotal
            );

            if (array_key_exists('tax', $data)) {
                $tax = max(
                    0,
                    (float) $data['tax']
                );
            } else {
                $tax = (float) $existing['tax'];
            }

            $totalAmount = round(
                ($subtotal - $discount) + $tax,
                2
            );

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
            ];

            $values = [
                'guest_id' => $guestId,
                'room_id' => $roomId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'number_of_guests' => $numberOfGuests,
                'adults' => $adults,
                'children' => $children,
                'number_of_nights' => $numberOfNights,
                'room_rate' => $roomRate,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'currency' => $data['currency']
                    ?? $existing['currency'],
                'status' => $data['status']
                    ?? $existing['status'],
                'payment_status' => $data['payment_status']
                    ?? $existing['payment_status'],
                'source' => $data['source']
                    ?? $existing['source'],
                'special_requests' => $data['special_requests']
                    ?? $existing['special_requests'],
                'notes' => $data['notes']
                    ?? $existing['notes'],
            ];

            /*
             * Validate status if supplied.
             */
            $allowedStatuses = [
                'pending',
                'confirmed',
                'checked_in',
                'checked_out',
                'completed',
                'cancelled',
                'no_show',
            ];

            if (!in_array(
                $values['status'],
                $allowedStatuses,
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid booking status.'
                );
            }

            $allowedPaymentStatuses = [
                'unpaid',
                'partial',
                'paid',
                'refunded',
                'failed',
            ];

            if (!in_array(
                $values['payment_status'],
                $allowedPaymentStatuses,
                true
            )) {
                throw new InvalidArgumentException(
                    'Invalid payment status.'
                );
            }

            $setParts = [];

            foreach ($allowedFields as $field) {
                $setParts[] = "`{$field}` = :{$field}";
            }

            $sql = "
                UPDATE bookings
                SET " . implode(', ', $setParts) . "
                WHERE id = :id
            ";

            $statement = $this->db->prepare($sql);

            $values['id'] = $id;

            $statement->execute(
                array_combine(
                    array_map(
                        static fn ($key) => ':' . $key,
                        array_keys($values)
                    ),
                    array_values($values)
                )
            );

            $this->db->commit();

            $booking = $this->getById($id);

            if (!$booking) {
                throw new RuntimeException(
                    'Booking could not be retrieved after update.'
                );
            }

            return $booking;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE BOOKING
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): array
    {
        $booking = $this->getById($id);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        /*
         * Prefer cancellation when the booking has financial history.
         */
        $paymentSummary = $this->getPaymentSummary($id);

        if (($paymentSummary['payment_count'] ?? 0) > 0) {
            $this->cancel(
                $id,
                'Booking cancelled through the delete operation.'
            );

            return [
                'id' => $id,
                'cancelled' => true,
            ];
        }

        $statement = $this->db->prepare(
            'DELETE FROM bookings WHERE id = :id'
        );

        $statement->execute([
            ':id' => $id,
        ]);

        if ($statement->rowCount() === 0) {
            throw new RuntimeException(
                'Booking could not be deleted.'
            );
        }

        return [
            'id' => $id,
            'deleted' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM
    |--------------------------------------------------------------------------
    */

    public function confirm(int $id): array
    {
        $booking = $this->getById($id);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        if ($booking['status'] !== 'pending') {
            throw new RuntimeException(
                'Only pending bookings can be confirmed.'
            );
        }

        if (!$this->isRoomAvailable(
            (int) $booking['room_id'],
            $booking['check_in'],
            $booking['check_out'],
            $id
        )) {
            throw new RuntimeException(
                'The room is no longer available for this booking.'
            );
        }

        $statement = $this->db->prepare("
            UPDATE bookings
            SET status = 'confirmed'
            WHERE id = :id
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        return $this->getById($id);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK IN
    |--------------------------------------------------------------------------
    */

    public function checkIn(
        int $id,
        array $data = []
    ): array {
        $booking = $this->getById($id);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        if (!in_array(
            $booking['status'],
            [
                'confirmed',
                'pending',
            ],
            true
        )) {
            throw new RuntimeException(
                'This booking cannot be checked in.'
            );
        }

        $today = new DateTimeImmutable(
            'today'
        );

        $checkInDate = new DateTimeImmutable(
            $booking['check_in']
        );

        if ($today < $checkInDate) {
            throw new RuntimeException(
                'The check-in date has not arrived yet.'
            );
        }

        if (!$this->isRoomAvailable(
            (int) $booking['room_id'],
            $booking['check_in'],
            $booking['check_out'],
            $id
        )) {
            throw new RuntimeException(
                'The room is not available for check-in.'
            );
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare("
                UPDATE bookings
                SET status = 'checked_in'
                WHERE id = :id
            ");

            $statement->execute([
                ':id' => $id,
            ]);

            /*
             * Mark the room as occupied.
             */
            $roomStatement = $this->db->prepare("
                UPDATE rooms
                SET
                    status = 'occupied',
                    is_available = 0
                WHERE id = :room_id
            ");

            $roomStatement->execute([
                ':room_id' => $booking['room_id'],
            ]);

            $this->db->commit();

            return $this->getById($id);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK OUT
    |--------------------------------------------------------------------------
    */

    public function checkOut(
        int $id,
        array $data = []
    ): array {
        $booking = $this->getById($id);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        if ($booking['status'] !== 'checked_in') {
            throw new RuntimeException(
                'Only checked-in bookings can be checked out.'
            );
        }

        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare("
                UPDATE bookings
                SET status = 'checked_out'
                WHERE id = :id
            ");

            $statement->execute([
                ':id' => $id,
            ]);

            /*
             * Release the room.
             *
             * A future confirmed booking may exist, but the room is
             * physically available after this guest checks out.
             */
            $roomStatement = $this->db->prepare("
                UPDATE rooms
                SET
                    status = 'available',
                    is_available = 1
                WHERE id = :room_id
                  AND status = 'occupied'
            ");

            $roomStatement->execute([
                ':room_id' => $booking['room_id'],
            ]);

            /*
             * If the booking is fully paid, mark it completed.
             */
            $paymentSummary = $this->getPaymentSummary($id);

            if (
                ($paymentSummary['remaining_amount'] ?? 0) <= 0
                && ($paymentSummary['payment_count'] ?? 0) > 0
            ) {
                $completedStatement = $this->db->prepare("
                    UPDATE bookings
                    SET
                        status = 'completed',
                        payment_status = 'paid'
                    WHERE id = :id
                ");

                $completedStatement->execute([
                    ':id' => $id,
                ]);
            }

            $this->db->commit();

            return $this->getById($id);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL BOOKING
    |--------------------------------------------------------------------------
    */

    public function cancel(
        int $id,
        ?string $reason = null
    ): array {
        $booking = $this->getById($id);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        if (in_array(
            $booking['status'],
            [
                'cancelled',
                'completed',
                'checked_out',
            ],
            true
        )) {
            throw new RuntimeException(
                'This booking cannot be cancelled.'
            );
        }

        $this->db->beginTransaction();

        try {
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

            /*
             * Release the room if it is currently occupied by
             * this booking.
             */
            $roomStatement = $this->db->prepare("
                UPDATE rooms
                SET
                    status = 'available',
                    is_available = 1
                WHERE id = :room_id
                  AND status = 'occupied'
            ");

            $roomStatement->execute([
                ':room_id' => $booking['room_id'],
            ]);

            $this->db->commit();

            return $this->getById($id);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ROOM AVAILABILITY
    |--------------------------------------------------------------------------
    */

    public function checkAvailability(
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $excludeBookingId = null
    ): array {
        $this->validateDates(
            $checkIn,
            $checkOut
        );

        $room = $this->getRoom($roomId);

        if (!$room) {
            throw new RuntimeException(
                'Room not found.'
            );
        }

        $available = $this->isRoomAvailable(
            $roomId,
            $checkIn,
            $checkOut,
            $excludeBookingId
        );

        return [
            'room_id' => $roomId,
            'room_number' => $room['room_number'],
            'room_type' => $room['room_type'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'available' => $available,
        ];
    }

    public function isRoomAvailable(
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $excludeBookingId = null
    ): bool {
        $room = $this->getRoom($roomId);

        if (!$room) {
            return false;
        }

        if (
            $room['status'] === 'maintenance'
            || $room['status'] === 'inactive'
        ) {
            return false;
        }

        return !$this->hasOverlappingBooking(
            $roomId,
            $checkIn,
            $checkOut,
            $excludeBookingId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CALENDAR
    |--------------------------------------------------------------------------
    */

    public function getCalendarBookings(
        ?string $start = null,
        ?string $end = null
    ): array {
        $where = [
            "b.status NOT IN ('cancelled', 'no_show')",
        ];

        $params = [];

        if ($start !== null) {
            $where[] = 'b.check_out >= :start';
            $params[':start'] = $start;
        }

        if ($end !== null) {
            $where[] = 'b.check_in <= :end';
            $params[':end'] = $end;
        }

        $sql = "
            SELECT
                b.id,
                b.booking_reference,
                b.guest_id,
                b.room_id,
                b.check_in,
                b.check_out,
                b.status,
                b.total_amount,

                CONCAT(
                    g.first_name,
                    ' ',
                    g.last_name
                ) AS guest_name,

                r.room_number,
                r.room_type

            FROM bookings b

            INNER JOIN guests g
                ON g.id = b.guest_id

            INNER JOIN rooms r
                ON r.id = b.room_id

            WHERE " . implode(' AND ', $where) . "

            ORDER BY b.check_in ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */

    public function getPayments(int $bookingId): array
    {
        $booking = $this->getById($bookingId);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        $statement = $this->db->prepare("
            SELECT *
            FROM payments
            WHERE booking_id = :booking_id
            ORDER BY created_at DESC
        ");

        $statement->execute([
            ':booking_id' => $bookingId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE
    |--------------------------------------------------------------------------
    */

    public function getInvoice(int $bookingId): array
    {
        $booking = $this->getById($bookingId);

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        $payments = $this->getPayments(
            $bookingId
        );

        $summary = $this->getPaymentSummary(
            $bookingId
        );

        return [
            'invoice_number' => $this->getInvoiceNumber(
                $booking,
                $payments
            ),

            'booking' => $booking,

            'guest' => [
                'id' => $booking['guest_id'],
                'name' => trim(
                    ($booking['guest_first_name'] ?? '')
                    . ' '
                    . ($booking['guest_last_name'] ?? '')
                ),
                'email' => $booking['guest_email'] ?? null,
                'phone' => $booking['guest_phone'] ?? null,
            ],

            'room' => [
                'id' => $booking['room_id'],
                'number' => $booking['room_number'] ?? null,
                'type' => $booking['room_type'] ?? null,
            ],

            'charges' => [
                'room_rate' => (float) $booking['room_rate'],
                'nights' => (int) $booking['number_of_nights'],
                'subtotal' => (float) $booking['subtotal'],
                'discount' => (float) $booking['discount'],
                'tax' => (float) $booking['tax'],
                'total' => (float) $booking['total_amount'],
            ],

            'payments' => $payments,

            'payment_summary' => $summary,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT SUMMARY
    |--------------------------------------------------------------------------
    */

    public function getPaymentSummary(
        int $bookingId
    ): array {
        $statement = $this->db->prepare("
            SELECT
                COUNT(*) AS payment_count,

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
                ) AS refunded_amount

            FROM payments

            WHERE booking_id = :booking_id
        ");

        $statement->execute([
            ':booking_id' => $bookingId,
        ]);

        $row = $statement->fetch(
            PDO::FETCH_ASSOC
        ) ?: [];

        $bookingStatement = $this->db->prepare("
            SELECT total_amount
            FROM bookings
            WHERE id = :id
            LIMIT 1
        ");

        $bookingStatement->execute([
            ':id' => $bookingId,
        ]);

        $totalAmount = (float) (
            $bookingStatement->fetchColumn() ?? 0
        );

        $paidAmount = (float) (
            $row['paid_amount'] ?? 0
        );

        $refundedAmount = (float) (
            $row['refunded_amount'] ?? 0
        );

        return [
            'payment_count' => (int) (
                $row['payment_count'] ?? 0
            ),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'refunded_amount' => $refundedAmount,
            'remaining_amount' => max(
                0,
                round(
                    $totalAmount - $paidAmount,
                    2
                )
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

    private function applyFilters(
        array $filters,
        array &$where,
        array &$params
    ): void {
        if (!empty($filters['status'])) {
            $where[] = 'b.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = 'b.payment_status = :payment_status';
            $params[':payment_status'] = $filters['payment_status'];
        }

        if (!empty($filters['guest_id'])) {
            $where[] = 'b.guest_id = :guest_id';
            $params[':guest_id'] = (int) $filters['guest_id'];
        }

        if (!empty($filters['room_id'])) {
            $where[] = 'b.room_id = :room_id';
            $params[':room_id'] = (int) $filters['room_id'];
        }

        if (!empty($filters['check_in'])) {
            $where[] = 'b.check_in >= :filter_check_in';
            $params[':filter_check_in'] = $filters['check_in'];
        }

        if (!empty($filters['check_out'])) {
            $where[] = 'b.check_out <= :filter_check_out';
            $params[':filter_check_out'] = $filters['check_out'];
        }

        if (!empty($filters['search'])) {
            $where[] = "
                (
                    b.booking_reference LIKE :search
                    OR g.first_name LIKE :search
                    OR g.last_name LIKE :search
                    OR g.email LIKE :search
                    OR r.room_number LIKE :search
                )
            ";

            $params[':search'] = '%' . $filters['search'] . '%';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OVERLAPPING BOOKING CHECK
    |--------------------------------------------------------------------------
    |
    | Two bookings overlap when:
    |
    | existing.check_in < requested.check_out
    | AND
    | existing.check_out > requested.check_in
    |
    */

    private function hasOverlappingBooking(
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
                AND id <> :exclude_booking_id
            ";

            $params[':exclude_booking_id'] = $excludeBookingId;
        }

        $statement = $this->db->prepare($sql);

        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | ROOM LOOKUP
    |--------------------------------------------------------------------------
    */

    private function getRoom(
        int $roomId
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM rooms
            WHERE id = :id
            LIMIT 1
        ");

        $statement->execute([
            ':id' => $roomId,
        ]);

        $room = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $room ?: null;
    }

    private function getRoomForUpdate(
        int $roomId
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM rooms
            WHERE id = :id
            LIMIT 1
            FOR UPDATE
        ");

        $statement->execute([
            ':id' => $roomId,
        ]);

        $room = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $room ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | GUEST VALIDATION
    |--------------------------------------------------------------------------
    */

    private function assertGuestExists(
        int $guestId
    ): void {
        $statement = $this->db->prepare("
            SELECT id
            FROM guests
            WHERE id = :id
            LIMIT 1
        ");

        $statement->execute([
            ':id' => $guestId,
        ]);

        if (!$statement->fetchColumn()) {
            throw new InvalidArgumentException(
                'Guest not found.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BOOKING VALIDATION
    |--------------------------------------------------------------------------
    */

    private function validateBookingData(
        array $data
    ): void {
        foreach (
            [
                'guest_id',
                'room_id',
                'check_in',
                'check_out',
            ] as $field
        ) {
            if (
                !isset($data[$field])
                || $data[$field] === ''
                || $data[$field] === null
            ) {
                throw new InvalidArgumentException(
                    "{$field} is required."
                );
            }
        }

        if ((int) $data['guest_id'] <= 0) {
            throw new InvalidArgumentException(
                'Invalid guest_id.'
            );
        }

        if ((int) $data['room_id'] <= 0) {
            throw new InvalidArgumentException(
                'Invalid room_id.'
            );
        }

        $this->validateDates(
            (string) $data['check_in'],
            (string) $data['check_out']
        );
    }

    private function validateDates(
        string $checkIn,
        string $checkOut
    ): void {
        $checkInDate = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $checkIn
        );

        $checkOutDate = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $checkOut
        );

        $checkInErrors = DateTimeImmutable::getLastErrors();

        if ($checkInErrors === false) {
            $checkInErrors = [
                'warning_count' => 0,
                'error_count' => 0,
            ];
        }

        if (
            !$checkInDate
            || $checkInErrors['warning_count'] > 0
            || $checkInErrors['error_count'] > 0
        ) {
            throw new InvalidArgumentException(
                'Invalid check-in date. Expected YYYY-MM-DD.'
            );
        }

        $checkOutErrors = DateTimeImmutable::getLastErrors();

        if ($checkOutErrors === false) {
            $checkOutErrors = [
                'warning_count' => 0,
                'error_count' => 0,
            ];
        }

        if (
            !$checkOutDate
            || $checkOutErrors['warning_count'] > 0
            || $checkOutErrors['error_count'] > 0
        ) {
            throw new InvalidArgumentException(
                'Invalid check-out date. Expected YYYY-MM-DD.'
            );
        }

        if ($checkOutDate <= $checkInDate) {
            throw new InvalidArgumentException(
                'Check-out date must be after check-in date.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NIGHT CALCULATION
    |--------------------------------------------------------------------------
    */

    private function calculateNights(
        string $checkIn,
        string $checkOut
    ): int {
        $start = new DateTimeImmutable(
            $checkIn
        );

        $end = new DateTimeImmutable(
            $checkOut
        );

        return (int) $start->diff($end)->days;
    }

    /*
    |--------------------------------------------------------------------------
    | BOOKING REFERENCE
    |--------------------------------------------------------------------------
    */

    private function generateBookingReference(): string
    {
        do {
            $reference =
                'BK-'
                . date('Y')
                . '-'
                . strtoupper(
                    bin2hex(
                        random_bytes(4)
                    )
                );

            $statement = $this->db->prepare("
                SELECT COUNT(*)
                FROM bookings
                WHERE booking_reference = :reference
            ");

            $statement->execute([
                ':reference' => $reference,
            ]);

            $exists = (int) $statement->fetchColumn() > 0;
        } while ($exists);

        return $reference;
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE NUMBER
    |--------------------------------------------------------------------------
    */

    private function getInvoiceNumber(
        array $booking,
        array $payments
    ): ?string {
        foreach ($payments as $payment) {
            if (!empty($payment['invoice_number'])) {
                return $payment['invoice_number'];
            }
        }

        return !empty($booking['booking_reference'])
            ? 'INV-' . $booking['booking_reference']
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    private function getDatabaseConnection(): PDO
    {
        /*
         * Supports the common Database::getConnection()
         * pattern used by this project.
         */
        if (
            class_exists('Database')
            && method_exists(
                'Database',
                'getConnection'
            )
        ) {
            $connection = Database::getConnection();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        /*
         * Also support a database.php file exposing
         * a getDatabaseConnection() function.
         */
        if (
            function_exists(
                'getDatabaseConnection'
            )
        ) {
            $connection = getDatabaseConnection();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        /*
         * Support a $pdo variable from database.php.
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
