<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Reports';

// Summary stats
$totalBooks      = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='active'")->fetch_assoc()['c'];
$totalStudents   = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student' AND status='active'")->fetch_assoc()['c'];
$totalIssued     = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued'")->fetch_assoc()['c'];
$totalReturned   = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='returned'")->fetch_assoc()['c'];
$totalOverdue    = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued' AND due_date<CURDATE()")->fetch_assoc()['c'];
$totalFinesColl  = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE fine_paid='yes'")->fetch_assoc()['t'];
$totalFinesPend  = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE fine_paid='no' AND fine_amount>0")->fetch_assoc()['t'];

// Most issued books
$topBooks = $conn->query("SELECT b.title,b.author,COUNT(bi.id) as issue_count FROM book_issues bi
    JOIN books b ON bi.book_id=b.id GROUP BY bi.book_id ORDER BY issue_count DESC LIMIT 8");

// Most active students
$topStudents = $conn->query("SELECT u.name,u.student_id,COUNT(bi.id) as issue_count FROM book_issues bi
    JOIN users u ON bi.student_id=u.id GROUP BY bi.student_id ORDER BY issue_count DESC LIMIT 8");

// Monthly issues (last 6 months)
$monthlyData = $conn->query("SELECT DATE_FORMAT(issue_date,'%b %Y') as month,
    COUNT(*) as issued FROM book_issues
    WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(issue_date,'%Y-%m') ORDER BY issue_date ASC");
?>
<?php include '../includes/header.php'; ?>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-books"></i></div><div class="stat-info"><div class="stat-value"><?= $totalBooks ?></div><div class="stat-label">Total Books</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div><div class="stat-info"><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">Students</div></div></div>
  <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-arrow-right-from-bracket"></i></div><div class="stat-info"><div class="stat-value"><?= $totalIssued ?></div><div class="stat-label">Currently Issued</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-rotate-left"></i></div><div class="stat-info"><div class="stat-value"><?= $totalReturned ?></div><div class="stat-label">Returned</div></div></div>
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-clock"></i></div><div class="stat-info"><div class="stat-value"><?= $totalOverdue ?></div><div class="stat-label">Overdue</div></div></div>
  <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-coins"></i></div><div class="stat-info"><div class="stat-value">₹<?= number_format($totalFinesColl,0) ?> / ₹<?= number_format($totalFinesPend,0) ?></div><div class="stat-label">Fines (Collected / Pending)</div></div></div>
</div>

<div class="grid-2" style="gap:20px">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-fire"></i> Most Issued Books</div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Book</th><th>Issues</th></tr></thead>
        <tbody>
        <?php if ($topBooks->num_rows): $i=1; while($r=$topBooks->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><small style="color:var(--muted)"><?= htmlspecialchars($r['author']) ?></small></td>
            <td><span class="badge badge-gold"><?= $r['issue_count'] ?></span></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="3" style="text-align:center;color:var(--muted)">No data</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-graduation-cap"></i> Most Active Students</div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Student</th><th>Total Issues</th></tr></thead>
        <tbody>
        <?php if ($topStudents->num_rows): $i=1; while($r=$topStudents->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($r['name']) ?><br><small><?= $r['student_id']?:'—' ?></small></td>
            <td><span class="badge badge-info"><?= $r['issue_count'] ?></span></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="3" style="text-align:center;color:var(--muted)">No data</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($monthlyData->num_rows): ?>
<div class="card" style="margin-top:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Monthly Issue Trend (Last 6 Months)</div></div>
  <?php
  $months = []; $counts = [];
  while($m=$monthlyData->fetch_assoc()) { $months[]=$m['month']; $counts[]=$m['issued']; }
  $maxCount = max($counts) ?: 1;
  ?>
  <div style="display:flex;align-items:flex-end;gap:14px;height:160px;padding:10px 0 0">
    <?php for($i=0;$i<count($months);$i++): ?>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px">
      <span style="font-size:12px;font-weight:600;color:var(--ink)"><?= $counts[$i] ?></span>
      <div style="width:100%;background:var(--gold);border-radius:4px 4px 0 0;height:<?= round(($counts[$i]/$maxCount)*120) ?>px;min-height:4px;transition:all 0.3s"></div>
      <span style="font-size:11px;color:var(--muted);text-align:center"><?= $months[$i] ?></span>
    </div>
    <?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
