<?php
declare(strict_types=1);

/** Validate hotel booking input; returns an empty array when valid. */
function validateBooking(array $data): array
{
	$errors = [];

	$name = trim((string) ($data['guest_name'] ?? ''));
	if ($name === '' || strlen($name) > 120) {
		$errors['guest_name'] = 'Guest name is required and must be no longer than 120 characters.';
	}

	$email = trim((string) ($data['guest_email'] ?? ''));
	if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors['guest_email'] = 'Enter a valid email address.';
	}

	$roomId = filter_var($data['room_id'] ?? null, FILTER_VALIDATE_INT);
	if ($roomId === false || $roomId < 1) {
		$errors['room_id'] = 'Room ID must be a positive integer.';
	}

	$checkIn = parseBookingDate($data['check_in'] ?? null);
	$checkOut = parseBookingDate($data['check_out'] ?? null);
	if ($checkIn === null) {
		$errors['check_in'] = 'Enter a valid check-in date (YYYY-MM-DD).';
	}
	if ($checkOut === null) {
		$errors['check_out'] = 'Enter a valid check-out date (YYYY-MM-DD).';
	} elseif ($checkIn !== null && $checkOut <= $checkIn) {
		$errors['check_out'] = 'Check-out must be after check-in.';
	}

	$guestCount = filter_var($data['guests'] ?? null, FILTER_VALIDATE_INT);
	if ($guestCount === false || $guestCount < 1 || $guestCount > 20) {
		$errors['guests'] = 'Guest count must be an integer between 1 and 20.';
	}

	return $errors;
}

/** Parse strict YYYY-MM-DD input; return null for invalid dates. */
function parseBookingDate(mixed $value): ?DateTimeImmutable
{
	if (!is_string($value)) {
		return null;
	}

	$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
	$status = DateTimeImmutable::getLastErrors();
	if ($date === false
		|| ($status !== false && ($status['warning_count'] > 0 || $status['error_count'] > 0))
		|| $date->format('Y-m-d') !== $value) {
		return null;
	}

	return $date;
}
