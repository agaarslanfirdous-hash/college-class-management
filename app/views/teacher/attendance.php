<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">My attendance records</h1>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Date</th><th>Class</th><th>Status</th><th>Check-in</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) ($r['record_date'] ?? '')) ?></td>
                <td><?= \e((string) ($r['class_code'] ?? '')) ?></td>
                <td><?= \e((string) ($r['status'] ?? '')) ?></td>
                <td><?= \e((string) ($r['check_in_time'] ?? '—')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
