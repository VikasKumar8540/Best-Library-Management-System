<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Issue Book';
$msg = $err = '';

// Get fine settings
$fs = $conn->query("SELECT * FROM fine_settings LIMIT 1")->fetch_assoc();
$maxDays = $fs['max_issue_days'] ?? 14;

if (isset($_POST['issue_book'])) {
    $bookId    = (int)$_POST['book_id'];
    $studentId = (int)$_POST['student_id'];
    $issueDate = clean($_POST['issue_date']);
    $dueDate   = clean($_POST['due_date']);
    $libId     = $_SESSION['user_id'];

    // Check availability
    $bk = $conn->query("SELECT available_copies FROM books WHERE id=$bookId AND status='active'");
    if (!$bk->num_rows) { $err = 'Book not found.'; }
    else {
        $avail = $bk->fetch_assoc()['available_copies'];
        if ($avail < 1) { $err = 'No copies available.'; }
        else {
            // Check student doesn't already have this book
            $existing = $conn->query("SELECT id FROM book_issues WHERE book_id=$bookId AND student_id=$studentId AND status='issued'");
            if ($existing->num_rows) { $err = 'Student already has this book issued.'; }
            else {
                $stmt = $conn->prepare("INSERT INTO book_issues (book_id,student_id,issued_by,issue_date,due_date) VALUES (?,?,?,?,?)");
                $stmt->bind_param("iiiss",$bookId,$studentId,$libId,$issueDate,$dueDate);
                if ($stmt->execute()) {
                    $conn->query("UPDATE books SET available_copies=available_copies-1 WHERE id=$bookId");
                    $msg = 'Book issued successfully!';
                } else { $err = 'Issue failed: '.$conn->error; }
            }
        }
    }
}

$books    = $conn->query("SELECT id,title,author,available_copies FROM books WHERE status='active' AND available_copies>0 ORDER BY title");
$students = $conn->query("SELECT id,name,student_id FROM users WHERE role='student' AND status='active' ORDER BY name");
$dueDefault = date('Y-m-d', strtotime("+{$maxDays} days"));
?>
<?php include '../includes/header.php'; ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="card" style="max-width:700px">
  <div class="card-header"><div class="card-title"><i class="fas fa-arrow-right-from-bracket"></i> Issue a Book</div></div>
  <form method="POST">
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Select Book *</label>
        <select name="book_id" class="form-select" required>
          <option value="">— Choose a book —</option>
          <?php while($b=$books->fetch_assoc()): ?>
          <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> by <?= htmlspecialchars($b['author']) ?> (<?= $b['available_copies'] ?> avail.)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Select Student *</label>
        <select name="student_id" class="form-select" required>
          <option value="">— Choose a student —</option>
          <?php while($s=$students->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> <?= $s['student_id'] ? '('.$s['student_id'].')' : '' ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Issue Date *</label>
        <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Due Date * <small style="color:var(--muted)">(Default: <?= $maxDays ?> days)</small></label>
        <input type="date" name="due_date" class="form-control" value="<?= $dueDefault ?>" required>
      </div>
    </div>
    <button type="submit" name="issue_book" class="btn btn-primary"><i class="fas fa-check"></i> Issue Book</button>
  </form>
</div>

<!-- Recent Issues Today -->
<div class="card" style="margin-top:22px">
  <div class="card-header"><div class="card-title"><i class="fas fa-clock"></i> Issued Today</div></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Book</th><th>Student</th><th>Issue Date</th><th>Due Date</th></tr></thead>
      <tbody>
      <?php
      $today = $conn->query("SELECT bi.*,b.title,u.name FROM book_issues bi
        JOIN books b ON bi.book_id=b.id JOIN users u ON bi.student_id=u.id
        WHERE DATE(bi.created_at)=CURDATE() ORDER BY bi.created_at DESC");
      if ($today->num_rows): while($r=$today->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['title']) ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= fmtDate($r['issue_date']) ?></td>
          <td><?= fmtDate($r['due_date']) ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="4" style="text-align:center;color:var(--muted)">No books issued today</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
