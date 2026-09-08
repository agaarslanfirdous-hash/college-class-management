<?php
declare(strict_types=1);
use App\Core\CSRF;
require __DIR__ . '/../partials/header.php';
?>
<div class="row justify-content-center px-2 px-sm-0">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <h1 class="h3 mb-4">Sign in</h1>
        <form method="post" action="<?= \base_url('login') ?>" class="card shadow-sm" id="form-login">
            <div class="card-body">
                <?= CSRF::field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" name="email" id="email" required autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" name="password" id="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
                <p class="mt-3 mb-0 small"><a href="<?= \base_url('forgot-password') ?>">Forgot password?</a></p>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
