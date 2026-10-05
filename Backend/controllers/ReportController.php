<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * File: backend/controllers/ReportController.php
 *
 * Handles HTTP requests for hotel reports.
 */

require_once __DIR__ . '/../services/ReportService.php';

if (!class_exists('ReportService', false)) {
    /**
     * Fallback service used when the real ReportService is not loaded.
     *
     * @method array summary(array $filters)
     * @method array dashboard(array $filters)
     * @method array revenue(array $filters)
     * @method array occupancy(array $filters)
     * @method array bookings(array $filters)
     * @method array guests(array $filters)
     * @method array rooms(array $filters)
     * @method array payments(array $filters)
     * @method array staff(array $filters)
     * @method array monthly(int $year, int $month)
     * @method array yearly(int $year)
     * @method array financial(array $filters)
     * @method array performance(array $filters)
     * @method mixed export(string $type, string $format, array $filters)
     * @method mixed find(int $id)
     * @method mixed generate(string $type, array $filters)
     */
    class ReportService
    {
        public function __construct()
        {
        }
    }
}

class ReportController
{
    private ReportService $reportService;

    public function __construct(
        ?ReportService $reportService = null
    ) {
        $this->reportService =
            $reportService ?? new ReportService();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        try {
            $filters = $this->getDateFilters();

            $report =
                $this->reportService->summary(
                    $filters
                );

            $this->success(
                $report,
                'Report summary retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'dashboard'
                )
            ) {
                $report =
                    $this->reportService->dashboard(
                        $filters
                    );
            } else {
                $report =
                    $this->reportService->summary(
                        $filters
                    );
            }

            $this->success(
                $report,
                'Dashboard report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/revenue
    |--------------------------------------------------------------------------
    */

    public function revenue(): void
    {
        try {
            $filters = $this->getDateFilters();

            $filters['group_by'] =
                $this->getGroupBy();

            $filters =
                $this->applyOptionalFilters(
                    $filters,
                    $_GET
                );

            if (
                method_exists(
                    $this->reportService,
                    'revenue'
                )
            ) {
                $report =
                    $this->reportService->revenue(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Revenue report service is not available.'
                );
            }

            $this->success(
                $report,
                'Revenue report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/occupancy
    |--------------------------------------------------------------------------
    */

    public function occupancy(): void
    {
        try {
            $filters = $this->getDateFilters();

            $filters =
                $this->applyOptionalFilters(
                    $filters,
                    $_GET
                );

            if (
                method_exists(
                    $this->reportService,
                    'occupancy'
                )
            ) {
                $report =
                    $this->reportService->occupancy(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Occupancy report service is not available.'
                );
            }

            $this->success(
                $report,
                'Occupancy report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/bookings
    |--------------------------------------------------------------------------
    */

    public function bookings(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'bookings'
                )
            ) {
                $report =
                    $this->reportService->bookings(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Booking report service is not available.'
                );
            }

            $this->success(
                $report,
                'Booking report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/guests
    |--------------------------------------------------------------------------
    */

    public function guests(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'guests'
                )
            ) {
                $report =
                    $this->reportService->guests(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Guest report service is not available.'
                );
            }

            $this->success(
                $report,
                'Guest report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/rooms
    |--------------------------------------------------------------------------
    */

    public function rooms(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'rooms'
                )
            ) {
                $report =
                    $this->reportService->rooms(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Room report service is not available.'
                );
            }

            $this->success(
                $report,
                'Room report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/payments
    |--------------------------------------------------------------------------
    */

    public function payments(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'payments'
                )
            ) {
                $report =
                    $this->reportService->payments(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Payment report service is not available.'
                );
            }

            $this->success(
                $report,
                'Payment report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/staff
    |--------------------------------------------------------------------------
    */

    public function staff(): void
    {
        try {
            $filters = $this->getDateFilters();

            $filters =
                $this->applyOptionalFilters(
                    $filters,
                    $_GET
                );

            if (
                method_exists(
                    $this->reportService,
                    'staff'
                )
            ) {
                $report =
                    $this->reportService->staff(
                        $filters
                    );
            } else {
                throw new RuntimeException(
                    'Staff report service is not available.'
                );
            }

            $this->success(
                $report,
                'Staff report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/monthly
    |--------------------------------------------------------------------------
    */

    public function monthly(): void
    {
        try {
            $year = $this->getIntQuery(
                'year',
                (int) date('Y')
            );

            $month = $this->getIntQuery(
                'month',
                (int) date('m')
            );

            if (
                $month < 1
                || $month > 12
            ) {
                $this->error(
                    'Month must be between 1 and 12.',
                    422
                );

                return;
            }

            if (
                $year < 2000
                || $year > 2100
            ) {
                $this->error(
                    'Invalid year.',
                    422
                );

                return;
            }

            if (
                method_exists(
                    $this->reportService,
                    'monthly'
                )
            ) {
                $report =
                    $this->reportService->monthly(
                        $year,
                        $month
                    );
            } else {
                throw new RuntimeException(
                    'Monthly report service is not available.'
                );
            }

            $this->success(
                $report,
                'Monthly report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/yearly
    |--------------------------------------------------------------------------
    */

    public function yearly(): void
    {
        try {
            $year = $this->getIntQuery(
                'year',
                (int) date('Y')
            );

            if (
                $year < 2000
                || $year > 2100
            ) {
                $this->error(
                    'Invalid year.',
                    422
                );

                return;
            }

            if (
                method_exists(
                    $this->reportService,
                    'yearly'
                )
            ) {
                $report =
                    $this->reportService->yearly(
                        $year
                    );
            } else {
                throw new RuntimeException(
                    'Yearly report service is not available.'
                );
            }

            $this->success(
                $report,
                'Yearly report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/summary
    |--------------------------------------------------------------------------
    */

    public function summary(): void
    {
        try {
            $filters = $this->getDateFilters();

            $report =
                $this->reportService->summary(
                    $filters
                );

            $this->success(
                $report,
                'Hotel summary retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/financial
    |--------------------------------------------------------------------------
    */

    public function financial(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'financial'
                )
            ) {
                $report =
                    $this->reportService->financial(
                        $filters
                    );
            } else {
                /*
                 * Revenue is the safest fallback for a
                 * financial overview when no dedicated
                 * financial method exists.
                 */
                $report =
                    $this->reportService->revenue(
                        $filters
                    );
            }

            $this->success(
                $report,
                'Financial report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/performance
    |--------------------------------------------------------------------------
    */

    public function performance(): void
    {
        try {
            $filters = $this->getDateFilters();

            if (
                method_exists(
                    $this->reportService,
                    'performance'
                )
            ) {
                $report =
                    $this->reportService->performance(
                        $filters
                    );
            } else {
                $report =
                    $this->reportService->summary(
                        $filters
                    );
            }

            $this->success(
                $report,
                'Hotel performance report retrieved successfully.'
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/export
    |--------------------------------------------------------------------------
    */

    public function export(): void
    {
        try {
            $type =
                $this->normalizeReportType(
                    (string) $this->getQuery(
                        'type',
                        'revenue'
                    ),
                    'type'
                );

            $format =
                $this->normalizeExportFormat(
                    (string) $this->getQuery(
                        'format',
                        'csv'
                    )
                );

            $filters =
                $this->getDateFilters();

            $filters['group_by'] =
                $this->getGroupBy();

            $filters =
                $this->applyOptionalFilters(
                    $filters,
                    $_GET
                );

            if (
                method_exists(
                    $this->reportService,
                    'export'
                )
            ) {
                $result =
                    $this->reportService->export(
                        $type,
                        $format,
                        $filters
                    );

                $this->sendExport(
                    $result,
                    $type,
                    $format
                );

                return;
            }

            $data =
                $this->getReportData(
                    $type,
                    $filters
                );

            if ($format === 'json') {
                $this->sendJsonDownload(
                    $data,
                    $type
                );

                return;
            }

            $this->sendCsvDownload(
                $data,
                $type
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/reports/{id}
    |--------------------------------------------------------------------------
    */

    public function show(
        int $id
    ): void {
        try {
            if ($id <= 0) {
                $this->error(
                    'Invalid report ID.',
                    422
                );

                return;
            }

            if (
                method_exists(
                    $this->reportService,
                    'find'
                )
            ) {
                $report =
                    $this->reportService->find(
                        $id
                    );

                if (!$report) {
                    $this->error(
                        'Report not found.',
                        404
                    );

                    return;
                }

                $this->success(
                    $report,
                    'Report retrieved successfully.'
                );

                return;
            }

            $this->error(
                'Saved reports are not supported.',
                501
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/reports/generate
    |--------------------------------------------------------------------------
    */

    public function generate(): void
    {
        try {
            $data =
                $this->getJsonInput();

            if (
                empty($data['type'])
            ) {
                $this->error(
                    'Report type is required.',
                    422
                );

                return;
            }

            $type =
                $this->normalizeReportType(
                    (string) $data['type'],
                    'type'
                );

            $filters = [
                'date_from' =>
                    $data['date_from']
                    ?? null,

                'date_to' =>
                    $data['date_to']
                    ?? null,

                'group_by' =>
                    $data['group_by']
                    ?? 'day',

                'room_type' =>
                    $data['room_type']
                    ?? null,

                'department' =>
                    $data['department']
                    ?? null,

                'payment_method' =>
                    $data['payment_method']
                    ?? null,
            ];

            $filters = array_filter(
                $filters,
                static fn ($value) =>
                    $value !== null
                    && $value !== ''
            );

            if (
                isset($filters['group_by'])
            ) {
                $filters['group_by'] =
                    $this->normalizeGroupByValue(
                        (string) $filters['group_by']
                    );
            }

            $filters =
                $this->applyOptionalFilters(
                    $filters,
                    $data
                );

            $this->validateDateRange(
                $filters
            );

            if (
                method_exists(
                    $this->reportService,
                    'generate'
                )
            ) {
                $report =
                    $this->reportService->generate(
                        $type,
                        $filters
                    );
            } else {
                $report =
                    $this->getReportData(
                        $type,
                        $filters
                    );
            }

            $this->success(
                $report,
                'Report generated successfully.',
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

    private function getAllowedReportTypes(): array
    {
        return [
            'revenue',
            'occupancy',
            'bookings',
            'guests',
            'rooms',
            'payments',
            'staff',
            'financial',
            'performance',
            'summary',
        ];
    }

    private function normalizeReportType(
        string $type,
        string $fieldName = 'type'
    ): string {
        $normalized =
            strtolower(
                trim($type)
            );

        if ($normalized === '') {
            throw new InvalidArgumentException(
                "{$fieldName} is required."
            );
        }

        if (
            !in_array(
                $normalized,
                $this->getAllowedReportTypes(),
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid report type.'
            );
        }

        return $normalized;
    }

    private function normalizeExportFormat(
        string $format
    ): string {
        $normalized =
            strtolower(
                trim($format)
            );

        if (
            !in_array(
                $normalized,
                ['csv', 'json'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported export format.'
            );
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | DATE FILTERS
    |--------------------------------------------------------------------------
    */

    private function getDateFilters(): array
    {
        $dateFrom =
            $this->getQuery(
                'date_from'
            );

        $dateTo =
            $this->getQuery(
                'date_to'
            );

        if (
            $dateFrom === null
            && $dateTo === null
        ) {
            /*
             * Default to current month.
             */
            $dateFrom =
                date('Y-m-01');

            $dateTo =
                date('Y-m-t');
        }

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $this->validateDateRange(
            $filters
        );

        return $filters;
    }

    private function validateDateRange(
        array $filters
    ): void {
        $dateFrom =
            $filters['date_from']
            ?? null;

        $dateTo =
            $filters['date_to']
            ?? null;

        if ($dateFrom !== null) {
            $this->validateDate(
                $dateFrom,
                'date_from'
            );
        }

        if ($dateTo !== null) {
            $this->validateDate(
                $dateTo,
                'date_to'
            );
        }

        if (
            $dateFrom !== null
            && $dateTo !== null
            && $dateFrom > $dateTo
        ) {
            throw new InvalidArgumentException(
                'date_from cannot be later than date_to.'
            );
        }
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
            || $parsed->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                "{$field} must use YYYY-MM-DD format."
            );
        }
    }

    private function applyOptionalFilters(
        array $filters,
        array $source
    ): array {
        foreach (
            ['room_type', 'department', 'payment_method']
            as $key
        ) {
            if (
                !array_key_exists(
                    $key,
                    $source
                )
            ) {
                continue;
            }

            $value = $source[$key];

            if (is_array($value)) {
                throw new InvalidArgumentException(
                    "{$key} must be a single value."
                );
            }

            if (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    "{$key} must be a scalar value."
                );
            }

            $normalized =
                trim(
                    (string) $value
                );

            if ($normalized !== '') {
                $filters[$key] =
                    $normalized;
            }
        }

        return $filters;
    }

    /*
    |--------------------------------------------------------------------------
    | GROUPING
    |--------------------------------------------------------------------------
    */

    private function normalizeGroupByValue(
        string $value
    ): string {
        $groupBy = strtolower(trim($value));

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

    private function getGroupBy(): string
    {
        $groupBy = $this->getQuery(
            'group_by',
            'day'
        );

        return $this->normalizeGroupByValue(
            $groupBy ?? 'day'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPORT DISPATCH
    |--------------------------------------------------------------------------
    */

    private function getReportData(
        string $type,
        array $filters
    ): mixed {
        return match ($type) {
            'revenue' =>
                $this->reportService->revenue(
                    $filters
                ),

            'occupancy' =>
                $this->reportService->occupancy(
                    $filters
                ),

            'bookings' =>
                $this->reportService->bookings(
                    $filters
                ),

            'guests' =>
                $this->reportService->guests(
                    $filters
                ),

            'rooms' =>
                $this->reportService->rooms(
                    $filters
                ),

            'payments' =>
                $this->reportService->payments(
                    $filters
                ),

            'staff' =>
                $this->reportService->staff(
                    $filters
                ),

            'financial' =>
                method_exists(
                    $this->reportService,
                    'financial'
                )
                    ? $this->reportService->financial(
                        $filters
                    )
                    : $this->reportService->revenue(
                        $filters
                    ),

            'performance' =>
                method_exists(
                    $this->reportService,
                    'performance'
                )
                    ? $this->reportService->performance(
                        $filters
                    )
                    : $this->reportService->summary(
                        $filters
                    ),

            'summary' =>
                $this->reportService->summary(
                    $filters
                ),

            default =>
                throw new InvalidArgumentException(
                    'Unsupported report type.'
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |--------------------------------------------------------------------------
    */

    private function sendExport(
        mixed $result,
        string $type,
        string $format
    ): void {
        if (
            is_string($result)
            && $format === 'csv'
        ) {
            header(
                'Content-Type: text/csv; charset=utf-8'
            );

            header(
                'Content-Disposition: attachment; filename="'
                . $this->safeFilename($type)
                . '-report-'
                . date('Y-m-d')
                . '.csv"'
            );

            echo $result;
            exit;
        }

        if ($format === 'json') {
            $this->sendJsonDownload(
                $result,
                $type
            );

            return;
        }

        $this->sendCsvDownload(
            $result,
            $type
        );
    }

    private function sendJsonDownload(
        mixed $data,
        string $type
    ): void {
        header(
            'Content-Type: application/json; charset=utf-8'
        );

        header(
            'Content-Disposition: attachment; filename="'
            . $this->safeFilename($type)
            . '-report-'
            . date('Y-m-d')
            . '.json"'
        );

        echo json_encode(
            $data,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
        );

        exit;
    }

    private function sendCsvDownload(
        mixed $data,
        string $type
    ): void {
        header(
            'Content-Type: text/csv; charset=utf-8'
        );

        header(
            'Content-Disposition: attachment; filename="'
            . $this->safeFilename($type)
            . '-report-'
            . date('Y-m-d')
            . '.csv"'
        );

        $output = fopen(
            'php://output',
            'w'
        );

        if ($output === false) {
            throw new RuntimeException(
                'Unable to create CSV output.'
            );
        }

        /*
         * Normalize common report response formats.
         */
        $rows = $this->normalizeRows(
            $data
        );

        if (empty($rows)) {
            fputcsv(
                $output,
                ['No data available']
            );

            fclose($output);
            exit;
        }

        $headers = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                foreach (array_keys($row) as $key) {
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
        }

        if (empty($headers)) {
            fputcsv(
                $output,
                ['value']
            );

            foreach ($rows as $row) {
                fputcsv(
                    $output,
                    [(string) $row]
                );
            }

            fclose($output);
            exit;
        }

        fputcsv(
            $output,
            $headers
        );

        foreach ($rows as $row) {
            $line = [];

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

                $line[] = $this->protectCsvValue($value);
            }

            fputcsv(
                $output,
                $line
            );
        }

        fclose($output);
        exit;
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

        /*
         * Typical service response:
         *
         * [
         *     'items' => [...]
         * ]
         */
        if (
            isset($data['items'])
            && is_array($data['items'])
        ) {
            return $data['items'];
        }

        /*
         * Typical report response:
         *
         * [
         *     'data' => [...]
         * ]
         */
        if (
            isset($data['data'])
            && is_array($data['data'])
        ) {
            return $data['data'];
        }

        /*
         * Sequential array of rows.
         */
        if (
            array_is_list($data)
        ) {
            return $data;
        }

        /*
         * Associative report summary.
         */
        return [$data];
    }

    private function safeFilename(
        string $value
    ): string {
        $value =
            preg_replace(
                '/[^a-zA-Z0-9_-]+/',
                '-',
                $value
            );

        return trim(
            (string) $value,
            '-'
        ) ?: 'report';
    }

    private function protectCsvValue(mixed $value): mixed
    {
        if (
            is_string($value)
            && preg_match('/^[\s]*[=+\-@]/u', $value) === 1
        ) {
            return "'" . $value;
        }

        return $value;
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

        if (!is_scalar($_GET[$key])) {
            throw new InvalidArgumentException(
                "{$key} must be a single value."
            );
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
        if (!isset($_GET[$key])) {
            return $default;
        }

        $value = $_GET[$key];

        if (
            !is_scalar($value)
            || filter_var(
                (string) $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw new InvalidArgumentException(
                "{$key} must be an integer."
            );
        }

        return (int) $value;
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
    | ERROR HANDLING
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

        /*
         * Do not expose SQL errors, filesystem paths,
         * credentials, or internal stack traces.
         */
        $this->error(
            'An unexpected server error occurred while generating the report.',
            500
        );
    }
}
