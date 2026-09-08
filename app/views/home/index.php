<?php
declare(strict_types=1);
require __DIR__ . '/../partials/header.php';
?>
<div class="row align-items-center py-5">
    <div class="col-lg-7">
        <h1 class="display-5 fw-bold">Class management for your college</h1>
        <p class="lead text-muted">Teacher class selection, attendance monitoring, and role-based dashboards — secure and simple.</p>
        <a class="btn btn-primary btn-lg me-2" href="<?= \base_url('login') ?>">Sign in</a>
        <a class="btn btn-outline-secondary btn-lg" href="<?= \base_url('about') ?>">Learn more</a>
    </div>
    <div class="col-lg-5 mt-4 mt-lg-0">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-4">
                <h2 class="h5">Roles</h2>
                <ul class="mb-0">
                    <li><strong>Administrator</strong> — schedules, assignments, compliance</li>
                    <li><strong>Teacher</strong> — select classes, check-in</li>
                    <li><strong>Class monitor</strong> — verify attendance live</li>
                    <li><strong>Student</strong> — read-only timetable &amp; status</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
