<?php
/**
 * PDO singleton for MySQL. All queries must use prepared statements.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $c = \app_config()['db'] ?? [];
        $host = (string) ($c['host'] ?? '127.0.0.1');
        $name = (string) ($c['name'] ?? '');
        $user = (string) ($c['user'] ?? '');
        $pass = (string) ($c['pass'] ?? '');
        $charset = (string) ($c['charset'] ?? 'utf8mb4');
        $envPort = getenv('CMS_DB_PORT');
        if (is_string($envPort) && $envPort !== '') {
            $port = (int) $envPort;
        } else {
            $port = (isset($c['port']) && $c['port'] !== null) ? (int) $c['port'] : 3306;
        }
        $portPart = $port > 0 ? ";port={$port}" : '';
        $dsn = "mysql:host={$host}{$portPart};dbname={$name};charset={$charset}";
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            Logger::error('Database connection failed', ['exception' => $e->getMessage()]);
            if ((string) (\app_config()['environment'] ?? '') === 'development') {
                $hint = sprintf(
                    ' (host=%s port=%d db=%s). ' .
                    '“Connection refused” means MySQL is not running on that address/port, or the port is wrong. ' .
                    'Start MySQL in XAMPP, Laragon, or Services; then in app/config/config.php set "port" to match ' .
                    '(often 3306 for XAMPP, 3307/3308 for some Laragon installs). ' .
                    'Or set environment variable CMS_DB_PORT=3306 before PHP.',
                    $host,
                    $port,
                    $name
                );
                throw new \RuntimeException('Database: ' . $e->getMessage() . $hint, 0, $e);
            }
            throw $e;
        }
        return self::$pdo;
    }
}
