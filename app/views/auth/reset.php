<?php
declare(strict_types=1);
use App\Core\CSRF;
require __DIR__ . '/../partials/header.php';
$token = $token ?? '';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h3 mb-4">New password</h1>
        <form method="post" action="<?= \base_url('reset-password') ?>">
            <?= CSRF::field() ?>
            <input type="hidden" name="token" value="<?= \e((string) $token) ?>">
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" name="password" id="password" required minlength="10" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirm">Confirm</label>
                <input class="form-control" type="password" name="password_confirm" id="password_confirm" required minlength="10" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Update password</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
