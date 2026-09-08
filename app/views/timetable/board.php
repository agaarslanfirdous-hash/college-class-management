<?php
/**
 * Program timetable board. Staff: interactive. Students: same grid, read-only.
 *
 * @var bool $no_program
 * @var int $program_id
 * @var list<array<string,mixed>> $programs
 * @var list<array<string,mixed>> $periods
 * @var array<int, array<int, array<string,mixed>>> $by_day
 * @var list<string> $teach_codes
 * @var list<array<string,mixed>> $my_placements
 * @var list<array<string,mixed>> $all_teachers
 * @var list<array<string,mixed>> $exchanges
 * @var list<array<string,mixed>> $board_codes
 * @var bool $is_dept_class_monitor
 * @var string $role
 * @var bool $board_read_only
 * @var list<array<string,mixed>> $swap_proposals
 */
declare(strict_types=1);
use App\Core\Auth;
use App\Core\CSRF;
$readOnly = (bool)($board_read_only ?? false);
$canDragBoard = in_array($role, ['admin', 'monitor', 'teacher'], true) && !$readOnly;
$swapProposals = $swap_proposals ?? [];
$uidB = (int) Auth::id();
$dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
$programs = $programs ?? [];
$isDeptClassMonitor = (bool) ($is_dept_class_monitor ?? false);
$pidRet = (int) ($program_id ?? 0);
require __DIR__ . '/../partials/header.php';
if (!empty($no_program)) { ?>
    <h1 class="h3">Program timetable</h1>
    <p class="text-muted">No program loaded. From the project root run:</p>
    <pre class="bg-body-secondary p-3 small">php scripts/apply_timetable_migrations.php</pre>
    <?php require __DIR__ . '/../partials/footer.php';
    return;
}
?>
<link rel="stylesheet" href="<?= \base_url('assets/css/timetable-board.css') ?>">
<div class="tt-page">
    <div class="tt-hero p-3 p-md-4 mb-3 rounded-3">
        <h1 class="h3 text-white mb-0">Program timetable <span class="tt-hero__sub"><?= $readOnly ? '(view only — same program board as staff)' : '(interactive — SKUAST-style boards)' ?></span></h1>
        <p class="tt-hero__lead text-white-50 small mb-0"><?php if ($readOnly): ?>
            <strong>View only.</strong> This is the same department timetable your coordinators manage. You cannot change slots, drag blocks, or assign staff. Use the list below to switch <strong>department / program</strong> if your school uses more than one board.
        <?php else: ?>
            Click and <strong>hold the coloured block</strong>, <strong>drag</strong> it, then <strong>release</strong> on <strong>another</strong> block to <strong>swap</strong> time slots, or on an <strong>open</strong> cell to <strong>move</strong> (use a mouse; touch screens may be unreliable). Staff: admin, <strong>Monitor</strong>, <strong>teachers</strong>, class monitors. Pick a department below.
        <?php endif; ?></p>
    </div>
    <?php if ($programs !== []): ?>
        <form class="row g-2 align-items-end mb-3" method="get" action="<?= \base_url('timetable-board') ?>" id="form-tt-program">
            <div class="col-md-7">
                <label class="form-label tt-label" for="tt-select-program">Department / program</label>
                <select class="form-select tt-input" name="program_id" id="tt-select-program" onchange="this.form.submit()">
                    <?php foreach ($programs as $prg): ?>
                        <option value="<?= (int) $prg['id'] ?>"<?= (int) $prg['id'] === (int) $program_id ? ' selected' : '' ?>>
                            <?= $prg['department_name'] !== null && $prg['department_name'] !== '' ? \e((string) $prg['department_name']) . ' — ' : '' ?><?= \e((string) $prg['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5 small text-secondary">Faculties match common SKUAST-K units. Manage full lists under Admin → Departments.</div>
        </form>
    <?php endif; ?>
    <p class="tt-legend"><?php if ($readOnly): ?>
        Blocks show <strong>course</strong> and <strong>teacher</strong> for each slot. <code>P#</code> and <code>C#</code> are reference numbers only. Editing, swapping, and assignments are done by staff.
    <?php else: ?>
        Drag <strong>your</strong> name block to an <strong>open</strong> cell for a course you teach, or drop it on <strong>another teacher’s block</strong> to <strong>request a slot swap</strong> (the other teacher or a monitor must approve). Each block is the <strong>subject + teacher</strong> chip. Use <em>Class commitment</em> to confirm or decline a slot.
    <?php endif; ?></p>
    <?php if (!$readOnly && in_array($role, ['admin', 'monitor', 'teacher'], true)): ?>
        <p class="small text-dark tt-strip py-1 px-2 rounded"><strong>Who can drag-swap or move:</strong> administrators, <strong>Monitor</strong> role, <strong>teachers</strong>, and assigned <strong>class monitors</strong> (Admin → Assignments). <code>P#</code> / <code>C#</code> on each block are reference ids only — swapping is by drag and drop.</p>
        <?php if ($isDeptClassMonitor) : ?><p class="small text-success mb-0">You are an <strong>assigned class monitor</strong> for a class in this department—use the same tools to rearrange who teaches which slot.</p><?php endif; ?>
    <?php endif; ?>
    <?php
    $boardCodes = $board_codes ?? [];
    if ($boardCodes !== []) :
    ?>
    <details class="tt-ref mb-2 p-2 border rounded bg-light small">
        <summary class="fw-semibold cursor-pointer">Course codes on <em>this</em> program board</summary>
        <p class="text-muted mb-1">Each cell’s subject comes from this list. Swaps: drag a chip onto another chip. Classes from Admin → Classes are linked to these course codes where applicable.</p>
        <ul class="list-group list-group-flush">
            <?php foreach ($boardCodes as $bc) : ?>
                <li class="list-group-item d-flex flex-wrap justify-content-between gap-2 py-1 px-0">
                    <code class="tt-ref__code mb-0"><?= \e((string) $bc['course_code']) ?></code>
                    <span class="text-muted text-truncate" style="max-width:40rem" title="<?= \e((string) ($bc['course_name'] ?? '')) ?>"><?= \e((string) ($bc['course_name'] ?? '')) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </details>
    <?php endif; ?>
    <div class="tt-board-wrap mb-4" id="tt-board">
        <table class="tt-grid w-100" id="tt-grid" aria-label="Weekly timetable">
            <thead>
                <tr>
                    <th class="tt-time" scope="col">Time / Day</th>
                    <?php $di = 0; foreach ($dayNames as $dnum => $dn) : $di++; ?>
                        <th class="tt-day-col tt-day-<?= (int) $dnum ?>" data-day="<?= (int) $dnum ?>" scope="col"><?= \e($dn) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($periods as $p) :
                $pi = (int) $p['period_index'];
                $label = \e((string) $p['label'] . ' ' . (string) $p['time_range']);
                ?>
                <tr>
                    <th class="tt-time" scope="row"><?= $label ?></th>
                    <?php
                    for ($d = 1; $d <= 5; $d++) {
                        $cell = $by_day[$d][$pi] ?? null;
                        if ($cell === null) {
                            echo '<td class="tt-cell tt-cell--empty">—</td>';
                            continue;
                        }
                        $cc = $cell['course_code'] ? (string) $cell['course_code'] : '';
                        $isEmpty = $cc === '';
                        $plm = (int) ($cell['placement_id'] ?? 0);
                        $placedUid = (int) ($cell['placed_user_id'] ?? 0);
                        $dropId = (int) $cell['id'];
                        $slotOpen = (int) $plm === 0 && !$isEmpty;
                        $class = 'tt-cell tt-drop' . ($isEmpty ? ' tt-cell--empty' : '');
                        $canDrop = false;
                        if (!$readOnly && $slotOpen) {
                            if (in_array($role, ['admin', 'monitor'], true)) {
                                $canDrop = true;
                            } elseif ($role === 'teacher' && in_array($cc, $teach_codes, true)) {
                                $canDrop = true;
                            }
                        }
                        $av = (string) ($cell['availability_status'] ?? 'confirmed');
                        if ($plm < 1 && $av === 'declined') {
                            $av = 'confirmed';
                        }
                        $declR = (string) ($cell['decline_reason'] ?? '');
                        $declN = (string) ($cell['decline_note'] ?? '');
                        echo '<td class="' . $class . '" data-cell-id="' . $dropId . '" data-course="' . \e($cc) . '" data-free="' . ($slotOpen ? '1' : '0') . '">';
                        if ($plm < 1) {
                            if ($cc !== '') {
                                $canDragSubject = $canDragBoard && (in_array($role, ['admin', 'monitor'], true) || $isDeptClassMonitor);
                                if ($canDragSubject) {
                                    echo '<div class="tt-swap" draggable="true" data-swap-cell="' . (int) $dropId . '" title="Drag to swap this subject block with another time slot">';
                                }
                                echo '<div class="tt-course">' . \e($cc) . '</div>';
                                if ($canDragSubject) {
                                    echo '</div>';
                                }
                            }
                            if (!empty($cell['course_name'])) {
                                echo '<div class="t-name small text-muted text-truncate" style="max-width:7rem" title="' . \e((string) $cell['course_name']) . '">' . \e((string) $cell['course_name']) . '</div>';
                            }
                        }
                        if (!empty($cell['venue'])) {
                            echo '<div class="tt-venue">' . \e((string) $cell['venue']) . '</div>';
                        }
                        if (!empty($cell['display_note'])) {
                            echo '<div class="t-note">' . \e((string) $cell['display_note']) . '</div>';
                        }
                        if ($plm > 0) {
                            $isMine = $placedUid === (int) Auth::id();
                            $chClass = 'tt-chip' . ($isMine ? ' tt-mine' : '');
                            if ($av === 'pending') {
                                $chClass .= ' tt-ch--pending';
                            }
                            if ($av === 'declined') {
                                $chClass .= ' tt-ch--declined';
                            }
                            if ($av === 'confirmed') {
                                $chClass .= ' tt-ch--confirmed';
                            }
                            $titleAv = 'Commitment: ' . $av;
                            if ($declR !== '') {
                                $titleAv .= ' · ' . $declR;
                            }
                            if ($declN !== '') {
                                $titleAv .= ' — ' . $declN;
                            }
                            $cn = !empty($cell['course_name']) ? (string) $cell['course_name'] : '';
                            $tname = (string) ($cell['placed_name'] ?? '?');
                            $lineSubject = $cn !== '' ? $cn : ($cc !== '' ? $cc : '—');
                            $drAttr = $readOnly ? 'false' : 'true';
                            if ($role === 'teacher' && ! $isMine) {
                                $drAttr = 'false';
                            }
                            echo '<div class="tt-chips"><div class="' . $chClass . '" draggable="' . $drAttr . '" title="' . \e($titleAv) . '" data-availability="' . \e($av) . '" data-placement-id="' . $plm . '" data-user-id="' . $placedUid . '" data-cell-id="' . $dropId . '">';
                            echo '<div class="tt-chip__line tt-chip__subject" title="Subject on this cell">' . \e($lineSubject) . '</div>';
                            if ($cc !== '' && $cc !== $lineSubject) {
                                echo '<div class="tt-chip__line tt-chip__code text-white-50 small"><span class="badge bg-light text-dark">' . \e($cc) . '</span></div>';
                            }
                            echo '<div class="tt-chip__line tt-chip__teacher" title="Teacher (draggable with this subject)">' . \e($tname) . '</div>';
                            echo '<div class="tt-chip__ids">P#' . (int) $plm . ' · C#' . (int) $dropId . '</div>';
                            echo '</div></div>';
                            $canChangeAssign = in_array($role, ['admin', 'monitor'], true) || ($role === 'teacher' && $isMine);
                            if (!$readOnly && $canChangeAssign && $cc !== '' && $cc !== 'Lib' && $cc !== 'Tut') {
                                echo '<div class="tt-assign"><button type="button" class="btn btn-sm btn-link btn-assign-tt p-0 text-decoration-none small" data-cell-id="' . (int) $dropId . '" data-current-uid="' . (int) $placedUid . '" data-subject="' . \e($lineSubject . ' (' . $cc . ')') . '">Change teacher</button></div>';
                            }
                            if (!$readOnly && $isMine && $role === 'teacher' && in_array($av, ['pending', 'declined'], true)) {
                                echo '<div class="tt-actions mt-1 d-flex flex-wrap gap-1">';
                                echo '<form class="d-inline" method="post" action="' . \base_url('timetable-board/placement-respond') . '">';
                                echo CSRF::field() . '<input type="hidden" name="return_program_id" value="' . $pidRet . '"><input type="hidden" name="placement_id" value="' . $plm . '"><input type="hidden" name="slot_action" value="confirm"><button type="submit" class="btn btn-sm btn-success">I can teach this</button></form>';
                                echo '<button type="button" class="btn btn-sm btn-outline-danger btn-tt-decline" data-pid="' . (int) $plm . '">Cannot (reason)…</button></div>';
                            } elseif (!$readOnly && $isMine && $role === 'teacher' && $av === 'confirmed') {
                                echo '<div class="tt-actions mt-1"><button type="button" class="btn btn-sm btn-outline-secondary btn-tt-decline" data-pid="' . (int) $plm . '">Change to unavailable…</button></div>';
                            } elseif (!$readOnly && in_array($role, ['admin', 'monitor', 'teacher'], true) && $plm > 0 && !($role === 'teacher' && $isMine)) {
                                echo '<div class="tt-actions mt-1 d-flex flex-wrap gap-1 small">';
                                if (in_array($av, ['pending', 'declined'], true)) {
                                    echo '<form class="d-inline" method="post" action="' . \base_url('timetable-board/placement-respond') . '">' . CSRF::field() . '<input type="hidden" name="return_program_id" value="' . $pidRet . '"><input type="hidden" name="placement_id" value="' . $plm . '"><input type="hidden" name="slot_action" value="confirm"><button type="submit" class="btn btn-sm btn-outline-primary">Set confirmed</button></form>';
                                }
                                if (in_array($av, ['confirmed', 'declined', 'pending'], true)) {
                                    echo '<form class="d-inline" method="post" onsubmit="return confirm(\'Reset to pending for this slot?\');" action="' . \base_url('timetable-board/placement-respond') . '">' . CSRF::field() . '<input type="hidden" name="return_program_id" value="' . $pidRet . '"><input type="hidden" name="placement_id" value="' . $plm . '"><input type="hidden" name="slot_action" value="reset"><button type="submit" class="btn btn-sm btn-outline-dark">Reset pending</button></form>';
                                }
                                if ($av === 'declined' && $declR !== '') {
                                    echo '<span class="badge text-bg-light border">' . \e($declR) . '</span>';
                                }
                                echo '</div>';
                            }
                        }
                        if ($plm < 1 && ! $isEmpty && !$readOnly && in_array($role, ['admin', 'monitor'], true) && $cc !== '' && $cc !== 'Lib' && $cc !== 'Tut') {
                            $ls = !empty($cell['course_name']) ? (string) $cell['course_name'] : $cc;
                            echo '<div class="tt-assign"><button type="button" class="btn btn-sm btn-link btn-assign-tt p-0 text-decoration-none small" data-cell-id="' . (int) $dropId . '" data-current-uid="0" data-subject="' . \e($ls . ' (' . $cc . ')') . '">Assign teacher to this slot</button></div>';
                        } elseif ($canDrop) {
                            echo '<div class="small text-white-50 tt-hint">Drop</div>';
                        }
                        echo '</td>';
                    }
                    ?>
                </tr>
            <?php if ($pi === 4) { ?>
                <tr><th class="tt-time tt-cell--lunch" scope="row">Lunch</th><td class="tt-cell--lunch" colspan="5"><strong>Break</strong> · as per campus</td></tr>
            <?php } ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly) : ?>
    <form id="form-move" method="post" action="<?= \base_url('timetable-board/move') ?>" class="d-none">
        <?= CSRF::field() ?>
        <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
        <input type="hidden" name="placement_id" id="move-placement-id" value="">
        <input type="hidden" name="to_cell_id" id="move-to-cell" value="">
        <?php if (in_array($role, ['admin', 'monitor'], true)): ?>
            <input type="hidden" name="force" value="1">
        <?php endif; ?>
    </form>
    <form id="form-swap" method="post" action="<?= \base_url('timetable-board/swap') ?>" class="d-none">
        <?= CSRF::field() ?>
        <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
        <input type="hidden" name="placement_a_id" id="swap-a" value="">
        <input type="hidden" name="placement_b_id" id="swap-b" value="">
    </form>
    <form id="form-swap-cells" method="post" action="<?= \base_url('timetable-board/swap-cells') ?>" class="d-none">
        <?= CSRF::field() ?>
        <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
        <input type="hidden" name="cell_a_id" id="swap-cells-a" value="">
        <input type="hidden" name="cell_b_id" id="swap-cells-b" value="">
    </form>
    <div class="modal fade" id="ttModalDecline" tabindex="-1" aria-labelledby="ttModalDeclineLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= \base_url('timetable-board/placement-respond') ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="ttModalDeclineLabel">Mark slot unavailable</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
                    <input type="hidden" name="placement_id" id="tt-decline-pid" value="">
                    <input type="hidden" name="slot_action" value="decline">
                    <div class="mb-2">
                        <label class="form-label" for="tt-reason">Reason</label>
                        <select class="form-select" name="decline_reason" id="tt-reason" required>
                            <option value="">— Choose —</option>
                            <option value="personal">Personal</option>
                            <option value="meeting">Meeting / committee</option>
                            <option value="leave">On leave / medical</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="tt-note">Details (optional)</label>
                        <textarea class="form-control" name="decline_note" id="tt-note" rows="2" maxlength="500" placeholder="Short context for admin / monitor…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save &amp; mark declined</button>
                </div>
            </form>
        </div>
    </div>
    <?php if (in_array($role, ['admin', 'monitor', 'teacher'], true) && !empty($all_teachers)): ?>
    <div class="modal fade" id="ttModalAssign" tabindex="-1" aria-labelledby="ttModalAssignLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= \base_url('timetable-board/assign-teacher') ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="ttModalAssignLabel">Assign teacher to slot</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
                    <input type="hidden" name="cell_id" id="tt-assign-cell-id" value="">
                    <p class="text-muted small mb-2" id="tt-assign-subject-line" aria-live="polite">—</p>
                    <div class="mb-0">
                        <label class="form-label" for="tt-assign-teacher-id">Teacher</label>
                        <select class="form-select" name="teacher_user_id" id="tt-assign-teacher-id">
                            <option value="0">— Clear assignment —</option>
                            <?php foreach ($all_teachers as $t) : ?>
                                <option value="<?= (int) $t['id'] ?>"><?= \e((string) $t['full_name']) ?><?= !empty($t['email']) ? ' — ' . \e((string) $t['email']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; /* !$readOnly */ ?>
    <?php if (!$readOnly && $role === 'teacher'): ?>
        <h2 class="h5 mt-4">Exchange with another teacher</h2>
        <p class="small">Use when the target slot is taken. A monitor or admin must approve the swap of names on the same board you selected above.</p>
        <form method="post" action="<?= \base_url('timetable-board/exchange') ?>" class="row g-2 mb-4">
            <?= CSRF::field() ?>
            <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
            <div class="col-md-4">
                <label class="form-label" for="to_user_id">Colleague</label>
                <select class="form-select" name="to_user_id" id="to_user_id" required>
                    <option value="">— Select —</option>
                    <?php foreach ($all_teachers as $t) : if ((int) $t['id'] === (int) Auth::id()) { continue; } ?>
                        <option value="<?= (int) $t['id'] ?>"><?= \e((string) $t['full_name']) ?> (<?= \e((string) $t['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ex-from-cell">Your cell ID</label>
                <input class="form-control" name="from_cell_id" id="ex-from-cell" type="number" min="1" required placeholder="From grid">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ex-to-cell">Their cell ID</label>
                <input class="form-control" name="to_cell_id" id="ex-to-cell" type="number" min="1" required placeholder="Target">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="ex-msg">Message (optional)</label>
                <input class="form-control" name="message" id="ex-msg" type="text" placeholder="e.g. lab duty swap">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Request exchange</button>
            </div>
        </form>
    <?php endif; ?>
    <?php if (!$readOnly && $swapProposals !== []) : ?>
        <h2 class="h5">Pending slot swaps (teacher / class monitor)</h2>
        <p class="small text-muted">Teachers: swapping two teaching blocks <strong>requires the other teacher’s approval</strong> (or a class monitor’s if a Lib/free-style slot is involved). Administrators and the Monitor role can still swap without this queue on their account.</p>
        <ul class="list-group mb-4">
            <?php foreach ($swapProposals as $sp) :
                $assignee = $sp['assignee_user_id'] !== null && $sp['assignee_user_id'] !== '' ? (int) $sp['assignee_user_id'] : 0;
                $needM = (int) ($sp['needs_class_monitor'] ?? 0) === 1;
                $reqId = (int) $sp['requester_user_id'];
                $isReq = (int) $sp['requester_user_id'] === $uidB;
                $canResolve = ($isReq && $role !== 'admin') ? false : (in_array($role, ['admin'], true)
                    || $assignee === $uidB
                    || ($needM && in_array($role, ['monitor'], true))
                    || ($needM && $role === 'teacher' && $isDeptClassMonitor));
                ?>
                <li class="list-group-item d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <div>
                        <span class="text-muted">P#<?= (int) $sp['placement_a_id'] ?> ↔ P#<?= (int) $sp['placement_b_id'] ?></span>
                        <span class="d-block small">Requester: <?= \e((string) ($sp['requester_name'] ?? '')) ?>
                        <?php if ($assignee > 0) : ?>
                            · Awaiting: <?= \e((string) ($sp['assignee_name'] ?? 'teacher')) ?>
                        <?php elseif ($needM) : ?>
                            · Awaiting: <strong>class monitor</strong> or <strong>Monitor</strong> / admin
                        <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($canResolve) : ?>
                        <form method="post" action="<?= \base_url('timetable-board/swap-proposal') ?>" class="d-flex gap-1">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
                            <input type="hidden" name="proposal_id" value="<?= (int) $sp['id'] ?>">
                            <button class="btn btn-sm btn-success" type="submit" name="action" value="approve">Approve &amp; apply</button>
                            <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="reject">Reject</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (!$readOnly && !empty($exchanges) && in_array($role, ['admin', 'monitor', 'teacher'], true)) : ?>
        <h2 class="h5">Pending exchange requests</h2>
        <ul class="list-group mb-4">
            <?php foreach ($exchanges as $x) : ?>
                <li class="list-group-item d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <div>
                        <strong><?= \e((string) $x['from_name']) ?></strong>
                        (<?= \e((string) $x['from_code']) ?>) &harr;
                        <strong><?= \e((string) $x['to_name']) ?></strong>
                        (<?= \e((string) $x['to_code']) ?>)
                        <?php if (!empty($x['message'])): ?><span class="text-muted d-block small"><?= \e((string) $x['message']) ?></span><?php endif; ?>
                    </div>
                    <?php if (in_array($role, ['admin', 'monitor'], true)): ?>
                        <form method="post" action="<?= \base_url('timetable-board/exchange/resolve') ?>" class="d-flex gap-1">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="return_program_id" value="<?= $pidRet ?>">
                            <input type="hidden" name="exchange_id" value="<?= (int) $x['id'] ?>">
                            <button class="btn btn-sm btn-success" name="action" value="approve" type="submit">Approve</button>
                            <button class="btn btn-sm btn-outline-danger" name="action" value="reject" type="submit">Reject</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <p class="small text-secondary"><strong>On each block:</strong> <code>P#</code> = placement, <code>C#</code> = cell (<?= $readOnly ? 'for staff reference' : 'for the exchange form / support if needed' ?>).<?php if (!$readOnly) : ?> <strong>Swap = drag a chip onto another</strong> or an empty cell.<?php endif; ?> <a class="tt-link" href="https://skuastkashmir.ac.in" rel="noopener" target="_blank">SKUAST-K</a></p>
</div>
<?php if (!$readOnly) : ?>
<script>
(function() {
  var formMove = document.getElementById('form-move');
  var formSwap = document.getElementById('form-swap');
  var pInp = document.getElementById('move-placement-id');
  var toC = document.getElementById('move-to-cell');
  var sA = document.getElementById('swap-a');
  var sB = document.getElementById('swap-b');
  var isBoardManager = <?= $canDragBoard ? 'true' : 'false' ?>;
  var isAdminLike = <?= in_array($role, ['admin', 'monitor'], true) ? 'true' : 'false' ?>;
  var isTeacher = <?= $role === 'teacher' ? 'true' : 'false' ?>;
  var canSwapCells = <?= (in_array($role, ['admin', 'monitor'], true) || $isDeptClassMonitor) && !$readOnly ? 'true' : 'false' ?>;
  var myUid = <?= (int) Auth::id() ?>;
  var board = document.getElementById('tt-board') || document.querySelector('.tt-board-wrap');
  var drag = null;
  var DND = 'ttd:';
  var DCELL = 'ttc:';
  var formSwapCells = document.getElementById('form-swap-cells');
  var cellAInp = document.getElementById('swap-cells-a');
  var cellBInp = document.getElementById('swap-cells-b');
  var markCell = function(cell) {
    document.querySelectorAll('.tt-drop.drop-target').forEach(function (x) { x.classList.remove('drop-target'); });
    if (cell) cell.classList.add('drop-target');
  };
  function readDragPayload(e) {
    if (drag && (drag.placement || drag.kind === 'cell')) { return drag; }
    if (e && e.dataTransfer) {
      var t = e.dataTransfer.getData('text/plain') || '';
      if (t.indexOf(DCELL) === 0) { return { kind: 'cell', cell: t.slice(DCELL.length) }; }
      if (t.indexOf(DND) === 0) {
        var rest = t.slice(DND.length);
        var c = rest.indexOf(':');
        if (c >= 0) {
          return { kind: 'pl', placement: rest.slice(0, c), user: rest.slice(c + 1) || '0' };
        }
      }
    }
    return null;
  }
  if (board) {
    board.addEventListener('dragstart', function (e) {
      var sc = e.target && e.target.closest && e.target.closest('.tt-swap[draggable]');
      if (sc) {
        document.body.classList.add('tt-dnd-active');
        var cid = sc.getAttribute('data-swap-cell') || '';
        drag = { kind: 'cell', cell: cid };
        if (e.dataTransfer) {
          e.dataTransfer.setData('text/plain', DCELL + String(cid));
          e.dataTransfer.effectAllowed = 'move';
        }
        return;
      }
      var ch = e.target && e.target.closest && e.target.closest('.tt-chip[draggable]');
      if (!ch) return;
      document.body.classList.add('tt-dnd-active');
      drag = { kind: 'pl', placement: ch.getAttribute('data-placement-id') || '', user: ch.getAttribute('data-user-id') || '0' };
      if (e.dataTransfer) {
        e.dataTransfer.setData('text/plain', DND + String(drag.placement) + ':' + String(drag.user));
        e.dataTransfer.effectAllowed = 'move';
      }
    }, true);
    document.addEventListener('dragover', function (e) {
      if (!document.body.classList.contains('tt-dnd-active') || !board) return;
      var cell = e.target && e.target.closest && e.target.closest('td.tt-drop');
      if (cell && board.contains(cell)) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        markCell(cell);
      }
    }, true);
    board.addEventListener('dragend', function () {
      window.setTimeout(function () {
        document.body.classList.remove('tt-dnd-active');
        markCell(null);
        drag = null;
      }, 0);
    });
    board.addEventListener('dragleave', function (e) {
      var cell = e.target.closest && e.target.closest('.tt-drop');
      if (!cell) return;
      if (e.relatedTarget && cell.contains(e.relatedTarget)) return;
      cell.classList.remove('drop-target');
    });
    board.addEventListener('drop', function (e) {
      e.preventDefault();
      var d = readDragPayload(e);
      if (!d) { return; }
      var cell = e.target.closest && e.target.closest('.tt-drop');
      if (!cell || !board.contains(cell)) { return; }
      cell.classList.remove('drop-target');
      var targetCell = cell.getAttribute('data-cell-id');
      if (d.kind === 'cell' && d.cell && canSwapCells) {
        var t = targetCell || '';
        if (t && d.cell !== t && cellAInp && cellBInp && formSwapCells) {
          cellAInp.value = d.cell;
          cellBInp.value = t;
          if (window.confirm('Swap the subject/course for these two time slots? Teachers stay linked to the same block unless you change them separately.')) {
            formSwapCells.requestSubmit ? formSwapCells.requestSubmit() : formSwapCells.submit();
          }
        }
        return;
      }
      if (!d.placement) { return; }
      var free = cell.getAttribute('data-free') === '1';
      var ochip = cell.querySelector('.tt-chip[draggable]');
      if (isBoardManager) {
        if (ochip && sA && sB && formSwap) {
          sA.value = d.placement;
          sB.value = ochip.getAttribute('data-placement-id') || '';
          if (sA.value && sB.value && sA.value !== sB.value) { formSwap.requestSubmit ? formSwap.requestSubmit() : formSwap.submit(); }
        } else if (free && pInp && toC && formMove) {
          pInp.value = d.placement;
          toC.value = targetCell;
          formMove.requestSubmit ? formMove.requestSubmit() : formMove.submit();
        } else {
          window.alert(isAdminLike
            ? 'Drop on another class block to swap, or on an open teaching cell to move.'
            : (isTeacher
              ? 'Drop your block on a colleague’s block to request a swap, or on an open cell (course you teach) to move. Ask admin to add the course to your list if a cell won’t take your drop.'
              : 'You cannot move that slot this way.'));
        }
        return;
      }
      if (parseInt(d.user, 10) !== myUid) { window.alert('You can only drag your own class block on this board.'); return; }
      if (ochip) { window.alert('This slot is taken. Drop on the other teacher’s block above to request a swap, or use “Exchange with another teacher” below.'); return; }
      if (!free) { window.alert('You can only move to an open cell for a course you are listed to teach on this program.'); return; }
      if (pInp && toC && formMove) { pInp.value = d.placement; toC.value = targetCell; formMove.requestSubmit ? formMove.requestSubmit() : formMove.submit(); }
    });
  }
  document.querySelectorAll('.btn-tt-decline').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pid = btn.getAttribute('data-pid');
      var inp = document.getElementById('tt-decline-pid');
      if (inp) inp.value = pid;
      var m = document.getElementById('ttModalDecline');
      if (m && window.bootstrap) { new bootstrap.Modal(m).show(); }
    });
  });
  var mAssign = document.getElementById('ttModalAssign');
  var inpAssignCell = document.getElementById('tt-assign-cell-id');
  var subjLine = document.getElementById('tt-assign-subject-line');
  var selAssign = document.getElementById('tt-assign-teacher-id');
  document.querySelectorAll('.btn-assign-tt').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!mAssign || !window.bootstrap) return;
      var cid = btn.getAttribute('data-cell-id') || '';
      var cur = btn.getAttribute('data-current-uid') || '0';
      var subj = btn.getAttribute('data-subject') || '—';
      if (inpAssignCell) inpAssignCell.value = cid;
      if (subjLine) subjLine.textContent = 'Slot: ' + subj;
      if (selAssign) { selAssign.value = cur; }
      new bootstrap.Modal(mAssign).show();
    });
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
