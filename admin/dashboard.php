<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Dashboard';

// Stats
$totalBooks = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='active'")->fetch_assoc()['c'];
$totalStudents = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student' AND status='active'")->fetch_assoc()['c'];
$totalLibrarians = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='librarian' AND status='active'")->fetch_assoc()['c'];
$issuedBooks = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued'")->fetch_assoc()['c'];
$overdueBooks = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued' AND due_date < CURDATE()")->fetch_assoc()['c'];
$totalFines = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE fine_paid='no' AND fine_amount>0")->fetch_assoc()['t'];

// Recent issues
$recentIssues = $conn->query("SELECT bi.*, b.title as book_title, u.name as student_name, u.student_id as sid
    FROM book_issues bi
    JOIN books b ON bi.book_id=b.id
    JOIN users u ON bi.student_id=u.id
    ORDER BY bi.created_at DESC LIMIT 6");

// Recent students
$recentStudents = $conn->query("SELECT * FROM users WHERE role='student' ORDER BY created_at DESC LIMIT 5");
?>
<?php include '../includes/header.php'; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon gold"><i class="fas fa-books"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalBooks ?></div>
      <div class="stat-label">Total Books</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalStudents ?></div>
      <div class="stat-label">Students</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-user-tie"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalLibrarians ?></div>
      <div class="stat-label">Librarians</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-arrow-right-from-bracket"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= $issuedBooks ?></div>
      <div class="stat-label">Books Issued</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-clock"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= $overdueBooks ?></div>
      <div class="stat-label">Overdue</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fas fa-coins"></i></div>
    <div class="stat-info">
      <div class="stat-value">₹<?= number_format($totalFines, 0) ?></div>
      <div class="stat-label">Pending Fines</div>
    </div>
  </div>
</div>

<div class="grid-2" style="gap:20px">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list-check"></i> Recent Issues</div>
      <a href="../librarian/issued_books.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Book</th>
            <th>Student</th>
            <th>Due Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($recentIssues->num_rows > 0): ?>
            <?php while ($r = $recentIssues->fetch_assoc()): ?>
              <?php
              $today = strtotime(date('Y-m-d'));
              $due = strtotime($r['due_date']);
              $status = $r['status'];
              if ($status === 'issued' && $due < $today)
                $status = 'overdue';
              ?>
              <tr>
                <td><?= htmlspecialchars(substr($r['book_title'], 0, 28)) ?>...</td>
                <td><?= htmlspecialchars($r['student_name']) ?></td>
                <td><?= fmtDate($r['due_date']) ?></td>
                <td>
                  <?php if ($status === 'returned'): ?>
                    <span class="badge badge-success">Returned</span>
                  <?php elseif ($status === 'overdue'): ?>
                    <span class="badge badge-danger">Overdue</span>
                  <?php else: ?>
                    <span class="badge badge-info">Issued</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" style="text-align:center;color:var(--muted)">No recent issues</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-user-plus"></i> Recent Students</div>
      <a href="students.php" class="btn btn-outline btn-sm">Manage</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Student ID</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($recentStudents->num_rows > 0): ?>
            <?php while ($r = $recentStudents->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><span class="badge badge-gold"><?= $r['student_id'] ?: '—' ?></span></td>
                <td><?= fmtDate($r['created_at']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" style="text-align:center;color:var(--muted)">No students yet</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>