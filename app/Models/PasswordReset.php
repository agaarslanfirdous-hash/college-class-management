<?php
/**
 * Password reset tokens (hashed).
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PasswordReset extends Model
{
    protected function table(): string
    {
        return 'password_resets';
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): void
    {
        $this->execute('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
        $this->execute(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)',
            [$userId, $tokenHash, $expiresAt]
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    public function findValid(string $tokenHash): array|false
    {
        return $this->queryOne(
            'SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > NOW() LIMIT 1',
            [$tokenHash]
        );
    }

    public function deleteForUser(int $userId): void
    {
        $this->execute('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
    }
}
