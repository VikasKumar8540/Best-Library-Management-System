<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Return Book';
$msg = $err = '';
$issueRecord = null;

// Search for issue record
if (isset($_GET['find']) && isset($_GET['student_id'])) {
  $sid = (int) $_GET['student_id'];
  $issueRecord = $conn->query("SELECT bi.*,b.title,b.author,u.name as sname,u.student_id as roll
        FROM book_issues bi JOIN books b ON bi.book_id=b.id JOIN users u ON bi.student_id=u.id
        WHERE bi.student_id=$sid AND bi.status='issued' ORDER BY bi.issue_date DESC");
}

// Process return
if (isset($_POST['process_return'])) {
  $issueId = (int) $_POST['issue_id'];
  $retDate = clean($_POST['return_date']);
  $remarks = clean($_POST['remarks']);

  $ir = $conn->query("SELECT * FROM book_issues WHERE id=$issueId")->fetch_assoc();
  if (!$ir) {
    $err = 'Issue record not found.';
  } else {
    $fine = calculateFine($ir['due_date'], $retDate);
    $finePaid = $fine > 0 ? 'no' : 'yes';

    $conn->query("UPDATE book_issues SET status='returned', return_date='$retDate',
            fine_amount=$fine, fine_paid='$finePaid', remarks='$remarks' WHERE id=$issueId");
    $conn->query("UPDATE books SET available_copies=available_copies+1 WHERE id=" . $ir['book_id']);

    if ($fine > 0) {
      $msg = "Book returned. Fine of ₹$fine applied for late return.";
    } else {
      $msg = 'Book returned successfully. No fine.';
    }
  }
}

$students = $conn->query("SELECT u.id,u.name,u.student_id FROM users u
    INNER JOIN book_issues bi ON bi.student_id=u.id
    WHERE bi.status='issued' GROUP BY u.id ORDER BY u.name");
?>
<?php include '../includes/header.php'; ?>
<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?>
  <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="card" style="max-width:700px; margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-search"></i> Find Student's Books</div>
  </div>
  <form method="GET">
    <div style="display:flex;gap:10px;align-items:end">
      <div class="form-group" style="flex:1;margin:0">
        <label class="form-label">Select Student</label>
        <select name="student_id" class="form-select" required>
          <option value="">— Choose student —</option>
          <?php while ($s = $students->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= (isset($_GET['student_id']) && $_GET['student_id'] == $s['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>   <?= $s['student_id'] ? '(' . $s['student_id'] . ')' : '' ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" name="find" value="1" class="btn btn-primary" style="margin-bottom:5px">
        <i class="fas fa-search"></i> Find
      </button>
    </div>
  </form>
</div>

<?php if ($issueRecord && $issueRecord->num_rows > 0): ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-bookmark"></i> Books Issued to This Student</div>
    </div>
    <?php while ($r = $issueRecord->fetch_assoc()): ?>
      <?php
      $today = date('Y-m-d');
      $fine = calculateFine($r['due_date'], $today);
      $isOverdue = strtotime($r['due_date']) < time();
      ?>
      <div
        style="border:1px solid var(--border);border-radius:8px;padding:18px;margin-bottom:14px;background:<?= $isOverdue ? '#fffbeb' : '#f9f7f2' ?>">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
          <div>
            <strong style="font-size:15px"><?= htmlspecialchars($r['title']) ?></strong><br>
            <small style="color:var(--muted)"><?= htmlspecialchars($r['author']) ?></small>
          </div>
          <?php if ($isOverdue): ?>
            <span class="badge badge-danger"><i class="fas fa-clock"></i> Overdue — Fine:
              ₹<?= number_format($fine, 2) ?></span>
          <?php else: ?>
            <span class="badge badge-success">On time</span>
          <?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;font-size:13px;margin-bottom:14px">
          <div><span style="color:var(--muted)">Issued:</span> <?= fmtDate($r['issue_date']) ?></div>
          <div><span style="color:var(--muted)">Due:</span> <?= fmtDate($r['due_date']) ?></div>
          <div><span style="color:var(--muted)">Fine (today):</span> ₹<?= number_format($fine, 2) ?></div>
        </div>
        <form method="POST" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
          <input type="hidden" name="issue_id" value="<?= $r['id'] ?>">
          <div class="form-group" style="margin:0">
            <label class="form-label" style="font-size:11px">Return Date</label>
            <input type="date" name="return_date" class="form-control" value="<?= $today ?>" style="width:160px" required>
          </div>
          <div class="form-group" style="flex:1;margin:0">
            <label class="form-label" style="font-size:11px">Remarks</label>
            <input type="text" name="remarks" class="form-control" placeholder="Optional remarks" style="min-width:150px">
          </div>
          <button type="submit" name="process_return" class="btn btn-success" style="margin-bottom:18px">
            <i class="fas fa-rotate-left"></i> Return This Book
          </button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>

<?php elseif (isset($_GET['find'])): ?>
  <div class="card">
    <div class="empty-state"><i class="fas fa-book-open"></i>
      <p>No issued books found for this student.</p>
    </div>
  </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>