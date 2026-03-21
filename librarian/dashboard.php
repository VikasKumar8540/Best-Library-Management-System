<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Dashboard';

$totalBooks    = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='active'")->fetch_assoc()['c'];
$issuedBooks   = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued'")->fetch_assoc()['c'];
$overdueBooks  = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued' AND due_date < CURDATE()")->fetch_assoc()['c'];
$pendingReq    = $conn->query("SELECT COUNT(*) as c FROM book_requests WHERE status='pending'")->fetch_assoc()['c'];
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student' AND status='active'")->fetch_assoc()['c'];
$returnedToday = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='returned' AND DATE(return_date)=CURDATE()")->fetch_assoc()['c'];

// Recent issues
$recentIssues = $conn->query("SELECT bi.*, b.title as book_title, u.name as student_name
    FROM book_issues bi JOIN books b ON bi.book_id=b.id JOIN users u ON bi.student_id=u.id
    ORDER BY bi.created_at DESC LIMIT 5");

// Pending requests
$pendingRequests = $conn->query("SELECT br.*, u.name as student_name, b.title as book_title
    FROM book_requests br JOIN users u ON br.student_id=u.id
    LEFT JOIN books b ON br.book_id=b.id
    WHERE br.status='pending' ORDER BY br.created_at DESC LIMIT 5");
?>
<?php include '../includes/header.php'; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon gold"><i class="fas fa-books"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $totalBooks ?></div><div class="stat-label">Total Books</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">Students</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-arrow-right-from-bracket"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $issuedBooks ?></div><div class="stat-label">Books Issued</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-clock"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $overdueBooks ?></div><div class="stat-label">Overdue</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-rotate-left"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $returnedToday ?></div><div class="stat-label">Returned Today</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fas fa-message-dots"></i></div>
    <div class="stat-info"><div class="stat-value"><?= $pendingReq ?></div><div class="stat-label">Pending Requests</div></div>
  </div>
</div>

<div class="grid-2" style="gap:20px">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list-check"></i> Recent Issues</div>
      <a href="issued_books.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Book</th><th>Student</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        <?php if ($recentIssues->num_rows): while($r=$recentIssues->fetch_assoc()): ?>
          <?php $overdue = ($r['status']==='issued' && strtotime($r['due_date'])<time()); ?>
          <tr>
            <td><?= htmlspecialchars(substr($r['book_title'],0,25)).'...' ?></td>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td><?= fmtDate($r['due_date']) ?></td>
            <td><?= $overdue ? '<span class="badge badge-danger">Overdue</span>' : ($r['status']==='returned'?'<span class="badge badge-success">Returned</span>':'<span class="badge badge-info">Issued</span>') ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">No issues yet</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-message-dots"></i> Pending Requests</div>
      <a href="requests.php" class="btn btn-outline btn-sm">Manage</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Student</th><th>Message</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php if ($pendingRequests->num_rows): while($r=$pendingRequests->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td><?= htmlspecialchars(substr($r['message'],0,35)).'...' ?></td>
            <td><?= fmtDate($r['created_at']) ?></td>
            <td><a href="requests.php?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">No pending requests</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
