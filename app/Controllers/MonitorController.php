<?php
/**
 * Class monitor — verify attendance for assigned schedules.
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Notification;
use App\Models\RejectionExplanation;
use App\Models\TimetableBoardModel;
use App\Models\User;

final class MonitorController extends Controller
{
    private function gate(): void
    {
        $this->requireAuth();
        $r = Auth::role();
        if ($r === 'monitor') {
            return;
        }
        if ($r === 'teacher' && (new ClassAssignment())->userIsClassMonitor((int) Auth::id())) {
            return;
        }
        http_response_code(403);
        $this->view('layouts/error', [
            'title' => 'Forbidden',
            'message' => 'You do not have access to class-monitoring features.',
        ]);
        exit;
    }

    /**
     * @return list<int>
     */
    private function myScheduleIds(): array
    {
        $uid = (int) Auth::id();
        $rows = (new ClassAssignment())->allForAdmin();
        $ids = [];
        foreach ($rows as $r) {
            if ((int) ($r['monitor_id'] ?? 0) === $uid) {
                $ids[] = (int) $r['schedule_id'];
            }
        }
        return array_values(array_unique($ids));
    }

    public function dashboard(): void
    {
        $this->gate();
        $date = date('Y-m-d');
        $ids = $this->myScheduleIds();
        $rows = (new AttendanceRecord())->forMonitorSchedules($ids, $date);
        $this->view('monitor/dashboard', ['title' => 'Monitor dashboard', 'date' => $date, 'rows' => $rows]);
    }

    public function classes(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $all = (new ClassAssignment())->allForAdmin();
        $mine = [];
        foreach ($all as $r) {
            if ((int) ($r['monitor_id'] ?? 0) === $uid) {
                $mine[] = $r;
            }
        }
        $this->view('monitor/classes', ['title' => 'My classes', 'rows' => $mine]);
    }

    public function rejectionNotices(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $rows = (new RejectionExplanation())->listForClassMonitorUser($uid, 40);
        $timetableDeclines = (new TimetableBoardModel())->listDeclinedPlacementsForMonitorDepts($uid, 30);
        $this->view('monitor/rejections', [
            'title' => 'Rejections & updates',
            'rows' => $rows,
            'timetable_declines' => $timetableDeclines,
        ]);
    }

    public function verifyForm(string $scheduleId): void
    {
        $this->gate();
        $sid = (int) $scheduleId;
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        $db = \App\Core\Database::pdo();
        $st = $db->prepare(
            'SELECT ca.*, s.start_time, s.end_time, s.room, c.code AS class_code, t.id AS teacher_id, t.full_name AS teacher_name
             FROM class_assignments ca
             INNER JOIN schedules s ON s.id = ca.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ca.teacher_id
             WHERE ca.schedule_id = ? AND ca.monitor_id = ? AND ca.status IN (\'accepted\',\'overridden\')'
        );
        $st->execute([$sid, Auth::id()]);
        $assignment = $st->fetch(\PDO::FETCH_ASSOC);
        if ($assignment === false) {
            http_response_code(404);
            $this->view('layouts/error', ['title' => 'Not found', 'message' => 'Assignment not found.']);
            exit;
        }
        $ar = new AttendanceRecord();
        $rec = $ar->getOrCreate($sid, (int) $assignment['teacher_id'], $date);
        $this->view('monitor/verify', [
            'title' => 'Verify attendance',
            'assignment' => $assignment,
            'record' => $rec,
            'date' => $date,
        ]);
    }

    public function verifySubmit(): void
    {
        $this->gate();
        $this->validateCsrf();
        $sid = (int) ($_POST['schedule_id'] ?? 0);
        $tid = (int) ($_POST['teacher_id'] ?? 0);
        $date = (string) ($_POST['date'] ?? date('Y-m-d'));
        $status = (string) ($_POST['status'] ?? 'present');
        $allowed = ['present', 'absent', 'late', 'on_leave'];
        if (!in_array($status, $allowed, true)) {
            $status = 'present';
        }
        $v = \App\Core\Database::pdo()->prepare(
            'SELECT id FROM class_assignments WHERE schedule_id = ? AND teacher_id = ? AND monitor_id = ? AND status IN (\'accepted\',\'overridden\') LIMIT 1'
        );
        $v->execute([$sid, $tid, Auth::id()]);
        if (!$v->fetch()) {
            flash_set('error', 'Not authorized for this verification.');
            redirect('/monitor');
        }
        $notes = trim((string) ($_POST['notes'] ?? '')) ?: null;
        $escalated = !empty($_POST['escalated']);
        (new AttendanceRecord())->monitorVerify($sid, $tid, $date, (int) Auth::id(), $status, 'manual', $notes, $escalated);
        (new AuditLog())->write(Auth::id(), 'monitor_verify', 'attendance_records', $sid, $status);
        if ($escalated) {
            foreach ((new User())->allByRole('admin') as $a) {
                (new Notification())->create((int) $a['id'], 'escalation', 'Attendance escalated', 'Monitor flagged an attendance issue.');
            }
        }
        flash_set('success', 'Recorded.');
        redirect('/monitor/verify/' . $sid . '?date=' . urlencode($date));
    }

    public function reports(): void
    {
        $this->gate();
        $month = (string) ($_GET['month'] ?? date('Y-m'));
        $summary = (new AttendanceRecord())->summaryByMonth($month);
        $this->view('monitor/reports', ['title' => 'Reports', 'month' => $month, 'summary' => $summary]);
    }

    public function profile(): void
    {
        $this->gate();
        if (request_method() === 'POST') {
            $this->validateCsrf();
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? '')) ?: null;
            (new User())->updateProfile((int) Auth::id(), $name, $phone);
            $_SESSION['user_name'] = $name;
            $newPass = (string) ($_POST['new_password'] ?? '');
            if ($newPass !== '') {
                $minLen = (int) (\app_config()['security']['password_min_length'] ?? 10);
                if (strlen($newPass) >= $minLen) {
                    (new User())->updatePassword((int) Auth::id(), password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]));
                }
            }
            flash_set('success', 'Profile updated.');
            redirect('/monitor/profile');
        }
        $row = (new User())->find((int) Auth::id());
        $this->view('monitor/profile', ['title' => 'Profile', 'user' => $row]);
    }
}
