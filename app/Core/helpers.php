<?php
/**
 * Global helpers: escape, redirect, config, flash, base URL.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

/**
 * Escape output for HTML contexts (XSS mitigation).
 */
function e(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Application config array.
 *
 * @return array<string, mixed>
 */
function app_config(): array
{
    return $GLOBALS['app_config'] ?? [];
}

/**
 * Path prefix to strip for routing, from base_url only (e.g. /Aaaag/public_html on XAMPP). Empty on flat local dev.
 *
 * @return list<string>
 */
function application_path_prefix_candidates(): array
{
    $b = (string) (app_config()['base_url'] ?? '');
    if ($b === '') {
        return [];
    }
    $p = parse_url($b, PHP_URL_PATH);
    if (!is_string($p) || $p === '' || $p === '/') {
        return [];
    }
    return [rtrim($p, '/')];
}

/**
 * Longest candidate that is a prefix of $path (used for subfolder Apache / PHP rewrites).
 */
function best_request_path_prefix(string $path, array $candidates): string
{
    $best = '';
    foreach ($candidates as $c) {
        if (!is_string($c) || $c === '' || $c === '/') {
            continue;
        }
        if ($path === $c || str_starts_with($path, $c . '/')) {
            if (strlen($c) > strlen($best)) {
                $best = $c;
            }
        }
    }
    return $best;
}

/**
 * In-app route path, e.g. /timetable-board, /admin — normalizes subfolder + rewrite quirks in one place.
 */
function resolve_request_route_path(): string
{
    $path = str_replace('\\', '/', request_uri_path());
    if ($path === '') {
        $path = '/';
    }
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
    $scriptDir = rtrim($scriptDir, '/');
    if ($scriptDir === '') {
        $scriptDir = '/';
    }
    if ($scriptDir !== '/' && ($path === $scriptDir || str_starts_with($path, $scriptDir . '/'))) {
        $path = $path === $scriptDir ? '/' : substr($path, strlen($scriptDir));
        if ($path === '') {
            $path = '/';
        }
    }
    $pre = \best_request_path_prefix($path, \application_path_prefix_candidates());
    if ($pre !== '') {
        $path = $path === $pre ? '/' : substr($path, strlen($pre));
        if ($path === '') {
            $path = '/';
        }
        if (!isset($path[0]) || $path[0] !== '/') {
            $path = $path === '' ? '/' : '/' . ltrim($path, '/');
        }
    }
    $path = '/' . trim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/') ?: '/';
    }
    return $path;
}

function public_url_path(): string
{
    $b = (string) (app_config()['base_url'] ?? '');
    if ($b === '') {
        return '';
    }
    $p = parse_url($b, PHP_URL_PATH);
    if (!is_string($p) || $p === '' || $p === '/') {
        return '';
    }
    return rtrim($p, '/');
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) (app_config()['base_url'] ?? ''), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . base_url($path));
    }
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $m = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $m;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_uri_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    return is_string($path) ? $path : '/';
}

/**
 * Client IP (behind proxy may need X-Forwarded-For — configure carefully in production).
 */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
}

/**
 * Truncate a string; uses mbstring when the extension is loaded, otherwise byte-safe substr.
 */
function str_truncate(string $s, int $maxLen): string
{
    if ($maxLen < 1) {
        return '';
    }
    if (function_exists('mb_substr')) {
        return (string) \mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return (string) \substr($s, 0, $maxLen);
}
