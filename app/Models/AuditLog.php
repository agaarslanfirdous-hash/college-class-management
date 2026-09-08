<?php
/**
 * Audit trail for security-sensitive actions.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuditLog extends Model
{
    protected function table(): string
    {
        return 'audit_logs';
    }

    public function write(?int $userId, string $action, string $entity, ?int $entityId, ?string $details = null): void
    {
        $this->execute(
            'INSERT INTO audit_logs (user_id, action, entity, entity_id, ip, user_agent, details) VALUES (?,?,?,?,?,?,?)',
            [
                $userId,
                $action,
                $entity,
                $entityId,
                \client_ip(),
                \user_agent(),
                $details,
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 200): array
    {
        return $this->queryAll(
            'SELECT a.*, u.email AS user_email FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT ' . (int) $limit
        );
    }
}
