<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $depts */
/** @var array<int, list<array<string,mixed>>> $periods_by_dept */
$periodsByDept = $periods_by_dept ?? [];
require __DIR__ . '/../partials/header.php';
?>
<h1 class="h3 mb-4">Classes</h1>
<div class="card shadow-sm mb-4">
    <div class="card-header">Add class</div>
    <div class="card-body">
        <p class="small text-muted">Each <strong>class code</strong> (e.g. CS101) must be unique. Saving links that code to this department’s <strong>interactive timetable</strong> (the same program you open under Timetable) so you can assign teachers and drag the chip. Use a unique code that will appear as the <em>course code</em> on the board.</p>
        <form method="post" action="<?= \base_url('admin/classes/save') ?>" id="form-add-class">
            <?= CSRF::field() ?>
            <input type="hidden" name="id" value="0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Class name</th>
                            <th>Subject</th>
                            <th>Department</th>
                            <th>Section</th>
                            <th>Batch / year</th>
                            <th>Capacity</th>
                            <th>Grade / level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input class="form-control form-control-sm" name="code" placeholder="e.g. CS101" required></td>
                            <td><input class="form-control form-control-sm" name="name" required></td>
                            <td><input class="form-control form-control-sm" name="subject" required></td>
                            <td>
                                <select class="form-select form-select-sm" name="department_id" required>
                                    <?php foreach ($depts as $d) : ?>
                                        <option value="<?= (int) $d['id'] ?>"><?= \e((string) $d['code'] . ' — ' . (string) $d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input class="form-control form-control-sm" name="section" placeholder="A, B…"></td>
                            <td><input class="form-control form-control-sm" name="academic_batch" placeholder="2024–28, Y1…"></td>
                            <td style="width:5rem"><input class="form-control form-control-sm" name="capacity" type="number" value="30"></td>
                            <td><input class="form-control form-control-sm" name="grade_level" placeholder="e.g. Year 1"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mb-2"><input class="form-control" name="requirements" placeholder="Requirements (optional)"></div>
            <div class="border rounded p-2 mb-2 bg-body-secondary small">
                <p class="mb-2"><strong>Where on the program board grid?</strong> You can (1) pick a <strong>class start time on the 24-hour clock</strong> — the form maps it to the <strong>nearest / containing</strong> period for this department, (2) pick a period from the list, or (3) leave both on auto for the first free cell.</p>
                <p class="mb-2">Department period labels in the list use text like <code>9:30-10:30</code> for the board; the <strong>browser time field</strong> is always 24-hour (e.g. <code>09:30</code> for 9:30 a.m.) so the mapping is correct for morning slots.</p>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <label class="form-label" for="class-tt-time">Class start time (24-hour clock)</label>
                        <input type="time" class="form-control" id="class-tt-time" autocomplete="off" value="" step="300" title="Pick a time; optional — use the period list if you prefer">
                    </div>
                    <div class="col-12 col-md-8 col-lg-9 small text-muted" id="class-tt-time-hint" aria-live="polite">Choose a time to snap to a period, or set the period below. Leave the clock empty if you only use the list.</div>
                </div>
                <div class="row g-2 align-items-end mt-2">
                    <div class="col-sm-4 col-md-3">
                        <label class="form-label" for="class-tt-day">Day on board</label>
                        <select class="form-select form-select-sm" name="timetable_day" id="class-tt-day">
                            <option value="0">— Auto (first free) —</option>
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                        </select>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <label class="form-label" for="class-tt-period">Period (from program board)</label>
                        <select class="form-select form-select-sm" name="timetable_period" id="class-tt-period">
                            <option value="0">— Auto —</option>
                        </select>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Add class</button>
        </form>
    </div>
</div>
<script>
(function() {
  var byDept = <?= json_encode($periodsByDept, JSON_THROW_ON_ERROR) ?>;
  var deptSel = document.querySelector('#form-add-class select[name="department_id"]');
  var pSel = document.getElementById('class-tt-period');
  var tIn = document.getElementById('class-tt-time');
  var hint = document.getElementById('class-tt-time-hint');

  /* Ambiguous 12h-style text from DB (e.g. 7:00 meaning 1pm in some templates). */
  function to24(h, min) {
    h = h | 0; min = min | 0;
    if (h >= 8 && h <= 11) return h * 60 + min;
    if (h === 12) return 12 * 60 + min;
    if (h >= 1 && h <= 7) return (h + 12) * 60 + min;
    return h * 60 + min;
  }
  /* The browser time field is 24h — use minutes from midnight; do not use to24() (that is for DB time text). */
  function timeInputMinutes24h() {
    if (!tIn || !tIn.value) return null;
    var p = tIn.value.split(':');
    if (p.length < 2) return null;
    var h = parseInt(p[0], 10) | 0;
    var m = parseInt(p[1], 10) | 0;
    if (h < 0 || h > 23 || m < 0 || m > 59) return null;
    return h * 60 + m;
  }
  function firstTimeMinutes(tr) {
    if (!tr) return null;
    var s = String(tr).replace(/[–—]/g, '-');
    var m = s.match(/(\d{1,2}):(\d{2})/);
    if (!m) return null;
    return to24(parseInt(m[1], 10), parseInt(m[2], 10));
  }
  function parseRange(tr) {
    if (!tr) return null;
    var s = String(tr).replace(/[–—]/g, '-');
    var a = s.split('-');
    if (a.length < 2) { return { start: firstTimeMinutes(s), end: null }; }
    var t0 = a[0].match(/(\d{1,2}):(\d{2})/);
    var t1 = a[1] && a[1].match(/(\d{1,2}):(\d{2})/);
    if (!t0) return null;
    var st = to24(parseInt(t0[1], 10), parseInt(t0[2], 10));
    if (!t1) return { start: st, end: null };
    var en = to24(parseInt(t1[1], 10), parseInt(t1[2], 10));
    if (en <= st) en += 12 * 60;
    return { start: st, end: en };
  }
  function timeInputMinutes() {
    return timeInputMinutes24h();
  }
  function bestPeriodForTime(periods, userM) {
    if (userM == null) return null;
    var inRange = null, best = null, bestD = 1e9, i, pr, r, d;
    for (i = 0; i < periods.length; i++) {
      pr = periods[i];
      r = parseRange(pr.time_range || '');
      if (r && r.end != null && userM >= r.start && userM < r.end) {
        inRange = pr; break;
      }
      var sm = r && r.start != null ? r.start : firstTimeMinutes(pr.time_range);
      if (sm == null) continue;
      d = Math.abs(sm - userM);
      if (d < bestD) { bestD = d; best = pr; }
    }
    if (inRange) return inRange;
    return best;
  }
  function syncTimeToPeriod() {
    if (!pSel || !deptSel) return;
    var id = String(deptSel.value);
    var periods = byDept[id] || byDept[parseInt(id, 10)] || [];
    var userM = timeInputMinutes();
    if (userM == null) {
      if (hint) hint.textContent = 'Use the clock, or the period list.';
      return;
    }
    var pick = bestPeriodForTime(periods, userM);
    if (pick) {
      pSel.value = String(pick.period_index);
      if (hint) {
        hint.textContent = 'Mapped to period ' + (pick.label || pick.period_index) + ' — ' + (pick.time_range || '') + ' (row ' + pick.period_index + ' on the program board).';
      }
    } else if (hint) {
      hint.textContent = 'No period times found for this department. Pick a period in the list.';
    }
  }
  function refresh() {
    if (!pSel || !deptSel) return;
    var id = String(deptSel.value);
    var periods = byDept[id] || byDept[parseInt(id, 10)] || [];
    var keep = pSel.value;
    pSel.innerHTML = '<option value="0">— Auto —</option>';
    periods.forEach(function(p) {
      var o = document.createElement('option');
      o.value = String(p.period_index);
      o.textContent = (p.label || '') + ' · ' + (p.time_range || '');
      pSel.appendChild(o);
    });
    pSel.value = keep;
    if (pSel.selectedIndex < 0) pSel.value = '0';
    syncTimeToPeriod();
  }
  if (deptSel) deptSel.addEventListener('change', refresh);
  if (pSel) pSel.addEventListener('change', function() {
    if (hint) hint.textContent = 'Period set from list. You can still adjust the clock to snap to a different period.';
  });
  if (tIn) tIn.addEventListener('input', syncTimeToPeriod);
  if (tIn) tIn.addEventListener('change', syncTimeToPeriod);
  refresh();
})();
</script>
<?php foreach ($rows as $r) : ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="post" action="<?= \base_url('admin/classes/save') ?>">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Class name</th>
                                <th>Subject</th>
                                <th>Department</th>
                                <th>Section</th>
                                <th>Batch / year</th>
                                <th>Cap.</th>
                                <th>Grade / level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input class="form-control form-control-sm" name="code" value="<?= \e((string) $r['code']) ?>"></td>
                                <td><input class="form-control form-control-sm" name="name" value="<?= \e((string) $r['name']) ?>"></td>
                                <td><input class="form-control form-control-sm" name="subject" value="<?= \e((string) $r['subject']) ?>"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="department_id">
                                        <?php foreach ($depts as $d) : ?>
                                            <option value="<?= (int) $d['id'] ?>" <?= (int) $r['department_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= \e((string) $d['code'] . ' — ' . (string) $d['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input class="form-control form-control-sm" name="section" value="<?= \e((string) ($r['section'] ?? '')) ?>"></td>
                                <td><input class="form-control form-control-sm" name="academic_batch" value="<?= \e((string) ($r['academic_batch'] ?? '')) ?>"></td>
                                <td><input class="form-control form-control-sm" name="capacity" type="number" value="<?= (int) $r['capacity'] ?>"></td>
                                <td><input class="form-control form-control-sm" name="grade_level" value="<?= \e((string) ($r['grade_level'] ?? '')) ?>"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Requirements</label>
                    <input class="form-control form-control-sm" name="requirements" value="<?= \e((string) ($r['requirements'] ?? '')) ?>">
                </div>
                <button class="btn btn-sm btn-primary" type="submit">Save</button>
            </form>
            <form method="post" action="<?= \base_url('admin/classes/delete') ?>" class="mt-2" onsubmit="return confirm('Delete this class?');">
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
