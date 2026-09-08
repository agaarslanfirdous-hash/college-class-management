<?php
declare(strict_types=1);
/** @var string $month */
/** @var array<string,int> $summary */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Reports</h1>
<form method="get" class="row g-2 mb-3 no-print">
    <div class="col-auto"><input type="month" class="form-control" name="month" value="<?= \e($month) ?>"></div>
    <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Go</button></div>
    <div class="col-auto"><a class="btn btn-outline-secondary" href="<?= \base_url('admin/reports/export?month=' . urlencode($month)) ?>">Export CSV</a></div>
    <div class="col-auto"><button type="button" class="btn btn-outline-dark" onclick="window.print()">Print</button></div>
</form>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5">Attendance summary (<?= \e($month) ?>)</h2>
        <ul class="mb-0">
            <li>Present: <?= (int) ($summary['present_cnt'] ?? 0) ?></li>
            <li>Absent: <?= (int) ($summary['absent_cnt'] ?? 0) ?></li>
            <li>Late: <?= (int) ($summary['late_cnt'] ?? 0) ?></li>
            <li>On leave: <?= (int) ($summary['leave_cnt'] ?? 0) ?></li>
            <li>Total records: <?= (int) ($summary['total'] ?? 0) ?></li>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
