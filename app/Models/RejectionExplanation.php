<?php
/**
 * Teacher rejection reasons linked to class_assignments.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RejectionExplanation extends Model
{
    protected function table(): string
    {
        return 'rejection_explanations';
    }

    public function create(int $assignmentId, string $reasonType, string $reasonText): int
    {
        $this->deleteByAssignmentId($assignmentId);
        $this->execute(
            'INSERT INTO rejection_explanations (assignment_id, reason_type, reason_text) VALUES (?,?,?)',
            [$assignmentId, $reasonType, $reasonText]
        );
        $newId = (int) $this->lastInsertId();
        if ($newId > 0) {
            return $newId;
        }
        $row = $this->queryOne('SELECT id FROM rejection_explanations WHERE assignment_id = ?', [$assignmentId]);
        return $row !== false ? (int) $row['id'] : 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEnrolledStudent(int $studentId, int $limit = 30): array
    {
        return $this->queryAll(
            'SELECT re.id, re.reason_type, re.reason_text, re.admin_response, re.responded_at, re.created_at,
                    c.code AS class_code, c.name AS class_name, t.full_name AS teacher_name
             FROM rejection_explanations re
             INNER JOIN class_assignments ca ON ca.id = re.assignment_id
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ca.teacher_id
             INNER JOIN enrollments e ON e.class_id = c.id AND e.student_id = ?
             WHERE ca.status = \'rejected\'
             ORDER BY re.created_at DESC
             LIMIT ' . max(1, min(100, $limit)),
            [$studentId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForClassMonitorUser(int $monitorUserId, int $limit = 30): array
    {
        return $this->queryAll(
            'SELECT re.id, re.reason_type, re.reason_text, re.admin_response, re.responded_at, re.created_at,
                    c.code AS class_code, c.name AS class_name, t.full_name AS teacher_name, ca.id AS assignment_id
             FROM rejection_explanations re
             INNER JOIN class_assignments ca ON ca.id = re.assignment_id
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ca.teacher_id
             WHERE ca.status = \'rejected\' AND ca.monitor_id = ?
             ORDER BY re.created_at DESC
             LIMIT ' . max(1, min(100, $limit)),
            [$monitorUserId]
        );
    }

    public function deleteByAssignmentId(int $assignmentId): void
    {
        $this->execute('DELETE FROM rejection_explanations WHERE assignment_id = ?', [$assignmentId]);
    }

    public function respond(int $explanationId, string $response): void
    {
        $this->execute(
            'UPDATE rejection_explanations SET admin_response = ?, responded_at = NOW() WHERE id = ?',
            [$response, $explanationId]
        );
    }
}
