<?php
// ALL PHP LOGIC FIRST - before any HTML output
require_once 'includes/config.php';

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = clean($_POST['role'] ?? '');

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role=? AND status='active'");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['name']       = $user['name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['student_id'] = $user['student_id'];

                if ($role === 'admin')      redirect(APP_URL . '/admin/dashboard.php');
                elseif ($role === 'librarian') redirect(APP_URL . '/librarian/dashboard.php');
                else                        redirect(APP_URL . '/student/dashboard.php');
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'No account found with these credentials.';
        }
        $stmt->close();
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'You are not authorized to access that page.';
}

if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset') {
    $success = 'Password reset successfully! You can now sign in with your new password.';
}

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin')         redirect(APP_URL . '/admin/dashboard.php');
    elseif ($_SESSION['role'] === 'librarian') redirect(APP_URL . '/librarian/dashboard.php');
    else                                       redirect(APP_URL . '/student/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LibraSync — Library Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --ink: #1a1208;
    --parchment: #f5eedc;
    --gold: #c9922a;
    --gold-light: #e8b84b;
    --rust: #8b3a1e;
    --cream: #fdf8ef;
    --muted: #7a6a52;
    --shadow: rgba(26,18,8,0.18);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    min-height: 100vh;
    background: var(--ink);
    display: flex;
    font-family: 'DM Sans', sans-serif;
    overflow: hidden;
  }
  .bg-panel {
    flex: 1;
    position: relative;
    background:
      radial-gradient(ellipse at 30% 50%, rgba(201,146,42,0.12) 0%, transparent 60%),
      radial-gradient(ellipse at 80% 20%, rgba(139,58,30,0.10) 0%, transparent 50%),
      #0f0b06;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px;
    overflow: hidden;
  }
  .bg-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      repeating-linear-gradient(0deg, transparent, transparent 60px, rgba(201,146,42,0.03) 60px, rgba(201,146,42,0.03) 61px),
      repeating-linear-gradient(90deg, transparent, transparent 60px, rgba(201,146,42,0.03) 60px, rgba(201,146,42,0.03) 61px);
  }
  .brand-section {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 420px;
  }
  .brand-icon {
    width: 80px; height: 80px;
    border: 2px solid var(--gold);
    border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 28px;
    font-size: 36px; color: var(--gold);
    position: relative;
  }
  .brand-icon::before {
    content: '';
    position: absolute;
    inset: 4px;
    border: 1px solid rgba(201,146,42,0.3);
    border-radius: 2px;
  }
  .brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 52px;
    color: var(--parchment);
    letter-spacing: -1px;
    line-height: 1;
    margin-bottom: 12px;
  }
  .brand-name span { color: var(--gold); font-style: italic; }
  .brand-tagline {
    color: var(--muted);
    font-size: 14px;
    letter-spacing: 3px;
    text-transform: uppercase;
    font-weight: 300;
    margin-bottom: 48px;
  }
  .brand-features {
    display: flex; flex-direction: column; gap: 14px;
    text-align: left;
  }
  .feat-item {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 18px;
    background: rgba(201,146,42,0.06);
    border: 1px solid rgba(201,146,42,0.12);
    border-radius: 6px;
    color: rgba(245,238,220,0.7);
    font-size: 13px;
    letter-spacing: 0.3px;
  }
  .feat-item i { color: var(--gold); width: 16px; flex-shrink: 0; }

  /* Login panel */
  .login-panel {
    width: 460px;
    min-width: 460px;
    background: var(--cream);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 50px;
    position: relative;
  }
  .login-panel::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--gold), transparent);
  }
  .login-box { width: 100%; }
  .login-header {
    margin-bottom: 36px;
  }
  .login-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 30px;
    color: var(--ink);
    font-weight: 700;
    margin-bottom: 6px;
  }
  .login-header p {
    color: var(--muted);
    font-size: 14px;
  }

  .alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
    display: flex; align-items: center; gap: 10px;
  }
  .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
  .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

  .role-tabs {
    display: flex;
    background: #ede5d4;
    border-radius: 8px;
    padding: 4px;
    margin-bottom: 28px;
    gap: 3px;
  }
  .role-tab {
    flex: 1;
    padding: 9px 6px;
    border: none;
    background: transparent;
    border-radius: 6px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 500;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    letter-spacing: 0.3px;
  }
  .role-tab.active {
    background: var(--ink);
    color: var(--gold);
    box-shadow: 0 2px 8px var(--shadow);
  }
  .role-tab i { font-size: 11px; }

  .form-group { margin-bottom: 20px; }
  .form-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 7px;
  }
  .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid #ddd4c0;
    border-radius: 7px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--ink);
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
  }
  .form-control:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,146,42,0.1);
  }
  .input-icon {
    position: relative;
  }
  .input-icon i {
    position: absolute;
    left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 14px;
  }
  .input-icon .form-control { padding-left: 40px; }

  /* Forgot password link row */
  .pw-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 7px;
  }
  .pw-label-row .form-label { margin-bottom: 0; }
  .forgot-link {
    font-size: 12px;
    color: var(--gold);
    text-decoration: none;
    font-weight: 500;
    transition: color .2s;
  }
  .forgot-link:hover { color: var(--rust); text-decoration: underline; }

  .btn-login {
    width: 100%;
    padding: 14px;
    background: var(--ink);
    color: var(--gold-light);
    border: none;
    border-radius: 7px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
    position: relative;
    overflow: hidden;
  }
  .btn-login::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 40%, rgba(201,146,42,0.1));
  }
  .btn-login:hover {
    background: #2a1e0a;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(26,18,8,0.25);
  }
  .demo-creds {
    margin-top: 24px;
    padding: 14px;
    background: #f5eedc;
    border-radius: 7px;
    border: 1px solid #ddd4c0;
  }
  .demo-creds p {
    font-size: 11px;
    color: var(--muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
  }
  .demo-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--ink);
    padding: 3px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  .demo-row:last-child { border: none; }
  .demo-row span:first-child { color: var(--muted); }

  @media (max-width: 900px) {
    .bg-panel { display: none; }
    .login-panel { width: 100%; min-width: unset; }
    .login-panel::before { display: none; }
  }
