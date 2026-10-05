<?php

declare(strict_types=1);

final class ResponseHelper
{
    public static function success(
        mixed $data = null,
        ?string $message = 'Request successful',
        int $statusCode = 200,
        array $meta = []
    ): never {
        self::send($statusCode, true, $message, $data, $meta);
    }

    public static function error(
        ?string $message = 'Request failed',
        int $statusCode = 400,
        mixed $data = null,
        array $meta = []
    ): never {
        self::send($statusCode, false, $message, $data, $meta);
    }

    public static function validation(
        array $errors,
        ?string $message = 'Validation failed',
        int $statusCode = 422
    ): never {
        self::send($statusCode, false, $message, ['errors' => $errors], ['validation' => true]);
    }

    public static function notFound(?string $message = 'Resource not found'): never
    {
        self::error($message, 404);
    }

    public static function unauthorized(?string $message = 'Unauthorized access'): never
    {
        self::error($message, 401);
    }

    public static function forbidden(?string $message = 'Forbidden access'): never
    {
        self::error($message, 403);
    }

    public static function serverError(?string $message = 'Internal server error'): never
    {
        self::error($message, 500);
    }

    private static function send(
        int $statusCode,
        bool $success,
        ?string $message,
        mixed $data,
        array $meta = []
    ): never {
        http_response_code($statusCode);

        $payload = [
            'success' => $success,
            'message' => $message ?? ($success ? 'Request successful' : 'Request failed'),
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('response_success')) {
    function response_success(
        mixed $data = null,
        ?string $message = 'Request successful',
        int $statusCode = 200,
        array $meta = []
    ): never {
        ResponseHelper::success($data, $message, $statusCode, $meta);
    }
}

if (!function_exists('response_error')) {
    function response_error(
        ?string $message = 'Request failed',
        int $statusCode = 400,
        mixed $data = null,
        array $meta = []
    ): never {
        ResponseHelper::error($message, $statusCode, $data, $meta);
    }
}

if (!function_exists('response_validation')) {
    function response_validation(array $errors, ?string $message = 'Validation failed', int $statusCode = 422): never
    {
        ResponseHelper::validation($errors, $message, $statusCode);
    }
}

if (!function_exists('response_not_found')) {
    function response_not_found(?string $message = 'Resource not found'): never
    {
        ResponseHelper::notFound($message);
    }
}

if (!function_exists('response_unauthorized')) {
    function response_unauthorized(?string $message = 'Unauthorized access'): never
    {
        ResponseHelper::unauthorized($message);
    }
}

if (!function_exists('response_forbidden')) {
    function response_forbidden(?string $message = 'Forbidden access'): never
    {
        ResponseHelper::forbidden($message);
    }
}

if (!function_exists('response_server_error')) {
    function response_server_error(?string $message = 'Internal server error'): never
    {
        ResponseHelper::serverError($message);
    }
}
