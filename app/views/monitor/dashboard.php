<?php
declare(strict_types=1);
use App\Core\CSRF;
/** @var string $date */
/** @var list<array<string,mixed>> $rows */
require __DIR__ . '/../partials/header.php';
$csrfT = CSRF::token();
?>
<h1 class="h3 mb-4">Monitor dashboard</h1>
<p class="text-muted">Date: <?= \e($date) ?> · <a href="<?= \base_url('monitor/classes') ?>">Class list</a> · During the class period (or a few minutes before), you can <strong>record teacher attendance</strong> below. The list updates every <?= (int) (\app_config()['live_poll_seconds'] ?? 15) ?>s (same as students, but you verify as official record).</p>
<div class="table-responsive">
    <table class="table table-sm" id="monitor-live-table">
        <thead><tr><th>Class</th><th>Teacher</th><th>Time</th><th>Status</th><th>Session</th><th>Verify attendance</th></tr></thead>
        <tbody id="monitor-live-body"><tr><td colspan="6" class="text-muted">Loading&hellip;</td></tr></tbody>
    </table>
</div>
<script>
(function(){
  var CSRF_T = <?= json_encode($csrfT) ?>;
  function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  function attestationOpen(it) {
    var s = (it.ui_status || '');
    return s === 'starting_soon' || s === 'in_progress' || s === 'live';
  }
  function render(data){
    var tb = document.getElementById('monitor-live-body');
    if (!tb || !data.items) { return; }
    var d = (data && data.date) ? String(data.date) : '';
    if (!data.items.length) { tb.innerHTML = '<tr><td colspan="6" class="text-muted">No sessions today for your assignments.</td></tr>'; return; }
    var root = window.CMS_ROOT || '';
    tb.innerHTML = data.items.map(function(it){
      var show = attestationOpen(it) && it.schedule_id && (parseInt(String(it.teacher_id || 0), 10) > 0);
      var tname = it.teacher_name ? esc(String(it.teacher_name)) : '—';
      var sid = parseInt(String(it.schedule_id), 10) || 0;
      var tid = parseInt(String(it.teacher_id || 0), 10) || 0;
      var pr = '—';
      if (show) {
        pr = '<form class="d-inline" method="post" action="' + root + '/monitor/verify" style="white-space:nowrap">'
          + '<input type="hidden" name="_csrf" value="' + esc(CSRF_T) + '"/>'
          + '<input type="hidden" name="schedule_id" value="' + sid + '"/>'
          + '<input type="hidden" name="teacher_id" value="' + tid + '"/>'
          + '<input type="hidden" name="date" value="' + esc(d) + '"/>'
          + '<button class="btn btn-sm btn-success me-1" type="submit" name="status" value="present">Present</button>'
          + '<button class="btn btn-sm btn-outline-warning me-1" type="submit" name="status" value="absent">Absent</button>'
          + '<a class="btn btn-sm btn-outline-primary" href="' + root + '/monitor/verify/' + sid + '?date=' + encodeURIComponent(d) + '">Details</a></form>';
      }
      return '<tr><td>'+esc(it.class_code || '')+'</td><td>'+tname+'</td><td>'+esc(String(it.start_time||'').substring(0,5))+'–'+esc(String(it.end_time||'').substring(0,5))+'</td><td><span class="badge text-bg-secondary">'+esc(String(it.attendance_status||'pending'))+'</span></td><td>'+esc(String(it.ui_status))+'</td><td>'+pr+'</td></tr>';
    }).join('');
  }
  function load(){
    var root = (typeof window.CMS_ROOT === 'string' && window.CMS_ROOT) ? window.CMS_ROOT : '';
    var u = (root && root.length ? (root + '/api/live-status') : '/api/live-status');
    fetch(u, { credentials:'same-origin', headers:{'Accept':'application/json'}})
      .then(function(r){
        if (!r.ok) { throw new Error('status ' + r.status); }
        return r.json();
      })
      .then(render)
      .catch(function(){
        var tbx = document.getElementById('monitor-live-body');
        if (tbx) { tbx.innerHTML = '<tr><td colspan="6" class="text-danger">Could not load. Check <code>base_url</code> matches how you open this site.</td></tr>'; }
      });
  }
  load();
  setInterval(load, (window.CMS_POLL_SEC || 15) * 1000);
})();
</script>
<noscript>
    <h2 class="h6">Static list (enable JavaScript for class-time quick verify)</h2>
    <div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Class</th><th>Teacher</th><th>Time</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= \e((string) ($r['class_code'] ?? '')) ?></td>
                <td><?= \e((string) ($r['teacher_name'] ?? '')) ?></td>
                <td><?= \e(substr((string) ($r['start_time'] ?? ''), 0, 5)) ?></td>
                <td><span class="badge text-bg-secondary"><?= \e((string) ($r['status'] ?? '')) ?></span></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= \base_url('monitor/verify/' . (int) ($r['schedule_id'] ?? 0) . '?date=' . urlencode($date)) ?>">Verify</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</noscript>
<?php if ($rows === []): ?><p class="text-muted d-none" id="monitor-empty">No sessions today for your assignments.</p><?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
