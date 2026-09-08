<?php
/**
 * Sample configuration. Copy to config.php and edit for your server.
 * Never commit config.php (listed in .gitignore).
 *
 * @package CollegeCMS\Config
 */

declare(strict_types=1);

return [
    'app_name' => 'College Class Management',
    'environment' => 'production', // production | development
    // Full site URL with no trailing slash, or "auto" (detected from the request; recommended on shared hosting).
    // If "auto" is wrong, set "base_path" to the path only, e.g. "/group3/.../public_html" (and keep base_url as "auto").
    // CLI: set env CMS_BASE_URL when base_url is "auto".
    'base_url' => 'auto',
    'base_path' => null,
    'timezone' => 'Asia/Kolkata',
    'session' => [
        'name' => 'CMSSESSID',
        'lifetime' => 28800, // 8 hours absolute max
        'idle_timeout' => 1800, // 30 minutes idle
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ],
    'security' => [
        'csrf_token_key' => '_csrf_token',
        'login_max_attempts' => 5,
        'login_lockout_minutes' => 15,
        'password_min_length' => 10,
    ],
    'live_poll_seconds' => 15,
    /** Shared secret for cron_reminders.php (CLI argv or ?key= for web cron) */
    'cron_key' => 'change-this-cron-secret',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'college_cms',
        'user' => 'your_db_user',
        'pass' => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from_address' => 'noreply@your-college.edu',
        'from_name' => 'College CMS',
        'smtp_host' => 'localhost',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_tls' => true,
    ],
];
