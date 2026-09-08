<?php
/**
 * CSRF token generation and validation for POST requests.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

final class CSRF
{
    public static function token(): string
    {
        $key = (string) (\app_config()['security']['csrf_token_key'] ?? '_csrf_token');
        if (empty($_SESSION[$key]) || !is_string($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[$key];
    }

    public static function field(): string
    {
        $t = self::token();
        return '<input type="hidden" name="_csrf" value="' . \e($t) . '">';
    }

    public static function validate(?string $submitted): bool
    {
        $key = (string) (\app_config()['security']['csrf_token_key'] ?? '_csrf_token');
        $expected = $_SESSION[$key] ?? '';
        return is_string($submitted) && is_string($expected) && $expected !== ''
            && hash_equals($expected, $submitted);
    }
}
