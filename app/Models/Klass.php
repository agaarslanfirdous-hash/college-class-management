<?php
/**
 * Class/course master (table: classes). Named Klass to avoid PHP keyword.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Klass extends Model
{
    protected function table(): string
    {
        return 'classes';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allWithDepartment(): array
    {
        return $this->queryAll(
            'SELECT c.*, d.name AS department_name, d.code AS department_code
             FROM classes c
             INNER JOIN departments d ON d.id = c.department_id
             ORDER BY c.code'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filter(?int $departmentId, ?string $q): array
    {
        $sql = 'SELECT c.*, d.name AS department_name FROM classes c
                INNER JOIN departments d ON d.id = c.department_id WHERE 1=1';
        $params = [];
        if ($departmentId !== null) {
            $sql .= ' AND c.department_id = ?';
            $params[] = $departmentId;
        }
        if ($q !== null && $q !== '') {
            $sql .= ' AND (c.code LIKE ? OR c.name LIKE ? OR c.subject LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY c.code';
        return $this->queryAll($sql, $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $r = $this->queryOne('SELECT * FROM classes WHERE id = ?', [$id]);
        return $r === false ? null : $r;
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        if (trim($code) === '') {
            return false;
        }
        if ($exceptId === null) {
            $r = $this->queryOne('SELECT id FROM classes WHERE UPPER(TRIM(code)) = UPPER(TRIM(?)) LIMIT 1', [trim($code)]);
        } else {
            $r = $this->queryOne('SELECT id FROM classes WHERE UPPER(TRIM(code)) = UPPER(TRIM(?)) AND id != ? LIMIT 1', [trim($code), $exceptId]);
        }
        return $r !== false;
    }

    /**
     * @param array{code:string,name:string,subject:string,department_id:int,grade_level:?string,section:?string,academic_batch:?string,capacity:int,requirements:?string} $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO classes (code, name, subject, department_id, grade_level, section, academic_batch, capacity, requirements) VALUES (?,?,?,?,?,?,?,?,?)',
            [
                trim($data['code']),
                trim($data['name']),
                trim($data['subject']),
                $data['department_id'],
                $data['grade_level'] ?? null,
                $data['section'] ?? null,
                $data['academic_batch'] ?? null,
                (int) $data['capacity'],
                $data['requirements'] ?? null,
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE classes SET code=?, name=?, subject=?, department_id=?, grade_level=?, section=?, academic_batch=?, capacity=?, requirements=? WHERE id=?',
            [
                trim($data['code']),
                trim($data['name']),
                trim($data['subject']),
                $data['department_id'],
                $data['grade_level'] ?? null,
                $data['section'] ?? null,
                $data['academic_batch'] ?? null,
                (int) $data['capacity'],
                $data['requirements'] ?? null,
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM classes WHERE id = ?', [$id]);
    }
}
