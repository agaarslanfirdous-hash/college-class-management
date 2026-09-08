<?php
/**
 * Teacher dashboard, class selection, check-in.
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\Klass;
use App\Models\Notification;
use App\Models\RejectionExplanation;
use App\Models\User;

final class TeacherController extends Controller
{
    private function gate(): void
    {
        $this->requireRoles(['teacher']);
    }

    public function dashboard(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $offered = (new ClassAssignment())->offeredForTeacher($uid);
        $mine = (new ClassAssignment())->forTeacher($uid);
        $this->view('teacher/dashboard', [
            'title' => 'Teacher dashboard',
            'offered' => $offered,
            'mine' => $mine,
        ]);
    }

    public function browseClasses(): void
    {
        $this->gate();
        $dept = isset($_GET['department_id']) ? (int) $_GET['department_id'] : null;
        if ($dept === 0) {
            $dept = null;
        }
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
        $rows = (new Klass())->filter($dept, $q ?: null);
        $depts = (new \App\Models\Department())->all();
        $this->view('teacher/classes', ['title' => 'Browse classes', 'rows' => $rows, 'depts' => $depts]);
    }

    public function selections(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $rows = (new ClassAssignment())->offeredForTeacher($uid);
        $this->view('teacher/selections', ['title' => 'Class selections', 'rows' => $rows]);
    }

    public function selectionAccept(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['assignment_id'] ?? 0);
        $ca = new ClassAssignment();
        $row = $ca->find($id);
        if ($row !== false && (int) $row['teacher_id'] === (int) Auth::id() && (string) $row['status'] === 'offered') {
            $ca->accept($id);
            (new AuditLog())->write(Auth::id(), 'assignment_accept', 'class_assignments', $id, null);
            (new Notification())->create((int) Auth::id(), 'assignment', 'Class accepted', 'You accepted a class assignment.');
            flash_set('success', 'You have accepted this class assignment.');
        } else {
            flash_set('error', 'That offer is no longer available or does not belong to you.');
        }
        redirect('/teacher/selections');
    }

    public function selectionWithdraw(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['assignment_id'] ?? 0);
        if ($id < 1) {
            flash_set('error', 'Invalid assignment.');
            redirect('/teacher');
        }
        $err = (new ClassAssignment())->withdrawByTeacher($id, (int) Auth::id());
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            (new AuditLog())->write(Auth::id(), 'assignment_withdraw', 'class_assignments', $id, null);
            flash_set('success', 'You have withdrawn from this class assignment. Admin can reassign the slot.');
        }
        redirect('/teacher');
    }

    public function selectionReject(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['assignment_id'] ?? 0);
        $rawReason = (string) ($_POST['reason_type'] ?? 'other');
        $allowedReasons = ['schedule_conflicts', 'workload', 'health', 'personal', 'other'];
        $reasonType = in_array($rawReason, $allowedReasons, true) ? $rawReason : 'other';
        $reasonText = trim((string) ($_POST['reason_text'] ?? ''));
        if ($reasonText === '') {
            flash_set('error', 'Explanation required.');
            redirect('/teacher/selections');
        }
        $ca = new ClassAssignment();
        $row = $ca->find($id);
        if ($row === false || (int) $row['teacher_id'] !== (int) Auth::id() || (string) $row['status'] !== 'offered') {
            flash_set('error', 'That offer is no longer open for rejection, or it does not belong to you.');
            redirect('/teacher/selections');
        }
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            (new RejectionExplanation())->create($id, $reasonType, $reasonText);
            $ca->reject($id);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('selectionReject: ' . $e->getMessage());
            flash_set('error', 'Could not save your rejection. Please try again, or contact support if this persists.');
            redirect('/teacher/selections');
        }
        (new AuditLog())->write(Auth::id(), 'assignment_reject', 'class_assignments', $id, $reasonType);
        $ctx = $ca->findWithClassContext($id);
        $tName = $ctx !== false ? trim((string) ($ctx['teacher_name'] ?? '')) : '';
        if ($tName === '') {
            $tName = 'A teacher';
        }
        $classCode = $ctx !== false ? trim((string) ($ctx['class_code'] ?? '')) : '';
        $className = $ctx !== false ? trim((string) ($ctx['class_name'] ?? '')) : '';
        $dept = $ctx !== false ? trim((string) ($ctx['department_name'] ?? '')) : '';
        $classLine = $classCode !== '' || $className !== ''
            ? ($classCode !== '' ? $classCode . ' — ' : '') . $className
            : 'a class offer';
        $deptLine = $dept !== '' ? 'Department: ' . $dept : 'Department: (not set)';
        $title = 'Class offer declined';
        $message = $tName . ' declined the offer for ' . $classLine . '. ' . $deptLine . '.';
        $notifyUserIds = [];
        foreach ((new User())->allByRole('admin') as $a) {
            $notifyUserIds[(int) $a['id']] = true;
        }
        foreach ((new User())->allByRole('monitor') as $m) {
            $notifyUserIds[(int) $m['id']] = true;
        }
        if ($ctx !== false) {
            $mid = (int) ($ctx['monitor_id'] ?? 0);
            if ($mid > 0) {
                $notifyUserIds[$mid] = true;
            }
        }
        foreach (array_keys($notifyUserIds) as $nuid) {
            try {
                (new Notification())->create($nuid, 'rejection', $title, $message);
            } catch (\Throwable) {
            }
        }
        $classId = $ca->classIdForAssignment($id);
        if ($classId > 0) {
            $stuLine = 'A teacher declined a class you are enrolled in: ' . $classLine . '. ' . $deptLine;
            $stuTitle = 'Class offer update';
            foreach ((new Enrollment())->studentUserIdsForClass($classId) as $sid) {
                if ($sid < 1) {
                    continue;
                }
                try {
                    (new Notification())->create(
                        $sid,
                        'rejection',
                        $stuTitle,
                        $stuLine . (strlen($reasonText) > 0 ? ' (Reason provided to administration.)' : '')
                    );
                } catch (\Throwable) {
                }
            }
        }
        flash_set('success', 'Rejection submitted. An administrator can review the reason you provided.');
        redirect('/teacher/selections');
    }

    public function history(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $mine = (new ClassAssignment())->forTeacher($uid);
        $this->view('teacher/history', ['title' => 'Selection history', 'rows' => $mine]);
    }

    public function checkInForm(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $todayDow = (int) date('w');
        $assignments = (new ClassAssignment())->forTeacher($uid);
        $today = [];
        foreach ($assignments as $a) {
            if ((string) $a['status'] !== 'accepted' && (string) $a['status'] !== 'overridden') {
                continue;
            }
            if ((int) $a['day_of_week'] !== $todayDow) {
                continue;
            }
            if (!empty($a['is_cancelled'])) {
                continue;
            }
            $today[] = $a;
        }
        $this->view('teacher/checkin', ['title' => 'Check-in', 'today' => $today]);
    }

    public function checkInSubmit(): void
    {
        $this->gate();
        $this->validateCsrf();
        $sid = (int) ($_POST['schedule_id'] ?? 0);
        $pin = (string) ($_POST['pin'] ?? '');
        $uid = (int) Auth::id();
        $db = \App\Core\Database::pdo();
        $chk = $db->prepare(
            "SELECT id FROM class_assignments WHERE schedule_id = ? AND teacher_id = ? AND status IN ('accepted','overridden') LIMIT 1"
        );
        $chk->execute([$sid, $uid]);
        if (!$chk->fetch()) {
            flash_set('error', 'Invalid session.');
            redirect('/teacher/check-in');
        }
        $user = (new User())->find($uid);
        if ($user === false || empty($user['teacher_pin_hash'])) {
            flash_set('error', 'Set your PIN in Profile first.');
            redirect('/teacher/check-in');
        }
        if (!password_verify($pin, (string) $user['teacher_pin_hash'])) {
            flash_set('error', 'Invalid PIN.');
            redirect('/teacher/check-in');
        }
        $date = date('Y-m-d');
        (new AttendanceRecord())->teacherCheckIn($sid, $uid, $date);
        (new AuditLog())->write($uid, 'teacher_checkin', 'attendance_records', $sid, $date);
        flash_set('success', 'Checked in.');
        redirect('/teacher/check-in');
    }

    public function attendance(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $db = \App\Core\Database::pdo();
        $st = $db->prepare(
            'SELECT ar.*, s.start_time, c.code AS class_code FROM attendance_records ar
             INNER JOIN schedules s ON s.id = ar.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             WHERE ar.teacher_id = ? ORDER BY ar.record_date DESC LIMIT 60'
        );
        $st->execute([$uid]);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        $this->view('teacher/attendance', ['title' => 'My attendance', 'rows' => $rows]);
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
                if (strlen($newPass) >= $minLen && preg_match('/[A-Za-z]/', $newPass) && preg_match('/\d/', $newPass)) {
                    (new User())->updatePassword((int) Auth::id(), password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]));
                }
            }
            $pin = (string) ($_POST['new_pin'] ?? '');
            if ($pin !== '') {
                if (strlen($pin) >= 4 && strlen($pin) <= 12) {
                    (new User())->setTeacherPin((int) Auth::id(), password_hash($pin, PASSWORD_BCRYPT, ['cost' => 12]));
                }
            }
            (new AuditLog())->write(Auth::id(), 'profile_update', 'user', (int) Auth::id(), null);
            flash_set('success', 'Profile updated.');
            redirect('/teacher/profile');
        }
        $row = (new User())->find((int) Auth::id());
        $this->view('teacher/profile', ['title' => 'Profile', 'user' => $row]);
    }
}
