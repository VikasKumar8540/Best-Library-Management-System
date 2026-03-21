<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'My Books';
$uid = $_SESSION['user_id'];

$filter = clean($_GET['filter'] ?? 'all');
$where  = "WHERE bi.student_id=$uid";
if ($filter === 'issued')   $where .= " AND bi.status='issued'";
if ($filter === 'returned') $where .= " AND bi.status='returned'";
if ($filter === 'overdue')  $where .= " AND bi.status='issued' AND bi.due_date < CURDATE()";

$issues = $conn->query("SELECT bi.*,b.title,b.author,b.category FROM book_issues bi
    JOIN books b ON bi.book_id=b.id $where ORDER BY bi.created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<div class="card" style="margin-bottom:18px">
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="?filter=all" class="btn <?= $filter==='all'?'btn-primary':'btn-outline' ?> btn-sm">All</a>
    <a href="?filter=issued" class="btn <?= $filter==='issued'?'btn-primary':'btn-outline' ?> btn-sm">Currently Issued</a>
    <a href="?filter=overdue" class="btn <?= $filter==='overdue'?'btn-danger':'btn-outline' ?> btn-sm">Overdue</a>
    <a href="?filter=returned" class="btn <?= $filter==='returned'?'btn-success':'btn-outline' ?> btn-sm">Returned</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-bookmark"></i> My Book History</div>
    <span class="badge badge-info"><?= $issues->num_rows ?> records</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Book</th><th>Category</th><th>Issue Date</th><th>Due Date</th><th>Return Date</th><th>Fine</th><th>Status</th></tr></thead>
      <tbody>
      <?php if ($issues->num_rows): $i=1; while($r=$issues->fetch_assoc()): ?>
        <?php
          $isOverdue = ($r['status']==='issued' && strtotime($r['due_date'])<time());
          $fine      = $r['status']==='issued' && $isOverdue ? calculateFine($r['due_date']) : $r['fine_amount'];
          $daysLeft  = ceil((strtotime($r['due_date'])-time())/86400);
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td>
            <strong><?= htmlspecialchars($r['title']) ?></strong><br>
            <small style="color:var(--muted)"><?= htmlspecialchars($r['author']) ?></small>
          </td>
          <td><?= $r['category'] ? '<span class="badge badge-gold">'.htmlspecialchars($r['category']).'</span>' : '—' ?></td>
          <td><?= fmtDate($r['issue_date']) ?></td>
          <td>
            <?= fmtDate($r['due_date']) ?>
            <?php if ($r['status']==='issued' && !$isOverdue && $daysLeft<=3): ?>
              <br><small style="color:var(--warning)"><i class="fas fa-exclamation-triangle"></i> <?= $daysLeft ?>d left</small>
            <?php endif; ?>
          </td>
          <td><?= $r['return_date'] ? fmtDate($r['return_date']) : '—' ?></td>
          <td>
            <?php if ($fine > 0): ?>
              <span class="badge <?= $r['fine_paid']==='yes'?'badge-success':'badge-danger' ?>">
                ₹<?= number_format($fine,2) ?>
                <?= $r['fine_paid']==='yes' ? ' (Paid)' : ' (Due)' ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($isOverdue): ?>
              <span class="badge badge-danger">Overdue</span>
            <?php elseif ($r['status']==='returned'): ?>
              <span class="badge badge-success">Returned</span>
            <?php else: ?>
              <span class="badge badge-info">Issued</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-bookmark"></i><p>No records found.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
