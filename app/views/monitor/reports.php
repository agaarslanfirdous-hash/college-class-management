<?php
declare(strict_types=1);
/** @var string $month */
/** @var array<string,int> $summary */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Reports</h1>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto"><input type="month" class="form-control" name="month" value="<?= \e($month) ?>"></div>
    <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Go</button></div>
</form>
<ul>
    <li>Present: <?= (int) ($summary['present_cnt'] ?? 0) ?></li>
    <li>Absent: <?= (int) ($summary['absent_cnt'] ?? 0) ?></li>
    <li>Late: <?= (int) ($summary['late_cnt'] ?? 0) ?></li>
    <li>On leave: <?= (int) ($summary['leave_cnt'] ?? 0) ?></li>
</ul>
<?php require __DIR__ . '/../partials/footer.php'; ?>
