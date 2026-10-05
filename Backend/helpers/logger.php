<?php

/**
 * Lightweight file logger for the hotel management backend.
 *
 * Usage: HotelLogger::info('Reservation created', ['reservation_id' => 42]);
 */
final class HotelLogger
{
	private const LEVELS = [
		'debug' => 100,
		'info' => 200,
		'warning' => 300,
		'error' => 400,
		'critical' => 500,
	];

	private static ?string $file = null;
	private static string $minimumLevel = 'info';

	/** Set a custom log file and minimum severity. */
	public static function configure(?string $file = null, string $minimumLevel = 'info'): void
	{
		$minimumLevel = strtolower($minimumLevel);
		if (!isset(self::LEVELS[$minimumLevel])) {
			throw new InvalidArgumentException('Unknown log level: ' . $minimumLevel);
		}

		self::$file = $file;
		self::$minimumLevel = $minimumLevel;
	}

	public static function debug(string $message, array $context = []): void
	{
		self::write('debug', $message, $context);
	}

	public static function info(string $message, array $context = []): void
	{
		self::write('info', $message, $context);
	}

	public static function warning(string $message, array $context = []): void
	{
		self::write('warning', $message, $context);
	}

	public static function error(string $message, array $context = []): void
	{
		self::write('error', $message, $context);
	}

	public static function critical(string $message, array $context = []): void
	{
		self::write('critical', $message, $context);
	}

	private static function write(string $level, string $message, array $context): void
	{
		if (self::LEVELS[$level] < self::LEVELS[self::$minimumLevel]) {
			return;
		}

		$line = json_encode([
			'timestamp' => gmdate('c'),
			'level' => strtoupper($level),
			'message' => $message,
			'context' => $context,
		], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

		if ($line === false) {
			error_log('HotelLogger: failed to encode log record.');
			return;
		}

		$file = self::$file ?? (__DIR__ . '/../../logs/hotel.log');
		$directory = dirname($file);
		if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
			error_log($line);
			return;
		}

		if (@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
			error_log($line);
		}
	}
}
