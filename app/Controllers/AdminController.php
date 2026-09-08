<?php
/**
 * Administrator dashboard and CRUD.
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Klass;
use App\Models\Notification;
use App\Models\RejectionExplanation;
use App\Models\Schedule;
use App\Models\TimetableBoardModel;
use App\Models\User;

final class AdminController extends Controller
{
    private function gate(): void
    {
        $this->requireRoles(['admin']);
    }

    public function dashboard(): void
    {
        $this->gate();
        $db = \App\Core\Database::pdo();
        $counts = [
            'users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'teachers' => (int) $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
            'classes' => (int) $db->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
            'pending_rejections' => (int) $db->query(
                "SELECT COUNT(*) FROM class_assignments ca
                 INNER JOIN rejection_explanations re ON re.assignment_id = ca.id
                 WHERE ca.status='rejected' AND (re.admin_response IS NULL OR re.admin_response='')"
            )->fetchColumn(),
        ];
        $this->view('admin/dashboard', ['title' => 'Admin dashboard', 'counts' => $counts]);
    }

    public function users(): void
    {
        $this->gate();
        $users = (new User())->allByRole();
        $this->view('admin/users', ['title' => 'Users', 'users' => $users]);
    }

    public function userCreate(): void
    {
        $this->gate();
        $this->validateCsrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'student');
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $phone = trim((string) ($_POST['phone'] ?? '')) ?: null;
        $allowed = ['admin', 'teacher', 'monitor', 'student'];
        if (!in_array($role, $allowed, true) || $email === '' || $name === '') {
            flash_set('error', 'Invalid input.');
            redirect('/admin/users');
        }
        $minLen = (int) (\app_config()['security']['password_min_length'] ?? 10);
        if (strlen($pass) < $minLen) {
            flash_set('error', 'Password too short.');
            redirect('/admin/users');
        }
        if ((new User())->findByEmail($email) !== false) {
            flash_set('error', 'Email already exists.');
            redirect('/admin/users');
        }
        $id = (new User())->create([
            'email' => $email,
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => $role,
            'full_name' => $name,
            'phone' => $phone,
            'is_active' => 1,
        ]);
        (new AuditLog())->write(Auth::id(), 'user_create', 'user', $id, $email);
        flash_set('success', 'User created.');
        redirect('/admin/users');
    }

    public function userToggle(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === Auth::id()) {
            flash_set('error', 'Cannot deactivate yourself.');
            redirect('/admin/users');
        }
        $u = new User();
        $row = $u->find($id);
        if ($row === false) {
            redirect('/admin/users');
        }
        $u->setActive($id, !(bool) $row['is_active']);
        (new AuditLog())->write(Auth::id(), 'user_toggle_active', 'user', $id, null);
        flash_set('success', 'User updated.');
        redirect('/admin/users');
    }

    public function departments(): void
    {
        $this->gate();
        $rows = (new Department())->all();
        $editId = (int) ($_GET['edit'] ?? 0);
        $this->view('admin/departments', ['title' => 'Departments', 'rows' => $rows, 'edit_id' => $editId]);
    }

    public function departmentSave(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $d = new Department();
        if ($id > 0) {
            $d->update($id, $name, $code);
            (new AuditLog())->write(Auth::id(), 'department_update', 'department', $id, null);
        } else {
            $newId = $d->create($name, $code);
            (new TimetableBoardModel())->ensureProgramForDepartment($newId, $name, $code);
            (new AuditLog())->write(Auth::id(), 'department_create', 'department', $newId, null);
        }
        flash_set('success', 'Saved.');
        redirect('/admin/departments');
    }

    public function departmentDelete(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        try {
            (new Department())->delete($id);
            (new AuditLog())->write(Auth::id(), 'department_delete', 'department', $id, null);
            flash_set('success', 'Deleted.');
        } catch (\PDOException) {
            flash_set('error', 'Cannot delete: in use by classes.');
        }
        redirect('/admin/departments');
    }

    public function classes(): void
    {
        $this->gate();
        $rows = (new Klass())->allWithDepartment();
        $depts = (new Department())->all();
        $m = new TimetableBoardModel();
        $m->syncAllDepartmentsToPrograms();
        $periodsByDept = [];
        foreach ($depts as $d) {
            $pid = $m->getProgramIdForDepartment((int) $d['id']);
            $periodsByDept[(int) $d['id']] = $pid > 0 ? $m->periods($pid) : [];
        }
        $this->view('admin/classes', [
            'title' => 'Classes',
            'rows' => $rows,
            'depts' => $depts,
            'periods_by_dept' => $periodsByDept,
        ]);
    }

    public function classSave(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'code' => trim((string) ($_POST['code'] ?? '')),
            'name' => (string) ($_POST['name'] ?? ''),
            'subject' => (string) ($_POST['subject'] ?? ''),
            'department_id' => (int) ($_POST['department_id'] ?? 0),
            'grade_level' => trim((string) ($_POST['grade_level'] ?? '')) ?: null,
            'section' => trim((string) ($_POST['section'] ?? '')) ?: null,
            'academic_batch' => trim((string) ($_POST['academic_batch'] ?? '')) ?: null,
            'capacity' => (int) ($_POST['capacity'] ?? 30),
            'requirements' => trim((string) ($_POST['requirements'] ?? '')) ?: null,
        ];
        if ($data['code'] === '' || $data['name'] === '' || $data['subject'] === '') {
            flash_set('error', 'Class code, name, and subject are required.');
            redirect('/admin/classes');
        }
        if ($data['department_id'] < 1) {
            flash_set('error', 'Select a department.');
            redirect('/admin/classes');
        }
        $k = new Klass();
        if ($k->codeExists($data['code'], $id > 0 ? $id : null)) {
            flash_set(
                'error',
                'A class with code ' . $data['code'] . ' already exists. Use a different code (for example ' . $data['code'] . '-B or include section in the code), or edit the existing class below.'
            );
            redirect('/admin/classes');
        }
        $tday = (int) ($_POST['timetable_day'] ?? 0);
        $tper = (int) ($_POST['timetable_period'] ?? 0);
        if ($tday > 0 && $tper < 1) {
            flash_set('error', 'If you pick a day for the board, also pick a period (e.g. V = 1:30–2:30), or set both to auto.');
            redirect('/admin/classes');
        }
        if ($tper > 0 && $tday < 1) {
            flash_set('error', 'If you pick a period, also pick a weekday, or set both to auto.');
            redirect('/admin/classes');
        }
        $old = $id > 0 ? $k->findById($id) : null;
        $newId = 0;
        try {
            if ($id > 0) {
                $k->update($id, $data);
                (new AuditLog())->write(Auth::id(), 'class_update', 'classes', $id, $data['code']);
            } else {
                $newId = $k->create($data);
                (new AuditLog())->write(Auth::id(), 'class_create', 'classes', $newId, $data['code']);
            }
        } catch (\PDOException) {
            flash_set('error', 'Could not save: that class code may already be in use, or the data is invalid. Try a unique code.');
            redirect('/admin/classes');
        }
        $savedId = $id > 0 ? $id : $newId;
        $saved = $k->findById($savedId);
        if ($saved !== null) {
            $m = new TimetableBoardModel();
            $m->syncAllDepartmentsToPrograms();
            if ($old !== null) {
                $oc = trim((string) ($old['code'] ?? ''));
                $nc = trim((string) $data['code']);
                $od = (int) ($old['department_id'] ?? 0);
                $nd = (int) $data['department_id'];
                if ($oc !== $nc || $od !== $nd) {
                    $m->removeClassCodeFromProgramBoard($oc, $od);
                }
            }
            $msg = $m->syncClassToBoard(
                [
                    'code' => (string) $saved['code'],
                    'name' => (string) $saved['name'],
                    'subject' => (string) $saved['subject'],
                    'department_id' => (int) $saved['department_id'],
                ],
                $tday,
                $tper
            );
            if ($msg !== null) {
                flash_set('warning', 'Class saved, but timetable: ' . $msg);
            } else {
                flash_set('success', 'Saved. The class code is on this department’s interactive board (Timetable); pick that program to assign teachers and drag the chip.');
            }
        } else {
            flash_set('success', 'Saved.');
        }
        redirect('/admin/classes');
    }

    public function classDelete(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $k = new Klass();
        $row = $k->findById($id);
        if ($row !== null) {
            (new TimetableBoardModel())->removeClassCodeFromProgramBoard(
                (string) $row['code'],
                (int) $row['department_id']
            );
        }
        try {
            $k->delete($id);
            (new AuditLog())->write(Auth::id(), 'class_delete', 'classes', $id, null);
            flash_set('success', 'Deleted.');
        } catch (\PDOException) {
            flash_set('error', 'Cannot delete: schedules or enrollments exist.');
        }
        redirect('/admin/classes');
    }

    public function schedules(): void
    {
        $this->gate();
        $rows = (new Schedule())->allWithClass();
        $classes = (new Klass())->allWithDepartment();
        $this->view('admin/schedules', ['title' => 'Schedules', 'rows' => $rows, 'classes' => $classes]);
    }

    public function scheduleSave(): void
    {
        $this->gate();
        $this->validateCsrf();
        $data = [
            'class_id' => (int) ($_POST['class_id'] ?? 0),
            'day_of_week' => (int) ($_POST['day_of_week'] ?? 0),
            'start_time' => (string) ($_POST['start_time'] ?? '09:00'),
            'end_time' => (string) ($_POST['end_time'] ?? '10:00'),
            'room' => trim((string) ($_POST['room'] ?? '')) ?: null,
            'effective_from' => (string) ($_POST['effective_from'] ?? date('Y-m-d')),
            'effective_to' => trim((string) ($_POST['effective_to'] ?? '')) ?: null,
        ];
        $id = (new Schedule())->create($data);
        (new AuditLog())->write(Auth::id(), 'schedule_create', 'schedules', $id, null);
        flash_set('success', 'Schedule added.');
        redirect('/admin/schedules');
    }

    public function scheduleCancel(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? 'Cancelled'));
        (new Schedule())->cancel($id, $reason);
        (new AuditLog())->write(Auth::id(), 'schedule_cancel', 'schedules', $id, $reason);
        flash_set('success', 'Cancelled.');
        redirect('/admin/schedules');
    }

    public function assignments(): void
    {
        $this->gate();
        $rows = (new ClassAssignment())->allForAdmin();
        $schedules = (new Schedule())->allWithClass();
        $teachers = (new User())->allByRole('teacher');
        $monitors = (new User())->allByRole('monitor');
        $this->view('admin/assignments', [
            'title' => 'Assignments',
            'rows' => $rows,
            'schedules' => $schedules,
            'teachers' => $teachers,
            'monitors' => $monitors,
        ]);
    }

    public function assignmentOffer(): void
    {
        $this->gate();
        $this->validateCsrf();
        $sid = (int) ($_POST['schedule_id'] ?? 0);
        $tid = (int) ($_POST['teacher_id'] ?? 0);
        $mid = (int) ($_POST['monitor_id'] ?? 0) ?: null;
        $id = (new ClassAssignment())->offer($sid, $tid, $mid);
        (new AuditLog())->write(Auth::id(), 'assignment_offer', 'class_assignments', $id, null);
        $n = new Notification();
        $n->create($tid, 'assignment', 'New class offered', 'A new class assignment is available for your review.');
        flash_set('success', 'Offered to teacher.');
        redirect('/admin/assignments');
    }

    public function assignmentOverride(): void
    {
        $this->gate();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $newTid = (int) ($_POST['new_teacher_id'] ?? 0) ?: null;
        $mid = (int) ($_POST['monitor_id'] ?? 0) ?: null;
        $note = trim((string) ($_POST['note'] ?? ''));
        (new ClassAssignment())->overrideAssignment($id, $newTid, $mid, $note);
        (new AuditLog())->write(Auth::id(), 'assignment_override', 'class_assignments', $id, $note);
        flash_set('success', 'Overridden.');
        redirect('/admin/assignments');
    }

    public function rejections(): void
    {
        $this->gate();
        $rows = (new ClassAssignment())->pendingRejections();
        $log = (new ClassAssignment())->recentRejectedOffersWithExplanations(50);
        $timetableDeclines = (new TimetableBoardModel())->listDeclinedSlotPlacements(50);
        $this->view('admin/rejections', [
            'title' => 'Rejections',
            'rows' => $rows,
            'class_rejection_log' => $log,
            'timetable_declines' => $timetableDeclines,
        ]);
    }

    public function rejectionRespond(): void
    {
        $this->gate();
        $this->validateCsrf();
        $eid = (int) ($_POST['explanation_id'] ?? 0);
        $text = trim((string) ($_POST['admin_response'] ?? ''));
        (new RejectionExplanation())->respond($eid, $text);
        (new AuditLog())->write(Auth::id(), 'rejection_respond', 'rejection_explanations', $eid, null);
        flash_set('success', 'Response recorded.');
        redirect('/admin/rejections');
    }

    public function attendance(): void
    {
        $this->gate();
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        $rows = (new AttendanceRecord())->forDate($date);
        $this->view('admin/attendance', ['title' => 'Attendance', 'date' => $date, 'rows' => $rows]);
    }

    public function reports(): void
    {
        $this->gate();
        $month = (string) ($_GET['month'] ?? date('Y-m'));
        $summary = (new AttendanceRecord())->summaryByMonth($month);
        $this->view('admin/reports', ['title' => 'Reports', 'month' => $month, 'summary' => $summary]);
    }

    public function reportsExport(): void
    {
        $this->gate();
        $month = (string) ($_GET['month'] ?? date('Y-m'));
        $db = \App\Core\Database::pdo();
        $st = $db->prepare(
            "SELECT ar.record_date, ar.status, ar.check_in_time, c.code, t.full_name AS teacher
             FROM attendance_records ar
             INNER JOIN schedules s ON s.id = ar.schedule_id
             INNER JOIN classes c ON c.id = s.class_id
             INNER JOIN users t ON t.id = ar.teacher_id
             WHERE DATE_FORMAT(ar.record_date, '%Y-%m') = ?
             ORDER BY ar.record_date, c.code"
        );
        $st->execute([$month]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="attendance-' . $month . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['date', 'class', 'teacher', 'status', 'check_in']);
        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            fputcsv($out, [$row['record_date'], $row['code'], $row['teacher'], $row['status'], $row['check_in_time']]);
        }
        fclose($out);
        exit;
    }

    public function announcements(): void
    {
        $this->gate();
        $rows = (new Announcement())->allAdmin();
        $this->view('admin/announcements', ['title' => 'Announcements', 'rows' => $rows]);
    }

    public function announcementCreate(): void
    {
        $this->gate();
        $this->validateCsrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $target = (string) ($_POST['target_role'] ?? 'all');
        $allowed = ['all', 'admin', 'teacher', 'monitor', 'student'];
        if (!in_array($target, $allowed, true)) {
            $target = 'all';
        }
        $id = (new Announcement())->create((int) Auth::id(), $title, $body, $target);
        (new AuditLog())->write(Auth::id(), 'announcement_create', 'announcements', $id, null);
        flash_set('success', 'Published.');
        redirect('/admin/announcements');
    }

    public function auditLogs(): void
    {
        $this->gate();
        $rows = (new AuditLog())->recent(300);
        $this->view('admin/audit', ['title' => 'Audit log', 'rows' => $rows]);
    }

    public function settings(): void
    {
        $this->gate();
        $this->view('admin/settings', ['title' => 'Settings']);
    }

    public function enrollments(): void
    {
        $this->gate();
        $students = (new User())->allByRole('student');
        $classes = (new Klass())->allWithDepartment();
        $this->view('admin/enrollments', ['title' => 'Enrollments', 'students' => $students, 'classes' => $classes]);
    }

    public function enrollmentSave(): void
    {
        $this->gate();
        $this->validateCsrf();
        $sid = (int) ($_POST['student_id'] ?? 0);
        $cid = (int) ($_POST['class_id'] ?? 0);
        (new Enrollment())->enroll($sid, $cid);
        (new AuditLog())->write(Auth::id(), 'enrollment_add', 'enrollments', $cid, (string) $sid);
        flash_set('success', 'Enrolled.');
        redirect('/admin/enrollments');
    }

    public function enrollmentRemove(): void
    {
        $this->gate();
        $this->validateCsrf();
        $sid = (int) ($_POST['student_id'] ?? 0);
        $cid = (int) ($_POST['class_id'] ?? 0);
        (new Enrollment())->unenroll($sid, $cid);
        (new AuditLog())->write(Auth::id(), 'enrollment_remove', 'enrollments', $cid, (string) $sid);
        flash_set('success', 'Removed.');
        redirect('/admin/enrollments');
    }

    public function importCsv(): void
    {
        $this->gate();
        $this->validateCsrf();
        $type = (string) ($_POST['type'] ?? '');
        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            flash_set('error', 'No file.');
            redirect('/admin/settings');
        }
        $tmp = $_FILES['csv']['tmp_name'];
        $fh = fopen($tmp, 'r');
        if ($fh === false) {
            flash_set('error', 'Could not read file.');
            redirect('/admin/settings');
        }
        $line = 0;
        try {
            if ($type === 'departments') {
                $d = new Department();
                while (($row = fgetcsv($fh)) !== false) {
                    $line++;
                    if ($line === 1 && isset($row[0]) && strcasecmp((string) $row[0], 'name') === 0) {
                        continue;
                    }
                    if (count($row) < 2) {
                        continue;
                    }
                    $d->create(trim((string) $row[0]), trim((string) $row[1]));
                }
            } elseif ($type === 'classes') {
                $k = new Klass();
                $depts = (new Department())->all();
                $byCode = [];
                foreach ($depts as $dep) {
                    $byCode[(string) $dep['code']] = (int) $dep['id'];
                }
                while (($row = fgetcsv($fh)) !== false) {
                    $line++;
                    if ($line === 1 && isset($row[0]) && strcasecmp((string) $row[0], 'code') === 0) {
                        continue;
                    }
                    if (count($row) < 4) {
                        continue;
                    }
                    $code = trim((string) $row[0]);
                    $name = trim((string) $row[1]);
                    $subject = trim((string) $row[2]);
                    $deptCode = strtoupper(trim((string) $row[3]));
                    $capacity = isset($row[4]) ? (int) $row[4] : 30;
                    $grade = isset($row[5]) ? trim((string) $row[5]) : null;
                    if (!isset($byCode[$deptCode])) {
                        continue;
                    }
                    if ($k->codeExists($code)) {
                        continue;
                    }
                    $k->create([
                        'code' => $code,
                        'name' => $name,
                        'subject' => $subject,
                        'department_id' => $byCode[$deptCode],
                        'grade_level' => $grade,
                        'capacity' => $capacity,
                        'requirements' => null,
                    ]);
                }
            } elseif ($type === 'enrollments') {
                $e = new Enrollment();
                while (($row = fgetcsv($fh)) !== false) {
                    $line++;
                    if ($line === 1 && isset($row[0]) && strcasecmp((string) $row[0], 'student_email') === 0) {
                        continue;
                    }
                    if (count($row) < 2) {
                        continue;
                    }
                    $u = (new User())->findByEmail(trim((string) $row[0]));
                    $cls = (new Klass())->filter(null, trim((string) $row[1]));
                    if ($u === false || (string) $u['role'] !== 'student') {
                        continue;
                    }
                    $classId = null;
                    foreach ($cls as $c) {
                        if (strcasecmp((string) $c['code'], trim((string) $row[1])) === 0) {
                            $classId = (int) $c['id'];
                            break;
                        }
                    }
                    if ($classId) {
                        $e->enroll((int) $u['id'], $classId);
                    }
                }
            }
            (new AuditLog())->write(Auth::id(), 'csv_import', 'settings', null, $type);
            flash_set('success', 'Import completed.');
        } catch (\Throwable $ex) {
            flash_set('error', 'Import failed: ' . $ex->getMessage());
        } finally {
            fclose($fh);
        }
        redirect('/admin/settings');
    }
}
