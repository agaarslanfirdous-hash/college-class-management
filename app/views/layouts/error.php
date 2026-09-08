<?php
/**
 * Simple error page (403/404/500).
 *
 * @package CollegeCMS\Views
 */
declare(strict_types=1);
$title = $title ?? 'Error';
$message = $message ?? 'Something went wrong.';
require __DIR__ . '/../partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="h3"><?= \e((string) $title) ?></h1>
        <p class="lead"><?= \e((string) $message) ?></p>
        <a class="btn btn-primary" href="<?= \base_url('') ?>">Home</a>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
