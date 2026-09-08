<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Audit log</h1>
<div class="table-responsive" style="max-height:70vh;overflow:auto;">
    <table class="table table-sm">
        <thead class="sticky-top bg-body"><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) ($r['created_at'] ?? '')) ?></td>
                <td><?= \e((string) ($r['user_email'] ?? '—')) ?></td>
                <td><?= \e((string) ($r['action'] ?? '')) ?></td>
                <td><?= \e((string) ($r['entity'] ?? '')) ?> #<?= (int) ($r['entity_id'] ?? 0) ?></td>
                <td><?= \e((string) ($r['ip'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
