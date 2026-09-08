<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $classes */
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Schedules</h1>
<div class="card shadow-sm mb-4">
    <div class="card-header">Add schedule</div>
    <div class="card-body">
        <form method="post" action="<?= \base_url('admin/schedules/save') ?>" class="row g-2">
            <?= CSRF::field() ?>
            <div class="col-md-3">
                <label class="form-label small text-muted" for="sched-class-id">Class (department from Admin → Classes)</label>
                <select class="form-select" name="class_id" id="sched-class-id" required>
                    <?php if ($classes === []): ?>
                        <option value="">Add a class (with department) in Admin → Classes</option>
                    <?php else: ?>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= \e((string) $c['code']) ?> — <?= \e((string) ($c['name'] ?? '')) ?><?php
                            $dn = (string) ($c['department_name'] ?? '');
                            if ($dn !== '') {
                                echo ' · ' . \e($dn);
                            }
                            ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="day_of_week">
                    <?php foreach ($days as $i => $label): ?>
                        <option value="<?= $i ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input class="form-control" type="time" name="start_time" value="10:00" required></div>
            <div class="col-md-2"><input class="form-control" type="time" name="end_time" value="11:00" required></div>
            <div class="col-md-2"><input class="form-control" name="room" placeholder="Room"></div>
            <div class="col-md-2"><input class="form-control" type="date" name="effective_from" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-2"><input class="form-control" type="date" name="effective_to" placeholder="To (opt)"></div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"<?= $classes === [] ? ' disabled' : '' ?>>Add</button>
                <?php if ($classes === []): ?>
                    <p class="text-warning small mt-2 mb-0">No classes found. Create at least one class and assign a <strong>department</strong> under <a href="<?= \base_url('admin/classes') ?>">Admin → Classes</a> so this schedule is tied to a department and subject.</p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Class</th><th>Day</th><th>Time</th><th>Room</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $s): ?>
            <tr>
                <td><?= \e((string) ($s['class_code'] ?? '')) ?></td>
                <td><?= $days[(int) ($s['day_of_week'] ?? 0)] ?? '' ?></td>
                <td><?= \e(substr((string) ($s['start_time'] ?? ''), 0, 5)) ?>–<?= \e(substr((string) ($s['end_time'] ?? ''), 0, 5)) ?></td>
                <td><?= \e((string) ($s['room'] ?? '')) ?></td>
                <td>
                    <form method="post" action="<?= \base_url('admin/schedules/cancel') ?>" class="d-inline" onsubmit="return confirm('Cancel this schedule slot?');">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <input type="hidden" name="reason" value="Cancelled by admin">
                        <button type="submit" class="btn btn-sm btn-outline-warning">Cancel</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
