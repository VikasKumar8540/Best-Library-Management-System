<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'Send Request';
$uid = $_SESSION['user_id'];
$msg = $err = '';

// Pre-fill book if coming from catalog
$preBook = isset($_GET['book_id']) ? (int)$_GET['book_id'] : null;
$preBookData = null;
if ($preBook) {
    $r = $conn->query("SELECT id,title,author FROM books WHERE id=$preBook AND status='active'");
    if ($r->num_rows) $preBookData = $r->fetch_assoc();
}

if (isset($_POST['send_request'])) {
    $title   = clean($_POST['request_title']);
    $message = clean($_POST['message']);
    $bookId  = !empty($_POST['book_id']) ? (int)$_POST['book_id'] : 'NULL';

    if (!$message) { $err = 'Please write your message.'; }
    else {
        if ($bookId === 'NULL') {
            $stmt = $conn->prepare("INSERT INTO book_requests (student_id,request_title,message) VALUES (?,?,?)");
            $stmt->bind_param("iss",$uid,$title,$message);
        } else {
            $stmt = $conn->prepare("INSERT INTO book_requests (student_id,book_id,request_title,message) VALUES (?,?,?,?)");
            $stmt->bind_param("iiss",$uid,$bookId,$title,$message);
        }
        if ($stmt->execute()) $msg = 'Request sent successfully! The librarian will respond soon.';
        else $err = 'Failed to send: '.$conn->error;
    }
}

// Past requests
$myRequests = $conn->query("SELECT br.*,b.title as book_title FROM book_requests br
    LEFT JOIN books b ON br.book_id=b.id WHERE br.student_id=$uid ORDER BY br.created_at DESC");

$books = $conn->query("SELECT id,title,author FROM books WHERE status='active' AND available_copies>0 ORDER BY title");
?>
<?php include '../includes/header.php'; ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="grid-2" style="gap:22px;align-items:start">
  <!-- Send Request Form -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-paper-plane"></i> New Request</div></div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Related Book (optional)</label>
        <select name="book_id" class="form-select">
          <option value="">— General query / No specific book —</option>
          <?php while($b=$books->fetch_assoc()): ?>
          <option value="<?= $b['id'] ?>" <?= ($preBookData && $preBookData['id']==$b['id'])?'selected':'' ?>>
            <?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['author']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Subject</label>
        <input type="text" name="request_title" class="form-control" placeholder="Brief subject of your request"
          value="<?= $preBookData ? 'Request to issue: '.htmlspecialchars($preBookData['title']) : '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Message *</label>
        <textarea name="message" class="form-control" required style="min-height:130px"
          placeholder="Describe your request or issue in detail. e.g. I would like to issue this book for my project research..."
          ><?= $preBookData ? 'Hello, I would like to request the book "'.$preBookData['title'].'" by '.$preBookData['author'].'. Please let me know if I can get it issued.' : '' ?></textarea>
      </div>
      <button type="submit" name="send_request" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Request</button>
    </form>
  </div>

  <!-- Past Requests -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left"></i> My Requests</div></div>
    <?php if ($myRequests->num_rows): while($r=$myRequests->fetch_assoc()): ?>
      <?php
        $statusColors = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger','resolved'=>'badge-info'];
        $statusColor  = $statusColors[$r['status']] ?? 'badge-muted';
      ?>
      <div style="border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
          <strong style="font-size:13px"><?= htmlspecialchars($r['request_title'] ?: 'General Request') ?></strong>
          <span class="badge <?= $statusColor ?>"><?= ucfirst($r['status']) ?></span>
        </div>
        <?php if ($r['book_title']): ?>
          <p style="font-size:12px;color:var(--gold);margin-bottom:6px"><i class="fas fa-book"></i> <?= htmlspecialchars($r['book_title']) ?></p>
        <?php endif; ?>
        <p style="font-size:12px;color:var(--muted);margin-bottom:6px"><?= htmlspecialchars(substr($r['message'],0,80)) ?>...</p>
        <?php if ($r['librarian_reply']): ?>
          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:10px;margin-top:8px">
            <small style="font-size:11px;color:var(--success);font-weight:600;text-transform:uppercase">Librarian Reply</small>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($r['librarian_reply'])) ?></p>
          </div>
        <?php endif; ?>
        <small style="color:var(--muted)"><?= fmtDate($r['created_at']) ?></small>
      </div>
    <?php endwhile; else: ?>
      <div class="empty-state"><i class="fas fa-paper-plane"></i><p>No requests yet.</p></div>
    <?php endif; ?>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
