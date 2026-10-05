<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('HTTP_OK')) {
    define('HTTP_OK', 200);
}

if (!defined('HTTP_CREATED')) {
    define('HTTP_CREATED', 201);
}

if (!defined('HTTP_BAD_REQUEST')) {
    define('HTTP_BAD_REQUEST', 400);
}

if (!defined('HTTP_UNAUTHORIZED')) {
    define('HTTP_UNAUTHORIZED', 401);
}

if (!defined('HTTP_UNPROCESSABLE_ENTITY')) {
    define('HTTP_UNPROCESSABLE_ENTITY', 422);
}

if (!defined('HTTP_INTERNAL_SERVER_ERROR')) {
    define('HTTP_INTERNAL_SERVER_ERROR', 500);
}

if (!defined('MIN_PASSWORD_LENGTH')) {
    define('MIN_PASSWORD_LENGTH', 8);
}

if (!defined('MAX_PASSWORD_LENGTH')) {
    define('MAX_PASSWORD_LENGTH', 255);
}

if (!defined('DATETIME_FORMAT')) {
    define('DATETIME_FORMAT', 'Y-m-d H:i:s');
}

if (!defined('LOG_LEVEL_ERROR')) {
    define('LOG_LEVEL_ERROR', 'error');
}

/**
 * Hotel Management System
 *
 * Authentication Controller
 *
 * File:
 * backend/controllers/AuthController.php
 *
 * Responsibilities:
 * - Register users
 * - Login users
 * - Logout users
 * - Refresh access tokens
 * - Get authenticated user
 * - Forgot password
 * - Reset password
 * - Verify email
 *
 * Business logic belongs in AuthService.
 */

require_once BASE_PATH . '/services/AuthService.php';
require_once BASE_PATH . '/validation/authValidation.php';

class AuthController
{
    /**
     * @var object
     */
    private object $authService;


    // =====================================================
    // Constructor
    // =====================================================

    public function __construct()
    {
        $serviceClass = 'AuthService';

        if (!class_exists($serviceClass, true)) {
            throw new RuntimeException(
                "Authentication service class '{$serviceClass}' was not found."
            );
        }

        $this->authService = new $serviceClass();
    }


    // =====================================================
    // Register
    // =====================================================

    /**
     * Register a new user.
     *
     * POST /api/auth/register
     *
     * Expected body:
     *
     * {
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "password": "Password123!",
     *     "phone": "+1234567890"
     * }
     */
    public function register(array $request): void
    {
        try {

            $data = $this->getRequestBody($request);

            if (isset($data['email'])) {
                $data['email'] = $this->normalizeEmail(
                    (string) $data['email']
                );
            }

            $validation = $this->validateRegistration(
                $data
            );

            if (!$validation['valid']) {
                $this->validationError(
                    $validation['errors']
                );
                return;
            }

            $result = $this->authService->register(
                $data
            );

            $this->success(
                'Registration successful.',
                $result,
                HTTP_CREATED
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Registration failed.'
            );
        }
    }


    // =====================================================
    // Login
    // =====================================================

