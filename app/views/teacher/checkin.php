<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $today */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Check-in (PIN)</h1>
<p class="text-muted">Set your PIN under Profile first. Use your PIN to confirm presence for today’s sessions.</p>
<?php foreach ($today as $t): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6"><?= \e((string) ($t['class_code'] ?? '')) ?> · <?= \e(substr((string) ($t['start_time'] ?? ''), 0, 5)) ?></h2>
            <form method="post" action="<?= \base_url('teacher/check-in') ?>" class="row g-2 align-items-end">
                <?= CSRF::field() ?>
                <input type="hidden" name="schedule_id" value="<?= (int) ($t['schedule_id'] ?? 0) ?>">
                <div class="col-md-3"><input type="password" class="form-control" name="pin" placeholder="PIN" required autocomplete="one-time-code"></div>
                <div class="col-md-2"><button class="btn btn-primary" type="submit">Check in</button></div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php if ($today === []): ?><p class="text-muted">No classes scheduled for you today.</p><?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
