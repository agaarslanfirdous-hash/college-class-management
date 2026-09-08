<?php
/**
 * Thin PDO wrapper for models (table name + helpers).
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    abstract protected function table(): string;

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    /**
     * @param list<scalar|null> $params
     * @return list<array<string, mixed>>
     */
    public function queryAll(string $sql, array $params = []): array
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * @param list<scalar|null> $params
     * @return array<string, mixed>|false
     */
    public function queryOne(string $sql, array $params = []): array|false
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? false : $row;
    }

    /**
     * @param list<scalar|null> $params
     */
    public function execute(string $sql, array $params = []): bool
    {
        $st = $this->db->prepare($sql);
        return $st->execute($params);
    }

    public function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|false
     */
    public function find(int $id): array|false
    {
        $t = $this->table();
        return $this->queryOne("SELECT * FROM `{$t}` WHERE id = ? LIMIT 1", [$id]);
    }
}
