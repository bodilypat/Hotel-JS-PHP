<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/services/PaymentService.php
 *
 * Handles payment business logic.
 */

require_once __DIR__ . '/../config/database.php';

class PaymentService
{
    private PDO $db;

    /**
     * Allowed payment statuses.
     */
    private const STATUSES = [
        'pending',
        'processing',
        'completed',
        'failed',
        'refunded',
        'partially_refunded',
        'voided',
    ];

    /**
     * Statuses that effectively finalize a payment lifecycle.
     */
    private const FINAL_STATUSES = [
        'refunded',
        'partially_refunded',
        'voided',
    ];

    /**
     * Allowed payment methods.
     */
    private const PAYMENT_METHODS = [
        'cash',
        'card',
        'bank_transfer',
        'online',
        'mobile_payment',
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->getDatabaseConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL PAYMENTS
    |--------------------------------------------------------------------------
    */

    public function getAll(
        array $filters = []
    ): array {
        $page = max(
            1,
            (int) ($filters['page'] ?? 1)
        );

        $limit = min(
            100,
            max(
                1,
                (int) ($filters['limit'] ?? 20)
            )
        );

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
            $whereSql =
                'WHERE ' . implode(
                    ' AND ',
                    $where
                );
        }

        /*
         * Total records.
         */
        $countSql = "
            SELECT COUNT(*)
            FROM payments p

            LEFT JOIN bookings b
                ON b.id = p.booking_id

            LEFT JOIN guests g
                ON g.id = b.guest_id

            {$whereSql}
        ";

        $countStatement =
            $this->db->prepare($countSql);

        $countStatement->execute(
            $params
        );

        $total = (int)
            $countStatement->fetchColumn();

        /*
         * Payment records.
         */
        $sql = "
            SELECT
                p.*,

                b.booking_reference,
                b.guest_id,
                b.room_id,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                g.email AS guest_email,
                g.phone AS guest_phone

            FROM payments p

            LEFT JOIN bookings b
                ON b.id = p.booking_id

            LEFT JOIN guests g
                ON g.id = b.guest_id

            {$whereSql}

            ORDER BY p.created_at DESC

            LIMIT :limit
            OFFSET :offset
        ";

        $statement =
            $this->db->prepare($sql);

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
                    ? (int) ceil(
                        $total / $limit
                    )
                    : 0,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET PAYMENT BY ID
    |--------------------------------------------------------------------------
    */

    public function getById(
        int $id
    ): ?array {
        $statement = $this->db->prepare("
            SELECT
                p.*,

                b.booking_reference,
                b.guest_id,
                b.room_id,
                b.check_in,
                b.check_out,
                b.total_amount AS booking_total,

                CONCAT(
                    COALESCE(g.first_name, ''),
                    ' ',
                    COALESCE(g.last_name, '')
                ) AS guest_name,

                g.email AS guest_email,
                g.phone AS guest_phone

            FROM payments p

            LEFT JOIN bookings b
                ON b.id = p.booking_id

            LEFT JOIN guests g
                ON g.id = b.guest_id

            WHERE p.id = :id

            LIMIT 1
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        $payment = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $payment ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PAYMENT
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): array {
        $this->validateCreateData(
            $data
        );

        $bookingId = (int)
            $data['booking_id'];

        $booking = $this->getBooking(
            $bookingId
        );

        if (!$booking) {
            throw new RuntimeException(
                'Booking not found.'
            );
        }

        $amount = $this->normalizeAmount(
            $data['amount']
        );

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Payment amount must be greater than zero.'
            );
        }

        $paymentMethod =
            $this->normalizePaymentMethod(
                $data['payment_method']
            );

        $currency = strtoupper(
            trim(
                (string) (
                    $data['currency']
                    ?? $booking['currency']
                    ?? 'USD'
                )
            )
        );

        $transactionId =
            $this->normalizeTransactionId(
                $data['transaction_id'] ?? null
            );

        /*
         * Prevent duplicate transaction IDs.
         */
        if ($transactionId !== null) {
            $existing =
                $this->findByTransactionId(
                    $transactionId
                );

            if ($existing) {
                throw new RuntimeException(
                    'A payment with this transaction ID already exists.'
                );
            }
        }

        $status =
            $this->normalizeStatus(
                $data['status'] ?? 'completed'
            );

        $this->db->beginTransaction();

        try {
            /*
             * Generate payment reference.
             */
            $paymentReference =
                $this->generatePaymentReference();

            /*
             * Insert payment.
             */
            $paymentId = $this->insertPayment(
                [
                    'payment_reference' =>
                        $paymentReference,

                    'booking_id' =>
                        $bookingId,

                    'amount' =>
                        $amount,

                    'currency' =>
                        $currency,

                    'payment_method' =>
                        $paymentMethod,

                    'transaction_id' =>
                        $transactionId,

                    'status' =>
                        $status,

                    'notes' =>
                        $data['notes'] ?? null,

                    'paid_at' =>
                        $status === 'completed'
                            ? (
                                $data['paid_at']
                                ?? date('Y-m-d H:i:s')
                            )
                            : null,
                ]
            );

            /*
             * Update booking payment status.
             */
            $this->syncBookingPaymentStatus(
                $bookingId
            );

            $this->db->commit();

            $payment =
                $this->getById($paymentId);

            if (!$payment) {
                throw new RuntimeException(
                    'Payment was created but could not be retrieved.'
                );
            }

            return $payment;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PAYMENT
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): array {
        $payment = $this->getById($id);

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        if (
            in_array(
                $payment['status'],
                self::FINAL_STATUSES,
                true
            )
        ) {
            throw new RuntimeException(
                'A refunded or voided payment cannot be edited.'
            );
        }

        $allowedFields = [
            'payment_method',
            'transaction_id',
            'status',
            'currency',
            'notes',
            'paid_at',
        ];

        $updates = [];
        $params = [
            ':id' => $id,
        ];

        foreach ($allowedFields as $field) {
            if (!array_key_exists(
                $field,
                $data
            )) {
                continue;
            }

            $value = $data[$field];

            if ($field === 'payment_method') {
                $value =
                    $this->normalizePaymentMethod(
                        $value
                    );
            }

            if ($field === 'status') {
                $value =
                    $this->normalizeStatus(
                        $value
                    );
            }

            if ($field === 'currency') {
                $value = strtoupper(
                    trim((string) $value)
                );
            }

            if ($field === 'transaction_id') {
                $value =
                    $this->normalizeTransactionId(
                        $value
                    );
            }

            $updates[] =
                "`{$field}` = :{$field}";

            $params[":{$field}"] = $value;
        }

        if (empty($updates)) {
            return $payment;
        }

        $sql = "
            UPDATE payments
            SET " . implode(
                ', ',
                $updates
            ) . "
            WHERE id = :id
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute(
            $params
        );

        $this->syncBookingPaymentStatus(
            (int) $payment['booking_id']
        );

        $updated = $this->getById($id);

        if (!$updated) {
            throw new RuntimeException(
                'Payment could not be retrieved after update.'
            );
        }

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PAYMENT
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $id
    ): array {
        $payment = $this->getById($id);

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        if (
            in_array(
                $payment['status'],
                [
                    'completed',
                    'partially_refunded',
                    'refunded',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Completed payments cannot be deleted. Refund the payment instead.'
            );
        }

        $statement = $this->db->prepare("
            DELETE FROM payments
            WHERE id = :id
        ");

        $statement->execute([
            ':id' => $id,
        ]);

        $this->syncBookingPaymentStatus(
            (int) $payment['booking_id']
        );

        return [
            'id' => $id,
            'deleted' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | REFUND PAYMENT
    |--------------------------------------------------------------------------
    */

    public function refund(
        int $id,
        array $data = []
    ): array {
        $payment = $this->getById($id);

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        $currentStatus =
            (string) $payment['status'];

        if (
            !in_array(
                $currentStatus,
                [
                    'completed',
                    'partially_refunded',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only completed or partially refunded payments can be refunded.'
            );
        }

        $paymentAmount =
            (float) $payment['amount'];

        $alreadyRefunded =
            (float) (
                $payment['refunded_amount']
                ?? 0
            );

        $remainingRefundable = max(
            0,
            round(
                $paymentAmount
                - $alreadyRefunded,
                2
            )
        );

        if ($remainingRefundable <= 0) {
            throw new RuntimeException(
                'This payment has already been fully refunded.'
            );
        }

        $refundAmount =
            isset($data['amount'])
                ? $this->normalizeAmount(
                    $data['amount']
                )
                : $remainingRefundable;

        if ($refundAmount <= 0) {
            throw new InvalidArgumentException(
                'Refund amount must be greater than zero.'
            );
        }

        if (
            $refundAmount
            > $remainingRefundable
        ) {
            throw new InvalidArgumentException(
                'Refund amount cannot exceed the remaining refundable amount.'
            );
        }

        $newRefundedAmount = round(
            $alreadyRefunded
            + $refundAmount,
            2
        );

        $newStatus =
            $newRefundedAmount >= $paymentAmount
                ? 'refunded'
                : 'partially_refunded';

        $this->db->beginTransaction();

        try {
            /*
             * If the payments table contains refunded_amount,
             * update it directly.
             */
            $statement = $this->db->prepare("
                UPDATE payments
                SET
                    refunded_amount = :refunded_amount,
                    status = :status,
                    refund_reference = :refund_reference,
                    refunded_at = :refunded_at,
                    refund_reason = :refund_reason
                WHERE id = :id
            ");

            $refundReference =
                $this->generateRefundReference();

            $statement->execute([
                ':refunded_amount' =>
                    $newRefundedAmount,

                ':status' =>
                    $newStatus,

                ':refund_reference' =>
                    $refundReference,

                ':refunded_at' =>
                    date('Y-m-d H:i:s'),

                ':refund_reason' =>
                    $data['reason'] ?? null,

                ':id' =>
                    $id,
            ]);

            $this->syncBookingPaymentStatus(
                (int) $payment['booking_id']
            );

            $this->db->commit();

            $updated =
                $this->getById($id);

            if (!$updated) {
                throw new RuntimeException(
                    'Payment could not be retrieved after refund.'
                );
            }

            return $updated;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VOID PAYMENT
    |--------------------------------------------------------------------------
    */

    public function void(
        int $id,
        array $data = []
    ): array {
        $payment = $this->getById($id);

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        if (
            in_array(
                $payment['status'],
                [
                    'refunded',
                    'partially_refunded',
                    'voided',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'This payment cannot be voided.'
            );
        }

        if (
            $payment['status'] === 'completed'
        ) {
            throw new RuntimeException(
                'Completed payments should be refunded instead of voided.'
            );
        }

        $statement = $this->db->prepare("
            UPDATE payments
            SET
                status = 'voided',
                notes = :notes
            WHERE id = :id
        ");

        $existingNotes =
            $payment['notes'] ?? '';

        $reason =
            trim(
                (string) (
                    $data['reason']
                    ?? 'Payment voided.'
                )
            );

        $notes = trim(
            $existingNotes
            . (
                $existingNotes !== ''
                    ? "\n"
                    : ''
            )
            . $reason
        );

        $statement->execute([
            ':id' => $id,
            ':notes' => $notes,
        ]);

        $this->syncBookingPaymentStatus(
            (int) $payment['booking_id']
        );

        $updated =
            $this->getById($id);

        if (!$updated) {
            throw new RuntimeException(
                'Payment could not be retrieved after voiding.'
            );
        }

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE PAYMENT
    |--------------------------------------------------------------------------
    */

    public function complete(
        int $id
    ): array {
        $payment = $this->getById($id);

        if (!$payment) {
            throw new RuntimeException(
                'Payment not found.'
            );
        }

        if (
            $payment['status'] === 'completed'
        ) {
            return $payment;
        }

        if (
            !in_array(
                $payment['status'],
                [
                    'pending',
                    'processing',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only pending or processing payments can be completed.'
            );
        }

        $statement = $this->db->prepare("
            UPDATE payments
            SET
                status = 'completed',
                paid_at = :paid_at
            WHERE id = :id
        ");

        $statement->execute([
            ':id' => $id,
            ':paid_at' => date(
                'Y-m-d H:i:s'
            ),
        ]);

        $this->syncBookingPaymentStatus(
            (int) $payment['booking_id']
        );

        $updated =
            $this->getById($id);

        if (!$updated) {
            throw new RuntimeException(
                'Payment could not be retrieved after completion.'
            );
        }

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS BY BOOKING
    |--------------------------------------------------------------------------
    */

    public function getByBooking(
        int $bookingId
    ): array {
        $booking =
            $this->getBooking($bookingId);

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

        $payments =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return [
            'booking' => $booking,
            'payments' => $payments,
            'summary' =>
                $this->calculatePaymentSummary(
                    $payments,
                    (float) (
                        $booking['total_amount']
                        ?? 0
                    )
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS BY GUEST
    |--------------------------------------------------------------------------
    */

    public function getByGuest(
        int $guestId
    ): array {
        $statement = $this->db->prepare("
            SELECT
                p.*,

                b.booking_reference,
                b.check_in,
                b.check_out

            FROM payments p

            INNER JOIN bookings b
                ON b.id = p.booking_id

            WHERE b.guest_id = :guest_id

            ORDER BY p.created_at DESC
        ");

        $statement->execute([
            ':guest_id' => $guestId,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT SUMMARY
    |--------------------------------------------------------------------------
    */

    public function getSummary(
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

                COUNT(*) AS total_transactions,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'completed'
                            THEN p.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_paid,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'refunded'
                            THEN p.refunded_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_refunded,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'partially_refunded'
                            THEN p.refunded_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS partial_refunds,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'failed'
                            THEN p.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS failed_amount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'voided'
                            THEN p.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS voided_amount

            FROM payments p

            LEFT JOIN bookings b
                ON b.id = p.booking_id

            LEFT JOIN guests g
                ON g.id = b.guest_id

            {$whereSql}
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute(
            $params
        );

        $summary =
            $statement->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        /*
         * Payment methods breakdown.
         */
        $methodSql = "
            SELECT
                p.payment_method,

                COUNT(*) AS transaction_count,

                COALESCE(
                    SUM(p.amount),
                    0
                ) AS amount

            FROM payments p

            LEFT JOIN bookings b
                ON b.id = p.booking_id

            LEFT JOIN guests g
                ON g.id = b.guest_id

            {$whereSql}

            GROUP BY p.payment_method

            ORDER BY amount DESC
        ";

        $methodStatement =
            $this->db->prepare(
                $methodSql
            );

        $methodStatement->execute(
            $params
        );

        $methods =
            $methodStatement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return [
            'total_transactions' =>
                (int) (
                    $summary[
                        'total_transactions'
                    ] ?? 0
                ),

            'total_paid' =>
                (float) (
                    $summary['total_paid']
                    ?? 0
                ),

            'total_refunded' =>
                (float) (
                    $summary['total_refunded']
                    ?? 0
                ),

            'partial_refunds' =>
                (float) (
                    $summary['partial_refunds']
                    ?? 0
                ),

            'failed_amount' =>
                (float) (
                    $summary['failed_amount']
                    ?? 0
                ),

            'voided_amount' =>
                (float) (
                    $summary['voided_amount']
                    ?? 0
                ),

            'net_revenue' =>
                (float) (
                    $summary['total_paid'] ?? 0
                )
                - (
                    (float) (
                        $summary['total_refunded']
                        ?? 0
                    )
                    + (float) (
                        $summary['partial_refunds']
                        ?? 0
                    )
                ),

            'by_method' => $methods,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHODS
    |--------------------------------------------------------------------------
    */

    public function getPaymentMethods(): array
    {
        return self::PAYMENT_METHODS;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY TRANSACTION ID
    |--------------------------------------------------------------------------
    */

    private function findByTransactionId(
        string $transactionId
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM payments
            WHERE LOWER(transaction_id) = LOWER(:transaction_id)
            LIMIT 1
        ");

        $statement->execute([
            ':transaction_id' =>
                $transactionId,
        ]);

        $payment =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $payment ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | GET BOOKING
    |--------------------------------------------------------------------------
    */

    private function getBooking(
        int $bookingId
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM bookings
            WHERE id = :id
            LIMIT 1
        ");

        $statement->execute([
            ':id' => $bookingId,
        ]);

        $booking =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return $booking ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT PAYMENT
    |--------------------------------------------------------------------------
    */

    private function insertPayment(
        array $data
    ): int {
        $sql = "
            INSERT INTO payments (
                payment_reference,
                booking_id,
                amount,
                currency,
                payment_method,
                transaction_id,
                status,
                notes,
                paid_at
            )
            VALUES (
                :payment_reference,
                :booking_id,
                :amount,
                :currency,
                :payment_method,
                :transaction_id,
                :status,
                :notes,
                :paid_at
            )
        ";

        $statement =
            $this->db->prepare($sql);

        $statement->execute([
            ':payment_reference' =>
                $data['payment_reference'],

            ':booking_id' =>
                $data['booking_id'],

            ':amount' =>
                $data['amount'],

            ':currency' =>
                $data['currency'],

            ':payment_method' =>
                $data['payment_method'],

            ':transaction_id' =>
                $data['transaction_id'],

            ':status' =>
                $data['status'],

            ':notes' =>
                $data['notes'],

            ':paid_at' =>
                $data['paid_at'],
        ]);

        return (int)
            $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC BOOKING PAYMENT STATUS
    |--------------------------------------------------------------------------
    */

    private function syncBookingPaymentStatus(
        int $bookingId
    ): void {
        $booking =
            $this->getBooking($bookingId);

        if (!$booking) {
            return;
        }

        $statement = $this->db->prepare("
            SELECT

                COALESCE(
                    SUM(
                        CASE
                            WHEN status IN (
                                'completed',
                                'partially_refunded'
                            )
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS paid_amount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'refunded' THEN amount
                            WHEN status = 'partially_refunded' THEN COALESCE(refunded_amount, 0)
                            ELSE 0
                        END
                    ),
                    0
                ) AS refunded_amount

            FROM payments

            WHERE booking_id = :booking_id
        ");

        $statement->execute([
            ':booking_id' => $bookingId,
        ]);

        $summary =
            $statement->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        $paidAmount =
            (float) (
                $summary['paid_amount']
                ?? 0
            );

        $refundedAmount =
            (float) (
                $summary['refunded_amount']
                ?? 0
            );

        $bookingTotal =
            (float) (
                $booking['total_amount']
                ?? 0
            );

        $effectivePaid =
            max(
                0,
                $paidAmount - $refundedAmount
            );

        if ($effectivePaid <= 0) {
            $status = 'unpaid';
        } elseif (
            $effectivePaid < $bookingTotal
        ) {
            $status = 'partial';
        } else {
            $status = 'paid';
        }

        $update = $this->db->prepare("
            UPDATE bookings
            SET payment_status = :status
            WHERE id = :id
        ");

        $update->execute([
            ':status' => $status,
            ':id' => $bookingId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE CREATE DATA
    |--------------------------------------------------------------------------
    */

    private function validateCreateData(
        array $data
    ): void {
        foreach (
            [
                'booking_id',
                'amount',
                'payment_method',
            ] as $field
        ) {
            if (
                !array_key_exists(
                    $field,
                    $data
                )
                || $data[$field] === ''
                || $data[$field] === null
            ) {
                throw new InvalidArgumentException(
                    "The {$field} field is required."
                );
            }
        }

        if (
            !is_numeric(
                $data['booking_id']
            )
            || (int) $data['booking_id'] <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid booking ID.'
            );
        }

        if (
            !is_numeric(
                $data['amount']
            )
        ) {
            throw new InvalidArgumentException(
                'Payment amount must be numeric.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILTERS
    |--------------------------------------------------------------------------
    */

    private function buildFilters(
        array $filters,
        array &$where,
        array &$params
    ): void {
        if (
            isset($filters['booking_id'])
            && $filters['booking_id'] !== ''
        ) {
            $where[] =
                'p.booking_id = :booking_id';

            $params[':booking_id'] =
                (int) $filters['booking_id'];
        }

        if (
            isset($filters['guest_id'])
            && $filters['guest_id'] !== ''
        ) {
            $where[] =
                'b.guest_id = :guest_id';

            $params[':guest_id'] =
                (int) $filters['guest_id'];
        }

        if (
            !empty($filters['status'])
        ) {
            $status = strtolower(trim((string) $filters['status']));

            if (!in_array($status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid payment status.');
            }

            $where[] =
                'p.status = :status';

            $params[':status'] =
                $status;
        }

        if (
            !empty($filters['payment_method'])
        ) {
            $method = strtolower(trim((string) $filters['payment_method']));

            if (!in_array($method, self::PAYMENT_METHODS, true)) {
                throw new InvalidArgumentException('Invalid payment method.');
            }

            $where[] =
                'p.payment_method = :payment_method';

            $params[':payment_method'] =
                $method;
        }

        if (
            !empty($filters['date_from'])
        ) {
            $where[] =
                'DATE(p.created_at) >= :date_from';

            $params[':date_from'] =
                $filters['date_from'];
        }

        if (
            !empty($filters['date_to'])
        ) {
            $where[] =
                'DATE(p.created_at) <= :date_to';

            $params[':date_to'] =
                $filters['date_to'];
        }

        if (
            !empty($filters['search'])
        ) {
            $where[] = "
                (
                    p.payment_reference LIKE :search

                    OR p.transaction_id LIKE :search

                    OR b.booking_reference LIKE :search

                    OR g.first_name LIKE :search

                    OR g.last_name LIKE :search
                )
            ";

            $params[':search'] =
                '%' . $filters['search'] . '%';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE PAYMENT SUMMARY
    |--------------------------------------------------------------------------
    */

    private function calculatePaymentSummary(
        array $payments,
        float $bookingTotal
    ): array {
        $paid = 0.0;
        $refunded = 0.0;

        foreach ($payments as $payment) {
            $status =
                $payment['status'] ?? '';

            $amount =
                (float) (
                    $payment['amount'] ?? 0
                );

            $refund =
                (float) (
                    $payment['refunded_amount']
                    ?? 0
                );

            if (
                in_array(
                    $status,
                    [
                        'completed',
                        'partially_refunded',
                    ],
                    true
                )
            ) {
                $paid += $amount;
            }

            if ($status === 'refunded') {
                $refunded += max($refund, $amount);
            } elseif ($refund > 0) {
                $refunded += $refund;
            }
        }

        $effectivePaid =
            max(
                0,
                $paid - $refunded
            );

        return [
            'booking_total' =>
                round(
                    $bookingTotal,
                    2
                ),

            'paid_amount' =>
                round(
                    $effectivePaid,
                    2
                ),

            'refunded_amount' =>
                round(
                    $refunded,
                    2
                ),

            'remaining_amount' =>
                round(
                    max(
                        0,
                        $bookingTotal
                        - $effectivePaid
                    ),
                    2
                ),

            'payment_status' =>
                $effectivePaid <= 0
                    ? 'unpaid'
                    : (
                        $effectivePaid
                        < $bookingTotal
                            ? 'partial'
                            : 'paid'
                    ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AMOUNT NORMALIZATION
    |--------------------------------------------------------------------------
    */

    private function normalizeAmount(
        mixed $amount
    ): float {
        if (
            !is_numeric($amount)
        ) {
            throw new InvalidArgumentException(
                'Amount must be numeric.'
            );
        }

        $amount = round(
            (float) $amount,
            2
        );

        if ($amount < 0) {
            throw new InvalidArgumentException(
                'Amount cannot be negative.'
            );
        }

        return $amount;
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE PAYMENT METHOD
    |--------------------------------------------------------------------------
    */

    private function normalizePaymentMethod(
        mixed $value
    ): string {
        $normalized = strtolower(
            trim((string) $value)
        );

        if (!in_array($normalized, self::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException(
                'Invalid payment method.'
            );
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE STATUS
    |--------------------------------------------------------------------------
    */

    private function normalizeStatus(
        mixed $value
    ): string {
        $normalized = strtolower(
            trim((string) $value)
        );

        if (!in_array($normalized, self::STATUSES, true)) {
            throw new InvalidArgumentException(
                'Invalid payment status.'
            );
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE TRANSACTION ID
    |--------------------------------------------------------------------------
    */

    private function normalizeTransactionId(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE PAYMENT REFERENCE
    |--------------------------------------------------------------------------
    */

    private function generatePaymentReference(): string
    {
        do {
            $reference =
                'PAY-'
                . date('Ymd')
                . '-'
                . strtoupper(
                    bin2hex(
                        random_bytes(4)
                    )
                );

            $statement = $this->db->prepare("
                SELECT COUNT(*)
                FROM payments
                WHERE payment_reference = :reference
            ");

            $statement->execute([
                ':reference' =>
                    $reference,
            ]);

            $exists =
                (int) $statement->fetchColumn()
                > 0;
        } while ($exists);

        return $reference;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE REFUND REFERENCE
    |--------------------------------------------------------------------------
    */

    private function generateRefundReference(): string
    {
        return 'REF-'
            . date('Ymd')
            . '-'
            . strtoupper(
                bin2hex(
                    random_bytes(4)
                )
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    private function getDatabaseConnection(): PDO
    {
        /*
         * Supports Database::getConnection()
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
         * Supports getDatabaseConnection()
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
         * Supports global $pdo.
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
