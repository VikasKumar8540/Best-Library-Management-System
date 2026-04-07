<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'My Profile';
$msg = $err = '';

$uid  = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

// ── UPDATE NAME ONLY (email & phone are read-only for students) ──────────────
if (isset($_POST['update_profile'])) {
    $name = clean($_POST['name']);

    if (!$name) {
        $err = 'Full name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s.\'\-]+$/', $name)) {
        $err = 'Name can only contain letters, spaces, and basic punctuation.';
    } else {
        $conn->query("UPDATE users SET name='$name' WHERE id=$uid");
        $_SESSION['name'] = $name;
        $msg  = 'Profile updated successfully!';
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
    $maxSize = 2 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err = 'Upload failed. Please try again.';
    } elseif (!in_array($file['type'], $allowed)) {
        $err = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
    } elseif ($file['size'] > $maxSize) {
        $err = 'Image must be under 2 MB.';
    } else {
        $uploadDir = '../assets/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

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

$avatarSrc = !empty($user['avatar']) && file_exists('../' . $user['avatar'])
    ? '../' . $user['avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=c9922a&color=fff&size=120';
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<div class="grid-2" style="gap:22px;align-items:start">

  <!-- ── LEFT: Avatar + Account Info ──────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:22px">

    <!-- Avatar upload -->
    <div class="card" style="text-align:center">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-image"></i> Profile Picture</div>
      </div>
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

    <!-- Read-only account info -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-circle-info"></i> Account Info</div>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php
        $infoRows = [
            ['fas fa-id-badge',      'Student ID', $user['student_id'] ?: '—'],
            ['fas fa-graduation-cap','Role',        ucfirst($user['role'])],
            ['fas fa-envelope',      'Email',       $user['email']],
            ['fas fa-phone',         'Phone',       $user['phone'] ?: '—'],
            ['fas fa-calendar',      'Joined',      fmtDate($user['created_at'])],
            ['fas fa-map-marker-alt','Address',     $user['address'] ?: '—'],
        ];
        foreach ($infoRows as [$icon, $label, $val]): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--parchment);border:1px solid var(--border);border-radius:8px">
          <i class="fas <?= $icon ?>" style="color:var(--gold);width:16px;text-align:center;flex-shrink:0"></i>
          <div>
            <small style="color:var(--muted);display:block"><?= $label ?></small>
            <strong style="font-size:13px"><?= htmlspecialchars($val) ?></strong>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Info notice about read-only fields -->
      <div style="margin-top:14px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;display:flex;gap:10px;align-items:flex-start">
        <i class="fas fa-circle-info" style="color:#d97706;margin-top:2px;flex-shrink:0"></i>
        <span style="font-size:12px;color:#92400e;line-height:1.5">
          Email and phone can only be changed by an <strong>Admin</strong> or <strong>Librarian</strong>. Contact them if you need to update these details.
        </span>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: Edit Forms ─────────────────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:22px">

    <!-- Edit profile — name only -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-user-pen"></i> Edit Profile</div>
      </div>
      <form method="POST" id="profileForm" novalidate>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" id="nameInput" class="form-control" required
            value="<?= htmlspecialchars($user['name']) ?>"
            placeholder="Your full name"
            oninput="validateName(this)">
          <small id="nameError" style="color:var(--danger);font-size:12px;display:none;margin-top:4px">
            <i class="fas fa-exclamation-circle"></i> Name must contain letters only.
          </small>
        </div>

        <!-- Email — read-only display -->
        <div class="form-group">
          <label class="form-label" style="display:flex;align-items:center;gap:6px">
            Email
            <span style="background:#fef3c7;color:#92400e;font-size:10px;padding:2px 7px;border-radius:10px;font-weight:600;letter-spacing:.3px">
              <i class="fas fa-lock" style="font-size:9px"></i> READ-ONLY
            </span>
          </label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
            disabled style="background:#f5f5f5;color:var(--muted);cursor:not-allowed">
        </div>

        <!-- Phone — read-only display -->
        <div class="form-group">
          <label class="form-label" style="display:flex;align-items:center;gap:6px">
            Phone
            <span style="background:#fef3c7;color:#92400e;font-size:10px;padding:2px 7px;border-radius:10px;font-weight:600;letter-spacing:.3px">
              <i class="fas fa-lock" style="font-size:9px"></i> READ-ONLY
            </span>
          </label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '—') ?>"
            disabled style="background:#f5f5f5;color:var(--muted);cursor:not-allowed">
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
          <div style="position:relative">
            <input type="password" name="current_password" id="curPw" class="form-control" required
              placeholder="Enter current password">
            <button type="button" onclick="toggleVis('curPw',this)"
              style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">New Password * <small style="font-weight:400;color:var(--muted)">(min 6 chars)</small></label>
          <div style="position:relative">
            <input type="password" name="new_password" id="newPw" class="form-control" required
              placeholder="Enter new password" oninput="checkStrength(this.value)">
            <button type="button" onclick="toggleVis('newPw',this)"
              style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div style="margin-top:6px">
            <div style="height:4px;border-radius:2px;background:#e8dcc8;overflow:hidden">
              <div id="strengthFill" style="height:100%;width:0;border-radius:2px;transition:width .3s,background .3s"></div>
            </div>
            <small id="strengthText" style="font-size:11px;margin-top:3px;display:block"></small>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password *</label>
          <div style="position:relative">
            <input type="password" name="confirm_password" id="confirmPw" class="form-control" required
              placeholder="Repeat new password" oninput="checkMatch()">
            <button type="button" onclick="toggleVis('confirmPw',this)"
              style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted)">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <small id="matchMsg" style="font-size:12px;margin-top:4px;display:none"></small>
        </div>
        <button type="submit" name="change_password" class="btn btn-primary">
          <i class="fas fa-lock"></i> Update Password
        </button>
      </form>
    </div>

  </div>
</div>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
  }
}

function toggleVis(id, btn) {
  const input   = document.getElementById(id);
  const showing = input.type === 'text';
  input.type    = showing ? 'password' : 'text';
  btn.querySelector('i').className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
}

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

function checkStrength(val) {
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');
  const hasLen = val.length >= 6, hasNum = /\d/.test(val), hasUpp = /[A-Z]/.test(val);
  const score  = [hasLen, hasNum, hasUpp].filter(Boolean).length;
  const cfg    = [
    {w:'0%',   c:'#dc2626', t:''},
    {w:'33%',  c:'#dc2626', t:'Weak'},
    {w:'66%',  c:'#d97706', t:'Fair'},
    {w:'100%', c:'#16a34a', t:'Strong'},
  ][score];
  fill.style.width = cfg.w; fill.style.background = cfg.c;
  text.textContent = cfg.t; text.style.color = cfg.c;
}

function checkMatch() {
  const pw  = document.getElementById('newPw').value;
  const cpw = document.getElementById('confirmPw').value;
  const msg = document.getElementById('matchMsg');
  if (!cpw) { msg.style.display = 'none'; return; }
  msg.style.display = 'block';
  if (pw === cpw) { msg.textContent = '✓ Passwords match'; msg.style.color = '#16a34a'; }
  else            { msg.textContent = '✗ Passwords do not match'; msg.style.color = '#dc2626'; }
}

document.getElementById('profileForm').addEventListener('submit', function(e) {
  const nameInput = document.getElementById('nameInput');
  const nameVal   = nameInput.value.trim();
  if (!nameVal || !/^[a-zA-Z\s'\-\.]+$/.test(nameVal)) {
    e.preventDefault(); validateName(nameInput); nameInput.focus();
  }
});
</script>

<?php include '../includes/footer.php'; ?>
