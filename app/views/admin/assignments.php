<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $schedules */
/** @var list<array<string,mixed>> $teachers */
/** @var list<array<string,mixed>> $monitors */
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Class assignments</h1>
<div class="card shadow-sm mb-4">
    <div class="card-header">Offer class to teacher</div>
    <div class="card-body">
        <form method="post" action="<?= \base_url('admin/assignments/offer') ?>" class="row g-2">
            <?= CSRF::field() ?>
            <div class="col-md-4">
                <select class="form-select" name="schedule_id" required>
                    <?php foreach ($schedules as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= \e((string) $s['class_code']) ?> — <?= $days[(int) $s['day_of_week']] ?> <?= \e(substr((string) $s['start_time'], 0, 5)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="teacher_id" required>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= \e((string) $t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="monitor_id">
                    <option value="0">— Monitor —</option>
                    <?php foreach ($monitors as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"><?= \e((string) $m['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Offer</button></div>
        </form>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead><tr><th>Class</th><th>When</th><th>Teacher</th><th>Monitor</th><th>Status</th><th>Override</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): ?>
            <tr>
                <td><?= \e((string) ($a['class_code'] ?? '')) ?></td>
                <td><?= $days[(int) ($a['day_of_week'] ?? 0)] ?? '' ?> <?= \e(substr((string) ($a['start_time'] ?? ''), 0, 5)) ?></td>
                <td><?= \e((string) ($a['teacher_name'] ?? '')) ?></td>
                <td><?= \e((string) ($a['monitor_name'] ?? '—')) ?></td>
                <td><span class="badge text-bg-secondary"><?= \e((string) ($a['status'] ?? '')) ?></span></td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#ov<?= (int) $a['id'] ?>">Override</button>
                    <div class="collapse mt-2" id="ov<?= (int) $a['id'] ?>">
                        <form method="post" action="<?= \base_url('admin/assignments/override') ?>" class="row g-1">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <div class="col-12">
                                <select class="form-select form-select-sm" name="new_teacher_id">
                                    <option value="0">Keep teacher</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?= (int) $t['id'] ?>"><?= \e((string) $t['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <select class="form-select form-select-sm" name="monitor_id">
                                    <option value="0">— Monitor —</option>
                                    <?php foreach ($monitors as $m): ?>
                                        <option value="<?= (int) $m['id'] ?>"><?= \e((string) $m['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12"><input class="form-control form-control-sm" name="note" placeholder="Note" required></div>
                            <div class="col-12"><button class="btn btn-sm btn-warning" type="submit">Apply</button></div>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
