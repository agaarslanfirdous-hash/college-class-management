<?php
/**
 * Per-user in-app notifications.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Notification extends Model
{
    protected function table(): string
    {
        return 'notifications';
    }

    public function create(int $userId, string $type, string $title, string $message): int
    {
        $this->execute(
            'INSERT INTO notifications (user_id, type, title, message) VALUES (?,?,?,?)',
            [$userId, $type, $title, $message]
        );
        return (int) $this->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(int $userId, int $limit = 50): array
    {
        return $this->queryAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        $r = $this->queryOne(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
        return (int) ($r['c'] ?? 0);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
    }
}
