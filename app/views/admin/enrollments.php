<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $students */
/** @var list<array<string,mixed>> $classes */
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Enrollments</h1>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="<?= \base_url('admin/enrollments/save') ?>" class="row g-2">
            <?= CSRF::field() ?>
            <div class="col-md-5">
                <select class="form-select" name="student_id" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= \e((string) $s['full_name']) ?> (<?= \e((string) $s['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <select class="form-select" name="class_id" required>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= \e((string) $c['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Enroll</button></div>
        </form>
    </div>
</div>
<p class="text-muted small">To remove an enrollment, use the student/class pair below (add a simple list in production).</p>
<?php require __DIR__ . '/../partials/footer.php'; ?>
