<?php
/**
 * Top navigation by role.
 *
 * @package CollegeCMS\Views
 */
declare(strict_types=1);
use App\Core\Auth;
use App\Models\ClassAssignment;
$u = Auth::user();
$teacherIsClassMonitor = false;
if (Auth::check() && (string) ($u['role'] ?? '') === 'teacher') {
    $teacherIsClassMonitor = (new ClassAssignment())->userIsClassMonitor((int) $u['id']);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary" aria-label="Main">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?php
            if (!Auth::check()) { echo \base_url(''); }
            else {
                $nr = (string) ($u['role'] ?? '');
                echo \base_url($nr === 'student' ? 'student' : 'timetable-board');
            }
        ?>"><?= \e((string) (\app_config()['app_name'] ?? 'CMS')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (!Auth::check()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= \base_url('') ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= \base_url('about') ?>">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= \base_url('contact') ?>">Contact</a></li>
                <?php else: ?>
                    <?php $r = $u['role'] ?? ''; ?>
                    <?php if ($r === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('timetable-board') ?>">Timetable</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('admin') ?>">Admin</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('admin/users') ?>">Users</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navMoreAdmin" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More sections">More <span class="d-inline-block" style="letter-spacing:0.15em" aria-hidden="true">⋮</span></a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navMoreAdmin">
                                <li><a class="dropdown-item" href="<?= \base_url('admin/departments') ?>">Departments</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/classes') ?>">Classes</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/schedules') ?>">Schedules</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/assignments') ?>">Assignments</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/rejections') ?>">Rejections &amp; explanations</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/attendance') ?>">Attendance</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/reports') ?>">Reports</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/announcements') ?>">Announcements</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/enrollments') ?>">Enrollments</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/settings') ?>">Import</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('admin/audit-logs') ?>">Audit</a></li>
                            </ul>
                        </li>
                    <?php elseif ($r === 'teacher'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('timetable-board') ?>">Timetable</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('teacher') ?>">Overview</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('teacher/selections') ?>">Selections</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navMoreTeacher" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More sections">More <span class="d-inline-block" style="letter-spacing:0.15em" aria-hidden="true">⋮</span></a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navMoreTeacher">
                                <li><a class="dropdown-item" href="<?= \base_url('teacher/classes') ?>">Browse classes</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('teacher/check-in') ?>">Check-in</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('teacher/history') ?>">History</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('teacher/attendance') ?>">My attendance</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('teacher/profile') ?>">Profile</a></li>
                                <?php if (!empty($teacherIsClassMonitor)) : ?>
                                <li><hr class="dropdown-divider" role="presentation" /></li>
                                <li><a class="dropdown-item" href="<?= \base_url('monitor') ?>">Class monitor: overview</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('monitor/rejections') ?>">Class monitor: rejections &amp; updates</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php elseif ($r === 'monitor'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('timetable-board') ?>">Timetable</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('monitor') ?>">Overview</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('monitor/rejections') ?>">Rejections &amp; updates</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('monitor/reports') ?>">Reports</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navMoreMonitor" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More sections">More <span class="d-inline-block" style="letter-spacing:0.15em" aria-hidden="true">⋮</span></a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navMoreMonitor">
                                <li><a class="dropdown-item" href="<?= \base_url('monitor/classes') ?>">My classes</a></li>
                                <li><a class="dropdown-item" href="<?= \base_url('monitor/profile') ?>">Profile</a></li>
                            </ul>
                        </li>
                    <?php elseif ($r === 'student'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('student') ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('student/class-updates') ?>">Rejections &amp; updates</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('timetable-board') ?>">Timetable</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('student/announcements') ?>">Announcements</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \base_url('student/profile') ?>">Profile</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= \base_url('notifications') ?>">Notifications <?php
                        $badgeClass = 'badge text-bg-warning';
                        $c = 0;
                        if ($u) {
                            $c = (new \App\Models\Notification())->unreadCount((int) $u['id']);
                        }
                        $hide = $c > 0 ? '' : ' d-none';
                        echo '<span class="' . $badgeClass . $hide . '" id="nav-unread-count">' . ($c > 0 ? (string) (int) $c : '') . '</span>';
                    ?></a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($u): ?>
                    <li class="nav-item"><span class="navbar-text me-2"><?= \e($u['full_name']) ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="<?= \base_url('logout') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= \base_url('login') ?>">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
