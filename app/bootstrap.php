<?php
/**
 * Application bootstrap: config, autoload, error handling, session init.
 *
 * @package CollegeCMS
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public_html');

$configFile = APP_PATH . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = APP_PATH . '/config/config.sample.php';
}
/** @var array<string,mixed> $config */
$config = require $configFile;

$rawBase = (string) ($config['base_url'] ?? 'auto');
$isDev = (string) ($config['environment'] ?? 'production') === 'development';
// Exact URL from config (XAMPP subfolder, or matching host:port to the browser on purpose).
if (str_starts_with($rawBase, 'http://') || str_starts_with($rawBase, 'https://')) {
    $config['base_url'] = rtrim($rawBase, '/');
    if (PHP_SAPI !== 'cli') {
        $GLOBALS['cms_url_base_path'] = '';
    }
} elseif ($isDev && $rawBase === 'auto' && PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $config['base_url'] = ($isHttps ? 'https' : 'http') . '://' . (string) $_SERVER['HTTP_HOST'];
    $GLOBALS['cms_url_base_path'] = '';
} elseif ($rawBase === 'auto' && PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $scheme = $isHttps ? 'https' : 'http';
    $host = (string) $_SERVER['HTTP_HOST'];
    $path = '';
    $fromConfig = $config['base_path'] ?? null;
    if (is_string($fromConfig) && $fromConfig !== '') {
        $hostOnly = (string) preg_replace('/:\d+$/', '', $host);
        $isLoopback = in_array($hostOnly, ['127.0.0.1', '::1', '[::1]'], true)
            || strcasecmp($hostOnly, 'localhost') === 0;
        $isPrivateLan = (bool) preg_match(
            '/^192\.168\.\d+\.\d+$|^10\.\d+\.\d+\.\d+$|^172\.(1[6-9]|2\d|3[01])\.\d+\.\d+$/',
            $hostOnly
        );
        if ($isLoopback || $isPrivateLan) {
            // Dev / LAN: don’t use a cPanel /group/.../public_html in base_url; links and routes would 404.
            $fromConfig = null;
        }
    }
    if (is_string($fromConfig) && $fromConfig !== '') {
        $path = rtrim(str_replace('\\', '/', $fromConfig), '/');
    } else {
        $g = $GLOBALS['cms_url_base_path'] ?? null;
        if (is_string($g) && rtrim($g, '/') !== '') {
            $path = rtrim($g, '/');
        } else {
            $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $phpSelf = (string) ($_SERVER['PHP_SELF'] ?? $script);
            $dir1 = rtrim(str_replace('\\', '/', dirname($script)), '/');
            $dir2 = rtrim(str_replace('\\', '/', dirname($phpSelf)), '/');
            $cand = (strlen($dir1) > 1) ? $dir1 : $dir2;
            if ((strlen($cand) <= 1) && (strlen($dir2) > 1)) {
                $cand = $dir2;
            }
            $path = (in_array($cand, ['', '/', '.'], true) ? '' : $cand);
            if ($path === '') {
                $req = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
                if (preg_match('#^(.+?/public_html)(/|$)#', $req, $m)) {
                    $path = rtrim($m[1], '/');
                }
            }
        }
    }
    $config['base_url'] = $scheme . '://' . $host . $path;
} elseif ($rawBase === 'auto' && PHP_SAPI === 'cli') {
    $fromEnv = getenv('CMS_BASE_URL');
    $config['base_url'] = is_string($fromEnv) && $fromEnv !== '' ? rtrim($fromEnv, '/') : 'http://127.0.0.1:8080';
}

$GLOBALS['app_config'] = $config;

date_default_timezone_set((string) ($config['timezone'] ?? 'UTC'));

$env = (string) ($config['environment'] ?? 'production');
if ($env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $logDir = STORAGE_PATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/php-error.log');
}

require_once APP_PATH . '/Core/helpers.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = substr($class, 4);
    $path = APP_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

// Session: secure defaults (call session_start after this file loads)
$sessionCfg = $config['session'] ?? [];
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name((string) ($sessionCfg['name'] ?? 'CMSSESSID'));
    $isHttps = PHP_SAPI !== 'cli' && (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    );
    // Only send Secure session cookies on HTTPS. On http://127.0.0.1 this must be false or login "hangs" / never sticks.
    $secure = $isHttps && (bool) ($sessionCfg['cookie_secure'] ?? true);
    $samesite = (string) ($sessionCfg['cookie_samesite'] ?? 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => (bool) ($sessionCfg['cookie_httponly'] ?? true),
        'samesite' => $samesite,
    ]);
    session_start();
}

// Idle / absolute session timeout
$idle = (int) ($sessionCfg['idle_timeout'] ?? 1800);
$maxLife = (int) ($sessionCfg['lifetime'] ?? 28800);
$now = time();
if (!empty($_SESSION['_login_at'])) {
    if ($now - (int) $_SESSION['_login_at'] > $maxLife) {
        $_SESSION = [];
        session_destroy();
        session_start();
    } elseif (!empty($_SESSION['_last_activity']) && $now - (int) $_SESSION['_last_activity'] > $idle) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
}
$_SESSION['_last_activity'] = $now;
