<?php

declare(strict_types=1);

/**
 * Validation helpers for hotel guest records.
 *
 * Expected fields: first_name, last_name, email, phone, address,
 * nationality, and id_number.
 */
final class GuestValidation
{
    /**
     * Validate guest data and return field-specific error messages.
     * For partial updates, set $isUpdate to true to skip absent fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        $requiredFields = ['first_name', 'last_name', 'email', 'phone'];
        $optionalFields = ['address', 'nationality', 'id_number'];

        foreach (array_merge($requiredFields, $optionalFields) as $field) {
            if ($isUpdate && !array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field] ?? null;
            $required = in_array($field, $requiredFields, true);

            if (!is_string($value)) {
                if ($value === null && !$required) {
                    continue;
                }
                $errors[$field] = 'Must be a string.';
                continue;
            }

            $value = trim($value);
            if ($value === '') {
                if ($required) {
                    $errors[$field] = 'This field is required.';
                }
                continue;
            }

            if (in_array($field, ['first_name', 'last_name'], true)
                && (mb_strlen($value) > 100 || !preg_match("/^[\p{L}][\p{L}\p{M}' -]*$/u", $value))) {
                $errors[$field] = 'Must be a valid name of up to 100 characters.';
            } elseif ($field === 'email'
                && (mb_strlen($value) > 254 || filter_var($value, FILTER_VALIDATE_EMAIL) === false)) {
                $errors[$field] = 'Must be a valid email address.';
            } elseif ($field === 'phone'
                && !preg_match('/^\+?[0-9 ()\-.]{7,20}$/', $value)) {
                $errors[$field] = 'Must be a valid phone number.';
            } elseif ($field === 'address' && mb_strlen($value) > 500) {
                $errors[$field] = 'Must not exceed 500 characters.';
            } elseif ($field === 'nationality'
                && (mb_strlen($value) > 100 || !preg_match("/^[\p{L}\p{M} .'-]+$/u", $value))) {
                $errors[$field] = 'Must be a valid nationality.';
            } elseif ($field === 'id_number'
                && (mb_strlen($value) > 50 || !preg_match('/^[A-Za-z0-9-]+$/', $value))) {
                $errors[$field] = 'Must contain only letters, numbers, and hyphens (up to 50 characters).';
            }
        }

        return $errors;
    }
}