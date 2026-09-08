<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var array<string,mixed> $assignment */
/** @var array<string,mixed> $record */
/** @var string $date */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Verify attendance</h1>
<p><?= \e((string) ($assignment['class_code'] ?? '')) ?> · <?= \e((string) ($assignment['teacher_name'] ?? '')) ?> · <?= \e($date) ?></p>
<p class="small text-muted">Room: <?= \e((string) ($assignment['room'] ?? '—')) ?></p>
<div class="card shadow-sm">
    <div class="card-body">
        <p>Current record status: <strong><?= \e((string) ($record['status'] ?? 'pending')) ?></strong>
            <?php if (!empty($record['check_in_time'])): ?> · Check-in: <?= \e((string) $record['check_in_time']) ?><?php endif; ?></p>
        <form method="post" action="<?= \base_url('monitor/verify') ?>">
            <?= CSRF::field() ?>
            <input type="hidden" name="schedule_id" value="<?= (int) ($assignment['schedule_id'] ?? 0) ?>">
            <input type="hidden" name="teacher_id" value="<?= (int) ($assignment['teacher_id'] ?? 0) ?>">
            <input type="hidden" name="date" value="<?= \e($date) ?>">
            <div class="mb-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="on_leave">On leave</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="escalated" value="1" id="esc">
                <label class="form-check-label" for="esc">Escalate to administrator</label>
            </div>
            <button class="btn btn-primary" type="submit">Save verification</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
