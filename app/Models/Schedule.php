<?php
/**
 * Class schedule occurrences.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Schedule extends Model
{
    protected function table(): string
    {
        return 'schedules';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allWithClass(): array
    {
        return $this->queryAll(
            'SELECT s.*, c.code AS class_code, c.name AS class_name, c.subject
             FROM schedules s
             INNER JOIN classes c ON c.id = s.class_id
             WHERE s.is_cancelled = 0
             ORDER BY s.day_of_week, s.start_time'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forClass(int $classId): array
    {
        return $this->queryAll(
            'SELECT * FROM schedules WHERE class_id = ? ORDER BY day_of_week, start_time',
            [$classId]
        );
    }

    /**
     * @param array{class_id:int,day_of_week:int,start_time:string,end_time:string,room:?string,effective_from:string,effective_to:?string} $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room, effective_from, effective_to, is_cancelled)
             VALUES (?,?,?,?,?,?,?,0)',
            [
                $data['class_id'],
                $data['day_of_week'],
                $data['start_time'],
                $data['end_time'],
                $data['room'] ?? null,
                $data['effective_from'],
                $data['effective_to'] ?? null,
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function cancel(int $id, string $reason): void
    {
        $this->execute(
            'UPDATE schedules SET is_cancelled = 1, cancellation_reason = ? WHERE id = ?',
            [$reason, $id]
        );
    }

    /**
     * Active schedules on a given weekday (0=Sun .. 6=Sat).
     *
     * @return list<array<string, mixed>>
     */
    public function forDayOfWeek(int $dow): array
    {
        return $this->queryAll(
            'SELECT s.*, c.code AS class_code, c.name AS class_name
             FROM schedules s
             INNER JOIN classes c ON c.id = s.class_id
             WHERE s.day_of_week = ? AND s.is_cancelled = 0
             ORDER BY s.start_time',
            [$dow]
        );
    }

    /**
     * Schedules starting in time window (for cron reminders). $dow weekday, HH:MM:SS range.
     *
     * @return list<array<string, mixed>>
     */
    public function forDayAndTimeBetween(int $dow, string $startFrom, string $startTo): array
    {
        return $this->queryAll(
            'SELECT s.*, c.code AS class_code FROM schedules s
             INNER JOIN classes c ON c.id = s.class_id
             WHERE s.day_of_week = ? AND s.is_cancelled = 0 AND s.start_time >= ? AND s.start_time < ?',
            [$dow, $startFrom, $startTo]
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    public function findWithClass(int $id): array|false
    {
        return $this->queryOne(
            'SELECT s.*, c.code AS class_code, c.name AS class_name, c.subject, c.capacity
             FROM schedules s INNER JOIN classes c ON c.id = s.class_id WHERE s.id = ?',
            [$id]
        );
    }
}
