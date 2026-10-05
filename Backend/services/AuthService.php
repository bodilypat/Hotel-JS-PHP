<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Authentication Service
 *
 * File:
 * backend/services/AuthService.php
 *
 * Responsibilities:
 * - User registration
 * - Authentication
 * - JWT access/refresh tokens
 * - Logout / token revocation
 * - Password reset
 * - Email verification
 * - Password changes
 *
 * Database:
 * MySQL / MariaDB through PDO
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('DATETIME_FORMAT')) {
    define('DATETIME_FORMAT', 'Y-m-d H:i:s');
}

if (!defined('MIN_PASSWORD_LENGTH')) {
    define('MIN_PASSWORD_LENGTH', 8);
}

if (!defined('MAX_PASSWORD_LENGTH')) {
    define('MAX_PASSWORD_LENGTH', 128);
}

if (!defined('EMAIL_VERIFICATION_EXPIRATION')) {
    define('EMAIL_VERIFICATION_EXPIRATION', 60 * 60 * 24 * 2);
}

if (!defined('PASSWORD_RESET_EXPIRATION')) {
    define('PASSWORD_RESET_EXPIRATION', 60 * 60);
}

if (!defined('DEFAULT_USER_ROLE')) {
    define('DEFAULT_USER_ROLE', 'user');
}

if (!defined('USER_STATUS_ACTIVE')) {
    define('USER_STATUS_ACTIVE', 'active');
}

if (!defined('USER_STATUS_SUSPENDED')) {
    define('USER_STATUS_SUSPENDED', 'suspended');
}

if (!defined('JWT_EXPIRATION')) {
    define('JWT_EXPIRATION', 60 * 60);
}

if (!defined('AUTH_SCHEME')) {
    define('AUTH_SCHEME', 'Bearer');
}

if (!defined('TOKEN_TYPE_ACCESS')) {
    define('TOKEN_TYPE_ACCESS', 'access');
}

if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost');
}

if (!defined('JWT_ALGORITHM')) {
    define('JWT_ALGORITHM', 'HS256');
}

if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', 'development-secret-key');
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

if (!defined('LOG_LEVEL_ERROR')) {
    define('LOG_LEVEL_ERROR', 'error');
}

// ---------------------------------------------------------
// Dependencies
// ---------------------------------------------------------

if (file_exists(BASE_PATH . '/models/User.php')) {
    require_once BASE_PATH . '/models/User.php';
}

// ---------------------------------------------------------
// Authentication Service
// ---------------------------------------------------------

class AuthService
{
    private PDO $db;
    private const REFRESH_TOKEN_EXPIRATION = 60 * 60 * 24 * 7;

    // =====================================================
    // Constructor
    // =====================================================

    public function __construct()
    {
        if (function_exists('db')) {
            $this->db = db();
        } else {
            throw new RuntimeException('Database connection is not configured.');
        }
    }

    // =====================================================
    // Register
    // =====================================================

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Required registration fields are missing.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        $this->validatePassword($password);

        $existingUser = $this->findUserByEmail($email);
        if ($existingUser !== null) {
            throw new RuntimeException('An account with this email already exists.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Unable to securely process password.');
        }

        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = date(DATETIME_FORMAT, time() + EMAIL_VERIFICATION_EXPIRATION);

        $userId = $this->createUser([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'password_hash' => $passwordHash,
            'role' => DEFAULT_USER_ROLE,
            'status' => USER_STATUS_ACTIVE,
            'email_verified' => 0,
            'email_verification_token' => hash('sha256', $verificationToken),
            'email_verification_expires' => $verificationExpires,
        ]);

        $user = $this->findUserById($userId);
        if ($user === null) {
            throw new RuntimeException('Unable to retrieve newly created user.');
        }

        $this->sendVerificationEmail($email, $firstName, $verificationToken);

