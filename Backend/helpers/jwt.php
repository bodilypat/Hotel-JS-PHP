<?php

declare(strict_types=1);

/**
 * Lightweight HS256 JWT helper for API authentication.
 * Configure JWT_SECRET with a long, random secret (at least 32 bytes).
 */
final class JwtHelper
{
	private const ALGORITHM = 'HS256';

	/** Create a signed token. Pass identity and authorization data in $claims. */
	public static function createToken(array $claims, ?int $ttlSeconds = null): string
	{
		$secret = self::secret();
		$now = time();
		$ttl = $ttlSeconds ?? (int) (getenv('JWT_TTL') ?: 3600);

		if ($ttl < 1) {
			throw new InvalidArgumentException('Token lifetime must be positive.');
		}

		$payload = array_merge($claims, [
			'iat' => $now,
			'exp' => $now + $ttl,
		]);
		$header = ['typ' => 'JWT', 'alg' => self::ALGORITHM];
		$unsigned = self::encodeJson($header) . '.' . self::encodeJson($payload);
		$signature = hash_hmac('sha256', $unsigned, $secret, true);

		return $unsigned . '.' . self::base64UrlEncode($signature);
	}

	/** Verify signature and expiry, returning claims or throwing on invalid input. */
	public static function verifyToken(string $token): array
	{
		$parts = explode('.', $token);
		if (count($parts) !== 3) {
			throw new UnexpectedValueException('Malformed token.');
		}

		[$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
		$header = self::decodeJson($encodedHeader);
		if (($header['alg'] ?? null) !== self::ALGORITHM || ($header['typ'] ?? null) !== 'JWT') {
			throw new UnexpectedValueException('Unsupported token header.');
		}

		$unsigned = $encodedHeader . '.' . $encodedPayload;
		$expected = hash_hmac('sha256', $unsigned, self::secret(), true);
		$provided = self::base64UrlDecode($encodedSignature);
		if (!hash_equals($expected, $provided)) {
			throw new UnexpectedValueException('Invalid token signature.');
		}

		$claims = self::decodeJson($encodedPayload);
		if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int) $claims['exp'] <= time()) {
			throw new UnexpectedValueException('Token has expired or has no expiry.');
		}

		return $claims;
	}

	/** Read JWT_SECRET, refusing to run with an insecure or missing secret. */
	private static function secret(): string
	{
		$secret = getenv('JWT_SECRET');
		if ($secret === false || strlen($secret) < 32) {
			throw new RuntimeException('JWT_SECRET must be configured with at least 32 characters.');
		}

		return $secret;
	}

	private static function encodeJson(array $value): string
	{
		return self::base64UrlEncode(json_encode($value, JSON_THROW_ON_ERROR));
	}

	private static function decodeJson(string $value): array
	{
		$decoded = json_decode(self::base64UrlDecode($value), true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($decoded)) {
			throw new UnexpectedValueException('Invalid token JSON.');
		}

		return $decoded;
	}

	private static function base64UrlEncode(string $value): string
	{
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	private static function base64UrlDecode(string $value): string
	{
		if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
			throw new UnexpectedValueException('Invalid base64url value.');
		}

		$decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
		if ($decoded === false) {
			throw new UnexpectedValueException('Invalid base64url value.');
		}

		return $decoded;
	}
}
