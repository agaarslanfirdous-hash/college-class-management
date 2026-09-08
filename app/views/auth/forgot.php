<?php
declare(strict_types=1);
use App\Core\CSRF;
require __DIR__ . '/../partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h3 mb-4">Forgot password</h1>
        <form method="post" action="<?= \base_url('forgot-password') ?>">
            <?= CSRF::field() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" name="email" id="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Send reset link</button>
            <a class="btn btn-link" href="<?= \base_url('login') ?>">Back</a>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
