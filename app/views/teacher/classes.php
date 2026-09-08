<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $depts */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Browse classes</h1>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-3">
        <select class="form-select" name="department_id" onchange="this.form.submit()">
            <option value="">All departments</option>
            <?php foreach ($depts as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (isset($_GET['department_id']) && (int) $_GET['department_id'] === (int) $d['id']) ? 'selected' : '' ?>><?= \e((string) $d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4"><input class="form-control" name="q" placeholder="Search" value="<?= \e((string) ($_GET['q'] ?? '')) ?>"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary" type="submit">Filter</button></div>
</form>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Code</th><th>Name</th><th>Subject</th><th>Dept</th><th>Capacity</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) $r['code']) ?></td>
                <td><?= \e((string) $r['name']) ?></td>
                <td><?= \e((string) $r['subject']) ?></td>
                <td><?= \e((string) ($r['department_name'] ?? '')) ?></td>
                <td><?= (int) ($r['capacity'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
