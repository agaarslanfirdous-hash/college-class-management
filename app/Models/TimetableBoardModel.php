<?php
/**
 * Interactive program timetable: cells, placements, exchange requests.
 *
 * @package CollegeCMS\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use PDO;

final class TimetableBoardModel extends Model
{
    protected function table(): string
    {
        return 'tt_programs';
    }

    public function activeProgramId(): int
    {
        $r = $this->queryOne('SELECT id FROM tt_programs WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        return (int) ($r['id'] ?? 0);
    }

    public function resolveProgramId(?int $requestedId): int
    {
        if ($requestedId !== null && $requestedId > 0) {
            $r = $this->queryOne('SELECT id FROM tt_programs WHERE id = ? AND is_active = 1', [$requestedId]);
            if ($r !== false) {
                return (int) $r['id'];
            }
        }
        return $this->activeProgramId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActiveProgramsWithDepartments(): array
    {
        try {
            return $this->queryAll(
                'SELECT p.id, p.title, p.session_info, p.department_id,
                        d.name AS department_name, d.code AS department_code
                 FROM tt_programs p
                 LEFT JOIN departments d ON d.id = p.department_id
                 WHERE p.is_active = 1
                 ORDER BY COALESCE(d.name, \'\'), p.id'
            );
        } catch (\Throwable) {
            return $this->queryAll(
                'SELECT id, title, session_info, NULL AS department_id, NULL AS department_name, NULL AS department_code
                 FROM tt_programs WHERE is_active = 1 ORDER BY id'
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function periods(int $programId): array
    {
        return $this->queryAll(
            'SELECT * FROM tt_periods WHERE program_id = ? ORDER BY sort_order',
            [$programId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cellsWithPlacements(int $programId): array
    {
        try {
            return $this->queryAll(
                'SELECT c.*, p.id AS placement_id, p.user_id AS placed_user_id,
                        p.availability_status AS availability_status, p.decline_reason AS decline_reason, p.decline_note AS decline_note,
                        u.full_name AS placed_name, u.email AS placed_email
                 FROM tt_cells c
                 LEFT JOIN tt_placements p ON p.cell_id = c.id
                 LEFT JOIN users u ON u.id = p.user_id
                 WHERE c.program_id = ?
                 ORDER BY c.day_of_week, c.period_index',
                [$programId]
            );
        } catch (\Throwable) {
            return $this->queryAll(
                'SELECT c.*, p.id AS placement_id, p.user_id AS placed_user_id,
                        u.full_name AS placed_name, u.email AS placed_email
                 FROM tt_cells c
                 LEFT JOIN tt_placements p ON p.cell_id = c.id
                 LEFT JOIN users u ON u.id = p.user_id
                 WHERE c.program_id = ?
                 ORDER BY c.day_of_week, c.period_index',
                [$programId]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function teacherCoursesForUser(int $programId, int $userId): array
    {
        return $this->queryAll(
            'SELECT course_code FROM tt_teacher_courses WHERE program_id = ? AND user_id = ?',
            [$programId, $userId]
        );
    }

    public function canTeach(int $programId, int $userId, ?string $courseCode): bool
    {
        if ($courseCode === null || $courseCode === '' || $courseCode === 'Lib' || $courseCode === 'Tut') {
            return false;
        }
        $r = $this->queryOne(
            'SELECT 1 FROM tt_teacher_courses WHERE program_id = ? AND user_id = ? AND course_code = ? LIMIT 1',
            [$programId, $userId, $courseCode]
        );
        return $r !== false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function teachersInProgram(int $programId): array
    {
        return $this->queryAll(
            'SELECT DISTINCT u.id, u.full_name, u.email FROM tt_teacher_courses t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.program_id = ? AND u.role = \'teacher\'
             ORDER BY u.full_name',
            [$programId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function myPlacements(int $programId, int $userId): array
    {
        return $this->queryAll(
            'SELECT p.*, c.day_of_week, c.period_index, c.course_code
             FROM tt_placements p
             INNER JOIN tt_cells c ON c.id = p.cell_id
             WHERE c.program_id = ? AND p.user_id = ?',
            [$programId, $userId]
        );
    }

    public function getPlacementById(int $id): array|false
    {
        return $this->queryOne('SELECT p.*, c.course_code, c.program_id FROM tt_placements p INNER JOIN tt_cells c ON c.id = p.cell_id WHERE p.id = ?', [$id]);
    }

    public function getCell(int $cellId): array|false
    {
        return $this->queryOne('SELECT * FROM tt_cells WHERE id = ?', [$cellId]);
    }

    public function getPlacementOnCell(int $cellId): array|false
    {
        return $this->queryOne('SELECT * FROM tt_placements WHERE cell_id = ?', [$cellId]);
    }

    /**
     * After a placement moves, ensure tt_teacher_courses lists (program, user, course) for the cell’s subject so the teacher and course stay together for drag/swap rules.
     */
    public function ensureTeacherLinkedToCellCourse(int $programId, int $userId, int $cellId): void
    {
        if ($userId < 1) {
            return;
        }
        $c = $this->getCell($cellId);
        if ($c === false) {
            return;
        }
        $cc = $c['course_code'] !== null && (string) $c['course_code'] !== '' ? (string) $c['course_code'] : '';
        if ($cc === '' || $cc === 'Lib' || $cc === 'Tut') {
            return;
        }
        $r = $this->queryOne(
            'SELECT 1 FROM tt_teacher_courses WHERE program_id = ? AND user_id = ? AND course_code = ?',
            [$programId, $userId, $cc]
        );
        if ($r === false) {
            $this->execute(
                'INSERT INTO tt_teacher_courses (program_id, user_id, course_code) VALUES (?,?,?)',
                [$programId, $userId, $cc]
            );
        }
    }

    /**
     * Move a placement to an empty cell, or no-op. Returns error message or null on success.
     */
    public function tryMoveToEmpty(int $placementId, int $toCellId, int $actorId, string $actorRole, bool $force): ?string
    {
        $p = $this->getPlacementById($placementId);
        if ($p === false) {
            return 'Placement not found.';
        }
        if ((string) $actorRole === 'teacher' && (int) $p['user_id'] !== $actorId) {
            return 'You can only move your own class block.';
        }
        $to = $this->getCell($toCellId);
        if ($to === false) {
            return 'Target cell not found.';
        }
        if ((int) $to['program_id'] !== (int) $p['program_id']) {
            return 'Invalid target.';
        }
        $other = $this->getPlacementOnCell($toCellId);
        if ($other !== false) {
            return 'Target slot is occupied. Use exchange request or ask admin/monitor to swap.';
        }
        $cc = $to['course_code'] !== null && $to['course_code'] !== '' ? (string) $to['course_code'] : null;
        if (!$force && (string) $actorRole === 'teacher') {
            if (!$this->canTeach((int) $p['program_id'], $actorId, $cc)) {
                return 'This course is not in your list for this board. Ask admin to add you.';
            }
        }
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE tt_placements SET cell_id = ? WHERE id = ?')->execute([$toCellId, $placementId]);
            try {
                $this->execute(
                    "UPDATE tt_placements SET availability_status = 'confirmed', decline_reason = NULL, decline_note = NULL, responded_at = COALESCE(responded_at, NOW()) WHERE id = ?",
                    [$placementId]
                );
            } catch (\Throwable) {
                // Optional columns (after migration 003)
            }
            $db->commit();
            $this->ensureTeacherLinkedToCellCourse((int) $p['program_id'], (int) $p['user_id'], $toCellId);
        } catch (\Throwable $e) {
            $db->rollBack();
            return 'Could not move (duplicate or constraint).';
        }
        return null;
    }

    public function trySwapPlacements(int $aPlacementId, int $bPlacementId, int $actorId, string $actorRole, bool $force): ?string
    {
        if (!in_array($actorRole, ['admin', 'monitor', 'teacher'], true)) {
            return 'Only administrators, class monitors, and teachers can swap blocks on this board.';
        }
        $pa = $this->queryOne('SELECT p.*, c.program_id AS pid FROM tt_placements p JOIN tt_cells c ON c.id = p.cell_id WHERE p.id = ?', [$aPlacementId]);
        $pb = $this->queryOne('SELECT p.*, c.program_id AS pid FROM tt_placements p JOIN tt_cells c ON c.id = p.cell_id WHERE p.id = ?', [$bPlacementId]);
        if ($pa === false || $pb === false) {
            return 'Placement not found.';
        }
        if ((int) $pa['pid'] !== (int) $pb['pid']) {
            return 'Different timetables.';
        }
        $aCell = (int) $pa['cell_id'];
        $bCell = (int) $pb['cell_id'];
        if ($aCell === $bCell) {
            return 'These placements are already on the same cell.';
        }
        $programId = (int) $pa['pid'];
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $tmp = $this->queryOne(
                'SELECT c.id FROM tt_cells c
                 WHERE c.program_id = ?
                 AND NOT EXISTS (SELECT 1 FROM tt_placements p WHERE p.cell_id = c.id)
                 LIMIT 1',
                [$programId]
            );
            if ($tmp === false) {
                $db->rollBack();
                return 'Cannot swap: this board has no empty cell to use for a safe exchange. The timetable is fully assigned; add an open cell in the template, or use admin tools to free a slot first.';
            }
            $tmpCell = (int) $tmp['id'];
            if ($tmpCell === $aCell || $tmpCell === $bCell) {
                $db->rollBack();
                return 'Cannot swap: internal error picking a free cell. Try again or contact support.';
            }
            $this->execute('UPDATE tt_placements SET cell_id = ? WHERE id = ?', [$tmpCell, $aPlacementId]);
            $this->execute('UPDATE tt_placements SET cell_id = ? WHERE id = ?', [$aCell, $bPlacementId]);
            $this->execute('UPDATE tt_placements SET cell_id = ? WHERE id = ?', [$bCell, $aPlacementId]);
            try {
                $u = $db->prepare("UPDATE tt_placements SET availability_status = 'confirmed', decline_reason = NULL, decline_note = NULL, responded_at = COALESCE(responded_at, NOW()) WHERE id IN (?,?)");
                $u->execute([$aPlacementId, $bPlacementId]);
            } catch (\Throwable) {
            }
            $db->commit();
            $aUser = (int) $pa['user_id'];
            $bUser = (int) $pb['user_id'];
            $this->ensureTeacherLinkedToCellCourse($programId, $aUser, $bCell);
            $this->ensureTeacherLinkedToCellCourse($programId, $bUser, $aCell);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if (stripos($e->getMessage(), 'deadlock') !== false) {
                return 'Swap was busy. Try again once.';
            }
            error_log('tt_swap: ' . $e->getMessage());
            return 'Swap failed. Try again.';
        }
        return null;
    }

    public function setPlacementResponse(
        int $placementId,
        int $actorId,
        string $actorRole,
        string $action,
        ?string $declineReason,
        ?string $declineNote
    ): ?string {
        $p = $this->getPlacementById($placementId);
        if ($p === false) {
            return 'Slot not found.';
        }
        if (!in_array($actorRole, ['admin', 'monitor', 'teacher'], true)) {
            return 'Not allowed.';
        }
        if ($actorRole === 'teacher' && (int) $p['user_id'] !== $actorId) {
            return 'You can only update your own teaching slots.';
        }
        if ($action === 'confirm' || $action === 'reset') {
            if ($action === 'reset' && $actorRole === 'teacher') {
                return 'Only admin or class monitor can reset.';
            }
            if ($action === 'reset' && in_array($actorRole, ['admin', 'monitor'], true)) {
                $this->execute(
                    "UPDATE tt_placements SET availability_status = 'pending', decline_reason = NULL, decline_note = NULL, responded_at = NULL WHERE id = ?",
                    [$placementId]
                );
            } else {
                $this->execute(
                    "UPDATE tt_placements SET availability_status = 'confirmed', decline_reason = NULL, decline_note = NULL, responded_at = NOW() WHERE id = ?",
                    [$placementId]
                );
            }
            return null;
        }
        if ($action === 'decline') {
            if (!in_array($declineReason, ['personal', 'meeting', 'leave', 'other'], true)) {
                return 'Pick a reason: personal, meeting, on leave, or other.';
            }
            $note = $declineNote !== null && $declineNote !== '' ? \str_truncate($declineNote, 500) : null;
            $this->execute(
                "UPDATE tt_placements SET availability_status = 'declined', decline_reason = ?, decline_note = ?, responded_at = NOW() WHERE id = ?",
                [$declineReason, $note, $placementId]
            );
            return null;
        }
        return 'Invalid request.';
    }

    public function addPlacement(int $cellId, int $userId, int $actorId, string $actorRole, bool $force): ?string
    {
        $c = $this->getCell($cellId);
        if ($c === false) {
            return 'Cell not found.';
        }
        if ($this->getPlacementOnCell($cellId) !== false) {
            return 'That slot is already taken.';
        }
        $cc = $c['course_code'] ? (string) $c['course_code'] : null;
        if (!$force && (string) $actorRole === 'teacher') {
            if ($actorId !== $userId) {
                return 'Not allowed.';
            }
            if (!$this->canTeach((int) $c['program_id'], $userId, $cc)) {
                return 'You are not listed for that course on this board.';
            }
        }
        $this->execute(
            'INSERT INTO tt_placements (cell_id, user_id) VALUES (?,?)',
            [$cellId, $userId]
        );
        return null;
    }

    public function removePlacement(int $placementId, int $actorId, string $actorRole, bool $force): ?string
    {
        $p = $this->getPlacementById($placementId);
        if ($p === false) {
            return 'Not found.';
        }
        if (!$force && (string) $actorRole === 'teacher' && (int) $p['user_id'] !== $actorId) {
            return 'Not allowed.';
        }
        if (!$force && !in_array($actorRole, ['admin', 'monitor', 'teacher'], true)) {
            return 'Not allowed.';
        }
        $this->execute('DELETE FROM tt_placements WHERE id = ?', [$placementId]);
        return null;
    }

    public function createExchangeRequest(
        int $programId,
        int $fromUser,
        int $toUser,
        int $fromCell,
        int $toCell,
        ?string $message
    ): int {
        $this->execute(
            'INSERT INTO tt_exchange_requests (program_id, from_user_id, to_user_id, from_cell_id, to_cell_id, message) VALUES (?,?,?,?,?,?)',
            [$programId, $fromUser, $toUser, $fromCell, $toCell, $message]
        );
        return (int) $this->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listExchanges(int $programId, ?int $forUserId, string $role): array
    {
        if ($role === 'student') {
            return [];
        }
        if ($role === 'admin' || $role === 'monitor') {
            return $this->queryAll(
                "SELECT e.*, fc.course_code AS from_code, tc.course_code AS to_code,
                 uf.full_name AS from_name, ut.full_name AS to_name, ru.email AS resolver_email
                 FROM tt_exchange_requests e
                 INNER JOIN tt_cells fc ON fc.id = e.from_cell_id
                 INNER JOIN tt_cells tc ON tc.id = e.to_cell_id
                 INNER JOIN users uf ON uf.id = e.from_user_id
                 INNER JOIN users ut ON ut.id = e.to_user_id
                 LEFT JOIN users ru ON ru.id = e.resolved_by_user_id
                 WHERE e.program_id = ? AND e.status = 'pending' ORDER BY e.id DESC",
                [$programId]
            );
        }
        return $this->queryAll(
            "SELECT e.*, fc.course_code AS from_code, tc.course_code AS to_code,
             uf.full_name AS from_name, ut.full_name AS to_name
             FROM tt_exchange_requests e
             INNER JOIN tt_cells fc ON fc.id = e.from_cell_id
             INNER JOIN tt_cells tc ON tc.id = e.to_cell_id
             INNER JOIN users uf ON uf.id = e.from_user_id
             INNER JOIN users ut ON ut.id = e.to_user_id
             WHERE e.program_id = ? AND (e.from_user_id = ? OR e.to_user_id = ?) AND e.status = 'pending'
             ORDER BY e.id DESC",
            [$programId, $forUserId, $forUserId]
        );
    }

    public function resolveExchange(int $exchangeId, int $resolverId, string $resolverRole, string $action): ?string
    {
        if (!in_array($resolverRole, ['admin', 'monitor'], true)) {
            return 'Not authorized.';
        }
        if (!in_array($action, ['approve', 'reject'], true)) {
            return 'Invalid action.';
        }
        $e = $this->queryOne('SELECT * FROM tt_exchange_requests WHERE id = ? AND status = \'pending\'', [$exchangeId]);
        if ($e === false) {
            return 'Request not found.';
        }
        $pFrom = $this->getPlacementOnCell((int) $e['from_cell_id']);
        $pTo = $this->getPlacementOnCell((int) $e['to_cell_id']);
        if ($action === 'reject') {
            $this->execute(
                'UPDATE tt_exchange_requests SET status=\'rejected\', resolved_by_user_id=?, resolved_at=NOW() WHERE id=?',
                [$resolverId, $exchangeId]
            );
            return null;
        }
        if ($pFrom === false || $pTo === false) {
            return 'A slot no longer has the expected teacher placement.';
        }
        if ((int) $pFrom['user_id'] !== (int) $e['from_user_id'] || (int) $pTo['user_id'] !== (int) $e['to_user_id']) {
            return 'Placements have changed. Reject this and create a new request.';
        }
        $u1 = (int) $e['from_user_id'];
        $u2 = (int) $e['to_user_id'];
        $i1 = (int) $pFrom['id'];
        $i2 = (int) $pTo['id'];
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $st = $db->prepare('UPDATE tt_placements SET user_id = CASE id WHEN ? THEN ? WHEN ? THEN ? END WHERE id IN (?,?)');
            $st->execute([$i1, $u2, $i2, $u1, $i1, $i2]);
            $this->execute(
                'UPDATE tt_exchange_requests SET status=\'approved\', resolved_by_user_id=?, resolved_at=NOW() WHERE id=?',
                [$resolverId, $exchangeId]
            );
            $db->commit();
        } catch (\Throwable) {
            $db->rollBack();
            return 'Could not complete exchange.';
        }
        return null;
    }

    /**
     * When a new department is created, add a program board (copy layout from the first program) if none exists.
     */
    public function ensureProgramForDepartment(int $departmentId, string $deptName, string $deptCode): void
    {
        try {
            $ex = $this->queryOne('SELECT id FROM tt_programs WHERE department_id = ? LIMIT 1', [$departmentId]);
            if ($ex !== false) {
                return;
            }
            $base = $this->queryOne('SELECT id FROM tt_programs WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
            if ($base === false) {
                return;
            }
            $baseId = (int) $base['id'];
            $this->execute(
                'INSERT INTO tt_programs (title, session_info, is_active, department_id) VALUES (?,?,1,?)',
                [$deptName . ' — Timetable', 'Code: ' . $deptCode, $departmentId]
            );
            $newId = (int) $this->lastInsertId();
            $this->execute(
                'INSERT INTO tt_periods (program_id, period_index, label, time_range, sort_order)
                 SELECT ?, period_index, label, time_range, sort_order FROM tt_periods WHERE program_id = ?',
                [$newId, $baseId]
            );
            $this->execute(
                'INSERT INTO tt_cells (program_id, day_of_week, period_index, course_code, course_name, venue, display_note)
                 SELECT ?, day_of_week, period_index, course_code, course_name, venue, display_note FROM tt_cells WHERE program_id = ?',
                [$newId, $baseId]
            );
            $this->execute(
                'INSERT INTO tt_teacher_courses (program_id, user_id, course_code)
                 SELECT DISTINCT ?, user_id, course_code FROM tt_teacher_courses WHERE program_id = ?',
                [$newId, $baseId]
            );
            $this->execute(
                'INSERT INTO tt_placements (cell_id, user_id)
                 SELECT c2.id, p.user_id
                 FROM tt_placements p
                 INNER JOIN tt_cells c1 ON c1.id = p.cell_id AND c1.program_id = ?
                 INNER JOIN tt_cells c2 ON c2.program_id = ? AND c2.day_of_week = c1.day_of_week AND c2.period_index = c1.period_index
                 LEFT JOIN tt_placements ex ON ex.cell_id = c2.id
                 WHERE ex.id IS NULL',
                [$baseId, $newId]
            );
            try {
                $this->execute(
                    "UPDATE tt_placements p JOIN tt_cells c ON c.id = p.cell_id SET p.availability_status = 'confirmed', p.responded_at = NOW() WHERE c.program_id = ?",
                    [$newId]
                );
            } catch (\Throwable) {
            }
        } catch (\Throwable) {
            // tt_programs may lack department_id; skip auto board
        }
    }

    /**
     * Ensure every department in `departments` has a matching tt_programs row (for timetable dropdown).
     */
    public function syncAllDepartmentsToPrograms(): void
    {
        try {
            $depts = $this->queryAll('SELECT id, name, code FROM departments ORDER BY name');
        } catch (\Throwable) {
            return;
        }
        foreach ($depts as $d) {
            $this->ensureProgramForDepartment((int) $d['id'], (string) $d['name'], (string) $d['code']);
        }
    }

    public function getProgramIdForDepartment(int $departmentId): int
    {
        if ($departmentId < 1) {
            return 0;
        }
        $r = $this->queryOne('SELECT id FROM tt_programs WHERE department_id = ? AND is_active = 1 LIMIT 1', [$departmentId]);
        if ($r !== false) {
            return (int) $r['id'];
        }
        $d = $this->queryOne('SELECT id, name, code FROM departments WHERE id = ?', [$departmentId]);
        if ($d === false) {
            return 0;
        }
        $this->ensureProgramForDepartment((int) $d['id'], (string) $d['name'], (string) $d['code']);
        $r2 = $this->queryOne('SELECT id FROM tt_programs WHERE department_id = ? AND is_active = 1 LIMIT 1', [$departmentId]);
        return $r2 === false ? 0 : (int) $r2['id'];
    }

    /**
     * Clear a class code from a department’s interactive board (e.g. before re-linking after code change, or on class delete).
     */
    public function removeClassCodeFromProgramBoard(string $classCode, int $departmentId): void
    {
        $code = trim($classCode);
        if ($code === '' || $departmentId < 1) {
            return;
        }
        $pid = $this->getProgramIdForDepartment($departmentId);
        if ($pid < 1) {
            return;
        }
        $cells = $this->queryAll(
            'SELECT id FROM tt_cells WHERE program_id = ? AND UPPER(TRIM(course_code)) = UPPER(TRIM(?))',
            [$pid, $code]
        );
        foreach ($cells as $c) {
            $this->execute('DELETE FROM tt_placements WHERE cell_id = ?', [(int) $c['id']]);
        }
        $this->execute(
            "UPDATE tt_cells SET course_code = NULL, course_name = NULL, venue = NULL, display_note = NULL
             WHERE program_id = ? AND UPPER(TRIM(course_code)) = UPPER(TRIM(?))",
            [$pid, $code]
        );
    }

    /**
     * @param array{code: string, name: string, subject: string, department_id: int} $class
     * @param int $timetableDay 1..5 (Mon..Fri) or 0 = first free cell
     * @param int $timetablePeriod 1..7 or 0 = first free cell
     * @return null|string error message
     */
    public function syncClassToBoard(array $class, int $timetableDay = 0, int $timetablePeriod = 0): ?string
    {
        $this->syncAllDepartmentsToPrograms();
        $deptId = (int) ($class['department_id'] ?? 0);
        if ($deptId < 1) {
            return 'No department for this class.';
        }
        $code = trim((string) ($class['code'] ?? ''));
        if ($code === '') {
            return 'Class has no code.';
        }
        $cname = trim((string) ($class['name'] ?? ''));
        $subj = trim((string) ($class['subject'] ?? ''));
        $courseName = $cname !== '' ? $cname : $code;
        if ($subj !== '' && $cname !== '') {
            $courseName = $cname . ' (' . $subj . ')';
        } elseif ($subj !== '' && $cname === '') {
            $courseName = $code . ' — ' . $subj;
        }
        $drn = $this->queryOne('SELECT name FROM departments WHERE id = ?', [$deptId]);
        $venue = $drn !== false ? (string) ($drn['name'] ?? '') : '';

        $pid = $this->getProgramIdForDepartment($deptId);
        if ($pid < 1) {
            return 'No program board for this department. Run migrations or add a base timetable (program 1).';
        }

        $timetableDay = (int) $timetableDay;
        $timetablePeriod = (int) $timetablePeriod;
        if (($timetableDay > 0) xor ($timetablePeriod > 0)) {
            return 'Set both day and period for a specific slot, or both empty for the first free cell.';
        }
        if ($timetableDay > 0) {
            if ($timetableDay < 1 || $timetableDay > 5) {
                return 'Day must be Monday through Friday.';
            }
            if ($timetablePeriod < 1 || $timetablePeriod > 7) {
                return 'Period must be 1 to 7 (see timetable row labels for times, e.g. V = 1:30–2:30).';
            }
        }

        $rowCell = $this->queryOne(
            'SELECT id, course_code FROM tt_cells WHERE program_id = ? AND UPPER(TRIM(course_code)) = UPPER(TRIM(?))',
            [$pid, $code]
        );

        if ($timetableDay > 0 && $timetablePeriod > 0) {
            $cell = $this->queryOne(
                'SELECT * FROM tt_cells WHERE program_id = ? AND day_of_week = ? AND period_index = ?',
                [$pid, $timetableDay, $timetablePeriod]
            );
            if ($cell === false) {
                return 'No cell at the chosen day and period.';
            }
            $oc = (string) ($cell['course_code'] ?? '');
            if ($oc !== '' && $oc !== $code) {
                $x = $this->queryOne(
                    'SELECT id FROM tt_placements WHERE cell_id = ?',
                    [(int) $cell['id']]
                );
                if ($x !== false) {
                    return 'That time slot (day ' . $timetableDay . ', period ' . $timetablePeriod . ') is already used for ' . $oc
                        . ' and has a teacher. Clear or move that on the board first, or pick another time.';
                }
            }
            if ($rowCell !== false && (int) $rowCell['id'] !== (int) $cell['id']) {
                $this->execute('DELETE FROM tt_placements WHERE cell_id = ?', [(int) $rowCell['id']]);
                $this->execute('UPDATE tt_cells SET course_code = NULL, course_name = NULL, venue = NULL, display_note = NULL WHERE id = ?', [(int) $rowCell['id']]);
            }
            $this->execute(
                'UPDATE tt_cells SET course_code = ?, course_name = ?, venue = ? WHERE id = ?',
                [$code, $courseName, $venue !== '' ? $venue : null, (int) $cell['id']]
            );
            return null;
        }

        $this->execute(
            'UPDATE tt_cells SET course_name = ? WHERE program_id = ? AND UPPER(TRIM(course_code)) = UPPER(TRIM(?))',
            [$courseName, $pid, $code]
        );
        if ($rowCell !== false) {
            return null;
        }

        $empty = $this->queryOne(
            "SELECT id FROM tt_cells WHERE program_id = ? AND (course_code IS NULL OR TRIM(course_code) = '') ORDER BY day_of_week, period_index LIMIT 1",
            [$pid]
        );
        if ($empty !== false) {
            $this->execute('UPDATE tt_cells SET course_code = ?, course_name = ?, venue = ? WHERE id = ?', [$code, $courseName, $venue !== '' ? $venue : null, (int) $empty['id']]);
            return null;
        }
        return 'The department timetable has no free empty slot. Change an existing cell on the interactive timetable, or add capacity in the template.';
    }

    /**
     * @return list<array{course_code: string, course_name: string}>
     */
    public function listCourseLabelsForProgram(int $programId): array
    {
        if ($programId < 1) {
            return [];
        }
        $rows = $this->queryAll(
            "SELECT course_code, course_name FROM tt_cells
             WHERE program_id = ? AND course_code IS NOT NULL AND TRIM(course_code) <> ''
               AND UPPER(TRIM(course_code)) NOT IN ('LIB', 'TUT')
             ORDER BY course_code, id",
            [$programId]
        );
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            $k = strtoupper(trim((string) ($r['course_code'] ?? '')));
            if ($k === '' || isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = [
                'course_code' => (string) $r['course_code'],
                'course_name' => (string) ($r['course_name'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Set or change who teaches a cell; removes placement if $teacherUserId is 0.
     * Auto-adds tt_teacher_courses for the course so the name + subject move together on drag.
     */
    public function assignUserToCell(int $cellId, int $teacherUserId, int $actorId, string $actorRole): ?string
    {
        if (!in_array($actorRole, ['admin', 'monitor', 'teacher'], true)) {
            return 'Not allowed.';
        }
        $c = $this->getCell($cellId);
        if ($c === false) {
            return 'Cell not found.';
        }
        $programId = (int) $c['program_id'];
        if ($actorRole === 'teacher') {
            $p0 = $this->getPlacementOnCell($cellId);
            if ($p0 === false) {
                return 'Only a coordinator can assign a teacher to an empty slot. Move your own block with drag and drop, or ask admin.';
            }
            if ((int) $p0['user_id'] !== $actorId) {
                return 'You can only reassign the teacher on your own row. Ask a coordinator to change other slots.';
            }
        }
        if ($teacherUserId < 1) {
            $p = $this->getPlacementOnCell($cellId);
            if ($p !== false) {
                $this->execute('DELETE FROM tt_placements WHERE id = ?', [(int) $p['id']]);
            }
            return null;
        }
        $u = $this->queryOne(
            "SELECT id, full_name, role FROM users WHERE id = ? AND is_active = 1",
            [$teacherUserId]
        );
        if ($u === false || (string) $u['role'] !== 'teacher') {
            return 'Select an active teacher account.';
        }
        $cc = $c['course_code'] ? (string) $c['course_code'] : '';
        if ($cc === '' || $cc === 'Lib' || $cc === 'Tut') {
            return 'This grid cell has no course code. Edit the program template, or pick a teaching cell.';
        }
        $existsTc = $this->queryOne(
            'SELECT 1 FROM tt_teacher_courses WHERE program_id = ? AND user_id = ? AND course_code = ?',
            [$programId, $teacherUserId, $cc]
        );
        if ($existsTc === false) {
            $this->execute(
                'INSERT INTO tt_teacher_courses (program_id, user_id, course_code) VALUES (?,?,?)',
                [$programId, $teacherUserId, $cc]
            );
        }
        $p = $this->getPlacementOnCell($cellId);
        if ($p !== false) {
            $this->execute(
                'UPDATE tt_placements SET user_id = ? WHERE id = ?',
                [$teacherUserId, (int) $p['id']]
            );
            try {
                $this->execute(
                    "UPDATE tt_placements SET availability_status = 'confirmed', decline_reason = NULL, decline_note = NULL, responded_at = NOW() WHERE id = ?",
                    [(int) $p['id']]
                );
            } catch (\Throwable) {
            }
        } else {
            $this->execute('INSERT INTO tt_placements (cell_id, user_id) VALUES (?, ?)', [(int) $cellId, $teacherUserId]);
            try {
                $this->execute("UPDATE tt_placements SET availability_status = 'confirmed', responded_at = NOW() WHERE cell_id = ?", [(int) $cellId]);
            } catch (\Throwable) {
            }
        }
        return null;
    }

    /**
     * Move two cell rows by swapping (day, period) so the whole subject + teacher block moves in the grid.
     * Uses a three-step update to avoid unique (program,day,period) conflicts if that constraint is added.
     */
    public function tryExchangeCellTimeSlots(int $cellIdA, int $cellIdB, int $actorId, string $actorRole, bool $force): ?string
    {
        if (!in_array($actorRole, ['admin', 'monitor', 'teacher'], true) || !$force) {
            return 'Not allowed.';
        }
        $a = $this->getCell($cellIdA);
        $b = $this->getCell($cellIdB);
        if ($a === false || $b === false) {
            return 'Cell not found.';
        }
        if ((int) $a['program_id'] !== (int) $b['program_id']) {
            return 'Cells must be on the same program board.';
        }
        $dA = (int) $a['day_of_week'];
        $pA = (int) $a['period_index'];
        $dB = (int) $b['day_of_week'];
        $pB = (int) $b['period_index'];
        if ($dA === $dB && $pA === $pB) {
            return 'Choose two different time slots.';
        }
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE tt_cells SET day_of_week = 9, period_index = 99 WHERE id = ?')->execute([$cellIdA]);
            $db->prepare('UPDATE tt_cells SET day_of_week = ?, period_index = ? WHERE id = ?')->execute([$dA, $pA, $cellIdB]);
            $db->prepare('UPDATE tt_cells SET day_of_week = ?, period_index = ? WHERE id = ?')->execute([$dB, $pB, $cellIdA]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return 'Could not move subject slots. Try again.';
        }
        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDeclinedSlotPlacements(int $limit = 50): array
    {
        try {
            $lim = max(1, min(200, $limit));
            return $this->queryAll(
                'SELECT p.id, p.user_id, u.full_name AS teacher_name, p.decline_reason, p.decline_note, p.responded_at,
                        c.day_of_week, c.period_index, c.course_code, pr.title AS program_title, d.name AS department_name
                 FROM tt_placements p
                 INNER JOIN tt_cells c ON c.id = p.cell_id
                 INNER JOIN tt_programs pr ON pr.id = c.program_id
                 LEFT JOIN departments d ON d.id = pr.department_id
                 INNER JOIN users u ON u.id = p.user_id
                 WHERE p.availability_status = \'declined\'
                 ORDER BY p.responded_at DESC, p.id DESC
                 LIMIT ' . $lim
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Timetable (program board) declines where the cell course code matches a class the student is enrolled in.
     *
     * @return list<array<string, mixed>>
     */
    public function listDeclinedPlacementsForEnrolledStudent(int $studentId, int $limit = 40): array
    {
        $lim = max(1, min(100, $limit));
        try {
            return $this->queryAll(
                'SELECT p.id, p.user_id, u.full_name AS teacher_name, p.decline_reason, p.decline_note, p.responded_at,
                        c.day_of_week, c.period_index, c.course_code, pr.title AS program_title, d.name AS department_name
                 FROM tt_placements p
                 INNER JOIN tt_cells c ON c.id = p.cell_id
                 INNER JOIN tt_programs pr ON pr.id = c.program_id
                 LEFT JOIN departments d ON d.id = pr.department_id
                 INNER JOIN users u ON u.id = p.user_id
                 WHERE p.availability_status = \'declined\'
                 AND c.course_code IS NOT NULL AND TRIM(c.course_code) != \'\'
                 AND EXISTS (
                    SELECT 1 FROM enrollments e
                    INNER JOIN classes cl ON e.class_id = cl.id
                    WHERE e.student_id = ?
                    AND UPPER(TRIM(cl.code)) = UPPER(TRIM(c.course_code))
                 )
                 ORDER BY p.responded_at DESC, p.id DESC
                 LIMIT ' . $lim,
                [$studentId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Timetable slot declines in departments where this user monitors at least one class.
     *
     * @return list<array<string, mixed>>
     */
    public function listDeclinedPlacementsForMonitorDepts(int $monitorUserId, int $limit = 40): array
    {
        $lim = max(1, min(100, $limit));
        try {
            return $this->queryAll(
                'SELECT p.id, p.user_id, u.full_name AS teacher_name, p.decline_reason, p.decline_note, p.responded_at,
                        c.day_of_week, c.period_index, c.course_code, pr.title AS program_title, d.name AS department_name
                 FROM tt_placements p
                 INNER JOIN tt_cells c ON c.id = p.cell_id
                 INNER JOIN tt_programs pr ON pr.id = c.program_id
                 LEFT JOIN departments d ON d.id = pr.department_id
                 INNER JOIN users u ON u.id = p.user_id
                 WHERE p.availability_status = \'declined\'
                 AND pr.department_id IN (
                    SELECT DISTINCT cl.department_id FROM class_assignments ca
                    INNER JOIN schedules s ON s.id = ca.schedule_id
                    INNER JOIN classes cl ON cl.id = s.class_id
                    WHERE ca.monitor_id = ? AND ca.status IN (\'accepted\',\'overridden\')
                 )
                 ORDER BY p.responded_at DESC, p.id DESC
                 LIMIT ' . $lim,
                [$monitorUserId]
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
