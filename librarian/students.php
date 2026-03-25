<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Students';
$msg = $err = '';

// Add student
if (isset($_POST['add_student'])) {
    $name  = clean($_POST['name']);
    $email = clean($_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = clean($_POST['phone']);
    $sid   = clean($_POST['student_id']);
    $addr  = clean($_POST['address']);

    if (!$name) {
        $err = 'Full name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $name)) {
        $err = 'Name must contain letters only (no numbers or special characters).';
    } elseif ($phone && !preg_match('/^[0-9]+$/', $phone)) {
        $err = 'Phone number can only contain digits (0–9).';
    } elseif ($phone && strlen($phone) < 10) {
        $err = 'Phone number must be at least 10 digits.';
    } elseif ($phone && strlen($phone) > 15) {
        $err = 'Phone number cannot exceed 15 digits.';
    } elseif ($conn->query("SELECT id FROM users WHERE email='$email'")->num_rows) {
        $err = 'A student with this email already exists.';
    } elseif ($sid && $conn->query("SELECT id FROM users WHERE student_id='$sid'")->num_rows) {
        $err = 'A student with this Student ID already exists.';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,phone,student_id,address) VALUES (?,?,'$pass','student',?,?,?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $sid, $addr);
        if ($stmt->execute()) $msg = 'Student added!';
        else $err = 'Error: ' . $conn->error;
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM users WHERE id=$id AND role='student'")->fetch_assoc()['status'];
    $new = $cur === 'active' ? 'inactive' : 'active';
    $conn->query("UPDATE users SET status='$new' WHERE id=$id");
    $msg = 'Status updated.';
}

// Search
$search = clean($_GET['search'] ?? '');
$where  = "WHERE u.role='student'";
if ($search) $where .= " AND (u.name LIKE '%$search%' OR u.student_id LIKE '%$search%' OR u.email LIKE '%$search%')";

$students = $conn->query("SELECT u.*,
    (SELECT COUNT(*) FROM book_issues bi WHERE bi.student_id=u.id AND bi.status='issued') as active_issues,
    (SELECT COALESCE(SUM(fine_amount),0) FROM book_issues bi WHERE bi.student_id=u.id AND bi.fine_paid='no') as pending_fine
    FROM users u $where ORDER BY u.created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<!-- Add Student -->
<div class="card" style="margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-user-plus"></i> Add New Student</div>
  </div>
  <form method="POST" id="addStudentForm" novalidate>
    <div class="grid-3">

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" id="nameInput" class="form-control" required
          placeholder="Student's full name"
          oninput="validateName(this)">
        <small id="nameError" style="color:var(--danger);font-size:12px;display:none;margin-top:4px">
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
        <label class="form-label">Student ID <small style="font-weight:400;color:var(--muted)">(must be unique)</small></label>
        <input type="text" name="student_id" class="form-control" placeholder="e.g. STU003">
      </div>

      <div class="form-group">
        <label class="form-label">Phone <small style="font-weight:400;color:var(--muted)">(10–15 digits)</small></label>
        <input type="text" name="phone" id="phoneInput" class="form-control"
          placeholder="e.g. 9876543210"
          oninput="validatePhone(this)"
          maxlength="15">
        <small id="phoneError" style="color:var(--danger);font-size:12px;display:none;margin-top:4px">
          <i class="fas fa-exclamation-circle"></i> Enter a valid phone number (10–15 digits).
        </small>
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

<!-- Students Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-graduation-cap"></i> All Students</div>
    <form method="GET" style="display:flex;gap:8px">
      <input type="text" name="search" class="form-control" style="width:220px"
        placeholder="Search name, ID, email..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
      <?php if ($search): ?><a href="students.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Name</th><th>Student ID</th><th>Email</th><th>Phone</th><th>Active Issues</th><th>Fine</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php if ($students->num_rows): $i = 1; while ($s = $students->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
          <td><?= $s['student_id'] ? '<span class="badge badge-gold">'.htmlspecialchars($s['student_id']).'</span>' : '—' ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td><?= $s['phone'] ?: '—' ?></td>
          <td><?= $s['active_issues'] > 0 ? '<span class="badge badge-info">'.$s['active_issues'].'</span>' : '0' ?></td>
          <td><?= $s['pending_fine'] > 0 ? '<span class="badge badge-danger">₹'.number_format($s['pending_fine'],2).'</span>' : '₹0' ?></td>
          <td>
            <?= $s['status'] === 'active'
              ? '<span class="badge badge-success">Active</span>'
              : '<span class="badge badge-danger">Inactive</span>' ?>
          </td>
          <td>
            <a href="students.php?toggle=<?= $s['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
               class="btn btn-outline btn-sm"
               data-confirm="<?= $s['status']==='active' ? 'Deactivate' : 'Activate' ?> this student?">
              <?= $s['status'] === 'active' ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check"></i>' ?>
            </a>
          </td>
        </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="9"><div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No students found.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function validateName(input) {
  const valid = /^[a-zA-Z\s'\-\.]*$/.test(input.value);
  const errEl = document.getElementById('nameError');
  if (!valid) {
    input.value             = input.value.replace(/[^a-zA-Z\s'\-\.]/g, '');
    input.style.borderColor = 'var(--danger)';
    input.style.boxShadow   = '0 0 0 3px rgba(220,38,38,0.1)';
    errEl.style.display     = 'block';
  } else {
    input.style.borderColor = input.value.length > 0 ? 'var(--success)' : '';
    input.style.boxShadow   = '';
    errEl.style.display     = 'none';
  }
}

function validatePhone(input) {
  const errEl = document.getElementById('phoneError');

  // Strip non-digits in real time
  input.value = input.value.replace(/[^0-9]/g, '');

  const len = input.value.length;

  if (len === 0) {
    input.style.borderColor = '';
    input.style.boxShadow   = '';
    errEl.style.display     = 'none';
  } else if (len < 10) {
    input.style.borderColor = 'var(--danger)';
    input.style.boxShadow   = '0 0 0 3px rgba(220,38,38,0.1)';
    errEl.style.display     = 'block';
    errEl.innerHTML         = '<i class="fas fa-exclamation-circle"></i> Phone number must be at least 10 digits.';
  } else if (len > 15) {
    input.style.borderColor = 'var(--danger)';
    input.style.boxShadow   = '0 0 0 3px rgba(220,38,38,0.1)';
    errEl.style.display     = 'block';
    errEl.innerHTML         = '<i class="fas fa-exclamation-circle"></i> Phone number cannot exceed 15 digits.';
  } else {
    input.style.borderColor = 'var(--success)';
    input.style.boxShadow   = '';
    errEl.style.display     = 'none';
  }
}

document.getElementById('addStudentForm').addEventListener('submit', function(e) {
  const nameInput  = document.getElementById('nameInput');
  const phoneInput = document.getElementById('phoneInput');
  const nameVal    = nameInput.value.trim();
  const phoneVal   = phoneInput.value.trim();

  if (!nameVal || !/^[a-zA-Z\s'\-\.]+$/.test(nameVal)) {
    e.preventDefault();
    validateName(nameInput);
    nameInput.focus();
    return;
  }

  if (phoneVal && (phoneVal.length < 10 || phoneVal.length > 15)) {
    e.preventDefault();
    validatePhone(phoneInput);
    phoneInput.focus();
  }
});
</script>

<?php include '../includes/footer.php'; ?>
