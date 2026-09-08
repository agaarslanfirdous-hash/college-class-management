<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Class selections</h1>
<?php foreach ($rows as $r): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5"><?= \e((string) ($r['class_code'] ?? '')) ?> — <?= \e((string) ($r['subject'] ?? '')) ?></h2>
            <p class="small text-muted mb-2"><?= \e((string) ($r['dept_name'] ?? '')) ?> · <?= \e((string) ($r['grade_level'] ?? '')) ?></p>
            <form method="post" action="<?= \base_url('teacher/selections/accept') ?>" class="d-inline" onsubmit="return confirm('Accept this class? You are agreeing to take this teaching assignment on the schedule offered.');">
                <?= CSRF::field() ?>
                <input type="hidden" name="assignment_id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="btn btn-success">Accept (confirm)</button>
            </form>
            <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rej<?= (int) $r['id'] ?>">Reject</button>
            <div class="collapse mt-3" id="rej<?= (int) $r['id'] ?>">
                <form method="post" action="<?= \base_url('teacher/selections/reject') ?>">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="assignment_id" value="<?= (int) $r['id'] ?>">
                    <select class="form-select mb-2" name="reason_type">
                        <option value="schedule_conflicts">Schedule conflict</option>
                        <option value="workload">Workload</option>
                        <option value="health">Health</option>
                        <option value="personal">Personal</option>
                        <option value="other">Other</option>
                    </select>
                    <textarea class="form-control mb-2" name="reason_text" rows="2" placeholder="Explanation (required)" required></textarea>
                    <button type="submit" class="btn btn-danger">Submit rejection</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if ($rows === []): ?><p class="text-muted">No offers right now.</p><?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
