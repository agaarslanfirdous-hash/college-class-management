<?php
declare(strict_types=1);
use App\Core\CSRF;
require __DIR__ . '/../partials/header.php';
$csrfT = CSRF::token();
?>
<h1 class="h3 mb-4">Student dashboard</h1>
<p class="text-muted">When a class is <strong>in session</strong>, you can report whether the teacher is present. Today’s list updates every <?= (int) (\app_config()['live_poll_seconds'] ?? 15) ?>s.</p>
<div class="table-responsive">
    <table class="table table-sm" id="student-live-table">
        <thead><tr><th>Class</th><th>Time</th><th>Teacher</th><th>Status</th><th>UI</th><th>At class time</th></tr></thead>
        <tbody id="student-live-body"><tr><td colspan="6" class="text-muted">Loading…</td></tr></tbody>
    </table>
</div>
<script>
(function(){
  var CSRF_T = <?= json_encode($csrfT) ?>;
  function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  function render(data){
    var tb=document.getElementById('student-live-body');
    if(!tb||!data.items){ return; }
    if(!data.items.length){ tb.innerHTML='<tr><td colspan="6" class="text-muted">No classes today.</td></tr>'; return; }
    var root = window.CMS_ROOT || '';
    tb.innerHTML = data.items.map(function(it){
      var t = it.teacher_name ? esc(it.teacher_name) : '—';
      var showPresence = (it.ui_status === 'in_progress' || it.ui_status === 'live' || it.ui_status === 'starting_soon') && it.schedule_id;
      var pr = '—';
      if (showPresence) {
        var sid = parseInt(String(it.schedule_id), 10) || 0;
        pr = '<form class="d-inline" method="post" action="' + root + '/student/teacher-presence" style="white-space:nowrap">'
          + '<input type="hidden" name="_csrf" value="' + esc(CSRF_T) + '"/>'
          + '<input type="hidden" name="schedule_id" value="' + sid + '"/>'
          + '<button class="btn btn-sm btn-success me-1" type="submit" name="teacher_present" value="1">Yes — teacher present / seen</button>'
          + '<button class="btn btn-sm btn-outline-warning" type="submit" name="teacher_present" value="0">No — not seen / unsure</button></form>';
      }
      return '<tr><td>'+esc(it.class_code)+'</td><td>'+esc(String(it.start_time).substring(0,5))+'–'+esc(String(it.end_time).substring(0,5))+'</td><td>'+t+'</td><td><span class="badge text-bg-secondary">'+esc(it.attendance_status)+'</span></td><td>'+esc(it.ui_status)+'</td><td>'+pr+'</td></tr>';
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
        var tb = document.getElementById('student-live-body');
        if (tb) {
          tb.innerHTML = '<tr><td colspan="6" class="text-danger">Could not load the class list. Open this site with the <strong>same</strong> address the server uses (and set <code>base_url</code> in <code>app/config/config.php</code> to that full URL, or <code>auto</code>).</td></tr>';
        }
      });
  }
  load();
  setInterval(load, (window.CMS_POLL_SEC||15)*1000);
})();
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
