<?php
/**
 * Department master data.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Department extends Model
{
    protected function table(): string
    {
        return 'departments';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->queryAll('SELECT * FROM departments ORDER BY name');
    }

    public function create(string $name, string $code): int
    {
        $this->execute(
            'INSERT INTO departments (name, code) VALUES (?,?)',
            [trim($name), strtoupper(trim($code))]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, string $name, string $code): void
    {
        $this->execute(
            'UPDATE departments SET name = ?, code = ? WHERE id = ?',
            [trim($name), strtoupper(trim($code)), $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM departments WHERE id = ?', [$id]);
    }
}
