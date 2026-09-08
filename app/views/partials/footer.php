<?php
/**
 * Closing container, footer, scripts.
 *
 * @package CollegeCMS\Views
 */
declare(strict_types=1);
?>
</div>
</main>
<footer class="border-top py-3 mt-auto bg-body-tertiary">
    <div class="container small text-muted d-flex flex-wrap justify-content-between gap-2">
        <span>&copy; <?= date('Y') ?> <?= \e((string) (\app_config()['app_name'] ?? 'College')) ?></span>
        <span><a href="<?= \base_url('privacy') ?>">Privacy</a> · <a href="<?= \base_url('terms') ?>">Terms</a></span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="<?= \base_url('assets/js/app.js') ?>"></script>
</body>
</html>
