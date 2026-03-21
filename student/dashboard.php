<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'Dashboard';
$uid = $_SESSION['user_id'];

$activeIssues  = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE student_id=$uid AND status='issued'")->fetch_assoc()['c'];
$returnedBooks = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE student_id=$uid AND status='returned'")->fetch_assoc()['c'];
$pendingFine   = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE student_id=$uid AND fine_paid='no'")->fetch_assoc()['t'];
$myRequests    = $conn->query("SELECT COUNT(*) as c FROM book_requests WHERE student_id=$uid")->fetch_assoc()['c'];
$unreadNotifs  = getUnreadNotifications($uid);
$totalBooks    = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='active'")->fetch_assoc()['c'];

// Current issued books
$myBooks = $conn->query("SELECT bi.*,b.title,b.author FROM book_issues bi
    JOIN books b ON bi.book_id=b.id WHERE bi.student_id=$uid AND bi.status='issued' ORDER BY bi.due_date ASC");

// Recent notifications
$notifs = $conn->query("SELECT * FROM notifications WHERE (target_type='all' OR target_student_id=$uid)
    ORDER BY created_at DESC LIMIT 5");
?>
<?php include '../includes/header.php'; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-bookmark"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $activeIssues ?></div><div class="stat-label">Books Issued</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-rotate-left"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $returnedBooks ?></div><div class="stat-label">Books Returned</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-coins"></i></div>
    <div class="stat-info"><div class="stat-value">₹<?= number_format($pendingFine,0) ?></div><div class="stat-label">Pending Fine</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold"><i class="fas fa-book-open"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $totalBooks ?></div><div class="stat-label">Books in Library</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $myRequests ?></div><div class="stat-label">My Requests</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fas fa-bell"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $unreadNotifs ?></div><div class="stat-label">Unread Notifications</div></div>
  </div>
</div>

<div class="grid-2" style="gap:20px">
  <!-- Current Books -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-bookmark"></i> My Current Books</div>
      <a href="my_books.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if ($myBooks->num_rows): while($b=$myBooks->fetch_assoc()): ?>
      <?php
        $isOverdue = strtotime($b['due_date']) < time();
        $fine      = $isOverdue ? calculateFine($b['due_date']) : 0;
        $daysLeft  = ceil((strtotime($b['due_date']) - time()) / 86400);
      ?>
      <div style="border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px;background:<?= $isOverdue?'#fffbeb':'#f9fafb' ?>">
        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <strong><?= htmlspecialchars($b['title']) ?></strong><br>
            <small style="color:var(--muted)"><?= htmlspecialchars($b['author']) ?></small>
          </div>
          <?php if ($isOverdue): ?>
            <span class="badge badge-danger">Overdue</span>
          <?php else: ?>
            <span class="badge badge-<?= $daysLeft<=3?'warning':'success' ?>"><?= $daysLeft ?>d left</span>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px;font-size:12px;color:var(--muted)">
          Due: <?= fmtDate($b['due_date']) ?>
          <?php if ($isOverdue): ?> &nbsp;|&nbsp; <span style="color:var(--danger)">Fine: ₹<?= number_format($fine,2) ?></span><?php endif; ?>
        </div>
      </div>
    <?php endwhile; else: ?>
      <div class="empty-state"><i class="fas fa-book-open"></i><p>No books currently issued.</p></div>
    <?php endif; ?>
  </div>

  <!-- Notifications -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-bell"></i> Notifications</div>
      <a href="notifications.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if ($notifs->num_rows): while($n=$notifs->fetch_assoc()): ?>
      <?php $isNew = ($n['is_read']==='no'); ?>
      <div style="border-bottom:1px solid var(--border);padding:12px 0;<?= $isNew?'background:rgba(201,146,42,0.04)':'' ?>">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
          <?php if ($isNew): ?><span style="width:7px;height:7px;background:var(--gold);border-radius:50%;display:inline-block;flex-shrink:0"></span><?php endif; ?>
          <strong style="font-size:13px"><?= htmlspecialchars($n['title']) ?></strong>
        </div>
        <p style="font-size:13px;color:var(--muted)"><?= htmlspecialchars(substr($n['message'],0,75)).'...' ?></p>
        <small style="color:var(--muted)"><?= fmtDate($n['created_at']) ?></small>
      </div>
    <?php endwhile; else: ?>
      <div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications yet.</p></div>
    <?php endif; ?>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
