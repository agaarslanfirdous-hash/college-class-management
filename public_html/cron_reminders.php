<?php
/**
 * Scheduled reminders: ~1 hour before class start (in-app notification).
 * cPanel cron (hourly): php /path/to/public_html/cron_reminders.php YOUR_CRON_KEY
 * Or HTTPS: .../cron_reminders.php?key=YOUR_CRON_KEY
 *
 * @package CollegeCMS
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$key = $argv[1] ?? ($_GET['key'] ?? '');
$expected = (string) (\app_config()['cron_key'] ?? '');
if ($expected === '' || !hash_equals($expected, (string) $key)) {
    http_response_code(403);
    echo 'Forbidden';
    exit(1);
}

use App\Models\ClassAssignment;
use App\Models\Notification;
use App\Models\Schedule;

$dow = (int) date('w');
$rows = (new Schedule())->forDayOfWeek($dow);
$ca = new ClassAssignment();
$all = $ca->allForAdmin();
$bySchedule = [];
foreach ($all as $a) {
    if (!in_array((string) $a['status'], ['accepted', 'overridden'], true)) {
        continue;
    }
    $sid = (int) $a['schedule_id'];
    $bySchedule[$sid] = $a;
}

$now = time();
$sent = 0;
foreach ($rows as $s) {
    if (!isset($bySchedule[(int) $s['id']])) {
        continue;
    }
    $assign = $bySchedule[(int) $s['id']];
    $start = strtotime(date('Y-m-d') . ' ' . (string) $s['start_time']);
    if ($start === false) {
        continue;
    }
    $delta = $start - $now;
    if ($delta < 3000 || $delta > 4200) {
        continue;
    }
    $tid = (int) $assign['teacher_id'];
    $msg = 'Reminder: class ' . (string) $s['class_code'] . ' starts in about one hour.';
    (new Notification())->create($tid, 'reminder_1h', 'Upcoming class', $msg);
    $sent++;
}

header('Content-Type: text/plain; charset=utf-8');
echo 'OK reminders=' . $sent;
