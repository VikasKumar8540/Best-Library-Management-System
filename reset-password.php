<?php
require_once 'includes/config.php';

// Must have completed OTP verification
if (!isset($_SESSION['reset_email']) || empty($_SESSION['reset_verified'])) {
    redirect(APP_URL . '/forgot-password.php');
}

$email   = $_SESSION['reset_email'];
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($password) || empty($password2)) {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one letter and one number.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();
        $stmt->close();

        // Clean up password_resets and session
        $esc = $conn->real_escape_string($email);
        $conn->query("DELETE FROM password_resets WHERE email = '$esc'");
        unset($_SESSION['reset_email'], $_SESSION['reset_verified']);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LibraSync — Reset Password</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{--ink:#1a1208;--parchment:#f5eedc;--gold:#c9922a;--gold-light:#e8b84b;--cream:#fdf8ef;--muted:#7a6a52;--shadow:rgba(26,18,8,0.18);}
  *{margin:0;padding:0;box-sizing:border-box;}
  body{min-height:100vh;background:var(--ink);display:flex;font-family:'DM Sans',sans-serif;overflow:hidden;}
  .bg-panel{
    flex:1;position:relative;
    background:radial-gradient(ellipse at 30% 50%,rgba(201,146,42,.12) 0%,transparent 60%),
               radial-gradient(ellipse at 80% 20%,rgba(139,58,30,.10) 0%,transparent 50%),#0f0b06;
    display:flex;align-items:center;justify-content:center;padding:60px;overflow:hidden;
  }
  .bg-panel::before{content:'';position:absolute;inset:0;
    background-image:repeating-linear-gradient(0deg,transparent,transparent 60px,rgba(201,146,42,.03) 60px,rgba(201,146,42,.03) 61px),
                     repeating-linear-gradient(90deg,transparent,transparent 60px,rgba(201,146,42,.03) 60px,rgba(201,146,42,.03) 61px);}
  .brand-section{position:relative;z-index:2;text-align:center;max-width:400px;}
  .brand-icon{width:80px;height:80px;border:2px solid var(--gold);border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto 28px;font-size:36px;color:var(--gold);position:relative;}
  .brand-icon::before{content:'';position:absolute;inset:4px;border:1px solid rgba(201,146,42,.3);border-radius:2px;}
  .brand-name{font-family:'Playfair Display',serif;font-size:52px;color:var(--parchment);letter-spacing:-1px;line-height:1;margin-bottom:12px;}
  .brand-name span{color:var(--gold);font-style:italic;}
  .brand-tagline{color:var(--muted);font-size:14px;letter-spacing:3px;text-transform:uppercase;font-weight:300;margin-bottom:36px;}
  .req-box{background:rgba(201,146,42,.06);border:1px solid rgba(201,146,42,.15);border-radius:10px;padding:24px;text-align:left;}
  .req-box h3{font-family:'Playfair Display',serif;color:var(--gold);font-size:16px;margin-bottom:16px;}
  .req-item{display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(245,238,220,.65);margin-bottom:11px;transition:color .3s;}
  .req-item:last-child{margin-bottom:0;}
  .req-item i{width:16px;font-size:13px;color:var(--muted);transition:color .3s;}
  .req-item.met{color:rgba(245,238,220,.95);}
  .req-item.met i{color:#4ade80;}

  /* right panel */
  .login-panel{width:460px;min-width:460px;background:var(--cream);display:flex;align-items:center;justify-content:center;padding:60px 50px;position:relative;}
  .login-panel::before{content:'';position:absolute;left:0;top:0;bottom:0;width:1px;background:linear-gradient(to bottom,transparent,var(--gold),transparent);}
  .login-box{width:100%;}
  .login-header{margin-bottom:28px;}
  .icon-wrap{width:54px;height:54px;background:#f0e8d8;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;font-size:22px;color:var(--gold);}
  .login-header h2{font-family:'Playfair Display',serif;font-size:26px;color:var(--ink);font-weight:700;margin-bottom:6px;}
  .login-header p{color:var(--muted);font-size:14px;line-height:1.5;}
  .alert{padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:10px;}
  .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
  .form-group{margin-bottom:18px;}
  .form-label{display:block;font-size:12px;font-weight:500;color:var(--ink);letter-spacing:.5px;text-transform:uppercase;margin-bottom:7px;}
  .input-icon{position:relative;}
  .input-icon .icon-l{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;}
  .toggle-eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;padding:0;font-size:14px;transition:color .2s;}
  .toggle-eye:hover{color:var(--gold);}
  .form-control{width:100%;padding:12px 40px;border:1.5px solid #ddd4c0;border-radius:7px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--ink);background:#fff;outline:none;transition:all .2s;}
  .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,146,42,.1);}

  /* strength bar */
  .strength-bar{display:flex;gap:4px;height:4px;margin-top:8px;}
  .strength-seg{flex:1;border-radius:2px;background:#e8dcc8;transition:background .3s;}
  .strength-txt{font-size:11px;color:var(--muted);margin-top:5px;min-height:15px;transition:color .3s;}

  .btn-submit{width:100%;padding:14px;background:var(--ink);color:var(--gold-light);border:none;border-radius:7px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:all .2s;margin-top:4px;}
  .btn-submit:hover{background:#2a1e0a;transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,18,8,.25);}

  /* success overlay */
  .success-overlay{display:none;position:fixed;inset:0;background:rgba(26,18,8,.85);z-index:999;align-items:center;justify-content:center;}
  .success-overlay.show{display:flex;}
  .success-card{background:var(--cream);border-radius:16px;padding:48px 40px;text-align:center;max-width:340px;animation:popIn .4s cubic-bezier(.34,1.56,.64,1);}
  @keyframes popIn{from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1}}
  .success-icon{width:72px;height:72px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:30px;color:#16a34a;}
  .success-card h3{font-family:'Playfair Display',serif;font-size:22px;color:var(--ink);margin-bottom:10px;}
  .success-card p{color:var(--muted);font-size:14px;line-height:1.6;}
  .redirect-bar{height:3px;background:#e8dcc8;border-radius:2px;margin-top:24px;overflow:hidden;}
  .redirect-fill{height:100%;background:var(--gold);border-radius:2px;width:0%;transition:width 2.8s linear;}

  @media(max-width:900px){.bg-panel{display:none;}.login-panel{width:100%;min-width:unset;}.login-panel::before{display:none;}}
</style>
</head>
<body>

<div class="bg-panel">
  <div class="brand-section">
    <div class="brand-icon"><i class="fas fa-book-open"></i></div>
    <div class="brand-name">Libra<span>Sync</span></div>
    <div class="brand-tagline">Library Management System</div>
    <div class="req-box">
      <h3><i class="fas fa-shield-halved"></i> &nbsp;Password Requirements</h3>
      <div class="req-item" id="req-len"><i class="fas fa-circle-xmark"></i> At least 8 characters</div>
      <div class="req-item" id="req-letter"><i class="fas fa-circle-xmark"></i> At least one letter (A–Z)</div>
      <div class="req-item" id="req-num"><i class="fas fa-circle-xmark"></i> At least one number (0–9)</div>
      <div class="req-item" id="req-match"><i class="fas fa-circle-xmark"></i> Both passwords match</div>
    </div>
  </div>
</div>

<div class="login-panel">
  <div class="login-box">
    <div class="login-header">
      <div class="icon-wrap"><i class="fas fa-key"></i></div>
      <h2>Set New Password</h2>
      <p>Create a strong new password for your LibraSync account.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-icon">
          <i class="fas fa-lock icon-l"></i>
          <input type="password" name="password" id="pw1" class="form-control"
            placeholder="Enter new password" required autofocus>
          <button type="button" class="toggle-eye" onclick="togglePw('pw1',this)">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div class="strength-bar">
          <div class="strength-seg" id="s1"></div>
          <div class="strength-seg" id="s2"></div>
          <div class="strength-seg" id="s3"></div>
          <div class="strength-seg" id="s4"></div>
        </div>
        <div class="strength-txt" id="strTxt"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="input-icon">
          <i class="fas fa-lock icon-l"></i>
          <input type="password" name="password2" id="pw2" class="form-control"
            placeholder="Repeat new password" required>
          <button type="button" class="toggle-eye" onclick="togglePw('pw2',this)">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <i class="fas fa-check-circle"></i> &nbsp; Update Password
      </button>
    </form>
  </div>
</div>

<!-- Success overlay -->
<div class="success-overlay <?= $success ? 'show' : '' ?>" id="successOverlay">
  <div class="success-card">
    <div class="success-icon"><i class="fas fa-check"></i></div>
    <h3>Password Updated!</h3>
    <p>Your password has been reset successfully. Redirecting you to the login page…</p>
    <div class="redirect-bar"><div class="redirect-fill" id="redirectFill"></div></div>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
  btn.querySelector('i').className = el.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

const pw1    = document.getElementById('pw1');
const pw2    = document.getElementById('pw2');
const segs   = [1,2,3,4].map(n => document.getElementById('s'+n));
const strTxt = document.getElementById('strTxt');
const colors = ['#e24b4a','#f59e0b','#c9922a','#16a34a'];
const labels = ['Weak','Fair','Good','Strong'];

function setReq(id, met) {
  const el = document.getElementById(id);
  el.classList.toggle('met', met);
  el.querySelector('i').className = met ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
}
function checkMatch() { setReq('req-match', pw2.value.length > 0 && pw1.value === pw2.value); }

pw1.addEventListener('input', () => {
  const v = pw1.value;
  const hasLen    = v.length >= 8;
  const hasLetter = /[A-Za-z]/.test(v);
  const hasNum    = /[0-9]/.test(v);
  setReq('req-len',    hasLen);
  setReq('req-letter', hasLetter);
  setReq('req-num',    hasNum);
  checkMatch();

  // strength
  let s = 0;
  if (v.length > 0)  s = 1;
  if (v.length >= 8 && (hasLetter || hasNum)) s = 2;
  if (v.length >= 8 && hasLetter && hasNum)   s = 3;
  if (v.length >= 10 && hasLetter && hasNum && /[^A-Za-z0-9]/.test(v)) s = 4;

  segs.forEach((seg, i) => seg.style.background = i < s ? colors[s-1] : '#e8dcc8');
  strTxt.textContent  = v.length > 0 ? labels[s-1] || '' : '';
  strTxt.style.color  = s > 0 ? colors[s-1] : 'var(--muted)';
});
pw2.addEventListener('input', checkMatch);

// Auto-redirect after success
<?php if ($success): ?>
window.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => { document.getElementById('redirectFill').style.width = '100%'; }, 60);
  setTimeout(() => { window.location.href = '<?= APP_URL ?>/index.php?msg=password_reset'; }, 3000);
});
<?php endif; ?>
</script>
</body>
</html>
