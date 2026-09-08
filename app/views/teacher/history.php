<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Selection history</h1>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Class</th><th>Status</th><th>Decided</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) ($r['class_code'] ?? '')) ?></td>
                <td><span class="badge bg-secondary"><?= \e((string) ($r['status'] ?? '')) ?></span></td>
                <td><?= \e((string) ($r['decided_at'] ?? '—')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
