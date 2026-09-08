<?php
/**
 * JSON API for polling (live status, unread counts).
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Notification;

final class ApiController extends Controller
{
    public function notificationsUnread(): void
    {
        $this->requireAuth();
        $n = new Notification();
        $this->json(['count' => $n->unreadCount((int) Auth::id())]);
    }

    public function liveStatus(): void
    {
        $this->requireAuth();
        $role = Auth::role();
        $dow = (int) date('w');
        $date = date('Y-m-d');
        $db = \App\Core\Database::pdo();
        $items = [];

        if ($role === 'student') {
            $uid = (int) Auth::id();
            $sql = "SELECT s.id AS schedule_id, c.code AS class_code, s.start_time, s.end_time,
                           t.id AS teacher_id, t.full_name AS teacher_name, COALESCE(ar.status, 'pending') AS attendance_status
                    FROM enrollments e
                    INNER JOIN classes c ON c.id = e.class_id
                    INNER JOIN schedules s ON s.class_id = c.id AND s.day_of_week = ? AND s.is_cancelled = 0
                    INNER JOIN class_assignments ca ON ca.schedule_id = s.id AND ca.status IN ('accepted','overridden')
                    INNER JOIN users t ON t.id = ca.teacher_id
                    LEFT JOIN attendance_records ar ON ar.schedule_id = s.id AND ar.teacher_id = ca.teacher_id AND ar.record_date = ?
                    WHERE e.student_id = ?
                    ORDER BY s.start_time";
            $st = $db->prepare($sql);
            $st->execute([$dow, $date, $uid]);
            $items = $st->fetchAll();
        } elseif ($role === 'teacher') {
            $uid = (int) Auth::id();
            $sql = "SELECT s.id AS schedule_id, c.code AS class_code, s.start_time, s.end_time,
                           COALESCE(ar.status, 'pending') AS attendance_status
                    FROM class_assignments ca
                    INNER JOIN schedules s ON s.id = ca.schedule_id AND s.day_of_week = ? AND s.is_cancelled = 0
                    INNER JOIN classes c ON c.id = s.class_id
                    LEFT JOIN attendance_records ar ON ar.schedule_id = s.id AND ar.teacher_id = ca.teacher_id AND ar.record_date = ?
                    WHERE ca.teacher_id = ? AND ca.status IN ('accepted','overridden')
                    ORDER BY s.start_time";
            $st = $db->prepare($sql);
            $st->execute([$dow, $date, $uid]);
            $items = $st->fetchAll();
        } elseif ($role === 'monitor') {
            $uid = (int) Auth::id();
            $sql = "SELECT s.id AS schedule_id, c.code AS class_code, s.start_time, s.end_time,
                           t.id AS teacher_id, t.full_name AS teacher_name, COALESCE(ar.status, 'pending') AS attendance_status
                    FROM class_assignments ca
                    INNER JOIN schedules s ON s.id = ca.schedule_id AND s.day_of_week = ? AND s.is_cancelled = 0
                    INNER JOIN classes c ON c.id = s.class_id
                    INNER JOIN users t ON t.id = ca.teacher_id
                    LEFT JOIN attendance_records ar ON ar.schedule_id = s.id AND ar.teacher_id = ca.teacher_id AND ar.record_date = ?
                    WHERE ca.monitor_id = ? AND ca.status IN ('accepted','overridden')
                    ORDER BY s.start_time";
            $st = $db->prepare($sql);
            $st->execute([$dow, $date, $uid]);
            $items = $st->fetchAll();
        } elseif ($role === 'admin') {
            $sql = "SELECT s.id AS schedule_id, c.code AS class_code, s.start_time, s.end_time,
                           t.full_name AS teacher_name, COALESCE(ar.status, 'pending') AS attendance_status
                    FROM schedules s
                    INNER JOIN classes c ON c.id = s.class_id
                    LEFT JOIN class_assignments ca ON ca.schedule_id = s.id AND ca.status IN ('accepted','overridden')
                    LEFT JOIN users t ON t.id = ca.teacher_id
                    LEFT JOIN attendance_records ar ON ar.schedule_id = s.id AND ar.teacher_id = ca.teacher_id AND ar.record_date = ?
                    WHERE s.day_of_week = ? AND s.is_cancelled = 0
                    ORDER BY s.start_time";
            $st = $db->prepare($sql);
            $st->execute([$date, $dow]);
            $items = $st->fetchAll();
        }

        foreach ($items as &$it) {
            $it['ui_status'] = $this->computeUiStatus(
                (string) $it['start_time'],
                (string) $it['end_time'],
                (string) ($it['attendance_status'] ?? 'pending')
            );
        }
        unset($it);

        $this->json([
            'date' => $date,
            'server_time' => date('H:i:s'),
            'items' => $items,
        ]);
    }

    /**
     * Derive a simple UI label for dashboards (not-started / in-progress / verified / issue).
     */
    private function computeUiStatus(string $start, string $end, string $attendance): string
    {
        $now = time();
        $today = date('Y-m-d');
        $tsStart = strtotime($today . ' ' . $start);
        $tsEnd = strtotime($today . ' ' . $end);
        if ($tsStart === false || $tsEnd === false) {
            return 'unknown';
        }
        if ($tsEnd <= $tsStart) {
            $tsEnd += 86400;
        }
        $early = 5 * 60;
        if ($now < $tsStart - $early) {
            return 'upcoming';
        }
        if ($now < $tsStart) {
            return 'starting_soon';
        }
        if ($now >= $tsStart && $now <= $tsEnd) {
            return $attendance === 'pending' ? 'in_progress' : 'live';
        }
        if (in_array($attendance, ['absent', 'on_leave'], true)) {
            return 'issue';
        }
        return 'completed';
    }
}