</style>
</head>
<body>


<div class="bg-panel">
  <div class="brand-section">
    <div class="brand-icon"><i class="fas fa-book-open"></i></div>
    <div class="brand-name">Libra<span>Sync</span></div>
    <div class="brand-tagline">Library Management System</div>
    <div class="brand-features">
      <div class="feat-item"><i class="fas fa-books"></i> Manage books, authors & categories</div>
      <div class="feat-item"><i class="fas fa-exchange-alt"></i> Issue, return & track books</div>
      <div class="feat-item"><i class="fas fa-bell"></i> Notifications & student requests</div>
      <div class="feat-item"><i class="fas fa-coins"></i> Auto fine calculation</div>
      <div class="feat-item"><i class="fas fa-search"></i> Search & filter entire catalog</div>
    </div>
  </div>
</div>

<div class="login-panel">
  <div class="login-box">
    <div class="login-header">
      <h2>Welcome back</h2>
      <p>Sign in to continue to your dashboard</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="role-tabs">
        <button type="button" class="role-tab active" onclick="setRole('admin', this)">
          <i class="fas fa-shield-halved"></i> Admin
        </button>
        <button type="button" class="role-tab" onclick="setRole('librarian', this)">
          <i class="fas fa-user-tie"></i> Librarian
        </button>
        <button type="button" class="role-tab" onclick="setRole('student', this)">
          <i class="fas fa-graduation-cap"></i> Student
        </button>
      </div>
      <input type="hidden" name="role" id="roleInput" value="admin">

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control"
            placeholder="Enter your email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <div class="pw-label-row">
          <label class="form-label">Password</label>
          <a href="forgot-password.php" class="forgot-link">
            <i class="fas fa-key"></i> Forgot Password?
          </a>
        </div>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" class="form-control"
            placeholder="Enter your password" required>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="fas fa-arrow-right-to-bracket"></i> &nbsp; Sign In
      </button>
    </form>

    <div class="demo-creds">
      <p>Demo Credentials (password: <strong>password</strong>)</p>
      <div class="demo-row"><span>Admin:</span><span>admin@library.com</span></div>
      <div class="demo-row"><span>Librarian:</span><span>librarian@library.com</span></div>
      <div class="demo-row"><span>Student:</span><span>ananya@student.com</span></div>
    </div>
  </div>
</div>

<script>
function setRole(role, el) {
  document.getElementById('roleInput').value = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}
</script>
</body>
</html>
