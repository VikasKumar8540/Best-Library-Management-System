<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Book Requests';
$msg = $err = '';

// Reply to request
if (isset($_POST['reply_request'])) {
    $rid    = (int)$_POST['request_id'];
    $reply  = clean($_POST['reply']);
    $status = clean($_POST['status']);
    $libId  = $_SESSION['user_id'];
    $conn->query("UPDATE book_requests SET librarian_reply='$reply', status='$status',
        replied_by=$libId, replied_at=NOW() WHERE id=$rid");
    $msg = 'Reply sent!';
}

// View single request
$viewRequest = null;
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $vr  = $conn->query("SELECT br.*,u.name as sname,u.email as semail,u.student_id as roll,b.title as book_title
        FROM book_requests br JOIN users u ON br.student_id=u.id
        LEFT JOIN books b ON br.book_id=b.id WHERE br.id=$vid");
    if ($vr->num_rows) $viewRequest = $vr->fetch_assoc();
}

$filter = clean($_GET['filter'] ?? 'all');
$where  = '';
if ($filter === 'pending')  $where = "WHERE br.status='pending'";
if ($filter === 'resolved') $where = "WHERE br.status IN('approved','rejected','resolved')";

$requests = $conn->query("SELECT br.*,u.name as sname,u.student_id as roll
    FROM book_requests br JOIN users u ON br.student_id=u.id
    $where ORDER BY br.created_at DESC");
?>
<?php include '../includes/header.php'; ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>

<?php if ($viewRequest): ?>
<div class="card" style="margin-bottom:22px;max-width:700px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-message-dots"></i> Request from <?= htmlspecialchars($viewRequest['sname']) ?></div>
    <a href="requests.php" class="btn btn-outline btn-sm">← Back</a>
  </div>
  <div style="background:#f9f7f2;border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:18px">
    <div style="display:flex;justify-content:space-between;margin-bottom:10px">
      <strong><?= htmlspecialchars($viewRequest['sname']) ?></strong>
      <span class="badge <?= $viewRequest['status']==='pending'?'badge-warning':'badge-success' ?>"><?= ucfirst($viewRequest['status']) ?></span>
    </div>
    <?php if ($viewRequest['book_title']): ?>
    <p style="font-size:13px;margin-bottom:8px"><i class="fas fa-book" style="color:var(--gold)"></i> <strong>Book:</strong> <?= htmlspecialchars($viewRequest['book_title']) ?></p>
    <?php endif; ?>
    <?php if ($viewRequest['request_title']): ?>
    <p style="font-size:13px;margin-bottom:8px"><strong>Subject:</strong> <?= htmlspecialchars($viewRequest['request_title']) ?></p>
    <?php endif; ?>
    <p style="font-size:14px"><?= nl2br(htmlspecialchars($viewRequest['message'])) ?></p>
    <small style="color:var(--muted)"><?= fmtDate($viewRequest['created_at']) ?></small>
  </div>

  <?php if ($viewRequest['librarian_reply']): ?>
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:16px">
    <strong style="font-size:12px;color:var(--success);text-transform:uppercase">Your Reply</strong>
    <p style="margin-top:6px;font-size:14px"><?= nl2br(htmlspecialchars($viewRequest['librarian_reply'])) ?></p>
  </div>
  <?php endif; ?>

  <?php if ($viewRequest['status'] === 'pending'): ?>
  <form method="POST">
    <input type="hidden" name="request_id" value="<?= $viewRequest['id'] ?>">
    <div class="form-group">
      <label class="form-label">Your Reply *</label>
      <textarea name="reply" class="form-control" required placeholder="Write your reply to the student..."></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Action</label>
      <select name="status" class="form-select">
        <option value="approved">✅ Approve (Book can be issued)</option>
        <option value="rejected">❌ Reject</option>
        <option value="resolved">✔ Resolved (General query resolved)</option>
      </select>
    </div>
    <button type="submit" name="reply_request" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Reply</button>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:14px">
  <div style="display:flex;gap:8px">
    <a href="requests.php" class="btn <?= $filter==='all'?'btn-primary':'btn-outline' ?> btn-sm">All</a>
    <a href="?filter=pending" class="btn <?= $filter==='pending'?'btn-warning':'btn-outline' ?> btn-sm">Pending</a>
    <a href="?filter=resolved" class="btn <?= $filter==='resolved'?'btn-success':'btn-outline' ?> btn-sm">Resolved</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-message-dots"></i> Student Requests</div>
    <span class="badge badge-info"><?= $requests->num_rows ?> requests</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Student</th><th>Subject</th><th>Message</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if ($requests->num_rows): $i=1; while($r=$requests->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($r['sname']) ?><br><small><?= $r['roll']?:'—' ?></small></td>
          <td><?= htmlspecialchars(substr($r['request_title']??'',0,25)) ?: '—' ?></td>
          <td><?= htmlspecialchars(substr($r['message'],0,45)).'...' ?></td>
          <td><?= fmtDate($r['created_at']) ?></td>
          <td>
            <?php
              $sc = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','resolved'=>'badge-info'];
              echo '<span class="badge '.($sc[$r['status']]??'badge-muted').'">'.ucfirst($r['status']).'</span>';
            ?>
          </td>
          <td><a href="requests.php?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-message-dots"></i><p>No requests found.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
