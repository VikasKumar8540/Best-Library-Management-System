<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Students';
$msg = $err = '';

// Add student
if (isset($_POST['add_student'])) {
  $name = clean($_POST['name']);
  $email = clean($_POST['email']);
  $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $phone = clean($_POST['phone']);
  $sid = clean($_POST['student_id']);
  $addr = clean($_POST['address']);

  if (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $name)) {
    $err = 'Name must contain letters only (no numbers or special characters).';
  } elseif ($conn->query("SELECT id FROM users WHERE email='$email'")->num_rows) {
    $err = 'Email already registered.';
  } elseif ($sid && $conn->query("SELECT id FROM users WHERE student_id='$sid'")->num_rows) {
    $err = 'A student with this Student ID already exists.';
  } else {
    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,phone,student_id,address) VALUES (?,?,'$pass','student',?,?,?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $sid, $addr);
    if ($stmt->execute())
      $msg = 'Student added!';
    else
      $err = 'Error: ' . $conn->error;
  }
}

// Toggle status
if (isset($_GET['toggle'])) {
  $id = (int) $_GET['toggle'];
  $cur = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc()['status'];
  $new = $cur === 'active' ? 'inactive' : 'active';
  $conn->query("UPDATE users SET status='$new' WHERE id=$id");
  $msg = 'Status updated.';
}

$students = $conn->query("SELECT u.*, 
    (SELECT COUNT(*) FROM book_issues bi WHERE bi.student_id=u.id AND bi.status='issued') as active_issues,
    (SELECT COALESCE(SUM(fine_amount),0) FROM book_issues bi WHERE bi.student_id=u.id AND bi.fine_paid='no') as pending_fine
    FROM users u WHERE u.role='student' ORDER BY u.created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?>
  <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="card" style="margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-user-plus"></i> Add New Student</div>
  </div>
  <form method="POST" novalidate>
    <div class="grid-3">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" id="nameInput" class="form-control" required
          placeholder="Student's full name"
          pattern="[a-zA-Z\s'\-\.]+"
          title="Name must contain letters only"
          oninput="validateName(this)">
        <small id="nameError" style="color:var(--danger);font-size:12px;display:none">
          <i class="fas fa-exclamation-circle"></i> Name must contain letters only.
        </small>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required placeholder="student@email.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="text" name="password" class="form-control" required placeholder="Set a password">
      </div>
      <div class="form-group">
        <label class="form-label">Student ID</label>
        <input type="text" name="student_id" class="form-control" placeholder="e.g. STU003">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" placeholder="Mobile number">
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" placeholder="City, State">
      </div>
    </div>
    <button type="submit" name="add_student" class="btn btn-primary">
      <i class="fas fa-user-plus"></i> Add Student
    </button>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-graduation-cap"></i> All Students</div>
    <span class="badge badge-info"><?= $students->num_rows ?> students</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Student ID</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Active Issues</th>
          <th>Fine</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1;
        while ($s = $students->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
            <td><?= $s['student_id'] ? '<span class="badge badge-gold">' . htmlspecialchars($s['student_id']) . '</span>' : '—' ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= $s['phone'] ?: '—' ?></td>
            <td><?= $s['active_issues'] > 0 ? '<span class="badge badge-info">' . $s['active_issues'] . '</span>' : '0' ?></td>
            <td><?= $s['pending_fine'] > 0 ? '<span class="badge badge-danger">₹' . number_format($s['pending_fine'], 2) . '</span>' : '₹0' ?></td>
            <td>
              <?php if ($s['status'] === 'active'): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-danger">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="students.php?toggle=<?= $s['id'] ?>" class="btn btn-outline btn-sm"
                data-confirm="<?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this student?">
                <?= $s['status'] === 'active' ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check"></i>' ?>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function validateName(input) {
  const valid = /^[a-zA-Z\s'\-\.]*$/.test(input.value);
  const errEl = document.getElementById('nameError');
  if (!valid) {
    // Strip invalid characters in real time
    input.value = input.value.replace(/[^a-zA-Z\s'\-\.]/g, '');
    errEl.style.display = 'block';
    input.style.borderColor = 'var(--danger)';
  } else {
    errEl.style.display = 'none';
    input.style.borderColor = input.value.length > 0 ? 'var(--success)' : '';
  }
}
</script>

<?php include '../includes/footer.php'; ?>
