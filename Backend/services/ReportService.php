<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/services/ReportService.php
 *
 * Contains business logic and database queries for hotel reports.
 */

require_once __DIR__ . '/../config/database.php';

class ReportService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? $this->resolveDatabase();
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard(array $filters = []): array
    {
        $summary = $this->summary($filters);

        return [
            'summary' => $summary,
            'revenue' => $this->revenue($filters),
            'occupancy' => $this->occupancy($filters),
            'bookings' => $this->bookings($filters),
            'rooms' => $this->rooms($filters),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    public function summary(array $filters = []): array
    {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $stats = [];

        $stats['total_rooms'] =
            $this->scalar(
                'SELECT COUNT(*) FROM rooms'
            );

        $stats['available_rooms'] =
            $this->scalar(
                "SELECT COUNT(*)
                 FROM rooms
                 WHERE status = 'available'"
            );

        $stats['occupied_rooms'] =
            $this->scalar(
                "SELECT COUNT(*)
                 FROM rooms
                 WHERE status = 'occupied'"
            );

        $stats['maintenance_rooms'] =
            $this->scalar(
                "SELECT COUNT(*)
                 FROM rooms
                 WHERE status = 'maintenance'"
            );

        $stats['total_bookings'] =
            $this->scalar(
                'SELECT COUNT(*)
                 FROM bookings
                 WHERE DATE(check_in) <= :date_to
                   AND DATE(check_out) >= :date_from',
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        $stats['confirmed_bookings'] =
            $this->scalar(
                "SELECT COUNT(*)
                 FROM bookings
                 WHERE status = 'confirmed'
                   AND DATE(check_in) <= :date_to
                   AND DATE(check_out) >= :date_from",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        $stats['cancelled_bookings'] =
            $this->scalar(
                "SELECT COUNT(*)
                 FROM bookings
                 WHERE status = 'cancelled'
                   AND DATE(check_in) <= :date_to
                   AND DATE(check_out) >= :date_from",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        $stats['total_guests'] =
            $this->scalar(
                'SELECT COUNT(*)
                 FROM guests'
            );

        $stats['total_revenue'] =
            $this->number(
                $this->scalar(
                    "SELECT COALESCE(SUM(amount), 0)
                     FROM payments
                     WHERE status = 'completed'
                       AND DATE(payment_date)
                           BETWEEN :date_from AND :date_to",
                    [
                        ':date_from' => $dateFrom,
                        ':date_to' => $dateTo,
                    ]
                )
            );

        $stats['average_booking_value'] =
            $this->number(
                $this->scalar(
                    "SELECT COALESCE(AVG(total_amount), 0)
                     FROM bookings
                     WHERE status NOT IN ('cancelled')
                       AND DATE(check_in) <= :date_to
                       AND DATE(check_out) >= :date_from",
                    [
                        ':date_from' => $dateFrom,
                        ':date_to' => $dateTo,
                    ]
                )
            );

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statistics' => $stats,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue
    |--------------------------------------------------------------------------
    */

    public function revenue(array $filters = []): array
    {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $groupBy =
            $this->sanitizeGroupBy(
                $filters['group_by'] ?? 'day'
            );

        $paymentMethod =
            $filters['payment_method']
            ?? null;

        $groupExpression =
            $this->getDateGroupExpression(
                'payment_date',
                $groupBy
            );

        $sql = "
            SELECT
                {$groupExpression} AS period,
                COUNT(*) AS transaction_count,
                COALESCE(SUM(amount), 0) AS revenue
            FROM payments
            WHERE status = 'completed'
              AND DATE(payment_date)
                  BETWEEN :date_from AND :date_to
        ";

        $params = [
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo,
        ];

        if ($paymentMethod !== null) {
            $sql .= "
                AND payment_method = :payment_method
            ";

            $params[':payment_method'] =
                $paymentMethod;
        }

        $sql .= "
            GROUP BY {$groupExpression}
            ORDER BY period ASC
        ";

        $rows = $this->fetchAll(
            $sql,
            $params
        );

        $totalRevenue = 0.0;

        foreach ($rows as &$row) {
            $row['transaction_count'] =
                (int) $row['transaction_count'];

            $row['revenue'] =
                $this->number(
                    $row['revenue']
                );

            $totalRevenue +=
                $row['revenue'];
        }

        unset($row);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'group_by' => $groupBy,
            ],
            'payment_method' =>
                $paymentMethod,
            'total_revenue' =>
                round($totalRevenue, 2),
            'transactions' => $rows,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Occupancy
    |--------------------------------------------------------------------------
    */

    public function occupancy(array $filters = []): array
    {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $roomType =
            $filters['room_type']
            ?? null;

        $totalRoomsSql =
            'SELECT COUNT(*)
             FROM rooms';

        $totalRoomsParams = [];

        if ($roomType !== null) {
            $totalRoomsSql .=
                ' WHERE room_type = :room_type';

            $totalRoomsParams[':room_type'] =
                $roomType;
        }

        $totalRooms =
            (int) $this->scalar(
                $totalRoomsSql,
                $totalRoomsParams
            );

        $occupiedRoomNights =
            $this->scalar(
                "SELECT COALESCE(
                    SUM(
                        GREATEST(
                            0,
                            DATEDIFF(
                                LEAST(
                                    DATE(check_out),
                                    :date_to
                                ),
                                GREATEST(
                                    DATE(check_in),
                                    :date_from
                                )
                            )
                        )
                    ),
                    0
                )
                FROM bookings
                WHERE status NOT IN ('cancelled')
                  AND DATE(check_in) <= :date_to_2
                  AND DATE(check_out) >= :date_from_2",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                    ':date_from_2' => $dateFrom,
                    ':date_to_2' => $dateTo,
                ]
            );

        /*
         * Include the checkout day correctly by calculating
         * available room nights over the selected period.
         */
        $start =
            new DateTimeImmutable(
                $dateFrom
            );

        $end =
            new DateTimeImmutable(
                $dateTo
            );

        $days =
            (int) $start->diff($end)->days + 1;

        $availableRoomNights =
            $totalRooms * max(1, $days);

        $occupancyRate =
            $availableRoomNights > 0
            ? (
                ((float) $occupiedRoomNights)
                / $availableRoomNights
            ) * 100
            : 0;

        $statusBreakdown =
            $this->fetchAll(
                "SELECT
                    status,
                    COUNT(*) AS room_count
                 FROM rooms
                 " . (
                     $roomType !== null
                     ? "WHERE room_type = :room_type"
                     : ""
                 ) . "
                 GROUP BY status
                 ORDER BY status",
                $roomType !== null
                    ? [
                        ':room_type' =>
                            $roomType,
                    ]
                    : []
            );

        foreach (
            $statusBreakdown as &$row
        ) {
            $row['room_count'] =
                (int) $row['room_count'];
        }

        unset($row);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'days' => $days,
            ],
            'room_type' => $roomType,
            'total_rooms' => $totalRooms,
            'available_room_nights' =>
                $availableRoomNights,
            'occupied_room_nights' =>
                (int) $occupiedRoomNights,
            'occupancy_rate' =>
                round($occupancyRate, 2),
            'status_breakdown' =>
                $statusBreakdown,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Bookings
    |--------------------------------------------------------------------------
    */

    public function bookings(array $filters = []): array
    {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $statusBreakdown =
            $this->fetchAll(
                "SELECT
                    status,
                    COUNT(*) AS booking_count,
                    COALESCE(
                        SUM(total_amount),
                        0
                    ) AS total_amount
                 FROM bookings
                 WHERE DATE(check_in) <= :date_to
                   AND DATE(check_out) >= :date_from
                 GROUP BY status
                 ORDER BY booking_count DESC",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        foreach (
            $statusBreakdown as &$row
        ) {
            $row['booking_count'] =
                (int) $row['booking_count'];

            $row['total_amount'] =
                $this->number(
                    $row['total_amount']
                );
        }

        unset($row);

        $groupBy =
            $this->sanitizeGroupBy(
                $filters['group_by'] ?? 'day'
            );

        $groupExpression =
            $this->getDateGroupExpression(
                'check_in',
                $groupBy
            );

        $timeline =
            $this->fetchAll(
                "SELECT
                    {$groupExpression} AS period,
                    COUNT(*) AS booking_count,
                    COALESCE(
                        SUM(total_amount),
                        0
                    ) AS total_amount
                 FROM bookings
                 WHERE DATE(check_in)
                       BETWEEN :date_from
                       AND :date_to
                 GROUP BY {$groupExpression}
                 ORDER BY period ASC",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        foreach ($timeline as &$row) {
            $row['booking_count'] =
                (int) $row['booking_count'];

            $row['total_amount'] =
                $this->number(
                    $row['total_amount']
                );
        }

        unset($row);

        $total =
            array_sum(
                array_map(
                    static fn ($row) =>
                        (int) $row['booking_count'],
                    $statusBreakdown
                )
            );

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'group_by' => $groupBy,
            ],
            'total_bookings' => $total,
            'status_breakdown' =>
                $statusBreakdown,
            'timeline' => $timeline,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Guests
    |--------------------------------------------------------------------------
    */

    public function guests(array $filters = []): array
    {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $totalGuests =
            (int) $this->scalar(
                'SELECT COUNT(*) FROM guests'
            );

        $newGuests =
            (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM guests
                 WHERE DATE(created_at)
                       BETWEEN :date_from
                       AND :date_to",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        $countries =
            $this->fetchAll(
                "SELECT
                    country,
                    COUNT(*) AS guest_count
                 FROM guests
                 WHERE country IS NOT NULL
                   AND country <> ''
                 GROUP BY country
                 ORDER BY guest_count DESC"
            );

        foreach ($countries as &$row) {
            $row['guest_count'] =
                (int) $row['guest_count'];
        }

        unset($row);

        $repeatGuests =
            (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM (
                     SELECT guest_id
                     FROM bookings
                     WHERE status NOT IN ('cancelled')
                     GROUP BY guest_id
                     HAVING COUNT(*) > 1
                 ) AS repeat_guests"
            );

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'total_guests' => $totalGuests,
            'new_guests' => $newGuests,
            'repeat_guests' => $repeatGuests,
            'countries' => $countries,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Rooms
    |--------------------------------------------------------------------------
    */

    public function rooms(array $filters = []): array
    {
        $status =
            $this->fetchAll(
                "SELECT
                    status,
                    COUNT(*) AS room_count
                 FROM rooms
                 GROUP BY status
                 ORDER BY status"
            );

        foreach ($status as &$row) {
            $row['room_count'] =
                (int) $row['room_count'];
        }

        unset($row);

        $types =
            $this->fetchAll(
                "SELECT
                    room_type,
                    COUNT(*) AS room_count,
                    COALESCE(
                        AVG(price_per_night),
                        0
                    ) AS average_price
                 FROM rooms
                 GROUP BY room_type
                 ORDER BY room_count DESC"
            );

        foreach ($types as &$row) {
            $row['room_count'] =
                (int) $row['room_count'];

            $row['average_price'] =
                $this->number(
                    $row['average_price']
                );
        }

        unset($row);

        $totalRooms =
            (int) $this->scalar(
                'SELECT COUNT(*) FROM rooms'
            );

        $averagePrice =
            $this->number(
                $this->scalar(
                    'SELECT COALESCE(
                        AVG(price_per_night),
                        0
                    )
                    FROM rooms'
                )
            );

        return [
            'total_rooms' => $totalRooms,
            'average_price_per_night' =>
                $averagePrice,
            'status_breakdown' => $status,
            'room_types' => $types,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    public function payments(array $filters = []): array
    {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $methods =
            $this->fetchAll(
                "SELECT
                    payment_method,
                    COUNT(*) AS transaction_count,
                    COALESCE(
                        SUM(amount),
                        0
                    ) AS total_amount
                 FROM payments
                 WHERE DATE(payment_date)
                       BETWEEN :date_from
                       AND :date_to
                 GROUP BY payment_method
                 ORDER BY total_amount DESC",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        foreach ($methods as &$row) {
            $row['transaction_count'] =
                (int) $row['transaction_count'];

            $row['total_amount'] =
                $this->number(
                    $row['total_amount']
                );
        }

        unset($row);

        $statuses =
            $this->fetchAll(
                "SELECT
                    status,
                    COUNT(*) AS transaction_count,
                    COALESCE(
                        SUM(amount),
                        0
                    ) AS total_amount
                 FROM payments
                 WHERE DATE(payment_date)
                       BETWEEN :date_from
                       AND :date_to
                 GROUP BY status
                 ORDER BY transaction_count DESC",
                [
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo,
                ]
            );

        foreach ($statuses as &$row) {
            $row['transaction_count'] =
                (int) $row['transaction_count'];

            $row['total_amount'] =
                $this->number(
                    $row['total_amount']
                );
        }

        unset($row);

        $total =
            $this->number(
                $this->scalar(
                    "SELECT COALESCE(
                        SUM(amount),
                        0
                    )
                    FROM payments
                    WHERE status = 'completed'
                      AND DATE(payment_date)
                          BETWEEN :date_from
                          AND :date_to",
                    [
                        ':date_from' => $dateFrom,
                        ':date_to' => $dateTo,
                    ]
                )
            );

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'total_completed_payments' =>
                $total,
            'payment_methods' => $methods,
            'statuses' => $statuses,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */

    public function staff(array $filters = []): array
    {
        $department =
            $filters['department']
            ?? null;

        $where = [];
        $params = [];

        if ($department !== null) {
            $where[] =
                'department = :department';

            $params[':department'] =
                $department;
        }

        $whereSql =
            empty($where)
            ? ''
            : ' WHERE '
                . implode(
                    ' AND ',
                    $where
                );

        $summary =
            $this->fetchAll(
                "SELECT
                    department,
                    COUNT(*) AS staff_count
                 FROM staff
                 {$whereSql}
                 GROUP BY department
                 ORDER BY staff_count DESC",
                $params
            );

        foreach ($summary as &$row) {
            $row['staff_count'] =
                (int) $row['staff_count'];
        }

        unset($row);

        $total =
            (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM staff
                 {$whereSql}",
                $params
            );

        return [
            'total_staff' => $total,
            'department' => $department,
            'departments' => $summary,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Monthly
    |--------------------------------------------------------------------------
    */

    public function monthly(
        int $year,
        int $month
    ): array {
        if (
            $year < 2000
            || $year > 2100
        ) {
            throw new InvalidArgumentException(
                'Invalid year.'
            );
        }

        if (
            $month < 1
            || $month > 12
        ) {
            throw new InvalidArgumentException(
                'Invalid month.'
            );
        }

        $dateFrom =
            sprintf(
                '%04d-%02d-01',
                $year,
                $month
            );

        $dateTo =
            date(
                'Y-m-t',
                strtotime($dateFrom)
            );

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'group_by' => 'day',
        ];

        return [
            'year' => $year,
            'month' => $month,
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'revenue' =>
                $this->revenue($filters),
            'occupancy' =>
                $this->occupancy($filters),
            'bookings' =>
                $this->bookings($filters),
            'payments' =>
                $this->payments($filters),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Yearly
    |--------------------------------------------------------------------------
    */

    public function yearly(
        int $year
    ): array {
        if (
            $year < 2000
            || $year > 2100
        ) {
            throw new InvalidArgumentException(
                'Invalid year.'
            );
        }

        $dateFrom =
            sprintf(
                '%04d-01-01',
                $year
            );

        $dateTo =
            sprintf(
                '%04d-12-31',
                $year
            );

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'group_by' => 'month',
        ];

        return [
            'year' => $year,
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'revenue' =>
                $this->revenue($filters),
            'occupancy' =>
                $this->occupancy($filters),
            'bookings' =>
                $this->bookings($filters),
            'payments' =>
                $this->payments($filters),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Financial
    |--------------------------------------------------------------------------
    */

    public function financial(
        array $filters = []
    ): array {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $revenue =
            $this->number(
                $this->scalar(
                    "SELECT COALESCE(
                        SUM(amount),
                        0
                    )
                    FROM payments
                    WHERE status = 'completed'
                      AND DATE(payment_date)
                          BETWEEN :date_from
                          AND :date_to",
                    [
                        ':date_from' => $dateFrom,
                        ':date_to' => $dateTo,
                    ]
                )
            );

        $refunds =
            $this->number(
                $this->scalar(
                    "SELECT COALESCE(
                        SUM(amount),
                        0
                    )
                    FROM payments
                    WHERE status = 'refunded'
                      AND DATE(payment_date)
                          BETWEEN :date_from
                          AND :date_to",
                    [
                        ':date_from' => $dateFrom,
                        ':date_to' => $dateTo,
                    ]
                )
            );

        $bookingValue =
            $this->number(
                $this->scalar(
                    "SELECT COALESCE(
                        SUM(total_amount),
                        0
                    )
                    FROM bookings
                    WHERE status NOT IN ('cancelled')
                      AND DATE(check_in) <= :date_to
                      AND DATE(check_out) >= :date_from",
                    [
                        ':date_from' => $dateFrom,
                        ':date_to' => $dateTo,
                    ]
                )
            );

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'gross_revenue' => $revenue,
            'refunds' => $refunds,
            'net_revenue' =>
                round(
                    $revenue - $refunds,
                    2
                ),
            'booking_value' =>
                $bookingValue,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    */

    public function performance(
        array $filters = []
    ): array {
        [$dateFrom, $dateTo] =
            $this->normalizeDateRange($filters);

        $revenue =
            $this->revenue(
                [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'group_by' => 'month',
                ]
            );

        $occupancy =
            $this->occupancy(
                [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]
            );

        $bookings =
            $this->bookings(
                [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'group_by' => 'month',
                ]
            );

        $totalRooms =
            max(
                1,
                (int) $occupancy['total_rooms']
            );

        $totalRevenue =
            (float) $revenue['total_revenue'];

        $days =
            max(
                1,
                (int) $occupancy['period']['days']
            );

        $revPar =
            $totalRevenue
            / ($totalRooms * $days);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'revenue' => $revenue,
            'occupancy' => $occupancy,
            'bookings' => $bookings,
            'performance_indicators' => [
                'revenue_per_available_room' =>
                    round($revPar, 2),
                'occupancy_rate' =>
                    $occupancy['occupancy_rate'],
                'total_revenue' =>
                    $totalRevenue,
                'total_bookings' =>
                    $bookings['total_bookings'],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Generate
    |--------------------------------------------------------------------------
    */

    public function generate(
        string $type,
        array $filters = []
    ): mixed {
        $type =
            strtolower(
                trim($type)
            );

        return match ($type) {
            'revenue' =>
                $this->revenue($filters),

            'occupancy' =>
                $this->occupancy($filters),

            'bookings' =>
                $this->bookings($filters),

            'guests' =>
                $this->guests($filters),

            'rooms' =>
                $this->rooms($filters),

            'payments' =>
                $this->payments($filters),

            'staff' =>
                $this->staff($filters),

            'financial' =>
                $this->financial($filters),

            'performance' =>
                $this->performance($filters),

            'summary' =>
                $this->summary($filters),

            default =>
                throw new InvalidArgumentException(
                    'Unsupported report type.'
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

    public function export(
        string $type,
        string $format,
        array $filters = []
    ): string|array {
        $type =
            strtolower(
                trim($type)
            );

        $format =
            strtolower(
                trim($format)
            );

        $data =
            $this->generate(
                $type,
                $filters
            );

        if ($format === 'json') {
            return $data;
        }

        if ($format !== 'csv') {
            throw new InvalidArgumentException(
                'Unsupported export format.'
            );
        }

        return $this->convertToCsv(
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Optional saved report lookup
    |--------------------------------------------------------------------------
    |
    | The current database structure does not require a
    | reports table. This method can be enabled later if
    | a reports table is introduced.
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?array {
        if ($id <= 0) {
            return null;
        }

        /*
         * No reports table exists in the supplied schema.
         */
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | CSV
    |--------------------------------------------------------------------------
    */

    private function convertToCsv(
        mixed $data
    ): string {
        $rows =
            $this->normalizeRows(
                $data
            );

        if (empty($rows)) {
            return "No data available\n";
        }

        $headers = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (
                array_keys($row)
                as $key
            ) {
                if (
                    !in_array(
                        $key,
                        $headers,
                        true
                    )
                ) {
                    $headers[] = $key;
                }
            }
        }

        if (empty($headers)) {
            return "value\n"
                . $this->csvEscape(
                    (string) $data
                )
                . "\n";
        }

        $lines = [];

        $lines[] =
            implode(
                ',',
                array_map(
                    fn ($value) =>
                        $this->csvEscape(
                            (string) $value
                        ),
                    $headers
                )
            );

        foreach ($rows as $row) {
            $values = [];

            foreach ($headers as $header) {
                $value =
                    is_array($row)
                    ? ($row[$header] ?? '')
                    : '';

                if (
                    is_array($value)
                    || is_object($value)
                ) {
                    $value =
                        json_encode(
                            $value,
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        );
                }

                $values[] =
                    $this->csvEscape(
                        (string) $value
                    );
            }

            $lines[] =
                implode(
                    ',',
                    $values
                );
        }

        return implode(
            "\n",
            $lines
        ) . "\n";
    }

    private function normalizeRows(
        mixed $data
    ): array {
        if (!is_array($data)) {
            return [
                [
                    'value' => $data,
                ],
            ];
        }

        if (
            isset($data['items'])
            && is_array($data['items'])
        ) {
            return $data['items'];
        }

        if (
            isset($data['data'])
            && is_array($data['data'])
        ) {
            return $data['data'];
        }

        if (
            array_is_list($data)
        ) {
            return $data;
        }

        /*
         * Flatten common report collections for CSV.
         */
        foreach (
            [
                'transactions',
                'timeline',
                'status_breakdown',
                'payment_methods',
                'statuses',
                'room_types',
                'countries',
                'departments',
            ] as $key
        ) {
            if (
                isset($data[$key])
                && is_array($data[$key])
            ) {
                return $data[$key];
            }
        }

        return [$data];
    }

    private function csvEscape(
        string $value
    ): string {
        return '"'
            . str_replace(
                '"',
                '""',
                $value
            )
            . '"';
    }

    /*
    |--------------------------------------------------------------------------
    | Date Helpers
    |--------------------------------------------------------------------------
    */

    private function normalizeDateRange(
        array $filters
    ): array {
        $dateFrom =
            $filters['date_from']
            ?? date('Y-m-01');

        $dateTo =
            $filters['date_to']
            ?? date('Y-m-t');

        $this->validateDate(
            (string) $dateFrom,
            'date_from'
        );

        $this->validateDate(
            (string) $dateTo,
            'date_to'
        );

        if ($dateFrom > $dateTo) {
            throw new InvalidArgumentException(
                'date_from cannot be later than date_to.'
            );
        }

        return [
            (string) $dateFrom,
            (string) $dateTo,
        ];
    }

    private function validateDate(
        string $date,
        string $field
    ): void {
        $parsed =
            DateTime::createFromFormat(
                'Y-m-d',
                $date
            );

        if (
            !$parsed
            || $parsed->format('Y-m-d')
                !== $date
        ) {
            throw new InvalidArgumentException(
                "{$field} must use YYYY-MM-DD format."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SQL Helpers
    |--------------------------------------------------------------------------
    */

    private function getDateGroupExpression(
        string $column,
        string $groupBy
    ): string {
        /*
         * $column and $groupBy are internally generated
         * values, not raw user SQL.
         */
        return match ($groupBy) {
            'day' =>
                "DATE({$column})",

            'week' =>
                "YEARWEEK({$column}, 1)",

            'month' =>
                "DATE_FORMAT(
                    {$column},
                    '%Y-%m'
                )",

            'year' =>
                "YEAR({$column})",

            default =>
                throw new InvalidArgumentException(
                    'Invalid grouping.'
                ),
        };
    }

    private function sanitizeGroupBy(
        string $groupBy
    ): string {
        $groupBy =
            strtolower(
                trim($groupBy)
            );

        $allowed = [
            'day',
            'week',
            'month',
            'year',
        ];

        if (
            !in_array(
                $groupBy,
                $allowed,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid group_by value.'
            );
        }

        return $groupBy;
    }

    /*
    |--------------------------------------------------------------------------
    | Database Helpers
    |--------------------------------------------------------------------------
    */

    private function fetchAll(
        string $sql,
        array $params = []
    ): array {
        $statement =
            $this->db->prepare($sql);

        $statement->execute(
            $params
        );

        return $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function scalar(
        string $sql,
        array $params = []
    ): mixed {
        $statement =
            $this->db->prepare($sql);

        $statement->execute(
            $params
        );

        return $statement->fetchColumn();
    }

    private function number(
        mixed $value
    ): float {
        return round(
            (float) ($value ?? 0),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Database Connection Resolver
    |--------------------------------------------------------------------------
    */

    private function resolveDatabase(): PDO
    {
        /*
         * Supports the common database.php patterns:
         *
         * 1. $pdo
         * 2. $db
         * 3. getDatabaseConnection()
         * 4. getPDO()
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

        if (
            function_exists('getPDO')
        ) {
            $connection =
                getPDO();

            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        global $pdo, $db;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        if ($db instanceof PDO) {
            return $db;
        }

        throw new RuntimeException(
            'Database connection could not be initialized.'
        );
    }
}
