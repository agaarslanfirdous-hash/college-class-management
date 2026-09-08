<?php
declare(strict_types=1);
/** @var string $date */
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Attendance</h1>
<form method="get" class="row g-2 mb-3 no-print">
    <div class="col-auto"><input type="date" class="form-control" name="date" value="<?= \e($date) ?>"></div>
    <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Go</button></div>
</form>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Class</th><th>Teacher</th><th>Status</th><th>Check-in</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) ($r['class_code'] ?? '')) ?></td>
                <td><?= \e((string) ($r['teacher_name'] ?? '')) ?></td>
                <td><span class="badge status-<?= \e((string) ($r['status'] ?? '')) ?>"><?= \e((string) ($r['status'] ?? '')) ?></span></td>
                <td><?= \e((string) ($r['check_in_time'] ?? '—')) ?></td>
                <td><?= \e((string) ($r['notes'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
