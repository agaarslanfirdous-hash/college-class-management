<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Pending timetable chip swaps (other teacher or class monitor must approve).
 */
final class TtSwapProposal extends Model
{
    protected function table(): string
    {
        return 'tt_swap_proposals';
    }

    public function create(
        int $programId,
        int $placementA,
        int $placementB,
        int $requesterId,
        ?int $assigneeId,
        bool $needsClassMonitor
    ): int {
        $this->execute(
            'INSERT INTO tt_swap_proposals (program_id, placement_a_id, placement_b_id, requester_user_id, assignee_user_id, needs_class_monitor) VALUES (?,?,?,?,?,?)',
            [$programId, $placementA, $placementB, $requesterId, $assigneeId, $needsClassMonitor ? 1 : 0]
        );
        return (int) $this->lastInsertId();
    }

    /** @return array<string, mixed>|false */
    public function getPendingById(int $id): array|false
    {
        return $this->queryOne("SELECT * FROM tt_swap_proposals WHERE id = ? AND status = 'pending'", [$id]);
    }

    public function resolve(int $id, int $resolverId, string $status): void
    {
        $this->execute(
            'UPDATE tt_swap_proposals SET status = ?, resolved_at = NOW(), resolver_user_id = ? WHERE id = ?',
            [$status, $resolverId, $id]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPendingForProgram(int $programId): array
    {
        return $this->queryAll(
            'SELECT sp.*, ur.full_name AS requester_name, ua.full_name AS assignee_name
             FROM tt_swap_proposals sp
             LEFT JOIN users ur ON ur.id = sp.requester_user_id
             LEFT JOIN users ua ON ua.id = sp.assignee_user_id
             WHERE sp.program_id = ? AND sp.status = ? ORDER BY sp.id DESC',
            [$programId, 'pending']
        );
    }

    public function hasPendingForPair(int $placementA, int $placementB): bool
    {
        $a = min($placementA, $placementB);
        $b = max($placementA, $placementB);
        $r = $this->queryOne(
            "SELECT id FROM tt_swap_proposals WHERE status = 'pending'
             AND ((placement_a_id = ? AND placement_b_id = ?) OR (placement_a_id = ? AND placement_b_id = ?)) LIMIT 1",
            [$a, $b, $b, $a]
        );
        return $r !== false;
    }
}
