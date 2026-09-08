<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $users */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Users</h1>
<div class="card shadow-sm mb-4">
    <div class="card-header">Create user</div>
    <div class="card-body">
        <form method="post" action="<?= \base_url('admin/users/create') ?>" class="row g-2">
            <?= CSRF::field() ?>
            <div class="col-md-3"><input class="form-control" name="email" type="email" placeholder="Email" required></div>
            <div class="col-md-2"><input class="form-control" name="full_name" placeholder="Full name" required></div>
            <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
            <div class="col-md-2">
                <select class="form-select" name="role">
                    <option value="student">student</option>
                    <option value="teacher">teacher</option>
                    <option value="monitor">monitor</option>
                    <option value="admin">admin</option>
                </select>
            </div>
            <div class="col-md-2"><input class="form-control" name="password" type="password" placeholder="Password" required minlength="10"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
        </form>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= \e((string) $u['full_name']) ?></td>
                <td><?= \e((string) $u['email']) ?></td>
                <td><?= \e((string) $u['role']) ?></td>
                <td><?= !empty($u['is_active']) ? 'Yes' : 'No' ?></td>
                <td>
                    <form method="post" action="<?= \base_url('admin/users/toggle') ?>" class="d-inline">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= !empty($u['is_active']) ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
