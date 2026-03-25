<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Librarians';
$msg = $err = '';

if (isset($_POST['add_librarian'])) {
  $name = clean($_POST['name']);
  $email = clean($_POST['email']);
  $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $phone = clean($_POST['phone']);

  if (!$name) {
    $err = 'Full name is required.';
  } elseif (preg_match('/\d/', $name)) {
    $err = 'Name cannot contain numbers.';
  } elseif (!preg_match('/^[a-zA-Z\s.\'\-]+$/', $name)) {
    $err = 'Name can only contain letters, spaces, and basic punctuation (. \' -).';
  } elseif ($phone && !preg_match('/^[0-9]+$/', $phone)) {
    $err = 'Phone number can only contain digits (0-9).';
  } elseif ($phone && (strlen($phone) < 10 || strlen($phone) > 15)) {
    $err = 'Phone number must be between 10 and 15 digits.';
  } elseif ($conn->query("SELECT id FROM users WHERE email='$email'")->num_rows) {
    $err = 'Email already exists.';
  } else {
    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,'$pass','librarian',?)");
    $stmt->bind_param("sss", $name, $email, $phone);
    if ($stmt->execute())
      $msg = 'Librarian added!';
    else
      $err = 'Error adding librarian.';
  }
}

if (isset($_GET['toggle'])) {
  $id = (int) $_GET['toggle'];
  $cur = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc()['status'];
  $new = $cur === 'active' ? 'inactive' : 'active';
  $conn->query("UPDATE users SET status='$new' WHERE id=$id");
  $msg = 'Status updated.';
}

$librarians = $conn->query("SELECT * FROM users WHERE role='librarian' ORDER BY created_at DESC");
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?>
  <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="card" style="margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-user-plus"></i> Add Librarian</div>
  </div>
  <form method="POST" id="addLibrarianForm">
    <div class="grid-3">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" id="nameInput" class="form-control" required placeholder="Librarian's full name"
          oninput="validateName(this)">
        <small id="nameError" style="color:var(--danger);font-size:12px;display:none;margin-top:4px">
          <i class="fas fa-exclamation-circle"></i> Name cannot contain numbers or special characters.
        </small>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required placeholder="librarian@email.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="text" name="password" class="form-control" required placeholder="Set a password">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" id="phoneInput" class="form-control" placeholder="e.g. 09661306395"
          oninput="validatePhone(this)" maxlength="15">
        <small id="phoneError" style="color:var(--danger);font-size:12px;display:none;margin-top:4px">
          <i class="fas fa-exclamation-circle"></i> Phone number can only contain digits (0–9).
        </small>
      </div>
    </div>
    <button type="submit" name="add_librarian" class="btn btn-primary">
      <i class="fas fa-plus"></i> Add Librarian
    </button>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-user-tie"></i> All Librarians</div>
    <span class="badge badge-info"><?= $librarians->num_rows ?> librarians</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $librarians->data_seek(0);
        $i = 1;
        while ($l = $librarians->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
            <td><?= htmlspecialchars($l['email']) ?></td>
            <td><?= $l['phone'] ?: '—' ?></td>
            <td><?= $l['status'] === 'active'
              ? '<span class="badge badge-success">Active</span>'
              : '<span class="badge badge-danger">Inactive</span>' ?></td>
            <td><?= fmtDate($l['created_at']) ?></td>
            <td>
              <a href="librarians.php?toggle=<?= $l['id'] ?>" class="btn btn-outline btn-sm"
                data-confirm="<?= $l['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this librarian?">
                <?= $l['status'] === 'active'
                  ? '<i class="fas fa-ban"></i> Deactivate'
                  : '<i class="fas fa-check"></i> Activate' ?>
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
    const val = input.value;
    const hasDigit = /\d/.test(val);
    const hasInvalid = /[^a-zA-Z\s.\'\-]/.test(val);
    const errEl = document.getElementById('nameError');

    if (hasDigit || hasInvalid) {
      input.style.borderColor = 'var(--danger)';
      input.style.boxShadow = '0 0 0 3px rgba(220,38,38,0.1)';
      errEl.style.display = 'block';
      errEl.innerHTML = hasDigit
        ? '<i class="fas fa-exclamation-circle"></i> Name cannot contain numbers.'
        : '<i class="fas fa-exclamation-circle"></i> Name can only contain letters, spaces, and basic punctuation (. \' -)';
    } else {
      input.style.borderColor = '';
      input.style.boxShadow = '';
      errEl.style.display = 'none';
    }
  }

  function validatePhone(input) {
    const errEl = document.getElementById('phoneError');
    input.value = input.value.replace(/[^0-9]/g, '');

    if (input.value.length > 0 && input.value.length < 10) {
      input.style.borderColor = 'var(--danger)';
      input.style.boxShadow = '0 0 0 3px rgba(220,38,38,0.1)';
      errEl.style.display = 'block';
      errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Phone number must be at least 10 digits.';
    } else {
      input.style.borderColor = '';
      input.style.boxShadow = '';
      errEl.style.display = 'none';
    }
  }

  document.getElementById('addLibrarianForm').addEventListener('submit', function (e) {
    const nameInput = document.getElementById('nameInput');
    const phoneInput = document.getElementById('phoneInput');
    const nameVal = nameInput.value.trim();
    const phoneVal = phoneInput.value.trim();

    if (/\d/.test(nameVal) || /[^a-zA-Z\s.\'\-]/.test(nameVal)) {
      e.preventDefault();
      validateName(nameInput);
      nameInput.focus();
      return;
    }
    if (phoneVal && (phoneVal.length < 7 || /[^0-9]/.test(phoneVal))) {
      e.preventDefault();
      validatePhone(phoneInput);
      phoneInput.focus();
    }
  });
</script>

<?php include '../includes/footer.php'; ?>