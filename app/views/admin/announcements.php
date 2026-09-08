<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Announcements</h1>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="<?= \base_url('admin/announcements/create') ?>">
            <?= CSRF::field() ?>
            <div class="mb-2"><input class="form-control" name="title" placeholder="Title" required></div>
            <div class="mb-2"><textarea class="form-control" name="body" rows="3" placeholder="Body" required></textarea></div>
            <div class="mb-2">
                <select class="form-select" name="target_role">
                    <option value="all">All roles</option>
                    <option value="student">Students</option>
                    <option value="teacher">Teachers</option>
                    <option value="monitor">Monitors</option>
                    <option value="admin">Admins</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Publish</button>
        </form>
    </div>
</div>
<ul class="list-group">
    <?php foreach ($rows as $a): ?>
        <li class="list-group-item">
            <strong><?= \e((string) $a['title']) ?></strong>
            <span class="badge bg-secondary ms-2"><?= \e((string) $a['target_role']) ?></span>
            <?php if (empty($a['is_published'])): ?><span class="badge bg-warning text-dark">Draft</span><?php endif; ?>
            <div class="small text-muted"><?= \e((string) $a['author_name']) ?> — <?= \e((string) $a['created_at']) ?></div>
            <p class="mb-0 mt-1"><?= nl2br(\e((string) $a['body'])) ?></p>
        </li>
    <?php endforeach; ?>
</ul>
<?php require __DIR__ . '/../partials/footer.php'; ?>
