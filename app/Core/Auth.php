<?php
/**
 * Session-backed authentication helpers.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /**
     * @return array{id:int,email:string,role:string,full_name:string}|null
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => (int) $_SESSION['user_id'],
            'email' => (string) ($_SESSION['user_email'] ?? ''),
            'role' => (string) ($_SESSION['user_role'] ?? ''),
            'full_name' => (string) ($_SESSION['user_name'] ?? ''),
        ];
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    /**
     * @param array<string, scalar|null> $row users row
     */
    public static function login(array $row): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['user_email'] = (string) $row['email'];
        $_SESSION['user_role'] = (string) $row['role'];
        $_SESSION['user_name'] = (string) $row['full_name'];
        $_SESSION['_login_at'] = time();
        $_SESSION['_last_activity'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
