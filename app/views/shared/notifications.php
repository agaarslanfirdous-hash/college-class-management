<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Notifications</h1>
<form method="post" action="<?= \base_url('notifications/read-all') ?>" class="mb-3">
    <?= CSRF::field() ?>
    <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all read</button>
</form>
<ul class="list-group">
    <?php foreach ($rows as $n): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start">
            <div>
                <strong><?= \e((string) $n['title']) ?></strong>
                <span class="badge bg-secondary ms-1"><?= \e((string) $n['type']) ?></span>
                <?php if (empty($n['is_read'])): ?><span class="badge text-bg-warning">New</span><?php endif; ?>
                <div class="small text-muted"><?= \e((string) $n['created_at']) ?></div>
                <p class="mb-0 mt-1"><?= nl2br(\e((string) $n['message'])) ?></p>
            </div>
            <?php if (empty($n['is_read'])): ?>
                <form method="post" action="<?= \base_url('notifications/read') ?>">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php if ($rows === []): ?><p class="text-muted">No notifications.</p><?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
