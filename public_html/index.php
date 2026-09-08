<?php
/**
 * Front controller — all requests (except static files) route here.
 *
 * @package CollegeCMS
 */

declare(strict_types=1);

if (!isset($GLOBALS['cms_url_base_path'])) {
    $GLOBALS['cms_url_base_path'] = '';
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\MonitorController;
use App\Controllers\SharedController;
use App\Controllers\StudentController;
use App\Controllers\TeacherController;
use App\Controllers\TimetableBoardController;
use App\Core\Logger;
use App\Core\Router;

$router = new Router();

$router->get('/', fn () => (new HomeController())->index());
$router->get('/about', fn () => (new HomeController())->about());
$router->get('/contact', fn () => (new HomeController())->contact());
$router->get('/privacy', fn () => (new HomeController())->privacy());
$router->get('/terms', fn () => (new HomeController())->terms());
$router->get('/dashboard', fn () => (new HomeController())->dashboardRedirect());

$router->get('/login', fn () => (new AuthController())->showLogin());
$router->post('/login', fn () => (new AuthController())->login());
$router->get('/logout', fn () => (new AuthController())->logout());
$router->get('/forgot-password', fn () => (new AuthController())->showForgot());
$router->post('/forgot-password', fn () => (new AuthController())->forgot());
$router->get('/reset-password/{token}', fn (array $p) => (new AuthController())->showReset($p['token']));
$router->post('/reset-password', fn () => (new AuthController())->reset());

$router->get('/notifications', fn () => (new SharedController())->notifications());
$router->post('/notifications/read', fn () => (new SharedController())->notificationRead());
$router->post('/notifications/read-all', fn () => (new SharedController())->notificationsReadAll());

$router->get('/api/notifications/unread', fn () => (new ApiController())->notificationsUnread());
$router->get('/api/live-status', fn () => (new ApiController())->liveStatus());

$router->get('/timetable-board', fn () => (new TimetableBoardController())->board());
$router->post('/timetable-board/move', fn () => (new TimetableBoardController())->postMove());
$router->post('/timetable-board/swap', fn () => (new TimetableBoardController())->postSwap());
$router->post('/timetable-board/swap-cells', fn () => (new TimetableBoardController())->postSwapCells());
$router->post('/timetable-board/swap-proposal', fn () => (new TimetableBoardController())->postSwapProposalResolve());
$router->post('/timetable-board/exchange', fn () => (new TimetableBoardController())->postExchange());
$router->post('/timetable-board/exchange/resolve', fn () => (new TimetableBoardController())->postExchangeResolve());
$router->post('/timetable-board/placement-respond', fn () => (new TimetableBoardController())->postPlacementResponse());
$router->post('/timetable-board/assign-teacher', fn () => (new TimetableBoardController())->postAssignTeacher());

$router->get('/admin', fn () => (new AdminController())->dashboard());
$router->get('/admin/users', fn () => (new AdminController())->users());
$router->post('/admin/users/create', fn () => (new AdminController())->userCreate());
$router->post('/admin/users/toggle', fn () => (new AdminController())->userToggle());
$router->get('/admin/departments', fn () => (new AdminController())->departments());
$router->post('/admin/departments/save', fn () => (new AdminController())->departmentSave());
$router->post('/admin/departments/delete', fn () => (new AdminController())->departmentDelete());
$router->get('/admin/classes', fn () => (new AdminController())->classes());
$router->post('/admin/classes/save', fn () => (new AdminController())->classSave());
$router->post('/admin/classes/delete', fn () => (new AdminController())->classDelete());
$router->get('/admin/schedules', fn () => (new AdminController())->schedules());
$router->post('/admin/schedules/save', fn () => (new AdminController())->scheduleSave());
$router->post('/admin/schedules/cancel', fn () => (new AdminController())->scheduleCancel());
$router->get('/admin/assignments', fn () => (new AdminController())->assignments());
$router->post('/admin/assignments/offer', fn () => (new AdminController())->assignmentOffer());
$router->post('/admin/assignments/override', fn () => (new AdminController())->assignmentOverride());
$router->get('/admin/rejections', fn () => (new AdminController())->rejections());
$router->post('/admin/rejections/respond', fn () => (new AdminController())->rejectionRespond());
$router->get('/admin/attendance', fn () => (new AdminController())->attendance());
$router->get('/admin/reports', fn () => (new AdminController())->reports());
$router->get('/admin/reports/export', fn () => (new AdminController())->reportsExport());
$router->get('/admin/announcements', fn () => (new AdminController())->announcements());
$router->post('/admin/announcements/create', fn () => (new AdminController())->announcementCreate());
$router->get('/admin/audit-logs', fn () => (new AdminController())->auditLogs());
$router->get('/admin/settings', fn () => (new AdminController())->settings());
$router->post('/admin/import/csv', fn () => (new AdminController())->importCsv());
$router->get('/admin/enrollments', fn () => (new AdminController())->enrollments());
$router->post('/admin/enrollments/save', fn () => (new AdminController())->enrollmentSave());
$router->post('/admin/enrollments/remove', fn () => (new AdminController())->enrollmentRemove());

$router->get('/teacher', fn () => (new TeacherController())->dashboard());
$router->get('/teacher/classes', fn () => (new TeacherController())->browseClasses());
$router->get('/teacher/selections', fn () => (new TeacherController())->selections());
$router->post('/teacher/selections/accept', fn () => (new TeacherController())->selectionAccept());
$router->post('/teacher/selections/reject', fn () => (new TeacherController())->selectionReject());
$router->post('/teacher/selections/withdraw', fn () => (new TeacherController())->selectionWithdraw());
$router->get('/teacher/history', fn () => (new TeacherController())->history());
$router->get('/teacher/check-in', fn () => (new TeacherController())->checkInForm());
$router->post('/teacher/check-in', fn () => (new TeacherController())->checkInSubmit());
$router->get('/teacher/attendance', fn () => (new TeacherController())->attendance());
$router->get('/teacher/profile', fn () => (new TeacherController())->profile());
$router->post('/teacher/profile', fn () => (new TeacherController())->profile());

$router->get('/monitor', fn () => (new MonitorController())->dashboard());
$router->get('/monitor/rejections', fn () => (new MonitorController())->rejectionNotices());
$router->get('/monitor/classes', fn () => (new MonitorController())->classes());
$router->get('/monitor/verify/{scheduleId}', fn (array $p) => (new MonitorController())->verifyForm($p['scheduleId']));
$router->post('/monitor/verify', fn () => (new MonitorController())->verifySubmit());
$router->get('/monitor/reports', fn () => (new MonitorController())->reports());
$router->get('/monitor/profile', fn () => (new MonitorController())->profile());
$router->post('/monitor/profile', fn () => (new MonitorController())->profile());

$router->get('/student', fn () => (new StudentController())->dashboard());
$router->get('/student/class-updates', fn () => (new StudentController())->classUpdates());
$router->get('/student/timetable', fn () => (new StudentController())->timetable());
$router->get('/student/announcements', fn () => (new StudentController())->announcements());
$router->get('/student/profile', fn () => (new StudentController())->profile());
$router->post('/student/profile', fn () => (new StudentController())->profile());
$router->post('/student/teacher-presence', fn () => (new StudentController())->teacherPresenceReport());

try {
    $router->dispatch();
} catch (Throwable $e) {
    Logger::error('Unhandled exception', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    if ((\app_config()['environment'] ?? '') === 'development') {
        throw $e;
    }
    http_response_code(500);
    (new HomeController())->error500('A server error occurred. Please try again later.');
}
