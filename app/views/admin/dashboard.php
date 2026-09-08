<?php
declare(strict_types=1);
/** @var array<string,int> $counts */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Administrator dashboard</h1>
<div class="row g-3">
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><h2 class="h6 text-muted">Users</h2><p class="display-6 mb-0"><?= (int) ($counts['users'] ?? 0) ?></p></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><h2 class="h6 text-muted">Teachers</h2><p class="display-6 mb-0"><?= (int) ($counts['teachers'] ?? 0) ?></p></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm h-100"><div class="card-body"><h2 class="h6 text-muted">Classes</h2><p class="display-6 mb-0"><?= (int) ($counts['classes'] ?? 0) ?></p></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm h-100 border-warning"><div class="card-body"><h2 class="h6 text-muted">Pending rejections</h2><p class="display-6 mb-0"><?= (int) ($counts['pending_rejections'] ?? 0) ?></p><a href="<?= \base_url('admin/rejections') ?>" class="small">Review</a></div></div></div>
</div>
<p class="mt-4"><a class="btn btn-outline-primary" href="<?= \base_url('api/live-status') ?>" target="_blank" rel="noopener">Live status JSON</a> (for debugging)</p>
<?php require __DIR__ . '/../partials/footer.php'; ?>
