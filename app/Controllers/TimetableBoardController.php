<?php
/**
 * Program timetable board (tt_cells / placements). Staff: drag/swap, assign, exchanges.
 * Students: same board as staff, view only (all mutating routes still use gateNoStudents()).
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\Notification;
use App\Models\TimetableBoardModel;
use App\Models\TtSwapProposal;
use App\Models\User;

final class TimetableBoardController extends Controller
{
    private function backToBoard(int $programId = 0): void
    {
        if ($programId > 0) {
            redirect('/timetable-board?program_id=' . $programId);
        }
        redirect('/timetable-board');
    }

    private function requestedProgramIdFromRequest(): int
    {
        $b = (int) ($_POST['return_program_id'] ?? 0);
        if ($b > 0) {
            return $b;
        }
        return (int) ($_GET['program_id'] ?? 0);
    }

    /** P# labels like "12", "P#12", "P#12 · C#90" all resolve to 12. */
    private function parsePlacementId(mixed $raw): int
    {
        if (is_int($raw)) {
            return $raw;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return 0;
        }
        if (preg_match('/\d+/', $s, $m) !== 0) {
            return (int) $m[0];
        }
        return 0;
    }

    private function gateNoStudents(): void
    {
        $this->requireAuth();
        if (Auth::role() === 'student') {
            http_response_code(403);
            $this->view('layouts/error', [
                'title' => 'Not available',
                'message' => 'That action is not available for your account. You can only view the program timetable.',
            ]);
            exit;
        }
    }

    public function board(): void
    {
        $this->requireAuth();
        $m = new TimetableBoardModel();
        $m->syncAllDepartmentsToPrograms();
        $req = $this->requestedProgramIdFromRequest() ?: null;
        $pid = $m->resolveProgramId($req);
        if ($pid === 0) {
            $this->view('timetable/board', [
                'title' => 'Program timetable',
                'no_program' => true,
            ]);
            return;
        }
        $periods = $m->periods($pid);
        $rows = $m->cellsWithPlacements($pid);
        $byDay = [];
        for ($d = 1; $d <= 5; $d++) {
            $byDay[$d] = [];
        }
        foreach ($rows as $r) {
            $d = (int) $r['day_of_week'];
            if (!isset($byDay[$d])) {
                $byDay[$d] = [];
            }
            $byDay[$d][(int) $r['period_index']] = $r;
        }
        $uid = (int) Auth::id();
        $role = (string) Auth::role();
        $teach = $m->teacherCoursesForUser($pid, $uid);
        $teachCodes = array_map(static fn (array $x) => (string) $x['course_code'], $teach);
        $myPl = $m->myPlacements($pid, $uid);
        $teachers = $m->teachersInProgram($pid);
        $ex = $m->listExchanges($pid, $uid, $role);
        $allTeachers = $role === 'student' ? [] : (new User())->allByRole('teacher');
        $programs = $m->listActiveProgramsWithDepartments();
        $boardCodes = $m->listCourseLabelsForProgram($pid);
        $deptId = 0;
        $prgRow = $m->queryOne('SELECT department_id FROM tt_programs WHERE id = ?', [$pid]);
        if ($prgRow !== false && $prgRow['department_id'] !== null) {
            $deptId = (int) $prgRow['department_id'];
        }
        $isDeptClassMonitor = $deptId > 0
            && $role === 'teacher'
            && (new ClassAssignment())->userIsAssignedClassMonitorInDepartment($uid, $deptId);
        $boardReadOnly = $role === 'student';
        $swapProposals = [];
        if (!$boardReadOnly) {
            try {
                $swapProposals = (new TtSwapProposal())->listPendingForProgram($pid);
            } catch (\Throwable) {
                $swapProposals = [];
            }
        }
        $this->view('timetable/board', [
            'title' => $boardReadOnly ? 'Program timetable' : 'Program timetable (interactive)',
            'program_id' => $pid,
            'programs' => $programs,
            'periods' => $periods,
            'by_day' => $byDay,
            'teach_codes' => $teachCodes,
            'my_placements' => $myPl,
            'teachers' => $teachers,
            'all_teachers' => $allTeachers,
            'exchanges' => $ex,
            'role' => $role,
            'no_program' => false,
            'board_codes' => $boardCodes,
            'is_dept_class_monitor' => $isDeptClassMonitor,
            'board_read_only' => $boardReadOnly,
            'swap_proposals' => $swapProposals,
        ]);
    }

    public function postMove(): void
    {
        $this->gateNoStudents();
        $this->validateCsrf();
        $placementId = (int) ($_POST['placement_id'] ?? 0);
        $toCellId = (int) ($_POST['to_cell_id'] ?? 0);
        $force = !empty($_POST['force']) && in_array(Auth::role(), ['admin', 'monitor'], true);
        $m = new TimetableBoardModel();
        $err = $m->tryMoveToEmpty($placementId, $toCellId, (int) Auth::id(), (string) Auth::role(), $force);
        (new AuditLog())->write(
            (int) Auth::id(),
            'tt_move',
            'timetable',
            $placementId,
            $err ?? 'ok'
        );
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            flash_set('success', 'Timetable updated.');
        }
        $this->backToBoard($this->requestedProgramIdFromRequest());
    }

    public function postSwap(): void
    {
        $this->gateNoStudents();
        $this->validateCsrf();
        $a = $this->parsePlacementId($_POST['placement_a_id'] ?? '');
        $b = $this->parsePlacementId($_POST['placement_b_id'] ?? '');
        if ($a < 1 || $b < 1) {
            flash_set('error', 'Enter two valid placement numbers (the digits from P# on the chips, or use “Put in A / B” on a chip).');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        if ($a === $b) {
            flash_set('error', 'Choose two different placements to swap.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $m = new TimetableBoardModel();
        $uid = (int) Auth::id();
        $role = (string) Auth::role();
        $forceSwap = in_array($role, ['admin', 'monitor', 'teacher'], true);

        if ($role === 'admin' || $role === 'monitor') {
            $err = $m->trySwapPlacements($a, $b, $uid, $role, $forceSwap);
            (new AuditLog())->write($uid, 'tt_swap', 'timetable', $a, $err ?? 'ok');
            if ($err !== null) {
                flash_set('error', $err);
            } else {
                flash_set('success', 'Swapped two slots.');
            }
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }

        if ($role === 'teacher') {
            $pa = $m->getPlacementById($a);
            $pb = $m->getPlacementById($b);
            if ($pa === false || $pb === false) {
                flash_set('error', 'Placement not found.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            $u1 = (int) $pa['user_id'];
            $u2 = (int) $pb['user_id'];
            if ($u1 !== $uid && $u2 !== $uid) {
                flash_set('error', 'Drag your own class block to swap, or use Assign teacher to change a slot you coordinate.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            $c1 = $m->getCell((int) $pa['cell_id']);
            $c2 = $m->getCell((int) $pb['cell_id']);
            if ($c1 === false || $c2 === false) {
                flash_set('error', 'Cell not found.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            if ((int) $c1['program_id'] !== (int) $c2['program_id']) {
                flash_set('error', 'Different program boards.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            $pid = (int) $c1['program_id'];
            $freeish = static function (string $cc): bool {
                $cc = trim($cc);
                return $cc === '' || $cc === 'Lib' || $cc === 'Tut';
            };
            $cc1 = (string) ($c1['course_code'] ?? '');
            $cc2 = (string) ($c2['course_code'] ?? '');
            $needsClassMonitor = $freeish($cc1) || $freeish($cc2) || $u1 === $u2;

            $sp = new TtSwapProposal();
            if ($sp->hasPendingForPair($a, $b)) {
                flash_set('error', 'A pending swap is already waiting for this pair. Approve or reject it on the board first.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            if (!$needsClassMonitor) {
                $assignee = $u1 === $uid ? $u2 : $u1;
            } else {
                $assignee = null;
            }
            $propId = $sp->create($pid, $a, $b, $uid, $assignee, $needsClassMonitor);
            (new AuditLog())->write($uid, 'tt_swap_req', 'tt_swap_proposals', $propId, null);
            if ($assignee !== null) {
                (new Notification())->create(
                    $assignee,
                    'timetable_swap',
                    'Timetable: swap to approve',
                    'A colleague asked to swap timetabled slots with you. Open the program timetable, review pending approvals, and accept or reject.'
                );
            } else {
                $msg = 'A teacher asked to change timetabled slots on a program board. Open Timetable, review “Pending slot swaps” for your department, and accept or reject.';
                foreach ((new User())->allByRole('admin') as $ar) {
                    (new Notification())->create((int) $ar['id'], 'timetable_swap', 'Timetable: swap to approve (shared slot)', $msg);
                }
                foreach ((new User())->allByRole('monitor') as $ar) {
                    (new Notification())->create((int) $ar['id'], 'timetable_swap', 'Timetable: slot swap (Lib/free)', $msg);
                }
            }
            flash_set('success', 'Swap request sent. The other party or a class monitor must approve it before the grid updates.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }

        flash_set('error', 'Unable to process swap for your role.');
        $this->backToBoard($this->requestedProgramIdFromRequest());
    }

    public function postSwapCells(): void
    {
        $this->gateNoStudents();
        $this->validateCsrf();
        $a = (int) ($_POST['cell_a_id'] ?? 0);
        $b = (int) ($_POST['cell_b_id'] ?? 0);
        if ($a < 1 || $b < 1 || $a === $b) {
            flash_set('error', 'Choose two different time slots to swap.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $uid = (int) Auth::id();
        $role = (string) Auth::role();
        if (!in_array($role, ['admin', 'monitor', 'teacher'], true)) {
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $force = $role === 'admin' || $role === 'monitor';
        if ($role === 'teacher') {
            $m0 = new TimetableBoardModel();
            $c = $m0->getCell($a);
            if ($c === false) {
                flash_set('error', 'Cell not found.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            $prg = $m0->queryOne('SELECT department_id FROM tt_programs WHERE id = ?', [(int) $c['program_id']]);
            $dId = $prg !== false && $prg['department_id'] !== null ? (int) $prg['department_id'] : 0;
            if ($dId < 1 || !(new ClassAssignment())->userIsAssignedClassMonitorInDepartment($uid, $dId)) {
                flash_set('error', 'Only a class monitor or administrator can drag subject (course) slots to swap times. Drag teacher blocks to request a swap with a colleague instead.');
                $this->backToBoard($this->requestedProgramIdFromRequest());
            }
            $force = true;
        }
        $m = new TimetableBoardModel();
        $err = $m->tryExchangeCellTimeSlots($a, $b, $uid, $role, $force);
        (new AuditLog())->write($uid, 'tt_swap_cells', 'timetable', $a, $err ?? 'ok');
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            flash_set('success', 'The two time slots and their course labels were exchanged.');
        }
        $this->backToBoard($this->requestedProgramIdFromRequest());
    }

    public function postSwapProposalResolve(): void
    {
        $this->gateNoStudents();
        $this->validateCsrf();
        $pid = (int) ($_POST['return_program_id'] ?? 0) ?: (int) ($_GET['program_id'] ?? 0);
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);
        $act = (string) ($_POST['action'] ?? '');
        if ($proposalId < 1) {
            flash_set('error', 'Invalid request.');
            $this->backToBoard($pid);
        }
        $sp = new TtSwapProposal();
        $p = $sp->getPendingById($proposalId);
        if ($p === false) {
            flash_set('error', 'This proposal is not pending or no longer exists.');
            $this->backToBoard($pid);
        }
        $uid = (int) Auth::id();
        $role = (string) Auth::role();
        $m = new TimetableBoardModel();
        $deptId = 0;
        $prgRow = $m->queryOne('SELECT department_id FROM tt_programs WHERE id = ?', [(int) $p['program_id']]);
        if ($prgRow !== false && $prgRow['department_id'] !== null) {
            $deptId = (int) $prgRow['department_id'];
        }
        $isClassMonitor = $deptId > 0
            && $role === 'teacher'
            && (new ClassAssignment())->userIsAssignedClassMonitorInDepartment($uid, $deptId);
        if ((int) $p['requester_user_id'] === $uid && $role !== 'admin') {
            flash_set('error', 'You cannot approve or reject your own swap request. Ask a colleague, class monitor, or admin.');
            $this->backToBoard($pid);
        }
        $allowed = $role === 'admin'
            || (int) $p['assignee_user_id'] === $uid
            || ((int) $p['needs_class_monitor'] === 1 && $isClassMonitor)
            || ((int) $p['needs_class_monitor'] === 1 && in_array($role, ['monitor'], true));
        if (!$allowed) {
            flash_set('error', 'You are not allowed to respond to this swap request.');
            $this->backToBoard($pid);
        }
        if ($act === 'reject') {
            $sp->resolve($proposalId, $uid, 'rejected');
            (new AuditLog())->write($uid, 'tt_swap_reject', 'tt_swap_proposals', $proposalId, null);
            flash_set('success', 'Swap request rejected.');
            $this->backToBoard($pid);
        }
        if ($act === 'approve') {
            $a = (int) $p['placement_a_id'];
            $b = (int) $p['placement_b_id'];
            $err = $m->trySwapPlacements($a, $b, $uid, $role, true);
            (new AuditLog())->write($uid, 'tt_swap_approve', 'timetable', $a, $err ?? 'ok');
            if ($err !== null) {
                flash_set('error', $err);
                $this->backToBoard($pid);
            }
            $sp->resolve($proposalId, $uid, 'approved');
            (new Notification())->create((int) $p['requester_user_id'], 'timetable_swap', 'Timetable swap approved', 'Your timetabled slot swap was approved and applied.');
            flash_set('success', 'Swap completed.');
            $this->backToBoard($pid);
        }
        flash_set('error', 'Invalid action.');
        $this->backToBoard($pid);
    }

    public function postExchange(): void
    {
        $this->gateNoStudents();
        if (Auth::role() !== 'teacher') {
            flash_set('error', 'Only teachers use this form. Admins and monitors can swap in the staff section.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $this->validateCsrf();
        $fromUser = (int) Auth::id();
        $toUser = (int) ($_POST['to_user_id'] ?? 0);
        $fromCell = (int) ($_POST['from_cell_id'] ?? 0);
        $toCell = (int) ($_POST['to_cell_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));
        $m = new TimetableBoardModel();
        if ($toUser === 0 || $fromCell === 0 || $toCell === 0) {
            flash_set('error', 'Fill in all fields.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $cFrom = $m->getCell($fromCell);
        $pid = $cFrom !== false ? (int) $cFrom['program_id'] : $m->activeProgramId();
        $pFrom = $m->getPlacementOnCell($fromCell);
        $pTo = $m->getPlacementOnCell($toCell);
        if ($pFrom === false || (int) $pFrom['user_id'] !== $fromUser) {
            flash_set('error', 'You must own the “from” slot placement.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        if ($pTo === false || (int) $pTo['user_id'] !== $toUser) {
            flash_set('error', 'The other teacher must be placed on the target slot.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $id = $m->createExchangeRequest($pid, $fromUser, $toUser, $fromCell, $toCell, $message !== '' ? $message : null);
        (new AuditLog())->write($fromUser, 'tt_exchange_req', 'tt_exchange_requests', $id, null);
        (new Notification())->create($toUser, 'timetable_exchange', 'Timetable exchange', 'A colleague asked to exchange teaching slots. Open Timetable (interactive) to see pending items.');
        foreach ((new User())->allByRole('admin') as $a) {
            (new Notification())->create((int) $a['id'], 'timetable_exchange', 'Timetable exchange', 'A slot exchange is pending your/monitor review.');
        }
        flash_set('success', 'Exchange request submitted. A monitor or administrator can approve it.');
        $this->backToBoard($this->requestedProgramIdFromRequest());
    }

    public function postPlacementResponse(): void
    {
        $this->gateNoStudents();
        $this->validateCsrf();
        $rid = (int) ($_POST['return_program_id'] ?? 0) ?: (int) ($_GET['program_id'] ?? 0);
        $placementId = (int) ($_POST['placement_id'] ?? 0);
        $action = (string) ($_POST['slot_action'] ?? '');
        $reason = isset($_POST['decline_reason']) && $_POST['decline_reason'] !== '' ? (string) $_POST['decline_reason'] : null;
        $note = isset($_POST['decline_note']) ? trim((string) $_POST['decline_note']) : null;
        if ($placementId < 1) {
            flash_set('error', 'Invalid slot.');
            $this->backToBoard($rid);
        }
        $m = new TimetableBoardModel();
        $err = $m->setPlacementResponse(
            $placementId,
            (int) Auth::id(),
            (string) Auth::role(),
            $action,
            $reason,
            $note
        );
        (new AuditLog())->write(
            (int) Auth::id(),
            'tt_slot_' . $action,
            'tt_placements',
            $placementId,
            $err ?? 'ok'
        );
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            flash_set('success', 'Teaching slot status updated.');
        }
        if ($action === 'decline') {
            $n = 'A teacher reported they cannot take a class on the program timetable. Review the board for details.';
            foreach ((new User())->allByRole('admin') as $a) {
                (new Notification())->create((int) $a['id'], 'timetable_decline', 'Slot declined', $n);
            }
            foreach ((new User())->allByRole('monitor') as $a) {
                (new Notification())->create((int) $a['id'], 'timetable_decline', 'Slot declined', $n);
            }
        }
        $this->backToBoard($rid);
    }

    public function postExchangeResolve(): void
    {
        $this->requireRoles(['admin', 'monitor']);
        $this->validateCsrf();
        $eid = (int) ($_POST['exchange_id'] ?? 0);
        $act = (string) ($_POST['action'] ?? '');
        $m = new TimetableBoardModel();
        $err = $m->resolveExchange($eid, (int) Auth::id(), (string) Auth::role(), $act === 'approve' ? 'approve' : 'reject');
        (new AuditLog())->write((int) Auth::id(), 'tt_exchange_' . $act, 'tt_exchange_requests', $eid, $err);
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            flash_set('success', 'Request updated.');
        }
        $this->backToBoard($this->requestedProgramIdFromRequest());
    }

    public function postAssignTeacher(): void
    {
        $this->gateNoStudents();
        $this->validateCsrf();
        $cellId = (int) ($_POST['cell_id'] ?? 0);
        $userId = (int) ($_POST['teacher_user_id'] ?? 0);
        if ($cellId < 1) {
            flash_set('error', 'Invalid cell.');
            $this->backToBoard($this->requestedProgramIdFromRequest());
        }
        $m = new TimetableBoardModel();
        $err = $m->assignUserToCell($cellId, $userId, (int) Auth::id(), (string) Auth::role());
        (new AuditLog())->write(
            (int) Auth::id(),
            'tt_assign_teacher',
            'tt_placements',
            $cellId,
            $err ?? 'ok'
        );
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            flash_set('success', $userId < 1 ? 'Slot cleared.' : 'Teacher assigned. They can now be dragged with this course on the chip.');
        }
        $this->backToBoard($this->requestedProgramIdFromRequest());
    }
}
