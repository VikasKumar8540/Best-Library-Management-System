<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Librarians';
$msg = $err = '';

if (isset($_POST['add_librarian'])) {
    $name  = clean($_POST['name']);
    $email = clean($_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = clean($_POST['phone']);

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows) { $err = 'Email already exists.'; }
    else {
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,'$pass','librarian',?)");
        $stmt->bind_param("sss",$name,$email,$phone);
        if ($stmt->execute()) $msg = 'Librarian added!';
        else $err = 'Error adding librarian.';
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc()['status'];
    $new = $cur==='active'?'inactive':'active';
    $conn->query("UPDATE users SET status='$new' WHERE id=$id");
    $msg = 'Status updated.';
}

$librarians = $conn->query("SELECT * FROM users WHERE role='librarian' ORDER BY created_at DESC");
?>
<?php include '../includes/header.php'; ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title"><i class="fas fa-user-plus"></i> Add Librarian</div></div>
  <form method="POST">
    <div class="grid-3">
      <div class="form-group"><label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password *</label>
        <input type="text" name="password" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control"></div>
    </div>
    <button type="submit" name="add_librarian" class="btn btn-primary"><i class="fas fa-plus"></i> Add Librarian</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-user-tie"></i> All Librarians</div></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
      <tbody>
      <?php $i=1; while($l=$librarians->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
        <td><?= htmlspecialchars($l['email']) ?></td>
        <td><?= $l['phone']?:'—' ?></td>
        <td><?= $l['status']==='active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?></td>
        <td><?= fmtDate($l['created_at']) ?></td>
        <td>
          <a href="librarians.php?toggle=<?= $l['id'] ?>" class="btn btn-outline btn-sm">
            <?= $l['status']==='active' ? '<i class="fas fa-ban"></i> Deactivate' : '<i class="fas fa-check"></i> Activate' ?>
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
