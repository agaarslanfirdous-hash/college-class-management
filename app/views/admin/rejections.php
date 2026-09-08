<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $class_rejection_log */
/** @var list<array<string,mixed>> $timetable_declines */
$class_rejection_log = $class_rejection_log ?? [];
$timetable_declines = $timetable_declines ?? [];
$days = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Rejections &amp; explanations</h1>
<p class="text-muted">Pending class-offer explanations need an administrator reply. Below you also have a <strong>full log of class offer rejections</strong> and <strong>timetable slot declines</strong> (unavailable) from the program board.</p>

<h2 class="h5 mt-4">1. Pending: class offer — your response</h2>
<?php if ($rows === []): ?>
    <p class="text-muted">No pending class-offer rejections that need a reply.</p>
<?php endif; ?>
<?php foreach ($rows as $r): ?>
    <div class="card shadow-sm mb-3 border-warning">
        <div class="card-body">
            <p class="mb-1"><strong><?= \e((string) ($r['class_code'] ?? '')) ?></strong> — <?= \e((string) ($r['teacher_name'] ?? '')) ?></p>
            <p class="small text-muted mb-2"><?= \e((string) ($r['reason_type'] ?? '')) ?></p>
            <p><?= nl2br(\e((string) ($r['reason_text'] ?? ''))) ?></p>
            <form method="post" action="<?= \base_url('admin/rejections/respond') ?>" class="mt-2">
                <?= CSRF::field() ?>
                <input type="hidden" name="explanation_id" value="<?= (int) ($r['explanation_id'] ?? 0) ?>">
                <label class="form-label">Administrator response</label>
                <textarea class="form-control" name="admin_response" rows="2" required></textarea>
                <button class="btn btn-primary mt-2" type="submit">Submit response</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<h2 class="h5 mt-4">2. Timetable (program board) — slot declines &amp; reasons</h2>
<?php if ($timetable_declines === []): ?>
    <p class="text-muted">No declined timetable slots, or the database view is not available on this instance.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>When (responded)</th>
                    <th>Program / dept</th>
                    <th>Day · period</th>
                    <th>Cell / course</th>
                    <th>Teacher</th>
                    <th>Reason</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($timetable_declines as $d) : ?>
                <tr>
                    <td class="text-nowrap"><?= \e((string) ($d['responded_at'] ?? '—')) ?></td>
                    <td><?= \e(trim((string) ($d['program_title'] ?? '') . ' / ' . (string) ($d['department_name'] ?? ''))) ?></td>
                    <td><?= \e(($days[(int) ($d['day_of_week'] ?? 0)] ?? '—') . ' · P' . (int) ($d['period_index'] ?? 0)) ?></td>
                    <td><code><?= \e((string) ($d['course_code'] ?? '—')) ?></code></td>
                    <td><?= \e((string) ($d['teacher_name'] ?? '')) ?></td>
                    <td><?= \e((string) ($d['decline_reason'] ?? '—')) ?></td>
                    <td class="small"><?= \e((string) ($d['decline_note'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2 class="h5 mt-4">3. Class offer rejections (full log, including replied)</h2>
<?php if ($class_rejection_log === []): ?>
    <p class="text-muted">No class offer rejections recorded yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Class</th>
                    <th>Teacher</th>
                    <th>Day</th>
                    <th>Type</th>
                    <th>Explanation</th>
                    <th>Admin reply</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($class_rejection_log as $c) : ?>
                <tr>
                    <td class="text-nowrap small"><?= \e((string) ($c['created_at'] ?? '')) ?></td>
                    <td><?= \e((string) ($c['class_code'] ?? '')) ?></td>
                    <td><?= \e((string) ($c['teacher_name'] ?? '')) ?></td>
                    <td><?= \e($days[(int) ($c['day_of_week'] ?? 0)] ?? '—') ?></td>
                    <td class="small"><?= \e((string) ($c['reason_type'] ?? '')) ?></td>
                    <td class="small"><?= nl2br(\e((string) ($c['reason_text'] ?? ''))) ?></td>
                    <td class="small text-muted"><?= (trim((string) ($c['admin_response'] ?? '')) !== '') ? nl2br(\e((string) $c['admin_response'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
