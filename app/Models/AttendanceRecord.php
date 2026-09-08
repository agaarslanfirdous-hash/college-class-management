<?php
/**
 * Daily attendance per schedule and teacher.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AttendanceRecord extends Model
{
    protected function table(): string
    {
        return 'attendance_records';
    }

    /**
     * Get or create row for schedule/teacher/date.
     */
    /**
     * True if "now" is within the class session attestation window (a few minutes before start through end).
     */
    public function attestationWindowOpen(int $scheduleId, string $dateYmd, int $earlySeconds = 300): bool
    {
        $row = $this->queryOne(
            'SELECT s.day_of_week, s.start_time, s.end_time, s.is_cancelled FROM schedules s WHERE s.id = ?',
            [$scheduleId]
        );
        if ($row === false || !empty($row['is_cancelled'])) {
            return false;
        }
        $dow = (int) $row['day_of_week'];
        if ($dow !== (int) date('w', strtotime($dateYmd . ' 12:00:00'))) {
            return false;
        }
        $tsStart = strtotime($dateYmd . ' ' . (string) $row['start_time']);
        $tsEnd = strtotime($dateYmd . ' ' . (string) $row['end_time']);
        if ($tsStart === false || $tsEnd === false) {
            return false;
        }
        if ($tsEnd <= $tsStart) {
            $tsEnd += 86400;
        }
        $now = time();
        return $now >= $tsStart - $earlySeconds && $now <= $tsEnd;
    }

    public function getOrCreate(int $scheduleId, int $teacherId, string $date): array
    {
        $row = $this->queryOne(
            'SELECT * FROM attendance_records WHERE schedule_id = ? AND teacher_id = ? AND record_date = ?',
            [$scheduleId, $teacherId, $date]
        );
        if ($row !== false) {
            return $row;
        }
        $this->execute(
            'INSERT INTO attendance_records (schedule_id, teacher_id, record_date, status) VALUES (?,?,?, \'pending\')',
            [$scheduleId, $teacherId, $date]
        );
        $id = (int) $this->lastInsertId();
        $r = $this->queryOne('SELECT * FROM attendance_records WHERE id = ?', [$id]);
        return $r !== false ? $r : [];
    }

    public function teacherCheckIn(int $scheduleId, int $teacherId, string $date): void
    {
        $this->getOrCreate($scheduleId, $teacherId, $date);
        $this->execute(
            'UPDATE attendance_records SET check_in_time = NOW(), status = \'present\', verification_method = \'pin\'
             WHERE schedule_id = ? AND teacher_id = ? AND record_date = ?',
            [$scheduleId, $teacherId, $date]
        );
    }

    public function monitorVerify(
        int $scheduleId,
        int $teacherId,
        string $date,
        int $monitorId,
        string $status,
        string $method,
        ?string $notes,
        bool $escalated
    ): void {
        $this->getOrCreate($scheduleId, $teacherId, $date);
        $this->execute(
            'UPDATE attendance_records SET status = ?, verified_by_monitor_id = ?, reported_by_student_id = NULL, verification_method = ?, notes = ?, escalated = ?
             WHERE schedule_id = ? AND teacher_id = ? AND record_date = ?',
            [$status, $monitorId, $method, $notes, $escalated ? 1 : 0, $scheduleId, $teacherId, $date]
        );
    }

    /**
     * Student or (when used from same flow) attestation: records presence without PIN when no stronger verification yet exists.
     */
    public function applyStudentAttestation(
        int $scheduleId,
        int $teacherId,
        string $dateYmd,
        int $studentId,
        bool $teacherPresent
    ): void {
        $row = $this->getOrCreate($scheduleId, $teacherId, $dateYmd);
        $vMethod = (string) ($row['verification_method'] ?? 'none');
        $vMon = (int) ($row['verified_by_monitor_id'] ?? 0);
        if ($vMon > 0) {
            return;
        }
        if ($vMethod === 'pin' && (string) ($row['status'] ?? '') === 'present') {
            return;
        }
        $newStatus = $teacherPresent ? 'present' : 'absent';
        $this->execute(
            'UPDATE attendance_records
             SET status = ?, verification_method = \'student\', reported_by_student_id = ?,
                 check_in_time = CASE WHEN ? = 1 THEN COALESCE(check_in_time, NOW()) ELSE check_in_time END
             WHERE schedule_id = ? AND teacher_id = ? AND record_date = ?',
            [
                $newStatus,
                $studentId,
                $teacherPresent ? 1 : 0,
                $scheduleId,
                $teacherId,
                $dateYmd,
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forDate(string $date): array
    {
        return $this->queryAll(
            'SELECT ar.*, s.start_time, s.end_time, s.day_of_week, s.room,
                    c.code AS class_code, c.name AS class_name,
                    t.full_name AS teacher_name
             FROM attendance_records ar
             INNER JOIN schedules s ON s.id = ar.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ar.teacher_id
             WHERE ar.record_date = ?
             ORDER BY s.day_of_week, s.start_time',
            [$date]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forMonitorSchedules(array $scheduleIds, string $date): array
    {
        if ($scheduleIds === []) {
            return [];
        }
        $in = implode(',', array_map('intval', $scheduleIds));
        return $this->queryAll(
            "SELECT ar.*, s.start_time, s.end_time, s.room, c.code AS class_code, t.full_name AS teacher_name
             FROM attendance_records ar
             INNER JOIN schedules s ON s.id = ar.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ar.teacher_id
             WHERE ar.record_date = ? AND ar.schedule_id IN ({$in})
             ORDER BY s.start_time"
            , [$date]
        );
    }

    /**
     * Summary counts for a month.
     *
     * @return array<string, int>
     */
    public function summaryByMonth(string $yearMonth): array
    {
        $row = $this->queryOne(
            "SELECT
                SUM(status = 'present') AS present_cnt,
                SUM(status = 'absent') AS absent_cnt,
                SUM(status = 'late') AS late_cnt,
                SUM(status = 'on_leave') AS leave_cnt,
                COUNT(*) AS total
             FROM attendance_records
             WHERE DATE_FORMAT(record_date, '%Y-%m') = ?",
            [$yearMonth]
        );
        if (!is_array($row)) {
            return ['present_cnt' => 0, 'absent_cnt' => 0, 'late_cnt' => 0, 'leave_cnt' => 0, 'total' => 0];
        }
        return [
            'present_cnt' => (int) ($row['present_cnt'] ?? 0),
            'absent_cnt' => (int) ($row['absent_cnt'] ?? 0),
            'late_cnt' => (int) ($row['late_cnt'] ?? 0),
            'leave_cnt' => (int) ($row['leave_cnt'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }
}
