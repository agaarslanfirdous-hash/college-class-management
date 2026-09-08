<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Announcements</h1>
<?php foreach ($rows as $a): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5"><?= \e((string) $a['title']) ?></h2>
            <div class="small text-muted"><?= \e((string) $a['created_at']) ?> · <?= \e((string) $a['author_name']) ?></div>
            <p class="mb-0 mt-2"><?= nl2br(\e((string) $a['body'])) ?></p>
        </div>
    </div>
<?php endforeach; ?>
<?php if ($rows === []): ?><p class="text-muted">No announcements.</p><?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
