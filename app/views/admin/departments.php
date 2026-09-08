<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
/** @var int $edit_id */
$edit_id = (int) ($edit_id ?? 0);
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Departments</h1>
<p class="text-muted small">New departments automatically get a <strong>timetable program board</strong> (copy of the first board) so they appear under <a href="<?= \base_url('timetable-board') ?>">Timetable</a>.</p>
<div class="card shadow-sm mb-4">
    <div class="card-header">Add department</div>
    <div class="card-body">
        <form method="post" action="<?= \base_url('admin/departments/save') ?>" class="row g-2">
            <?= CSRF::field() ?>
            <input type="hidden" name="id" value="0">
            <div class="col-md-4"><input class="form-control" name="name" placeholder="Name" required></div>
            <div class="col-md-3"><input class="form-control" name="code" placeholder="Code" required></div>
            <div class="col-md-2"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Code</th>
                <th scope="col" class="text-end" style="width:14rem">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r) :
            $rid = (int) $r['id'];
            $isEdit = $edit_id === $rid;
            ?>
            <tr>
                <td>
                    <?php if ($isEdit) : ?>
                        <form id="dept-form-<?= $rid ?>" method="post" action="<?= \base_url('admin/departments/save') ?>" class="d-flex flex-wrap gap-2 align-items-center">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="id" value="<?= $rid ?>">
                            <input class="form-control" name="name" value="<?= \e((string) $r['name']) ?>" required>
                        </form>
                    <?php else : ?>
                        <?= \e((string) $r['name']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isEdit) : ?>
                        <input class="form-control" name="code" value="<?= \e((string) $r['code']) ?>" form="dept-form-<?= $rid ?>" required>
                    <?php else : ?>
                        <code><?= \e((string) $r['code']) ?></code>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ($isEdit) : ?>
                        <button class="btn btn-sm btn-primary" type="submit" form="dept-form-<?= $rid ?>">Save</button>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= \base_url('admin/departments') ?>">Cancel</a>
                    <?php else : ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= \base_url('admin/departments?edit=' . $rid) ?>">Edit</a>
                    <?php endif; ?>
                    <form method="post" action="<?= \base_url('admin/departments/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this department?');">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= $rid ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
