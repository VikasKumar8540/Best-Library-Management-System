<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'My Profile';
$msg = $err = '';

$uid  = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

// ── UPDATE NAME & PHONE ──────────────────────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $name  = clean($_POST['name']);
    $phone = clean($_POST['phone']);

    if (!$name) {
        $err = 'Name cannot be empty.';
    } else {
        $conn->query("UPDATE users SET name='$name', phone='$phone' WHERE id=$uid");
        $_SESSION['user_name'] = $name;           // keep session in sync
        $msg = 'Profile updated successfully!';
        $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
    }
}

// ── CHANGE PASSWORD ──────────────────────────────────────────────────────────
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $err = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $err = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=$uid");
        $msg = 'Password changed successfully!';
    }
}

// ── UPLOAD PROFILE PICTURE ───────────────────────────────────────────────────
if (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
    $file    = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err = 'Upload failed. Please try again.';
    } elseif (!in_array($file['type'], $allowed)) {
        $err = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
    } elseif ($file['size'] > $maxSize) {
        $err = 'Image must be under 2 MB.';
    } else {
        $uploadDir = '../assets/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Delete old avatar if it exists and isn't the default
        if (!empty($user['avatar']) && file_exists('../' . $user['avatar'])) {
            unlink('../' . $user['avatar']);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;
        $dbPath   = 'assets/avatars/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $conn->query("UPDATE users SET avatar='$dbPath' WHERE id=$uid");
            $msg  = 'Profile picture updated!';
            $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
        } else {
            $err = 'Could not save the image. Check folder permissions.';
        }
    }
}

// Avatar display helper
$avatarSrc = !empty($user['avatar']) && file_exists('../' . $user['avatar'])
    ? '../' . $user['avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=c9922a&color=fff&size=120';
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="grid-2" style="gap:22px;align-items:start">

  <!-- ── LEFT: Avatar + Info ──────────────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:22px">

    <!-- Avatar card -->
    <div class="card" style="text-align:center">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-image"></i> Profile Picture</div>
      </div>

      <!-- Current avatar -->
      <div style="margin:10px 0 18px">
        <img id="avatarPreview" src="<?= htmlspecialchars($avatarSrc) ?>"
          alt="Avatar"
          style="width:110px;height:110px;border-radius:50%;object-fit:cover;border:3px solid var(--gold);display:block;margin:0 auto 10px">
        <span style="font-size:13px;color:var(--muted)">JPG, PNG, WEBP · max 2 MB</span>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <input type="file" name="avatar" id="avatarInput" accept="image/*"
            class="form-control" onchange="previewAvatar(this)" style="font-size:13px">
        </div>
        <button type="submit" name="update_avatar" class="btn btn-primary" style="width:100%">
          <i class="fas fa-upload"></i> Upload Picture
        </button>
      </form>
    </div>

    <!-- Read-only info card -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-circle-info"></i> Account Info</div>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php
        $infoRows = [
          ['fas fa-envelope', 'Email',   $user['email']],
          ['fas fa-shield',   'Role',    ucfirst($user['role'])],
          ['fas fa-calendar', 'Joined',  fmtDate($user['created_at'])],
        ];
        foreach ($infoRows as [$icon, $label, $val]): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--parchment);border:1px solid var(--border);border-radius:8px">
          <i class="fas <?= $icon ?>" style="color:var(--gold);width:16px;text-align:center"></i>
          <div>
            <small style="color:var(--muted);display:block"><?= $label ?></small>
            <strong style="font-size:13px"><?= htmlspecialchars($val) ?></strong>
          </div>
        </div>
        <?php endforeach; ?>
        <p style="font-size:12px;color:var(--muted);margin:4px 0 0">
          <i class="fas fa-lock" style="margin-right:4px"></i>Email cannot be changed. Contact admin if needed.
        </p>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: Edit Forms ────────────────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:22px">

    <!-- Update name & phone -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-user-pen"></i> Edit Profile</div>
      </div>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" required
            value="<?= htmlspecialchars($user['name']) ?>" placeholder="Your full name">
        </div>
        <div class="form-group">
          <label class="form-label">Email <small style="font-weight:400;color:var(--muted)">(read-only)</small></label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
            disabled style="opacity:.6;cursor:not-allowed">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control"
            value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Mobile number">
        </div>
        <button type="submit" name="update_profile" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>

    <!-- Change password -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-key"></i> Change Password</div>
      </div>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Current Password *</label>
          <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
        </div>
        <div class="form-group">
          <label class="form-label">New Password * <small style="font-weight:400;color:var(--muted)">(min 6 chars)</small></label>
          <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password *</label>
          <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
        </div>
        <button type="submit" name="change_password" class="btn btn-primary">
          <i class="fas fa-lock"></i> Update Password
        </button>
      </form>
    </div>

  </div>
</div>

<script>
// Live preview before upload
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include '../includes/footer.php'; ?>
