<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $classes */
/** @var array<string, list<array<string,mixed>>> $timetable */
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Timetable</h1>
<?php foreach ($classes as $c): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header"><?= \e((string) $c['code']) ?> — <?= \e((string) $c['name']) ?></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Day</th><th>Time</th><th>Room</th></tr></thead>
                <tbody>
                <?php
                $code = (string) $c['code'];
                $slots = $timetable[$code] ?? [];
                foreach ($slots as $s):
                ?>
                    <tr>
                        <td><?= $days[(int) ($s['day_of_week'] ?? 0)] ?? '' ?></td>
                        <td><?= \e(substr((string) ($s['start_time'] ?? ''), 0, 5)) ?>–<?= \e(substr((string) ($s['end_time'] ?? ''), 0, 5)) ?></td>
                        <td><?= \e((string) ($s['room'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<?php if ($classes === []): ?><p class="text-muted">You are not enrolled in any classes yet.</p><?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
