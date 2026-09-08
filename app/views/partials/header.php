<?php
/**
 * HTML head and opening body; expects $title (string).
 *
 * @package CollegeCMS\Views
 */
declare(strict_types=1);
$appName = (string) (\app_config()['app_name'] ?? 'College CMS');
$pageTitle = isset($title) ? \e((string) $title) . ' — ' . \e($appName) : \e($appName);
$pollSec = (int) (\app_config()['live_poll_seconds'] ?? 15);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= \base_url('assets/css/app.css') ?>">
    <link rel="icon" href="<?= \base_url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="manifest" href="<?= \base_url('site.webmanifest') ?>">
    <script>
    window.CMS_POLL_SEC = <?= $pollSec ?>;
    window.CMS_ROOT = <?= json_encode(rtrim(\base_url(''), '/')) ?>;
    </script>
</head>
<body class="d-flex flex-column min-vh-100">
<a class="visually-hidden-focusable btn btn-sm btn-primary position-absolute m-2" href="#main">Skip to content</a>
<?php require __DIR__ . '/nav.php'; ?>
<main id="main" class="flex-grow-1 py-4">
<div class="container">
<?php
$err = \flash_get('error');
$succ = \flash_get('success');
if ($err): ?>
    <div class="alert alert-danger" role="alert"><?= \e($err) ?></div>
<?php endif;
if ($succ): ?>
    <div class="alert alert-success" role="alert"><?= \e($succ) ?></div>
<?php endif;
