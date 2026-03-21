<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Notifications';
$msg = $err = '';

if (isset($_POST['send_notification'])) {
    $title      = clean($_POST['title']);
    $message    = clean($_POST['message']);
    $targetType = clean($_POST['target_type']);
    $targetId   = $targetType === 'student' ? (int)$_POST['target_student_id'] : null;
    $sentBy     = $_SESSION['user_id'];

    if (!$title || !$message) { $err = 'Title and message are required.'; }
    elseif ($targetType === 'student' && !$targetId) { $err = 'Please select a student.'; }
    else {
        if ($targetId) {
            $stmt = $conn->prepare("INSERT INTO notifications (title,message,sent_by,target_type,target_student_id) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssissi", $title, $message, $sentBy, $targetType, $targetId);
        } else {
            $stmt = $conn->prepare("INSERT INTO notifications (title,message,sent_by,target_type) VALUES (?,?,?,?)");
            $stmt->bind_param("ssis", $title, $message, $sentBy, $targetType);
        }
        if ($stmt->execute()) $msg = 'Notification sent!';
        else $err = 'Failed to send: ' . $conn->error;
    }
}

$students = $conn->query("SELECT id,name,student_id FROM users WHERE role='student' AND status='active' ORDER BY name");

// 3 most recent
$recentNotifs = $conn->query("SELECT n.*,u.name as sender, s.name as target_name
    FROM notifications n
    JOIN users u ON n.sent_by=u.id
    LEFT JOIN users s ON n.target_student_id=s.id
    ORDER BY n.created_at DESC LIMIT 3");

// Everything after the first 3
$olderNotifs = $conn->query("SELECT n.*,u.name as sender, s.name as target_name
    FROM notifications n
    JOIN users u ON n.sent_by=u.id
    LEFT JOIN users s ON n.target_student_id=s.id
    ORDER BY n.created_at DESC LIMIT 200 OFFSET 3");

$totalCount = (int)$conn->query("SELECT COUNT(*) as c FROM notifications")->fetch_assoc()['c'];
$olderCount = max(0, $totalCount - 3);

// Helper to render a single notification row (used twice below)
function notifRow(array $n): void { ?>
  <div class="notif-row" style="border-bottom:1px solid var(--border);padding:12px 0">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:5px">
      <strong style="font-size:14px"><?= htmlspecialchars($n['title']) ?></strong>
      <?php if ($n['target_type'] === 'all'): ?>
        <span class="badge badge-info">All Students</span>
      <?php else: ?>
        <span class="badge badge-gold"><?= htmlspecialchars($n['target_name'] ?? '—') ?></span>
      <?php endif; ?>
    </div>
    <p style="font-size:13px;color:var(--muted);margin-bottom:4px">
      <?= htmlspecialchars(substr($n['message'], 0, 80)) ?>...
    </p>
    <small style="color:var(--muted)"><?= fmtDate($n['created_at']) ?></small>
  </div>
<?php }
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="grid-2" style="gap:22px;align-items:start">

  <!-- ── Send Form ─────────────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-bell"></i> Send Notification</div>
    </div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Notification Title *</label>
        <input type="text" name="title" class="form-control" required placeholder="e.g. Book Return Reminder">
      </div>
      <div class="form-group">
        <label class="form-label">Message *</label>
        <textarea name="message" class="form-control" required placeholder="Type your notification message..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Send To</label>
        <select name="target_type" class="form-select" id="targetType" onchange="toggleStudentSelect(this.value)">
          <option value="all">📢 All Students</option>
          <option value="student">👤 Specific Student</option>
        </select>
      </div>
      <div class="form-group" id="studentSelect" style="display:none">
        <label class="form-label">Select Student</label>
        <select name="target_student_id" class="form-select">
          <option value="">— Select student —</option>
          <?php $students->data_seek(0); while ($s = $students->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>">
              <?= htmlspecialchars($s['name']) ?>
              <?= $s['student_id'] ? '(' . $s['student_id'] . ')' : '' ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" name="send_notification" class="btn btn-primary">
        <i class="fas fa-paper-plane"></i> Send Notification
      </button>
    </form>
  </div>

  <!-- ── Recent Notifications ──────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Notifications</div>
      <?php if ($totalCount > 0): ?>
        <span style="font-size:12px;color:var(--muted)"><?= $totalCount ?> total</span>
      <?php endif; ?>
    </div>

    <!-- Always-visible: 3 most recent -->
    <?php if ($recentNotifs && $recentNotifs->num_rows > 0):
      while ($n = $recentNotifs->fetch_assoc()) notifRow($n);
    else: ?>
      <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications sent yet.</p></div>
    <?php endif; ?>

    <!-- Hidden older notifications -->
    <?php if ($olderCount > 0): ?>
      <div id="olderList" style="display:none">
        <?php while ($n = $olderNotifs->fetch_assoc()) notifRow($n); ?>
      </div>

      <!-- Toggle button -->
      <div style="padding-top:14px">
        <button id="toggleBtn" onclick="toggleOlder()" class="btn btn-outline btn-sm" style="width:100%">
          <i class="fas fa-chevron-down" id="toggleIcon"></i>
          <span id="toggleLabel">View All <?= $olderCount ?> Previous Notification<?= $olderCount > 1 ? 's' : '' ?></span>
        </button>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
function toggleStudentSelect(val) {
  document.getElementById('studentSelect').style.display = val === 'student' ? 'block' : 'none';
}

function toggleOlder() {
  const list     = document.getElementById('olderList');
  const icon     = document.getElementById('toggleIcon');
  const label    = document.getElementById('toggleLabel');
  const expanded = list.style.display !== 'none';

  list.style.display = expanded ? 'none' : 'block';
  icon.className     = expanded ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
  label.textContent  = expanded
    ? 'View All <?= $olderCount ?> Previous Notification<?= $olderCount > 1 ? "s" : "" ?>'
    : 'Show Less';
}
</script>

<?php include '../includes/footer.php'; ?>
