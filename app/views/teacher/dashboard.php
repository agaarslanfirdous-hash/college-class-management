<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $offered */
/** @var list<array<string,mixed>> $mine */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Teacher dashboard</h1>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">Pending offers</div>
            <div class="card-body">
                <?php if ($offered === []): ?><p class="text-muted mb-0">No pending offers.</p><?php endif; ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($offered as $o): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= \e((string) ($o['class_code'] ?? '')) ?> — <?= \e((string) ($o['subject'] ?? '')) ?></span>
                            <a class="btn btn-sm btn-primary" href="<?= \base_url('teacher/selections') ?>">Review</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">My assignments</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($mine as $m): ?>
                        <?php
                        $stM = (string) ($m['status'] ?? '');
                        if (!in_array($stM, ['accepted', 'overridden'], true)) { continue; }
                        ?>
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span><?= \e((string) ($m['class_code'] ?? '')) ?> — <span class="text-muted small"><?= \e((string) ($m['subject'] ?? '')) ?></span> <span class="badge bg-secondary"><?= \e($stM) ?></span></span>
                            <form method="post" action="<?= \base_url('teacher/selections/withdraw') ?>" class="d-inline" onsubmit="return confirm('Withdraw from this class assignment? Admin will need to cover the slot.');">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="assignment_id" value="<?= (int) $m['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete / withdraw</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
