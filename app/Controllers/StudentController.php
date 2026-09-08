<?php
/**
 * Student read-only views.
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\RejectionExplanation;
use App\Models\StudentTeacherPresence;
use App\Models\TimetableBoardModel;
use App\Models\User;

final class StudentController extends Controller
{
    private function gate(): void
    {
        $this->requireRoles(['student']);
    }

    public function dashboard(): void
    {
        $this->gate();
        $this->view('student/dashboard', [
            'title' => 'Student dashboard',
        ]);
    }

    public function classUpdates(): void
    {
        $this->gate();
        $uid = (int) Auth::id();
        $rows = (new RejectionExplanation())->listForEnrolledStudent($uid, 40);
        $timetableDeclines = (new TimetableBoardModel())->listDeclinedPlacementsForEnrolledStudent($uid, 30);
        $this->view('student/class-updates', [
            'title' => 'Rejections & updates',
            'rows' => $rows,
            'timetable_declines' => $timetableDeclines,
        ]);
    }

    public function timetable(): void
    {
        $this->gate();
        redirect('/timetable-board');
    }

    public function teacherPresenceReport(): void
    {
        $this->gate();
        $this->validateCsrf();
        $sid = (int) ($_POST['schedule_id'] ?? 0);
        $present = isset($_POST['teacher_present']) && $_POST['teacher_present'] === '1';
        if ($sid < 1) {
            flash_set('error', 'Invalid class slot.');
            redirect('/student');
        }
        $uid = (int) Auth::id();
        $st = Database::pdo()->prepare('SELECT s.class_id FROM schedules s WHERE s.id = ? AND s.is_cancelled = 0 LIMIT 1');
        $st->execute([$sid]);
        $row = $st->fetch();
        if ($row === false) {
            flash_set('error', 'Class slot not found.');
            redirect('/student');
        }
        $classId = (int) $row['class_id'];
        if (!in_array($classId, (new Enrollment())->classIdsForStudent($uid), true)) {
            flash_set('error', 'You are not enrolled in that class.');
            redirect('/student');
        }
        $today = date('Y-m-d');
        if (!(new AttendanceRecord())->attestationWindowOpen($sid, $today)) {
            flash_set('error', 'Teacher presence can only be reported during the class period (from a few minutes before start through the scheduled end).');
            redirect('/student');
        }
        (new StudentTeacherPresence())->set($uid, $sid, $today, $present);
        $tid = (new ClassAssignment())->activeTeacherIdForSchedule($sid);
        if ($tid > 0) {
            try {
                (new AttendanceRecord())->applyStudentAttestation($sid, $tid, $today, $uid, $present);
            } catch (\Throwable) {
            }
        }
        (new AuditLog())->write($uid, 'student_teacher_presence', 'schedules', $sid, $present ? 'yes' : 'no');
        flash_set('success', 'Your response for this class period was recorded. Thank you.');
        redirect('/student');
    }

    public function announcements(): void
    {
        $this->gate();
        $rows = (new Announcement())->forRole('student');
        $this->view('student/announcements', ['title' => 'Announcements', 'rows' => $rows]);
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
            flash_set('success', 'Profile updated.');
            redirect('/student/profile');
        }
        $row = (new User())->find((int) Auth::id());
        $this->view('student/profile', ['title' => 'Profile', 'user' => $row]);
    }
}