    /**
     * Authenticate a user.
     *
     * POST /api/auth/login
     *
     * Expected body:
     *
     * {
     *     "email": "john@example.com",
     *     "password": "Password123!"
     * }
     */
    public function login(array $request): void
    {
        try {

            $data = $this->getRequestBody($request);

            if (isset($data['email'])) {
                $data['email'] = $this->normalizeEmail(
                    (string) $data['email']
                );
            }

            $validation = $this->validateLogin(
                $data
            );

            if (!$validation['valid']) {
                $this->validationError(
                    $validation['errors']
                );
                return;
            }

            $result = $this->authService->login(
                $data
            );

            if (!$this->isSuccessfulAuthResult($result)) {
                $this->error(
                    $this->getAuthResultMessage(
                        $result,
                        'Invalid email or password.'
                    ),
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $this->success(
                'Login successful.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Login failed.'
            );
        }
    }


    // =====================================================
    // Logout
    // =====================================================

    /**
     * Logout the authenticated user.
     *
     * POST /api/auth/logout
     */
    public function logout(array $request): void
    {
        try {

            $token = $this->extractBearerToken(
                $request
            );

            $result = $this->authService->logout(
                $token
            );

            $this->success(
                'Logout successful.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Logout failed.'
            );
        }
    }


    // =====================================================
    // Current User
    // =====================================================

    /**
     * Get the currently authenticated user.
     *
     * GET /api/auth/me
     */
    public function me(array $request): void
    {
        try {

            $user = $this->getAuthenticatedUser(
                $request
            );

            if ($user === null) {
                $this->error(
                    'Authentication required.',
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $result = $this->authService->me(
                $user
            );

            $this->success(
                'Authenticated user retrieved successfully.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Unable to retrieve authenticated user.'
            );
        }
    }


    // =====================================================
    // Refresh Token
    // =====================================================

    /**
     * Refresh an access token.
     *
     * POST /api/auth/refresh
     *
     * Expected body:
     *
     * {
     *     "refresh_token": "..."
     * }
     */
    public function refresh(array $request): void
    {
        try {

            $data = $this->getRequestBody($request);

            $refreshToken = trim(
                (string) (
                    $data['refresh_token']
                    ?? ''
                )
            );

            if ($refreshToken === '') {
                $this->validationError([
                    'refresh_token' => [
                        'Refresh token is required.'
                    ]
                ]);
                return;
            }

            $result = $this->authService->refreshToken(
                $refreshToken
            );

            if (
                !is_array($result) ||
                ($result['success'] ?? true) === false
            ) {
                $this->error(
                    $result['message']
                        ?? 'Invalid or expired refresh token.',
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $this->success(
                'Access token refreshed successfully.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Token refresh failed.'
            );
        }
    }


    // =====================================================
    // Forgot Password
    // =====================================================

    /**
     * Request a password reset.
     *
     * POST /api/auth/forgot-password
     *
     * Expected body:
     *
     * {
     *     "email": "john@example.com"
     * }
     */
    public function forgotPassword(array $request): void
    {
        try {

            $data = $this->getRequestBody($request);

            $email = trim(
                (string) (
                    $data['email']
                    ?? ''
                )
            );

            if (isset($data['email'])) {
                $data['email'] = $this->normalizeEmail(
                    (string) $data['email']
                );
                $email = $data['email'];
            }

            if (
                $email === '' ||
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $this->validationError([
                    'email' => [
                        'A valid email address is required.'
                    ]
                ]);
                return;
            }

            /*
             * Do not reveal whether an email exists.
             *
             * The service should return the same public response
             * whether or not the account exists.
             */
            $this->authService->forgotPassword(
                $email
            );

            $this->success(
                'If an account exists for this email, password reset instructions have been sent.',
                null,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Password reset request failed.'
            );
        }
    }


    // =====================================================
    // Reset Password
    // =====================================================

    /**
     * Reset password using a reset token.
     *
     * POST /api/auth/reset-password
     *
     * Expected body:
     *
     * {
     *     "token": "...",
     *     "password": "NewPassword123!",
     *     "password_confirmation": "NewPassword123!"
     * }
     */
    public function resetPassword(array $request): void
    {
        try {

            $data = $this->getRequestBody($request);

            $token = trim(
                (string) (
                    $data['token']
                    ?? ''
                )
            );

            $password = (string) (
                $data['password']
                ?? ''
            );

            $passwordConfirmation = (string) (
                $data['password_confirmation']
                ?? ''
            );

            $errors = [];

            if ($token === '') {
                $errors['token'][] =
                    'Reset token is required.';
            }

            if (
                strlen($password) < MIN_PASSWORD_LENGTH
            ) {
                $errors['password'][] =
                    'Password must be at least '
                    . MIN_PASSWORD_LENGTH
                    . ' characters long.';
            }

            if (
                strlen($password) > MAX_PASSWORD_LENGTH
            ) {
                $errors['password'][] =
                    'Password cannot exceed '
                    . MAX_PASSWORD_LENGTH
                    . ' characters.';
            }

            if ($password !== $passwordConfirmation) {
                $errors['password_confirmation'][] =
                    'Passwords do not match.';
            }

            if (!empty($errors)) {
                $this->validationError(
                    $errors
                );
                return;
            }

            $result = $this->authService->resetPassword(
                $token,
                $password
            );

            if (
                !is_array($result) ||
                ($result['success'] ?? true) === false
            ) {
                $this->error(
                    $result['message']
                        ?? 'Invalid or expired reset token.',
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $this->success(
                'Password has been reset successfully.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Password reset failed.'
            );
        }
    }


    // =====================================================
    // Verify Email
    // =====================================================

    /**
     * Verify a user's email address.
     *
     * POST /api/auth/verify-email
     *
     * Expected body:
     *
     * {
     *     "token": "..."
     * }
     */
    public function verifyEmail(array $request): void
    {
        try {

            $data = $this->getRequestBody($request);

            $token = trim(
                (string) (
                    $data['token']
                    ?? ''
                )
            );

            if ($token === '') {
                $this->validationError([
                    'token' => [
                        'Verification token is required.'
                    ]
                ]);
                return;
            }

            $result = $this->authService->verifyEmail(
                $token
            );

            if (
                !is_array($result) ||
                ($result['success'] ?? true) === false
            ) {
                $this->error(
                    $result['message']
                        ?? 'Invalid or expired verification token.',
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $this->success(
                'Email address verified successfully.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Email verification failed.'
            );
        }
    }


    // =====================================================
    // Resend Verification Email
    // =====================================================

    /**
     * Resend email verification.
     *
     * POST /api/auth/resend-verification
     *
     * Expected body:
     *
     * {
     *     "email": "john@example.com"
     * }
     */
    public function resendVerification(
        array $request
    ): void {
        try {

            $data = $this->getRequestBody($request);

            $email = trim(
                (string) (
                    $data['email']
                    ?? ''
                )
            );

            if (isset($data['email'])) {
                $data['email'] = $this->normalizeEmail(
                    (string) $data['email']
                );
                $email = $data['email'];
            }

            if (
                $email === '' ||
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $this->validationError([
                    'email' => [
                        'A valid email address is required.'
                    ]
                ]);
                return;
            }

            $this->authService->resendVerification(
                $email
            );

            /*
             * Avoid revealing whether the email belongs
             * to an existing account.
             */
            $this->success(
                'If an account exists for this email, verification instructions have been sent.',
                null,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Unable to resend verification email.'
            );
        }
    }


    // =====================================================
    // Change Password
    // =====================================================

    /**
     * Change password for authenticated user.
     *
     * POST /api/auth/change-password
     *
     * Expected body:
     *
     * {
     *     "current_password": "OldPassword123!",
     *     "password": "NewPassword123!",
     *     "password_confirmation": "NewPassword123!"
     * }
     */
    public function changePassword(
        array $request
    ): void {
        try {

            $user = $this->getAuthenticatedUser(
                $request
            );

            if ($user === null) {
                $this->error(
                    'Authentication required.',
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $data = $this->getRequestBody($request);

            $currentPassword = (string) (
                $data['current_password']
                ?? ''
            );

            $password = (string) (
                $data['password']
                ?? ''
            );

            $passwordConfirmation = (string) (
                $data['password_confirmation']
                ?? ''
            );

            $errors = [];

            if ($currentPassword === '') {
                $errors['current_password'][] =
                    'Current password is required.';
            }

            if (
                strlen($password) < MIN_PASSWORD_LENGTH
            ) {
                $errors['password'][] =
                    'Password must be at least '
                    . MIN_PASSWORD_LENGTH
                    . ' characters long.';
            }

            if (
                strlen($password) > MAX_PASSWORD_LENGTH
            ) {
                $errors['password'][] =
                    'Password cannot exceed '
                    . MAX_PASSWORD_LENGTH
                    . ' characters.';
            }

            if ($password !== $passwordConfirmation) {
                $errors['password_confirmation'][] =
                    'Passwords do not match.';
            }

            if ($currentPassword === $password) {
                $errors['password'][] =
                    'New password must be different from the current password.';
            }

            if (!empty($errors)) {
                $this->validationError(
                    $errors
                );
                return;
            }

            $result = $this->authService->changePassword(
                $user,
                $currentPassword,
                $password
            );

            if (
                !is_array($result) ||
                ($result['success'] ?? true) === false
            ) {
                $this->error(
                    $result['message']
                        ?? 'Unable to change password.',
                    HTTP_UNAUTHORIZED
                );
                return;
            }

            $this->success(
                'Password changed successfully.',
                $result,
                HTTP_OK
            );

        } catch (Throwable $exception) {

            $this->handleException(
                $exception,
                'Password change failed.'
            );
        }
    }


    // =====================================================
    // Request Helpers
    // =====================================================

    /**
     * Get request body.
     */
    private function getRequestBody(
        array $request
    ): array {
        if (isset($request['body'])) {
            if (is_array($request['body'])) {
                return $request['body'];
            }

            if (is_string($request['body'])) {
                $body = trim($request['body']);

                if ($body === '') {
                    return [];
                }

                $decoded = json_decode($body, true);

                if (is_array($decoded)) {
                    return $decoded;
                }

                parse_str($body, $parsedBody);

                if (!empty($parsedBody) && is_array($parsedBody)) {
                    return $parsedBody;
                }
            }
        }

        if (isset($request['rawBody']) && is_string($request['rawBody'])) {
            $body = trim($request['rawBody']);

            if ($body !== '') {
                $decoded = json_decode($body, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        if (isset($GLOBALS['HTTP_RAW_POST_DATA']) && is_string($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $body = trim($GLOBALS['HTTP_RAW_POST_DATA']);

            if ($body !== '') {
                $decoded = json_decode($body, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }


    /**
     * Extract Bearer token from request headers.
     */
    private function extractBearerToken(
        array $request
    ): ?string {

        $headers = $request['headers'] ?? [];

        if (!is_array($headers)) {
            return null;
        }

        $authorization = null;

        foreach ($headers as $name => $value) {
            $headerName = strtolower((string) $name);

            if ($headerName === 'authorization') {
                $authorization = $value;
                break;
            }

            if (is_array($value)) {
                foreach ($value as $entry) {
                    if (is_string($entry) && strtolower(trim($entry)) !== '') {
                        $authorization = $entry;
                        break 2;
                    }
                }
            }
        }

        if (is_array($authorization)) {
            $authorization = $authorization[0] ?? null;
        }

        if (
            !is_string($authorization) ||
            trim($authorization) === ''
        ) {
            return null;
        }

        if (
            preg_match(
                '/^Bearer\s+(.+)$/i',
                trim($authorization),
                $matches
            )
        ) {
            return trim($matches[1]);
        }

        return null;
    }


    /**
     * Get authenticated user from request.
     *
     * The route/middleware layer may already attach the
     * authenticated user to the request.
     */
    private function getAuthenticatedUser(
        array $request
    ): ?array {

        if (
            isset($request['user']) &&
            is_array($request['user'])
        ) {
            return $request['user'];
        }

        /*
         * If the middleware has only supplied a token,
         * let AuthService resolve it.
         */
        $token = $this->extractBearerToken(
            $request
        );

        if ($token === null) {
            return null;
        }

        $user = $this->authService->authenticateToken(
            $token
        );

        if (
            !is_array($user) ||
            empty($user)
        ) {
            return null;
        }

        return $user;
    }

    /**
     * Normalize an email address before validation/authentication.
     */
    private function normalizeEmail(
        string $email
    ): string {
        $email = trim($email);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($email);
        }

        return strtolower($email);
    }

    /**
     * Determine whether an authentication result indicates success.
     */
    private function isSuccessfulAuthResult(
        mixed $result
    ): bool {
        if (!is_array($result)) {
            return false;
        }

        if (array_key_exists('success', $result)) {
            return (bool) $result['success'];
        }

        return true;
    }

    /**
     * Return the message from an auth result if present.
     */
    private function getAuthResultMessage(
        mixed $result,
        string $defaultMessage
    ): string {
        if (is_array($result)) {
            if (isset($result['message']) && is_string($result['message'])) {
                return $result['message'];
            }

            if (isset($result['error']) && is_string($result['error'])) {
                return $result['error'];
            }
        }

        if (is_string($result) && trim($result) !== '') {
            return $result;
        }

        return $defaultMessage;
    }


    // =====================================================
    // Validation
    // =====================================================

    /**
     * Validate registration data.
     */
    private function validateRegistration(
        array $data
    ): array {

        /*
         * Use the project's validation helper when it
         * provides a registration validator.
         */
        if (
            function_exists('validateRegistration')
        ) {
            return validateRegistration($data);
        }

        $errors = [];

        $firstName = trim(
            (string) ($data['first_name'] ?? '')
        );

        $lastName = trim(
            (string) ($data['last_name'] ?? '')
        );

        $email = trim(
            (string) ($data['email'] ?? '')
        );

        $password = (string) (
            $data['password'] ?? ''
        );

        if ($firstName === '') {
            $errors['first_name'][] =
                'First name is required.';
        }

        if ($lastName === '') {
            $errors['last_name'][] =
                'Last name is required.';
        }

        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors['email'][] =
                'A valid email address is required.';
        }

        if (
            strlen($password) < MIN_PASSWORD_LENGTH
        ) {
            $errors['password'][] =
                'Password must be at least '
                . MIN_PASSWORD_LENGTH
                . ' characters long.';
        }

        if (
            strlen($password) > MAX_PASSWORD_LENGTH
        ) {
            $errors['password'][] =
                'Password cannot exceed '
                . MAX_PASSWORD_LENGTH
                . ' characters.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }


    /**
     * Validate login data.
     */
    private function validateLogin(
        array $data
    ): array {

        if (
            function_exists('validateLogin')
        ) {
            return validateLogin($data);
        }

        $errors = [];

        $email = trim(
            (string) ($data['email'] ?? '')
        );

        $password = (string) (
            $data['password'] ?? ''
        );

        if (
            $email === '' ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors['email'][] =
                'A valid email address is required.';
        }

        if ($password === '') {
            $errors['password'][] =
                'Password is required.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }


    // =====================================================
    // JSON Responses
    // =====================================================

    /**
     * Return successful JSON response.
     */
    private function success(
        string $message,
        mixed $data = null,
        int $statusCode = HTTP_OK
    ): void {

        $this->sendJson(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ],
            $statusCode
        );
    }


    /**
     * Return error JSON response.
     */
    private function error(
        string $message,
        int $statusCode = HTTP_BAD_REQUEST,
        mixed $errors = null
    ): void {

        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->sendJson(
            $response,
            $statusCode
        );
    }


    /**
     * Return validation error response.
     */
    private function validationError(
        array $errors
    ): void {

        $this->error(
            'Validation failed.',
            HTTP_UNPROCESSABLE_ENTITY,
            $errors
        );
    }


    /**
     * Send JSON response.
     */
    private function sendJson(
        array $data,
        int $statusCode
    ): void {

        http_response_code(
            $statusCode
        );

        if (
            !headers_sent()
        ) {
            header(
                'Content-Type: application/json; charset=utf-8'
            );
        }

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }


    // =====================================================
    // Exception Handling
    // =====================================================

    /**
     * Handle controller exceptions.
     */
    private function handleException(
        Throwable $exception,
        string $publicMessage
    ): void {

        /*
         * Log the detailed exception on the server.
         */
        if (
            function_exists('logMessage')
        ) {
            logMessage(
                LOG_LEVEL_ERROR,
                $exception->getMessage(),
                [
                    'controller' => self::class,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
            );
        } else {
            error_log(
                sprintf(
                    '[%s] %s: %s in %s:%d',
                    date(DATETIME_FORMAT),
                    self::class,
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                )
            );
        }

        /*
         * Never expose internal exception details to clients
         * in production.
         */
        $message = $publicMessage;

        if (
            defined('APP_DEBUG') &&
            APP_DEBUG === true
        ) {
            $message = $exception->getMessage();
        }

        $this->error(
            $message,
            HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
