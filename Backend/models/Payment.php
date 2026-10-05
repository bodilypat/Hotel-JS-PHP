<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/models/Payment.php
 *
 * Handles database operations for payments.
 */

require_once __DIR__ . '/../config/database.php';

class Payment
{
    private const ALLOWED_STATUSES = [
        'pending',
        'processing',
        'completed',
        'failed',
        'refunded',
        'partially_refunded',
        'voided',
    ];

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->getDatabaseConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL PAYMENTS
    |--------------------------------------------------------------------------
    */

    public function all(
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
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FIND PAYMENT
    |--------------------------------------------------------------------------
    */

    public function find(
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
    | FIND BY PAYMENT REFERENCE
    |--------------------------------------------------------------------------
    */

    public function findByReference(
        string $reference
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM payments
            WHERE payment_reference = :reference
            LIMIT 1
        ");

        $statement->execute([
            ':reference' => $reference,
        ]);

        $payment = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return $payment ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND BY TRANSACTION ID
    |--------------------------------------------------------------------------
    */

    public function findByTransactionId(
        string $transactionId
    ): ?array {
        $statement = $this->db->prepare("
            SELECT *
            FROM payments
            WHERE transaction_id = :transaction_id
            LIMIT 1
        ");

        $statement->execute([
            ':transaction_id' =>
                $transactionId,
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
    ): int {
        $paymentReference = trim((string) ($data['payment_reference'] ?? ''));

        if ($paymentReference === '') {
            $paymentReference = $this->generatePaymentReference();
        }

        $bookingId = (int) ($data['booking_id'] ?? 0);

        if ($bookingId <= 0) {
            throw new InvalidArgumentException(
                'Booking ID is required.'
            );
        }

        $amount = (float) ($data['amount'] ?? 0);

        if ($amount < 0) {
            throw new InvalidArgumentException(
                'Payment amount cannot be negative.'
            );
        }

        $currency = strtoupper(trim((string) ($data['currency'] ?? 'USD')));

        if ($currency === '') {
            $currency = 'USD';
        }

        $status = $this->normalizeStatus(
            (string) ($data['status'] ?? 'pending')
        );

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

        $statement = $this->db->prepare($sql);

        $statement->execute([
            ':payment_reference' =>
                $paymentReference,

            ':booking_id' =>
                $bookingId,

            ':amount' =>
                $amount,

            ':currency' =>
                $currency,

            ':payment_method' =>
                $data['payment_method'] ?? null,

            ':transaction_id' =>
                $data['transaction_id'] ?? null,

            ':status' =>
                $status,

            ':notes' =>
                $data['notes'] ?? null,

            ':paid_at' =>
                $data['paid_at'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PAYMENT
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): bool {
        $allowedFields = [
            'amount',
            'currency',
            'payment_method',
            'transaction_id',
            'status',
            'notes',
            'paid_at',
            'refunded_amount',
            'refund_reference',
            'refunded_at',
            'refund_reason',
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

            if ($field === 'status' && isset($data[$field])) {
                $data[$field] = $this->normalizeStatus(
                    (string) $data[$field]
                );
            }

            $updates[] =
                "`{$field}` = :{$field}";

            $params[":{$field}"] =
                $data[$field];
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "
            UPDATE payments
            SET " . implode(
                ', ',
                $updates
            ) . "
            WHERE id = :id
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute(
            $params
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PAYMENT
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $id
    ): bool {
        $statement = $this->db->prepare("
            DELETE FROM payments
            WHERE id = :id
        ");

        return $statement->execute([
            ':id' => $id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET PAYMENTS BY BOOKING
    |--------------------------------------------------------------------------
    */

    public function findByBooking(
        int $bookingId
    ): array {
        $statement = $this->db->prepare("
            SELECT *
            FROM payments
            WHERE booking_id = :booking_id
            ORDER BY created_at DESC
        ");

        $statement->execute([
            ':booking_id' => $bookingId,
        ]);

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET PAYMENTS BY GUEST
    |--------------------------------------------------------------------------
    */

    public function findByGuest(
        int $guestId
    ): array {
        $statement = $this->db->prepare("
            SELECT
                p.*,

                b.booking_reference,
                b.check_in,
                b.check_out,
                b.room_id

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
    | GET PAYMENT TOTAL FOR BOOKING
    |--------------------------------------------------------------------------
    */

    public function getBookingPaidAmount(
        int $bookingId
    ): float {
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
                        COALESCE(
                            refunded_amount,
                            0
                        )
                    ),
                    0
                ) AS refunded_amount

            FROM payments

            WHERE booking_id = :booking_id
        ");

        $statement->execute([
            ':booking_id' => $bookingId,
        ]);

        $result = $statement->fetch(
            PDO::FETCH_ASSOC
        ) ?: [];

        $paid = (float) (
            $result['paid_amount'] ?? 0
        );

        $refunded = (float) (
            $result['refunded_amount'] ?? 0
        );

        return max(
            0,
            round(
                $paid - $refunded,
                2
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET BOOKING PAYMENT SUMMARY
    |--------------------------------------------------------------------------
    */

    public function getBookingPaymentSummary(
        int $bookingId,
        ?float $bookingTotal = null
    ): array {
        $payments =
            $this->findByBooking(
                $bookingId
            );

        $paidAmount = 0.0;
        $refundedAmount = 0.0;

        foreach ($payments as $payment) {
            $status =
                (string) (
                    $payment['status'] ?? ''
                );

            $amount =
                max(
                    0.0,
                    (float) (
                        $payment['amount'] ?? 0
                    )
                );

            $refund =
                max(
                    0.0,
                    (float) (
                        $payment['refunded_amount']
                        ?? 0
                    )
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
                $paidAmount += $amount;
            }

            $refundedAmount += $refund;

            if ($status === 'refunded') {
                $refundedAmount += $amount;
            }
        }

        $effectivePaid = max(
            0,
            $paidAmount - $refundedAmount
        );

        $remaining = null;

        if ($bookingTotal !== null) {
            $remaining = max(
                0,
                $bookingTotal - $effectivePaid
            );
        }

        return [
            'booking_id' =>
                $bookingId,

            'booking_total' =>
                $bookingTotal !== null
                    ? round(
                        $bookingTotal,
                        2
                    )
                    : null,

            'paid_amount' =>
                round(
                    $effectivePaid,
                    2
                ),

            'refunded_amount' =>
                round(
                    $refundedAmount,
                    2
                ),

            'remaining_amount' =>
                $remaining !== null
                    ? round(
                        $remaining,
                        2
                    )
                    : null,

            'payment_status' =>
                $bookingTotal === null
                    ? 'unknown'
                    : (
                        $effectivePaid <= 0
                            ? 'unpaid'
                            : (
                                $effectivePaid
                                < $bookingTotal
                                    ? 'partial'
                                    : 'paid'
                            )
                    ),
        ];
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
        $status = $this->normalizeStatus($status);

        $statement = $this->db->prepare("
            UPDATE payments
            SET status = :status
            WHERE id = :id
        ");

        return $statement->execute([
            ':status' => $status,
            ':id' => $id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MARK AS COMPLETED
    |--------------------------------------------------------------------------
    */

    public function markCompleted(
        int $id,
        ?string $paidAt = null
    ): bool {
        $statement = $this->db->prepare("
            UPDATE payments
            SET
                status = 'completed',
                paid_at = :paid_at
            WHERE id = :id
        ");

        return $statement->execute([
            ':paid_at' =>
                $paidAt
                ?? date('Y-m-d H:i:s'),

            ':id' => $id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MARK AS REFUNDED
    |--------------------------------------------------------------------------
    */

    public function markRefunded(
        int $id,
        float $refundAmount,
        string $refundReference,
        ?string $reason = null
    ): bool {
        if ($refundAmount < 0) {
            throw new InvalidArgumentException(
                'Refund amount cannot be negative.'
            );
        }

        $payment = $this->find($id);

        if (!$payment) {
            return false;
        }

        $paymentAmount =
            (float) (
                $payment['amount'] ?? 0
            );

        $currentRefunded =
            (float) (
                $payment['refunded_amount']
                ?? 0
            );

        if ($refundAmount === 0.0) {
            return true;
        }

        $newRefunded =
            round(
                $currentRefunded
                + $refundAmount,
                2
            );

        if ($newRefunded > $paymentAmount) {
            throw new InvalidArgumentException(
                'Refund amount exceeds payment amount.'
            );
        }

        $status =
            $newRefunded >= $paymentAmount
                ? 'refunded'
                : 'partially_refunded';

        $statement = $this->db->prepare("
            UPDATE payments
            SET
                status = :status,
                refunded_amount = :refunded_amount,
                refund_reference = :refund_reference,
                refunded_at = :refunded_at,
                refund_reason = :refund_reason
            WHERE id = :id
        ");

        return $statement->execute([
            ':status' =>
                $status,

            ':refunded_amount' =>
                $newRefunded,

            ':refund_reference' =>
                $refundReference,

            ':refunded_at' =>
                date('Y-m-d H:i:s'),

            ':refund_reason' =>
                $reason,

            ':id' =>
                $id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT COUNT
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

        $sql = "
            SELECT COUNT(*)
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

        return (int) $statement->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT STATISTICS
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
                ) AS completed_amount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'pending'
                            THEN p.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS pending_amount,

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
                        COALESCE(
                            p.refunded_amount,
                            0
                        )
                    ),
                    0
                ) AS refunded_amount

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

        $result =
            $statement->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        return [
            'total_transactions' =>
                (int) (
                    $result[
                        'total_transactions'
                    ] ?? 0
                ),

            'completed_amount' =>
                (float) (
                    $result[
                        'completed_amount'
                    ] ?? 0
                ),

            'pending_amount' =>
                (float) (
                    $result[
                        'pending_amount'
                    ] ?? 0
                ),

            'failed_amount' =>
                (float) (
                    $result[
                        'failed_amount'
                    ] ?? 0
                ),

            'refunded_amount' =>
                (float) (
                    $result[
                        'refunded_amount'
                    ] ?? 0
                ),
        ];
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
            $where[] =
                'p.status = :status';

            $params[':status'] =
                $this->normalizeStatus(
                    (string) $filters['status']
                );
        }

        if (
            !empty($filters['payment_method'])
        ) {
            $where[] =
                'p.payment_method = :payment_method';

            $params[':payment_method'] =
                $filters['payment_method'];
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

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException(
                'Invalid payment status.'
            );
        }

        return $status;
    }

    private function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
    }
}
