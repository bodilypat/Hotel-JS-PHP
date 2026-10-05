<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * User Model
 *
 * File:
 * backend/models/User.php
 *
 * Responsibilities:
 * - User CRUD operations
 * - User lookup
 * - Authentication-related database operations
 * - User status and role management
 *
 * Security:
 * - Passwords are never returned by default.
 * - Password hashing is handled with password_hash().
 * - All database queries use prepared statements.
 */

// ---------------------------------------------------------
// Dependencies
// ---------------------------------------------------------

if (!defined('BASE_PATH')) {
    define(
        'BASE_PATH',
        dirname(__DIR__)
    );
}

require_once BASE_PATH . '/config/database.php';


// ---------------------------------------------------------
// User Model
// ---------------------------------------------------------

class User
{
    /**
     * Database connection.
     */
    private PDO $db;


    // =====================================================
    // Constructor
    // =====================================================

    public function __construct(?PDO $db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
            return;
        }

        if (function_exists('db')) {
            $this->db = db();
            return;
        }

        throw new RuntimeException(
            'Database connection is not available.'
        );
    }


    // =====================================================
    // Find
    // =====================================================

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @param bool $includeSensitive
     * @return array|null
     */
    public function findById(
        int $id,
        bool $includeSensitive = false
    ): ?array {

        if ($id <= 0) {
            return null;
        }

        $fields = $includeSensitive
            ? '*'
            : $this->publicFields();

        $sql = "
            SELECT {$fields}
            FROM users
            WHERE id = :id
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'id' => $id,
        ]);

        $user = $statement->fetch();

        return $user ?: null;
    }


    /**
     * Find user by email.
     *
     * Defaults to public fields so sensitive data is not exposed.
     *
     * @param string $email
     * @param bool $includeSensitive
     * @return array|null
     */
    public function findByEmail(
        string $email,
        bool $includeSensitive = false
    ): ?array {

        $email = $this->normalizeEmail(
            $email
        );

        if ($email === '') {
            return null;
        }

        $fields = $includeSensitive
            ? '*'
            : $this->publicFields();

        $sql = "
            SELECT {$fields}
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'email' => $email,
        ]);

        $user = $statement->fetch();

        return $user ?: null;
    }


    /**
     * Find user by email without sensitive fields.
     */
    public function findPublicByEmail(
        string $email
    ): ?array {
        return $this->findByEmail(
            $email,
            false
        );
    }


    // =====================================================
    // Create
    // =====================================================

    /**
     * Create a user.
     *
     * Password should already be hashed.
     *
     * @param array $data
     * @return int
     */
    public function create(
        array $data
    ): int {

        $firstName = trim(
            (string) ($data['first_name'] ?? '')
        );

        $lastName = trim(
            (string) ($data['last_name'] ?? '')
        );

        $email = $this->normalizeEmail(
            (string) ($data['email'] ?? '')
        );

        $phone = isset($data['phone'])
            ? trim((string) $data['phone'])
            : null;

        $passwordHash = (string) (
            $data['password_hash'] ?? ''
        );

        $role = $this->normalizeRole(
            (string) ($data['role'] ?? 'guest')
        );

        $status = $this->normalizeStatus(
            (string) ($data['status'] ?? 'active')
        );

        $emailVerified = !empty(
            $data['email_verified']
        )
            ? 1
            : 0;


        if (
            $firstName === '' ||
            $lastName === '' ||
            $email === '' ||
            $passwordHash === ''
        ) {
            throw new InvalidArgumentException(
                'Required user fields are missing.'
            );
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid email address.'
            );
        }


        // -------------------------------------------------
        // Prevent Duplicate Email
        // -------------------------------------------------

        if (
            $this->emailExists($email)
        ) {
            throw new RuntimeException(
                'A user with this email already exists.'
            );
        }


        // -------------------------------------------------
        // Insert User
        // -------------------------------------------------

        $sql = "
            INSERT INTO users (
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
                password_reset_token,
                password_reset_expires,
                last_login_at,
                created_at,
                updated_at
            )
            VALUES (
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
                :password_reset_token,
                :password_reset_expires,
                :last_login_at,
                NOW(),
                NOW()
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'first_name' =>
                $firstName,

            'last_name' =>
                $lastName,

            'email' =>
                $email,

            'phone' =>
                $phone !== ''
                    ? $phone
                    : null,

            'password_hash' =>
                $passwordHash,

            'role' =>
                $role,

            'status' =>
                $status,

            'email_verified' =>
                $emailVerified,

            'email_verification_token' =>
                $data['email_verification_token']
                    ?? null,

            'email_verification_expires' =>
                $data['email_verification_expires']
                    ?? null,

            'password_reset_token' =>
                $data['password_reset_token']
                    ?? null,

            'password_reset_expires' =>
                $data['password_reset_expires']
                    ?? null,

            'last_login_at' =>
                $data['last_login_at']
                    ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }


    // =====================================================
    // Update
    // =====================================================

    /**
     * Update basic user information.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(
        int $id,
        array $data
    ): bool {

        if ($id <= 0) {
            return false;
        }


        $allowedFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'role',
            'status',
            'email_verified',
        ];


        $updates = [];
        $parameters = [
            'id' => $id,
        ];

        $emailChanged = false;


        foreach (
            $allowedFields as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $data
                )
            ) {
                continue;
            }

            $value = $data[$field];

            if ($field === 'email') {
                $value = $this->normalizeEmail(
                    (string) $value
                );

                if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException(
                        'Invalid email address.'
                    );
                }

                if ($this->emailExists($value, $id)) {
                    throw new RuntimeException(
                        'Email address is already in use.'
                    );
                }

                $emailChanged = true;
            }

            if ($field === 'role') {
                $value = $this->normalizeRole(
                    (string) $value
                );
            }

            if ($field === 'status') {
                $value = $this->normalizeStatus(
                    (string) $value
                );
            }

            if ($field === 'first_name' || $field === 'last_name') {
                $value = trim((string) $value);
            }

            if ($field === 'phone') {
                $value = trim((string) $value);
                $value = $value === '' ? null : $value;
            }

            $updates[] =
                "{$field} = :{$field}";

            $parameters[$field] =
                $value;
        }


        if (
            empty($updates)
        ) {
            return false;
        }

        if ($emailChanged) {
            $updates[] = 'email_verified = :email_verified';
            $parameters['email_verified'] = 0;
        }

        $updates[] = 'updated_at = NOW()';


        $sql = "
            UPDATE users
            SET " . implode(
                ', ',
                $updates
            ) . "
            WHERE id = :id
        ";


        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute(
            $parameters
        );


        return $statement->rowCount() > 0;
    }


    /**
     * Update user profile.
     *
     * This method intentionally allows only profile fields.
     */
    public function updateProfile(
        int $id,
        array $data
    ): bool {

        $allowedFields = [
            'first_name',
            'last_name',
            'phone',
        ];

        $updates = [];
        $parameters = [
            'id' => $id,
        ];


        foreach (
            $allowedFields as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $data
                )
            ) {
                continue;
            }

            $value = trim((string) $data[$field]);

            if ($field === 'phone') {
                $value = $value === '' ? null : $value;
            }

            $updates[] =
                "{$field} = :{$field}";

            $parameters[$field] =
                $value;
        }


        if (
            empty($updates)
        ) {
            return false;
        }


        $updates[] =
            'updated_at = NOW()';


        $sql = "
            UPDATE users
            SET " . implode(
                ', ',
                $updates
            ) . "
            WHERE id = :id
        ";


        $statement = $this->db->prepare(
            $sql
        );

        $statement->execute(
            $parameters
        );


        return $statement->rowCount() > 0;
    }


    // =====================================================
    // Password
    // =====================================================

    /**
     * Set a user's password.
     *
     * @param int $id
     * @param string $password
     * @return bool
     */
    public function setPassword(
        int $id,
        string $password
    ): bool {

        if (
            $id <= 0 ||
            $password === ''
        ) {
            return false;
        }


        $hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        if ($hash === false) {
            throw new RuntimeException(
                'Unable to hash password.'
            );
        }


        $statement = $this->db->prepare(
            "
            UPDATE users
            SET password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'password_hash' =>
                $hash,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Set an already-hashed password.
     *
     * Useful for AuthService.
     */
    public function setPasswordHash(
        int $id,
        string $passwordHash
    ): bool {

        if (
            $id <= 0 ||
            $passwordHash === ''
        ) {
            return false;
        }


        $statement = $this->db->prepare(
            "
            UPDATE users
            SET password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'password_hash' =>
                $passwordHash,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Verify a user's password.
     */
    public function verifyPassword(
        int $id,
        string $password
    ): bool {

        $user = $this->findById(
            $id,
            true
        );


        if (
            $user === null
        ) {
            return false;
        }


        $hash = (string) (
            $user['password_hash'] ?? ''
        );


        if ($hash === '') {
            return false;
        }


        return password_verify(
            $password,
            $hash
        );
    }


    // =====================================================
    // Email
    // =====================================================

    /**
     * Check whether email already exists.
     */
    public function emailExists(
        string $email,
        ?int $excludeUserId = null
    ): bool {

        $email = $this->normalizeEmail(
            $email
        );


        if (
            $email === ''
        ) {
            return false;
        }


        if (
            $excludeUserId !== null
        ) {

            $statement = $this->db->prepare(
                "
                SELECT id
                FROM users
                WHERE email = :email
                AND id != :id
                LIMIT 1
                "
            );


            $statement->execute([
                'email' =>
                    $email,

                'id' =>
                    $excludeUserId,
            ]);

        } else {

            $statement = $this->db->prepare(
                "
                SELECT id
                FROM users
                WHERE email = :email
                LIMIT 1
                "
            );


            $statement->execute([
                'email' =>
                    $email,
            ]);
        }


        return (bool) $statement->fetch();
    }


    /**
     * Update email address.
     */
    public function updateEmail(
        int $id,
        string $email
    ): bool {

        $email = $this->normalizeEmail(
            $email
        );


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid email address.'
            );
        }


        if (
            $this->emailExists(
                $email,
                $id
            )
        ) {
            throw new RuntimeException(
                'Email address is already in use.'
            );
        }


        $statement = $this->db->prepare(
            "
            UPDATE users
            SET email = :email,
                email_verified = 0,
                email_verification_token = NULL,
                email_verification_expires = NULL,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'email' =>
                $email,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Mark email as verified.
     */
    public function markEmailVerified(
        int $id
    ): bool {

        $statement = $this->db->prepare(
            "
            UPDATE users
            SET email_verified = 1,
                email_verification_token = NULL,
                email_verification_expires = NULL,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Store email verification token.
     */
    public function setEmailVerificationToken(
        int $id,
        string $tokenHash,
        string $expiresAt
    ): bool {

        $statement = $this->db->prepare(
            "
            UPDATE users
            SET email_verification_token = :token,
                email_verification_expires = :expires,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'token' =>
                $tokenHash,

            'expires' =>
                $expiresAt,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Find user by verification token.
     */
    public function findByVerificationToken(
        string $tokenHash
    ): ?array {

        $statement = $this->db->prepare(
            "
            SELECT *
            FROM users
            WHERE email_verification_token = :token
            AND email_verification_expires > NOW()
            LIMIT 1
            "
        );


        $statement->execute([
            'token' =>
                $tokenHash,
        ]);


        $user = $statement->fetch();

        return $user ?: null;
    }


    // =====================================================
    // Password Reset
    // =====================================================

    /**
     * Store password reset token.
     */
    public function setPasswordResetToken(
        int $id,
        string $tokenHash,
        string $expiresAt
    ): bool {

        $statement = $this->db->prepare(
            "
            UPDATE users
            SET password_reset_token = :token,
                password_reset_expires = :expires,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'token' =>
                $tokenHash,

            'expires' =>
                $expiresAt,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Find user by password reset token.
     */
    public function findByPasswordResetToken(
        string $tokenHash
    ): ?array {

        $statement = $this->db->prepare(
            "
            SELECT *
            FROM users
            WHERE password_reset_token = :token
            AND password_reset_expires > NOW()
            LIMIT 1
            "
        );


        $statement->execute([
            'token' =>
                $tokenHash,
        ]);


        $user = $statement->fetch();

        return $user ?: null;
    }


    /**
     * Clear password reset token.
     */
    public function clearPasswordResetToken(
        int $id
    ): bool {

        $statement = $this->db->prepare(
            "
            UPDATE users
            SET password_reset_token = NULL,
                password_reset_expires = NULL,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    // =====================================================
    // Login
    // =====================================================

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(
        int $id
    ): bool {

        $statement = $this->db->prepare(
            "
            UPDATE users
            SET last_login_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Get last login timestamp.
     */
    public function getLastLogin(
        int $id
    ): ?string {

        $statement = $this->db->prepare(
            "
            SELECT last_login_at
            FROM users
            WHERE id = :id
            LIMIT 1
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        $result = $statement->fetchColumn();

        return $result !== false
            ? (string) $result
            : null;
    }


    // =====================================================
    // Roles
    // =====================================================

    /**
     * Get user role.
     */
    public function getRole(
        int $id
    ): ?string {

        $statement = $this->db->prepare(
            "
            SELECT role
            FROM users
            WHERE id = :id
            LIMIT 1
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        $role = $statement->fetchColumn();

        return $role !== false
            ? (string) $role
            : null;
    }


    /**
     * Update user role.
     */
    public function updateRole(
        int $id,
        string $role
    ): bool {

        $role = $this->normalizeRole(
            $role
        );


        $statement = $this->db->prepare(
            "
            UPDATE users
            SET role = :role,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'role' =>
                $role,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Check whether a user has a specific role.
     */
    public function hasRole(
        int $id,
        string|array $roles
    ): bool {

        $userRole = $this->getRole(
            $id
        );


        if (
            $userRole === null
        ) {
            return false;
        }

        if (is_string($roles)) {
            return $userRole === $this->normalizeRole($roles);
        }

        $normalizedRoles = array_map(
            fn (string $role): string => $this->normalizeRole((string) $role),
            array_values((array) $roles)
        );

        return in_array(
            $userRole,
            $normalizedRoles,
            true
        );
    }


    // =====================================================
    // Status
    // =====================================================

    /**
     * Update user status.
     */
    public function updateStatus(
        int $id,
        string $status
    ): bool {

        $status = $this->normalizeStatus(
            $status
        );


        $statement = $this->db->prepare(
            "
            UPDATE users
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
            "
        );


        $statement->execute([
            'status' =>
                $status,

            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Check whether a user is active.
     */
    public function isActive(
        int $id
    ): bool {

        $statement = $this->db->prepare(
            "
            SELECT status
            FROM users
            WHERE id = :id
            LIMIT 1
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        $status = $statement->fetchColumn();


        return $status === 'active';
    }


    // =====================================================
    // List Users
    // =====================================================

    /**
     * Get users with pagination.
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAll(
        array $filters = [],
        int $page = 1,
        int $perPage = 20
    ): array {

        $page = max(
            1,
            $page
        );

        $perPage = min(
            100,
            max(
                1,
                $perPage
            )
        );

        $offset = (
            $page - 1
        ) * $perPage;


        $where = [];
        $parameters = [];


        // -------------------------------------------------
        // Search
        // -------------------------------------------------

        if (
            !empty($filters['search'])
        ) {

            $where[] = "
                (
                    first_name LIKE :search
                    OR last_name LIKE :search
                    OR email LIKE :search
                    OR phone LIKE :search
                )
            ";

            $parameters['search'] =
                '%' .
                trim(
                    (string) $filters['search']
                ) .
                '%';
        }


        // -------------------------------------------------
        // Role
        // -------------------------------------------------

        $roleFilter = trim((string) ($filters['role'] ?? ''));

        if ($roleFilter !== '') {
            $normalizedRole = $this->normalizeRole($roleFilter);
            $allowedRoleValues = ['guest', 'staff', 'manager', 'admin'];

            if (in_array($normalizedRole, $allowedRoleValues, true)) {
                $where[] = 'role = :role';
                $parameters['role'] = $normalizedRole;
            }
        }


        // -------------------------------------------------
        // Status
        // -------------------------------------------------

        $statusFilter = trim((string) ($filters['status'] ?? ''));

        if ($statusFilter !== '') {
            $normalizedStatus = $this->normalizeStatus($statusFilter);
            $allowedStatusValues = ['active', 'inactive', 'suspended'];

            if (in_array($normalizedStatus, $allowedStatusValues, true)) {
                $where[] = 'status = :status';
                $parameters['status'] = $normalizedStatus;
            }
        }


        $whereSql = '';

        if (
            !empty($where)
        ) {

            $whereSql =
                'WHERE ' .
                implode(
                    ' AND ',
                    $where
                );
        }


        // -------------------------------------------------
        // Count
        // -------------------------------------------------

        $countSql = "
            SELECT COUNT(*)
            FROM users
            {$whereSql}
        ";


        $countStatement =
            $this->db->prepare(
                $countSql
            );

        $countStatement->execute(
            $parameters
        );


        $total = (int)
            $countStatement->fetchColumn();


        // -------------------------------------------------
        // Fetch
        // -------------------------------------------------

        $sql = "
            SELECT {$this->publicFields()}
            FROM users
            {$whereSql}
            ORDER BY id DESC
            LIMIT :limit
            OFFSET :offset
        ";


        $statement = $this->db->prepare(
            $sql
        );


        foreach (
            $parameters as $key => $value
        ) {
            $statement->bindValue(
                ':' . $key,
                $value
            );
        }


        $statement->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );


        $statement->execute();


        $users = $statement->fetchAll();


        return [
            'data' =>
                $users,

            'pagination' => [
                'page' =>
                    $page,

                'per_page' =>
                    $perPage,

                'total' =>
                    $total,

                'total_pages' =>
                    $total > 0
                        ? (int) ceil(
                            $total / $perPage
                        )
                        : 0,
            ],
        ];
    }


    // =====================================================
    // Delete
    // =====================================================

    /**
     * Delete a user.
     *
     * This performs a hard delete. For a hotel system,
     * consider using deactivate() instead when historical
     * booking records must be preserved.
     */
    public function delete(
        int $id
    ): bool {

        if ($id <= 0) {
            return false;
        }


        $statement = $this->db->prepare(
            "
            DELETE FROM users
            WHERE id = :id
            "
        );


        $statement->execute([
            'id' =>
                $id,
        ]);


        return $statement->rowCount() > 0;
    }


    /**
     * Deactivate user without deleting historical data.
     */
    public function deactivate(
        int $id
    ): bool {
        return $this->updateStatus(
            $id,
            'inactive'
        );
    }


    // =====================================================
    // Statistics
    // =====================================================

    /**
     * Get basic user statistics.
     */
    public function getStatistics(): array
    {
        $statement = $this->db->query(
            "
            SELECT
                COUNT(*) AS total_users,

                SUM(
                    CASE
                        WHEN status = 'active'
                        THEN 1
                        ELSE 0
                    END
                ) AS active_users,

                SUM(
                    CASE
                        WHEN status = 'inactive'
                        THEN 1
                        ELSE 0
                    END
                ) AS inactive_users,

                SUM(
                    CASE
                        WHEN status = 'suspended'
                        THEN 1
                        ELSE 0
                    END
                ) AS suspended_users,

                SUM(
                    CASE
                        WHEN email_verified = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS verified_users

            FROM users
            "
        );


        $result = $statement->fetch();


        return [
            'total_users' =>
                (int) (
                    $result['total_users']
                    ?? 0
                ),

            'active_users' =>
                (int) (
                    $result['active_users']
                    ?? 0
                ),

            'inactive_users' =>
                (int) (
                    $result['inactive_users']
                    ?? 0
                ),

            'suspended_users' =>
                (int) (
                    $result['suspended_users']
                    ?? 0
                ),

            'verified_users' =>
                (int) (
                    $result['verified_users']
                    ?? 0
                ),
        ];
    }


    // =====================================================
    // Helpers
    // =====================================================

    /**
     * Normalize email address.
     */
    private function normalizeEmail(
        string $email
    ): string {
        return strtolower(
            trim($email)
        );
    }

    /**
     * Normalize and validate user role.
     */
    private function normalizeRole(
        string $role
    ): string {
        $role = strtolower(
            trim($role)
        );

        $allowedRoles = [
            'guest',
            'staff',
            'manager',
            'admin',
        ];

        if (in_array($role, $allowedRoles, true)) {
            return $role;
        }

        return 'guest';
    }

    /**
     * Normalize and validate user status.
     */
    private function normalizeStatus(
        string $status
    ): string {
        $status = strtolower(
            trim($status)
        );

        $allowedStatuses = [
            'active',
            'inactive',
            'suspended',
        ];

        if (in_array($status, $allowedStatuses, true)) {
            return $status;
        }

        return 'active';
    }

    /**
     * Fields safe for normal API responses.
     */
    private function publicFields(): string
    {
        return implode(
            ', ',
            [
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'role',
                'status',
                'email_verified',
                'last_login_at',
                'created_at',
                'updated_at',
            ]
        );
    }
}
