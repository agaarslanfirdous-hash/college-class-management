<?php
/**
 * File-based application logging for debugging and audit support.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static function path(): string
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/app.log';
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }
}
