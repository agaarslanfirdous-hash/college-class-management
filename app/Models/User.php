<?php
/**
 * User model — authentication and CRUD for admins.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected function table(): string
    {
        return 'users';
    }

    /**
     * @return array<string, mixed>|false
     */
    public function findByEmail(string $email): array|false
    {
        return $this->queryOne(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allByRole(?string $role = null): array
    {
        if ($role !== null && $role !== '') {
            return $this->queryAll(
                'SELECT id, email, role, full_name, phone, is_active, last_login_at, created_at FROM users WHERE role = ? ORDER BY full_name',
                [$role]
            );
        }
        return $this->queryAll(
            'SELECT id, email, role, full_name, phone, is_active, last_login_at, created_at FROM users ORDER BY role, full_name'
        );
    }

    /**
     * @param array{email:string,password_hash:string,role:string,full_name:string,phone?:?string,is_active:int} $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO users (email, password_hash, role, full_name, phone, is_active) VALUES (?,?,?,?,?,?)',
            [
                strtolower(trim($data['email'])),
                $data['password_hash'],
                $data['role'],
                $data['full_name'],
                $data['phone'] ?? null,
                (int) ($data['is_active'] ?? 1),
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function updateProfile(int $id, string $fullName, ?string $phone): void
    {
        $this->execute(
            'UPDATE users SET full_name = ?, phone = ? WHERE id = ?',
            [$fullName, $phone, $id]
        );
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public function setTeacherPin(int $id, ?string $pinHash): void
    {
        $this->execute('UPDATE users SET teacher_pin_hash = ? WHERE id = ?', [$pinHash, $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->execute('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    public function touchLogin(int $id): void
    {
        $this->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    /**
     * @return array<string, mixed>|false
     */
    public function findActiveById(int $id): array|false
    {
        return $this->queryOne(
            'SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );
    }
}
