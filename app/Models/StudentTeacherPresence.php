<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Student’s report during class: whether the teacher was seen (protocol at class time). */
final class StudentTeacherPresence extends Model
{
    protected function table(): string
    {
        return 'student_teacher_presence';
    }

    public function set(int $studentId, int $scheduleId, string $dateYmd, bool $teacherPresent): void
    {
        $this->execute(
            'INSERT INTO student_teacher_presence (student_id, schedule_id, class_date, teacher_present) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE teacher_present = VALUES(teacher_present), updated_at = NOW()',
            [$studentId, $scheduleId, $dateYmd, $teacherPresent ? 1 : 0]
        );
    }
}
