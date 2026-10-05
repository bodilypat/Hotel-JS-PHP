<?php
declare(strict_types=1);

/** Validate room input for create requests and partial updates. */
function validateRoomData(array $input, bool $isUpdate = false): array
{
    $errors = [];
    $data = [];
    $required = ['room_number', 'room_type', 'price_per_night', 'capacity'];

    if (!$isUpdate) {
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = 'This field is required.';
            }
        }
    }

    if (array_key_exists('room_number', $input)) {
        $value = trim((string) $input['room_number']);
        if ($value === '' || strlen($value) > 20) {
            $errors['room_number'] = 'Room number must be 1–20 characters.';
        } else {
            $data['room_number'] = $value;
        }
    }

    if (array_key_exists('room_type', $input)) {
        $value = trim((string) $input['room_type']);
        if ($value === '' || strlen($value) > 50) {
            $errors['room_type'] = 'Room type must be 1–50 characters.';
        } else {
            $data['room_type'] = $value;
        }
    }

    if (array_key_exists('price_per_night', $input)) {
        $value = filter_var($input['price_per_night'], FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < 0) {
            $errors['price_per_night'] = 'Price must be a non-negative number.';
        } else {
            $data['price_per_night'] = round((float) $value, 2);
        }
    }

    if (array_key_exists('capacity', $input)) {
        $value = filter_var($input['capacity'], FILTER_VALIDATE_INT);
        if ($value === false || $value < 1) {
            $errors['capacity'] = 'Capacity must be a positive whole number.';
        } else {
            $data['capacity'] = $value;
        }
    }

    if (array_key_exists('status', $input)) {
        $value = strtolower(trim((string) $input['status']));
        if (!in_array($value, ['available', 'occupied', 'reserved', 'maintenance'], true)) {
            $errors['status'] = 'Invalid room status.';
        } else {
            $data['status'] = $value;
        }
    } elseif (!$isUpdate) {
        $data['status'] = 'available';
    }

    if (array_key_exists('description', $input)) {
        $value = trim((string) $input['description']);
        if (strlen($value) > 1000) {
            $errors['description'] = 'Description cannot exceed 1000 characters.';
        } else {
            $data['description'] = $value;
        }
    }

    return ['valid' => $errors === [], 'errors' => $errors, 'data' => $data];
}