<?php
/**
 * Login attempt tracking for rate limiting.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LoginAttempt extends Model
{
    protected function table(): string
    {
        return 'login_attempts';
    }

    public function record(string $email, bool $success): void
    {
        $this->execute(
            'INSERT INTO login_attempts (email, ip, success) VALUES (?,?,?)',
            [strtolower(trim($email)), \client_ip(), $success ? 1 : 0]
        );
    }

    public function recentFailures(string $email, int $minutes): int
    {
        $r = $this->queryOne(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE email = ? AND ip = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
            [strtolower(trim($email)), \client_ip(), $minutes]
        );
        return (int) ($r['c'] ?? 0);
    }
}
