<?php
declare(strict_types=1);
use App\Core\CSRF;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Settings &amp; import</h1>
<p class="text-muted">CSV imports for bulk setup. First row may be headers (name,code for departments; code,name,subject,department_code,capacity,grade_level for classes).</p>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">Import departments</div>
            <div class="card-body">
                <form method="post" action="<?= \base_url('admin/import/csv') ?>" enctype="multipart/form-data">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="type" value="departments">
                    <input type="file" name="csv" class="form-control mb-2" accept=".csv" required>
                    <button class="btn btn-primary" type="submit">Upload</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">Import classes</div>
            <div class="card-body">
                <form method="post" action="<?= \base_url('admin/import/csv') ?>" enctype="multipart/form-data">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="type" value="classes">
                    <input type="file" name="csv" class="form-control mb-2" accept=".csv" required>
                    <button class="btn btn-primary" type="submit">Upload</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">Import enrollments</div>
            <div class="card-body">
                <form method="post" action="<?= \base_url('admin/import/csv') ?>" enctype="multipart/form-data">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="type" value="enrollments">
                    <input type="file" name="csv" class="form-control mb-2" accept=".csv" required>
                    <button class="btn btn-primary" type="submit">Upload</button>
                </form>
                <p class="small text-muted mb-0">Columns: student_email, class_code</p>
            </div>
        </div>
    </div>
</div>
<hr class="my-4">
<h2 class="h5">Cron (cPanel)</h2>
<p class="small">Schedule hourly: <code>php <?= \e(PUBLIC_PATH) ?>/cron_reminders.php <?= \e((string) (\app_config()['cron_key'] ?? 'CHANGE_ME')) ?></code></p>
<p class="small text-muted">Set <code>cron_key</code> in <code>app/config/config.php</code> and pass as argv[1] or <code>?key=</code> for web cron.</p>
<?php require __DIR__ . '/../partials/footer.php'; ?>
