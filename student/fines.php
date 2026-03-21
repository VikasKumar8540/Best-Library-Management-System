<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'My Fines';
$uid = $_SESSION['user_id'];

$fines = $conn->query("SELECT bi.*,b.title,b.author FROM book_issues bi
    JOIN books b ON bi.book_id=b.id
    WHERE bi.student_id=$uid AND (bi.fine_amount>0 OR (bi.status='issued' AND bi.due_date < CURDATE()))
    ORDER BY bi.due_date ASC");

$totalUnpaid = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues
    WHERE student_id=$uid AND fine_paid='no' AND fine_amount>0")->fetch_assoc()['t'];

// Also count accruing fines (issued & overdue, fine not yet set)
$accruingFine = 0;
$overdueFines = $conn->query("SELECT * FROM book_issues WHERE student_id=$uid AND status='issued' AND due_date < CURDATE()");
while($of = $overdueFines->fetch_assoc()) {
    if ($of['fine_amount'] == 0) {
        $accruingFine += calculateFine($of['due_date']);
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-coins"></i></div>
    <div class="stat-info">
      <div class="stat-value">₹<?= number_format($totalUnpaid,2) ?></div>
      <div class="stat-label">Total Unpaid Fine</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
    <div class="stat-info">
      <div class="stat-value">₹<?= number_format($accruingFine,2) ?></div>
      <div class="stat-label">Accruing (Overdue)</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
    <div class="stat-info">
      <div class="stat-value">
        <?php
          $totalPaid = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE student_id=$uid AND fine_paid='yes'")->fetch_assoc()['t'];
          echo '₹'.number_format($totalPaid,2);
        ?>
      </div>
      <div class="stat-label">Total Paid</div>
    </div>
  </div>
</div>

<?php if ($totalUnpaid > 0 || $accruingFine > 0): ?>
<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle"></i>
  You have pending fines. Please pay them at the library counter.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-coins"></i> Fine Details</div></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Book</th><th>Issue Date</th><th>Due Date</th><th>Return Date</th><th>Days Late</th><th>Fine</th><th>Paid?</th></tr></thead>
      <tbody>
      <?php if ($fines->num_rows): $i=1; $fines->data_seek(0); while($r=$fines->fetch_assoc()): ?>
        <?php
          $isOverdue = ($r['status']==='issued' && strtotime($r['due_date'])<time());
          $fine      = $isOverdue && $r['fine_amount']==0 ? calculateFine($r['due_date']) : $r['fine_amount'];
          $retDate   = $r['return_date'] ?: date('Y-m-d');
          $daysLate  = max(0, ceil((strtotime($retDate)-strtotime($r['due_date']))/86400));
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><small><?= htmlspecialchars($r['author']) ?></small></td>
          <td><?= fmtDate($r['issue_date']) ?></td>
          <td><?= fmtDate($r['due_date']) ?></td>
          <td><?= $r['return_date'] ? fmtDate($r['return_date']) : '<span style="color:var(--warning)">Not returned</span>' ?></td>
          <td><span class="badge badge-danger"><?= $daysLate ?> day<?= $daysLate!=1?'s':'' ?></span></td>
          <td><span class="badge badge-danger">₹<?= number_format($fine,2) ?></span></td>
          <td>
            <?php if ($r['fine_paid']==='yes'): ?>
              <span class="badge badge-success">✓ Paid</span>
            <?php elseif ($isOverdue): ?>
              <span class="badge badge-warning">Accruing</span>
            <?php else: ?>
              <span class="badge badge-danger">Unpaid</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-coins"></i><p>No fines. Keep returning books on time! 🎉</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
