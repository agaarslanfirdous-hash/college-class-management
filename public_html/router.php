<?php
/**
 * Router for PHP’s built-in web server only (not used on Apache).
 * For existing files, return false (server serves or executes .php as usual).
 * All other paths fall through to index.php (front controller).
 *
 * From this directory:
 *   php -S 127.0.0.1:8080 router.php
 */
declare(strict_types=1);

$raw = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
if (str_contains($raw, '..')) {
    require __DIR__ . '/index.php';
    return;
}
$raw = $raw === '' ? '/' : $raw;
$full = $raw === '/'
    ? null
    : (realpath(__DIR__ . $raw) ?: null);

$root = realpath(__DIR__);
if ($root !== null && $full !== null && str_starts_with($full, $root) && is_file($full)) {
    return false;
}

require __DIR__ . '/index.php';
