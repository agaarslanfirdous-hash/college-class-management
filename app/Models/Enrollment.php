<?php
/**
 * Student enrollment in classes.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Enrollment extends Model
{
    protected function table(): string
    {
        return 'enrollments';
    }

    public function enroll(int $studentId, int $classId): void
    {
        $this->execute(
            'INSERT IGNORE INTO enrollments (student_id, class_id) VALUES (?,?)',
            [$studentId, $classId]
        );
    }

    public function unenroll(int $studentId, int $classId): void
    {
        $this->execute(
            'DELETE FROM enrollments WHERE student_id = ? AND class_id = ?',
            [$studentId, $classId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function classesForStudent(int $studentId): array
    {
        return $this->queryAll(
            'SELECT c.*, e.id AS enrollment_id FROM enrollments e
             INNER JOIN classes c ON c.id = e.class_id
             WHERE e.student_id = ?
             ORDER BY c.code',
            [$studentId]
        );
    }

    /**
     * @return list<int>
     */
    public function classIdsForStudent(int $studentId): array
    {
        $rows = $this->queryAll(
            'SELECT class_id FROM enrollments WHERE student_id = ?',
            [$studentId]
        );
        return array_map(static fn (array $r): int => (int) $r['class_id'], $rows);
    }

    /**
     * @return list<int>
     */
    public function studentUserIdsForClass(int $classId): array
    {
        $rows = $this->queryAll(
            'SELECT student_id FROM enrollments WHERE class_id = ?',
            [$classId]
        );
        return array_map(static fn (array $r): int => (int) $r['student_id'], $rows);
    }
}
