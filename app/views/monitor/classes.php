<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">My assigned classes</h1>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Class</th><th>Day</th><th>Time</th><th>Teacher</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) ($r['class_code'] ?? '')) ?></td>
                <td><?= $days[(int) ($r['day_of_week'] ?? 0)] ?? '' ?></td>
                <td><?= \e(substr((string) ($r['start_time'] ?? ''), 0, 5)) ?></td>
                <td><?= \e((string) ($r['teacher_name'] ?? '')) ?></td>
                <td><a class="btn btn-sm btn-primary" href="<?= \base_url('monitor/verify/' . (int) ($r['schedule_id'] ?? 0)) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