        return [
            'user' => $this->sanitizeUser($user),
        ];
    }

    // =====================================================
    // Login
    // =====================================================

    /**
     * Authenticate a user.
     *
     * @param array $data
     * @return array
     */
    public function login(array $data): array
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Email and password are required.',
            ];
        }

        $user = $this->findUserByEmail($email);
        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
            ];
        }

        $status = (string) ($user['status'] ?? USER_STATUS_ACTIVE);
        if ($status !== USER_STATUS_ACTIVE) {
            if ($status === USER_STATUS_SUSPENDED) {
                return [
                    'success' => false,
                    'message' => 'Your account has been suspended.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Your account is not active.',
            ];
        }

        $passwordHash = (string) ($user['password_hash'] ?? $user['password'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
            ];
        }

        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash !== false) {
                $this->updatePasswordHash((int) $user['id'], $newHash);
            }
        }

        $tokens = $this->createTokenPair($user);
        $this->updateLastLogin((int) $user['id']);

        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'tokens' => [
                'token_type' => AUTH_SCHEME,
                'access_token' => $tokens['access_token'],
                'expires_in' => JWT_EXPIRATION,
                'refresh_token' => $tokens['refresh_token'],
            ],
        ];
    }

    // =====================================================
    // Logout
    // =====================================================

    /**
     * Logout by revoking the supplied token.
     */
    public function logout(?string $token): array
    {
        if ($token === null || trim($token) === '') {
            return [
                'success' => true,
                'message' => 'Already logged out.',
            ];
        }

        $token = $this->normalizeBearerToken($token);
        if ($token === '') {
            return [
                'success' => true,
                'message' => 'Already logged out.',
            ];
        }

        $payload = $this->decodeJwt($token);
        if ($payload !== null) {
            $jti = (string) ($payload['jti'] ?? '');
            $expiresAt = (int) ($payload['exp'] ?? time());

            if ($jti !== '') {
                $this->revokeToken($jti, $expiresAt);
            }
        }

        return [
            'success' => true,
        ];
    }

    // =====================================================
    // Current User
    // =====================================================

    /**
     * Return authenticated user information.
     */
    public function me(array $user): array
    {
        return [
            'user' => $this->sanitizeUser($user),
        ];
    }

    // =====================================================
    // Authenticate Token
    // =====================================================

    /**
     * Authenticate a JWT access token.
     */
    public function authenticateToken(string $token): ?array
    {
        $token = $this->normalizeBearerToken($token);
        if ($token === '') {
            return null;
        }

        $payload = $this->decodeJwt($token);
        if ($payload === null) {
            return null;
        }

        $userId = (int) ($payload['sub'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $jti = (string) ($payload['jti'] ?? '');
        if ($jti !== '' && $this->isTokenRevoked($jti)) {
            return null;
        }

        $user = $this->findUserById($userId);
        if ($user === null) {
            return null;
        }

        if (($user['status'] ?? '') !== USER_STATUS_ACTIVE) {
            return null;
        }

        return $user;
    }

    // =====================================================
    // Refresh Token
    // =====================================================

    /**
     * Generate a new access token from refresh token.
     */
    public function refreshToken(string $refreshToken): array
    {
        $refreshToken = $this->normalizeBearerToken($refreshToken);
        $refreshToken = trim($refreshToken);

        if ($refreshToken === '') {
            return [
                'success' => false,
                'message' => 'Refresh token is required.',
            ];
        }

        $tokenHash = hash('sha256', $refreshToken);

        $statement = $this->db->prepare(
            'SELECT *
             FROM refresh_tokens
             WHERE token_hash = :token_hash
             AND revoked_at IS NULL
             AND expires_at > NOW()
             LIMIT 1'
        );

        $statement->execute(['token_hash' => $tokenHash]);
        $storedToken = $statement->fetch();

        if (!$storedToken) {
            return [
                'success' => false,
                'message' => 'Invalid or expired refresh token.',
            ];
        }

        $user = $this->findUserById((int) $storedToken['user_id']);
        if ($user === null) {
            return [
                'success' => false,
                'message' => 'User account no longer exists.',
            ];
        }

        if (($user['status'] ?? '') !== USER_STATUS_ACTIVE) {
            return [
                'success' => false,
                'message' => 'User account is not active.',
            ];
        }

        $this->revokeRefreshToken((int) $storedToken['id']);
        $tokens = $this->createTokenPair($user);

        return [
            'success' => true,
            'tokens' => [
                'token_type' => AUTH_SCHEME,
                'access_token' => $tokens['access_token'],
                'expires_in' => JWT_EXPIRATION,
                'refresh_token' => $tokens['refresh_token'],
            ],
        ];
    }

    // =====================================================
    // Forgot Password
    // =====================================================

    /**
     * Create a password reset request.
     *
     * The method intentionally does not reveal whether
     * the account exists.
     */
    public function forgotPassword(string $email): void
    {
        $email = strtolower(trim($email));
        $user = $this->findUserByEmail($email);

        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date(DATETIME_FORMAT, time() + PASSWORD_RESET_EXPIRATION);

        $statement = $this->db->prepare(
            'UPDATE users
             SET password_reset_token = :token,
                 password_reset_expires = :expires
             WHERE id = :id'
        );

        $statement->execute([
            'token' => $tokenHash,
            'expires' => $expiresAt,
            'id' => $user['id'],
        ]);

        $this->sendPasswordResetEmail($email, (string) ($user['first_name'] ?? ''), $token);
    }

    // =====================================================
    // Reset Password
    // =====================================================

    /**
     * Reset password with reset token.
     */
    public function resetPassword(string $token, string $password): array
    {
        $token = trim($token);
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'Reset token is required.',
            ];
        }

        try {
            $this->validatePassword($password);
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }

        $tokenHash = hash('sha256', $token);

        $statement = $this->db->prepare(
            'SELECT *
             FROM users
             WHERE password_reset_token = :token
             AND password_reset_expires > NOW()
             LIMIT 1'
        );

        $statement->execute(['token' => $tokenHash]);
        $user = $statement->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ];
        }

        $existingPasswordHash = (string) ($user['password_hash'] ?? '');
        if ($existingPasswordHash !== '' && password_verify($password, $existingPasswordHash)) {
            return [
                'success' => false,
                'message' => 'New password must be different from the current one.',
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Unable to securely process password.');
        }

        $statement = $this->db->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 password_reset_token = NULL,
                 password_reset_expires = NULL,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'password_hash' => $passwordHash,
            'id' => $user['id'],
        ]);

        $this->revokeUserRefreshTokens((int) $user['id']);

        return [
            'success' => true,
        ];
    }

    // =====================================================
    // Verify Email
    // =====================================================

    /**
     * Verify email address using token.
     */
    public function verifyEmail(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'Verification token is required.',
            ];
        }

        $tokenHash = hash('sha256', $token);

        $statement = $this->db->prepare(
            'SELECT id
             FROM users
             WHERE email_verification_token = :token
             AND email_verification_expires > NOW()
             LIMIT 1'
        );

        $statement->execute(['token' => $tokenHash]);
        $user = $statement->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification token.',
            ];
        }

        $statement = $this->db->prepare(
            'UPDATE users
             SET email_verified = 1,
                 email_verification_token = NULL,
                 email_verification_expires = NULL,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute(['id' => $user['id']]);

        return [
            'success' => true,
        ];
    }

    // =====================================================
    // Resend Verification
    // =====================================================

    /**
     * Resend verification email.
     */
    public function resendVerification(string $email): void
    {
        $email = strtolower(trim($email));
        $user = $this->findUserByEmail($email);

        if ($user === null || !empty($user['email_verified'])) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date(DATETIME_FORMAT, time() + EMAIL_VERIFICATION_EXPIRATION);

        $statement = $this->db->prepare(
            'UPDATE users
             SET email_verification_token = :token,
                 email_verification_expires = :expires
             WHERE id = :id'
        );

        $statement->execute([
            'token' => $tokenHash,
            'expires' => $expiresAt,
            'id' => $user['id'],
        ]);

        $this->sendVerificationEmail($email, (string) ($user['first_name'] ?? ''), $token);
    }

    // =====================================================
    // Change Password
    // =====================================================

    /**
     * Change password for authenticated user.
     */
    public function changePassword(array $user, string $currentPassword, string $newPassword): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid user account.',
            ];
        }

        try {
            $this->validatePassword($newPassword);
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }

        $storedUser = $this->findUserById($userId);
        if ($storedUser === null) {
            return [
                'success' => false,
                'message' => 'User account not found.',
            ];
        }

        $passwordHash = (string) ($storedUser['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($currentPassword, $passwordHash)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        if (password_verify($newPassword, $passwordHash)) {
            return [
                'success' => false,
                'message' => 'New password must be different from the current one.',
            ];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($newHash === false) {
            throw new RuntimeException('Unable to securely process password.');
        }

        $this->updatePasswordHash($userId, $newHash);
        $this->revokeUserRefreshTokens($userId);

        return [
            'success' => true,
        ];
    }

    // =====================================================
    // User Database Operations
    // =====================================================

    /**
     * Find user by email.
     */
    private function findUserByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT *
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * Find user by ID.
     */
    private function findUserById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT *
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * Create a user.
     */
    private function createUser(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (
                first_name,
                last_name,
                email,
                phone,
                password_hash,
                role,
                status,
                email_verified,
                email_verification_token,
                email_verification_expires,
                created_at,
                updated_at
             ) VALUES (
                :first_name,
                :last_name,
                :email,
                :phone,
                :password_hash,
                :role,
                :status,
                :email_verified,
                :email_verification_token,
                :email_verification_expires,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
            'status' => $data['status'],
            'email_verified' => $data['email_verified'],
            'email_verification_token' => $data['email_verification_token'],
            'email_verification_expires' => $data['email_verification_expires'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update password hash.
     */
    private function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'password_hash' => $passwordHash,
            'id' => $userId,
        ]);
    }

    /**
     * Update last login timestamp.
     */
    private function updateLastLogin(int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET last_login_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute(['id' => $userId]);
    }

    /**
     * Validate a user password.
     */
    private function validatePassword(string $password): void
    {
        $password = trim($password);

        if ($password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password is too short.');
        }

        if (strlen($password) > MAX_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password is too long.');
        }
    }

    /**
     * Strip a Bearer authorization prefix when present.
     */
    private function normalizeBearerToken(string $token): string
    {
        $token = trim($token);

        if (preg_match('/^Bearer\s+/i', $token) === 1) {
            return trim(substr($token, 7));
        }

        return $token;
    }

    // =====================================================
    // JWT
    // =====================================================

    /**
     * Create access and refresh token pair.
     */
    private function createTokenPair(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Invalid user ID.');
        }

        $jti = bin2hex(random_bytes(16));
        $now = time();

        $payload = [
            'iss' => APP_URL,
            'aud' => 'hotel-management-api',
            'iat' => $now,
            'exp' => $now + JWT_EXPIRATION,
            'jti' => $jti,
            'sub' => $userId,
            'type' => TOKEN_TYPE_ACCESS,
        ];

        $accessToken = $this->encodeJwt($payload);

        $refreshToken = bin2hex(random_bytes(64));
        $refreshTokenHash = hash('sha256', $refreshToken);
        $refreshExpiration = time() + self::REFRESH_TOKEN_EXPIRATION;
        $refreshExpiresAt = date(DATETIME_FORMAT, $refreshExpiration);

        $statement = $this->db->prepare(
            'INSERT INTO refresh_tokens (
                user_id,
                token_hash,
                expires_at,
                created_at
             ) VALUES (
                :user_id,
                :token_hash,
                :expires_at,
                NOW()
             )'
        );

        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $refreshTokenHash,
            'expires_at' => $refreshExpiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Encode JWT.
     *
     * Uses the project's jwt helper when available.
     */
    private function encodeJwt(array $payload): string
    {
        if (function_exists('generateJwt')) {
            return generateJwt($payload);
        }

        if (function_exists('createJwt')) {
            return createJwt($payload);
        }

        if (JWT_ALGORITHM !== 'HS256') {
            throw new RuntimeException('Unsupported JWT algorithm.');
        }

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Decode and validate JWT.
     */
    private function decodeJwt(string $token): ?array
    {
        if (function_exists('verifyJwt')) {
            $payload = verifyJwt($token);
            return is_array($payload) ? $payload : null;
        }

        if (function_exists('decodeJwt')) {
            $payload = decodeJwt($token);
            return is_array($payload) ? $payload : null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, JWT_SECRET, true)
        );

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($payloadEncoded);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        if (isset($payload['iss']) && $payload['iss'] !== APP_URL) {
            return null;
        }

        if (isset($payload['type']) && $payload['type'] !== TOKEN_TYPE_ACCESS) {
            return null;
        }

        return $payload;
    }

    /**
     * Base64 URL encode.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode.
     */
    private function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }

    // =====================================================
    // Token Revocation
    // =====================================================

    /**
     * Store revoked access-token JTI.
     */
    private function revokeToken(string $jti, int $expiresAt): void
    {
        try {
            $statement = $this->db->prepare(
                'INSERT INTO revoked_tokens (
                    jti,
                    expires_at,
                    created_at
                 ) VALUES (
                    :jti,
                    :expires_at,
                    NOW()
                 )'
            );

            $statement->execute([
                'jti' => $jti,
                'expires_at' => date(DATETIME_FORMAT, $expiresAt),
            ]);
        } catch (PDOException $exception) {
            if (function_exists('logMessage')) {
                logMessage(LOG_LEVEL_ERROR, 'Unable to revoke JWT.', [
                    'jti' => $jti,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check whether JWT JTI has been revoked.
     */
    private function isTokenRevoked(string $jti): bool
    {
        try {
            $statement = $this->db->prepare(
                'SELECT id
                 FROM revoked_tokens
                 WHERE jti = :jti
                 AND expires_at > NOW()
                 LIMIT 1'
            );

            $statement->execute(['jti' => $jti]);
            return (bool) $statement->fetch();
        } catch (PDOException $exception) {
            if (function_exists('logMessage')) {
                logMessage(LOG_LEVEL_ERROR, 'Unable to check JWT revocation.', [
                    'jti' => $jti,
                    'error' => $exception->getMessage(),
                ]);
            }

            return true;
        }
    }

    /**
     * Revoke refresh token.
     */
    private function revokeRefreshToken(int $tokenId): void
    {
        $statement = $this->db->prepare(
            'UPDATE refresh_tokens
             SET revoked_at = NOW()
             WHERE id = :id
             AND revoked_at IS NULL'
        );

        $statement->execute(['id' => $tokenId]);
    }

    /**
     * Revoke all refresh tokens for a user.
     */
    private function revokeUserRefreshTokens(int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE refresh_tokens
             SET revoked_at = NOW()
             WHERE user_id = :user_id
             AND revoked_at IS NULL'
        );

        $statement->execute(['user_id' => $userId]);
    }

    // =====================================================
    // Email
    // =====================================================

    /**
     * Send verification email.
     *
     * This is intentionally a placeholder until an email
     * provider/service is connected.
     */
    private function sendVerificationEmail(string $email, string $firstName, string $token): void
    {
        if (APP_DEBUG === true) {
            error_log(sprintf(
                'Email verification token generated for %s (%s): %s',
                $email,
                $firstName,
                $token
            ));
        }
    }

    /**
     * Send password reset email.
     */
    private function sendPasswordResetEmail(string $email, string $firstName, string $token): void
    {
        if (APP_DEBUG === true) {
            error_log(sprintf(
                'Password reset token generated for %s (%s): %s',
                $email,
                $firstName,
                $token
            ));
        }
    }

    // =====================================================
    // User Sanitization
    // =====================================================

    /**
     * Remove sensitive database fields before returning
     * user information to the client.
     */
    private function sanitizeUser(array $user): array
    {
        $sensitiveFields = [
            'password',
            'password_hash',
            'password_reset_token',
            'password_reset_expires',
            'email_verification_token',
            'email_verification_expires',
        ];

        foreach ($sensitiveFields as $field) {
            unset($user[$field]);
        }

        return $user;
    }
}
