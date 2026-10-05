<?php

/** Validation for authentication requests in a hotel management system. */
final class AuthValidation
{
    /** @return array<string, string> */
    public static function validateRegister(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($name === '' || strlen($name) > 100) {
            $errors['name'] = 'Name is required and must be at most 100 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'Password must be 8 to 72 characters long.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must include at least one letter and one number.';
        }
        if (isset($input['password_confirmation']) && $password !== (string) $input['password_confirmation']) {
            $errors['password_confirmation'] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    /** @return array<string, string> */
    public static function validateLogin(array $input): array
    {
        $errors = [];
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($password === '' || strlen($password) > 72) {
            $errors['password'] = 'Password is required.';
        }

        return $errors;
    }
}