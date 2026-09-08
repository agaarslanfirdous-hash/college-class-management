<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var array<string,mixed>|false $user */
require __DIR__ . '/../partials/header.php';
if ($user === false) { echo '<p>User not found.</p>'; require __DIR__ . '/../partials/footer.php'; return; }
?>
<h1 class="h3 mb-4">Profile</h1>
<form method="post" action="<?= \base_url('student/profile') ?>">
    <?= CSRF::field() ?>
    <div class="mb-2"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?= \e((string) $user['full_name']) ?>" required></div>
    <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= \e((string) ($user['phone'] ?? '')) ?>"></div>
    <div class="mb-2"><label class="form-label">New password (optional)</label><input class="form-control" type="password" name="new_password" minlength="10" autocomplete="new-password"></div>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
<p class="small text-muted mt-3">Email: <?= \e((string) $user['email']) ?> (contact admin to change)</p>
<?php require __DIR__ . '/../partials/footer.php'; ?>
