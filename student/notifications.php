<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'Notifications';
$uid = $_SESSION['user_id'];

// Mark all as read when page is visited
$conn->query("UPDATE notifications SET is_read='yes' WHERE (target_type='all' OR target_student_id=$uid) AND is_read='no'");

$notifs = $conn->query("SELECT n.*,u.name as sender_name FROM notifications n
    JOIN users u ON n.sent_by=u.id
    WHERE n.target_type='all' OR n.target_student_id=$uid
    ORDER BY n.created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-bell"></i> All Notifications</div>
    <span class="badge badge-info"><?= $notifs->num_rows ?> notifications</span>
  </div>

  <?php if ($notifs->num_rows): while($n=$notifs->fetch_assoc()): ?>
    <div style="border-bottom:1px solid var(--border);padding:18px 0;display:flex;gap:16px">
      <div style="width:42px;height:42px;border-radius:50%;background:rgba(201,146,42,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas fa-bell" style="color:var(--gold);font-size:15px"></i>
      </div>
      <div style="flex:1">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px">
          <strong style="font-size:15px"><?= htmlspecialchars($n['title']) ?></strong>
          <div style="display:flex;align-items:center;gap:8px">
            <?php if ($n['target_type']==='all'): ?>
              <span class="badge badge-info">All Students</span>
            <?php else: ?>
              <span class="badge badge-gold">Personal</span>
            <?php endif; ?>
          </div>
        </div>
        <p style="font-size:14px;color:#444;line-height:1.6;margin-bottom:8px"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
        <div style="display:flex;gap:14px;font-size:12px;color:var(--muted)">
          <span><i class="fas fa-user" style="color:var(--gold)"></i> <?= htmlspecialchars($n['sender_name']) ?></span>
          <span><i class="fas fa-clock"></i> <?= fmtDate($n['created_at']) ?></span>
        </div>
      </div>
    </div>
  <?php endwhile; else: ?>
    <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications yet.</p></div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
