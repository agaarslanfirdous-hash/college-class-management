<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $timetable_declines */
$rows = $rows ?? [];
$timetable_declines = $timetable_declines ?? [];
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Rejections &amp; explanations</h1>
<p class="text-muted small">You are the <strong>class monitor</strong> for the classes below. This matches the administration <strong>Rejections &amp; explanations</strong> page, scoped to you: (1) <strong>class offers</strong> a teacher declined for your class, and (2) <strong>program-timetable</strong> slot unavailability in your departments.</p>
<h2 class="h5 mt-3">1. Class offer — teacher explanation</h2>
<?php if ($rows === []): ?>
    <p class="text-muted">No class-offer rejections for your monitored classes.</p>
<?php else: ?>
    <?php foreach ($rows as $r) : ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <p class="mb-1"><strong><?= \e((string) ($r['class_code'] ?? '')) ?></strong> — <?= \e((string) ($r['class_name'] ?? '')) ?></p>
                <p class="small text-muted mb-1"><?= \e((string) ($r['teacher_name'] ?? '')) ?> · <?= \e((string) ($r['reason_type'] ?? '')) ?> · <?= \e((string) ($r['created_at'] ?? '')) ?></p>
                <p class="mb-2"><?= nl2br(\e((string) ($r['reason_text'] ?? ''))) ?></p>
                <?php if (trim((string) ($r['admin_response'] ?? '')) !== '') : ?>
                    <p class="border-start border-3 border-primary ps-2 mb-0 small"><strong>Administration:</strong> <?= nl2br(\e((string) $r['admin_response'])) ?></p>
                <?php else : ?>
                    <p class="text-muted small mb-0">No administrator response yet.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2 class="h5 mt-4">2. Program timetable — slot unavailability (your departments)</h2>
<?php if ($timetable_declines === []): ?>
    <p class="text-muted">No timetable slot declines in your departments, or the program board is not in use.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Program / department</th>
                    <th>Day · P</th>
                    <th>Cell code</th>
                    <th>Teacher</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($timetable_declines as $d) : ?>
                <tr>
                    <td class="text-nowrap small"><?= \e((string) ($d['responded_at'] ?? '—')) ?></td>
                    <td class="small"><?= \e(trim((string) ($d['program_title'] ?? '') . ' / ' . (string) ($d['department_name'] ?? ''))) ?></td>
                    <td class="text-nowrap"><?= \e(($days[(int) ($d['day_of_week'] ?? 0)] ?? '—') . ' · P' . (int) ($d['period_index'] ?? 0)) ?></td>
                    <td><code><?= \e((string) ($d['course_code'] ?? '—')) ?></code></td>
                    <td><?= \e((string) ($d['teacher_name'] ?? '')) ?></td>
                    <td class="small"><?= \e((string) ($d['decline_reason'] ?? '—')) ?><?php if (trim((string) ($d['decline_note'] ?? '')) !== ''): ?><br><span class="text-muted"><?= \e((string) $d['decline_note']) ?></span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
