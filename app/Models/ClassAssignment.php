<?php
/**
 * Teacher–schedule assignments (offered / accepted / rejected / overridden).
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ClassAssignment extends Model
{
    protected function table(): string
    {
        return 'class_assignments';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forTeacher(int $teacherId): array
    {
        return $this->queryAll(
            'SELECT ca.*, s.day_of_week, s.start_time, s.end_time, s.room, s.is_cancelled,
                    c.code AS class_code, c.name AS class_name, c.subject, c.grade_level
             FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             WHERE ca.teacher_id = ?
             ORDER BY s.day_of_week, s.start_time',
            [$teacherId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function offeredForTeacher(int $teacherId): array
    {
        return $this->queryAll(
            'SELECT ca.*, s.day_of_week, s.start_time, s.end_time, s.room,
                    c.code AS class_code, c.name AS class_name, c.subject, c.capacity, c.grade_level, d.name AS dept_name
             FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN departments d ON d.id = c.department_id
             WHERE ca.teacher_id = ? AND ca.status = \'offered\' AND s.is_cancelled = 0
             ORDER BY c.subject, s.day_of_week',
            [$teacherId]
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    public function find(int $id): array|false
    {
        return $this->queryOne('SELECT * FROM class_assignments WHERE id = ?', [$id]);
    }

    public function classIdForAssignment(int $assignmentId): int
    {
        $r = $this->queryOne(
            'SELECT s.class_id AS cid FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             WHERE ca.id = ?',
            [$assignmentId]
        );
        return $r !== false ? (int) $r['cid'] : 0;
    }

    public function activeTeacherIdForSchedule(int $scheduleId): int
    {
        $r = $this->queryOne(
            "SELECT teacher_id FROM class_assignments
             WHERE schedule_id = ? AND status IN ('accepted', 'overridden') LIMIT 1",
            [$scheduleId]
        );
        return $r !== false ? (int) $r['teacher_id'] : 0;
    }

    /**
     * Assignment with teacher, class, and department labels (for notifications / admin UI).
     *
     * @return array<string, mixed>|false
     */
    public function findWithClassContext(int $id): array|false
    {
        return $this->queryOne(
            'SELECT ca.*, c.code AS class_code, c.name AS class_name, c.subject,
                    d.name AS department_name, t.full_name AS teacher_name, t.email AS teacher_email
             FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             LEFT JOIN departments d ON d.id = c.department_id
             INNER JOIN users t ON t.id = ca.teacher_id
             WHERE ca.id = ?',
            [$id]
        );
    }

    public function accept(int $id): void
    {
        $this->execute(
            "UPDATE class_assignments SET status = 'accepted', decided_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public function reject(int $id): void
    {
        $this->execute(
            "UPDATE class_assignments SET status = 'rejected', decided_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    /** Teacher steps back from an accepted (or admin-overridden) assignment. */
    public function withdrawByTeacher(int $assignmentId, int $teacherId): ?string
    {
        $row = $this->find($assignmentId);
        if ($row === false) {
            return 'Assignment not found.';
        }
        if ((int) $row['teacher_id'] !== $teacherId) {
            return 'This assignment is not yours.';
        }
        $st = (string) ($row['status'] ?? '');
        if (!in_array($st, ['accepted', 'overridden'], true)) {
            return 'You can only withdraw from an accepted class assignment. Use Reject for pending offers.';
        }
        try {
            $this->execute(
                "UPDATE class_assignments SET status = 'withdrawn', decided_at = NOW() WHERE id = ?",
                [$assignmentId]
            );
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'withdrawn') || str_contains($e->getMessage(), 'Data truncated')) {
                return 'Database not updated: run migration database/migrations/006_class_assignment_withdrawn.sql, then try again.';
            }
            throw $e;
        }
        return null;
    }

    public function overrideAssignment(int $id, ?int $newTeacherId, ?int $monitorId, string $note): void
    {
        if ($newTeacherId !== null) {
            $this->execute(
                "UPDATE class_assignments SET teacher_id = ?, monitor_id = ?, status = 'overridden', admin_override_note = ?, decided_at = NOW() WHERE id = ?",
                [$newTeacherId, $monitorId, $note, $id]
            );
        } else {
            $this->execute(
                "UPDATE class_assignments SET monitor_id = ?, status = 'overridden', admin_override_note = ?, decided_at = NOW() WHERE id = ?",
                [$monitorId, $note, $id]
            );
        }
    }

    /**
     * Offer a class to a teacher (admin).
     */
    public function offer(int $scheduleId, int $teacherId, ?int $monitorId): int
    {
        $this->execute(
            'INSERT INTO class_assignments (schedule_id, teacher_id, monitor_id, status) VALUES (?,?,?, \'offered\')',
            [$scheduleId, $teacherId, $monitorId]
        );
        return (int) $this->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allForAdmin(): array
    {
        return $this->queryAll(
            'SELECT ca.*, s.day_of_week, s.start_time, s.end_time, s.room,
                    c.code AS class_code, c.name AS class_name,
                    t.full_name AS teacher_name, t.email AS teacher_email,
                    m.full_name AS monitor_name
             FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ca.teacher_id
             LEFT JOIN users m ON m.id = ca.monitor_id
             ORDER BY ca.status, s.day_of_week, s.start_time'
        );
    }

    /**
     * Rejections pending admin response (has rejection_explanations row without admin_response).
     *
     * @return list<array<string, mixed>>
     */
    public function pendingRejections(): array
    {
        return $this->queryAll(
            'SELECT ca.*, re.reason_type, re.reason_text, re.id AS explanation_id, re.admin_response,
                    s.day_of_week, s.start_time, c.code AS class_code, t.full_name AS teacher_name
             FROM class_assignments ca
             INNER JOIN rejection_explanations re ON re.assignment_id = ca.id
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ca.teacher_id
             WHERE ca.status = \'rejected\' AND (re.admin_response IS NULL OR re.admin_response = \'\')
             ORDER BY re.created_at DESC'
        );
    }

    /** True if this user is the assigned class monitor (monitor_id) on at least one active assignment. */
    public function userIsClassMonitor(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        $r = $this->queryOne(
            'SELECT 1 FROM class_assignments ca
             WHERE ca.monitor_id = ? AND ca.status IN (\'accepted\', \'overridden\')
             LIMIT 1',
            [$userId]
        );
        return $r !== false;
    }

    /**
     * Teachers who rejected a class (with admin reply if any) — for admin “rejections & explanations” list.
     *
     * @return list<array<string, mixed>>
     */
    public function recentRejectedOffersWithExplanations(int $limit = 40): array
    {
        return $this->queryAll(
            'SELECT re.id AS explanation_id, re.reason_type, re.reason_text, re.admin_response, re.responded_at, re.created_at,
                    c.code AS class_code, t.full_name AS teacher_name, ca.id AS assignment_id, s.day_of_week, s.start_time
             FROM class_assignments ca
             INNER JOIN rejection_explanations re ON re.assignment_id = ca.id
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ca.teacher_id
             WHERE ca.status = \'rejected\'
             ORDER BY re.created_at DESC
             LIMIT ' . max(1, min(200, (int) $limit))
        );
    }

    /** User is the assigned class monitor (monitor_id) for at least one class in this department. */
    public function userIsAssignedClassMonitorInDepartment(int $userId, int $departmentId): bool
    {
        if ($userId < 1 || $departmentId < 1) {
            return false;
        }
        $r = $this->queryOne(
            'SELECT 1 FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             WHERE ca.monitor_id = ? AND c.department_id = ?
             LIMIT 1',
            [$userId, $departmentId]
        );
        return $r !== false;
    }
}
