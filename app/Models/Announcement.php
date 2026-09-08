<?php
/**
 * Published announcements by role target.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Announcement extends Model
{
    protected function table(): string
    {
        return 'announcements';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forRole(string $role): array
    {
        return $this->queryAll(
            'SELECT a.*, u.full_name AS author_name FROM announcements a
             INNER JOIN users u ON u.id = a.author_id
             WHERE a.is_published = 1 AND (a.target_role = \'all\' OR a.target_role = ?)
             ORDER BY a.created_at DESC',
            [$role]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allAdmin(): array
    {
        return $this->queryAll(
            'SELECT a.*, u.full_name AS author_name FROM announcements a
             INNER JOIN users u ON u.id = a.author_id
             ORDER BY a.created_at DESC'
        );
    }

    public function create(int $authorId, string $title, string $body, string $targetRole): int
    {
        $this->execute(
            'INSERT INTO announcements (author_id, title, body, target_role, is_published) VALUES (?,?,?,?,1)',
            [$authorId, $title, $body, $targetRole]
        );
        return (int) $this->lastInsertId();
    }

    public function setPublished(int $id, bool $pub): void
    {
        $this->execute('UPDATE announcements SET is_published = ? WHERE id = ?', [$pub ? 1 : 0, $id]);
    }
}
