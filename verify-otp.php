<?php
require_once 'includes/config.php';

// Must come from forgot-password step
if (!isset($_SESSION['reset_email'])) {
  redirect(APP_URL . '/forgot-password.php');
}

$email = $_SESSION['reset_email'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Resend: wipe session and go back to step 1
  if (isset($_POST['resend'])) {
    $esc = $conn->real_escape_string($email);
    $conn->query("DELETE FROM password_resets WHERE email = '$esc'");
    unset($_SESSION['reset_email']);
    redirect(APP_URL . '/forgot-password.php?resend=1');
  }

  // Single OTP input
  $otp = preg_replace('/\D/', '', $_POST['otp'] ?? '');
  $otp = substr($otp, 0, 6);

  if (strlen($otp) !== 6) {
    $error = 'Please enter the complete 6-digit OTP.';
  } else {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
      "SELECT id FROM password_resets
             WHERE email = ? AND otp = ? AND expires_at > ? AND used = 0"
    );
    $stmt->bind_param("sss", $email, $otp, $now);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
      $row = $res->fetch_assoc();
      $conn->query("UPDATE password_resets SET used = 1 WHERE id = " . (int) $row['id']);
      $stmt->close();
      $_SESSION['reset_verified'] = true;
      redirect(APP_URL . '/reset-password.php');
    } else {
      $error = 'Invalid or expired OTP. Please check and try again.';
      $stmt->close();
    }
  }
}

function maskEmail($email)
{
  [$local, $domain] = explode('@', $email, 2);
  $len = strlen($local);
  if ($len <= 2)
    return str_repeat('*', $len) . '@' . $domain;
  return substr($local, 0, 2) . str_repeat('*', $len - 2) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LibraSync — Verify OTP</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --ink: #1a1208;
      --parchment: #f5eedc;
      --gold: #c9922a;
      --gold-light: #e8b84b;
      --cream: #fdf8ef;
      --muted: #7a6a52;
      --shadow: rgba(26, 18, 8, 0.18);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

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
      background: radial-gradient(ellipse at 30% 50%, rgba(201, 146, 42, .12) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(139, 58, 30, .10) 0%, transparent 50%), #0f0b06;
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
      background-image: repeating-linear-gradient(0deg, transparent, transparent 60px, rgba(201, 146, 42, .03) 60px, rgba(201, 146, 42, .03) 61px),
        repeating-linear-gradient(90deg, transparent, transparent 60px, rgba(201, 146, 42, .03) 60px, rgba(201, 146, 42, .03) 61px);
    }

    .brand-section {
      position: relative;
      z-index: 2;
      text-align: center;
      max-width: 400px;
    }

    .brand-icon {
      width: 80px;
      height: 80px;
      border: 2px solid var(--gold);
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 28px;
      font-size: 36px;
      color: var(--gold);
      position: relative;
    }

    .brand-icon::before {
      content: '';
      position: absolute;
      inset: 4px;
      border: 1px solid rgba(201, 146, 42, .3);
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

    .brand-name span {
      color: var(--gold);
      font-style: italic;
    }

    .brand-tagline {
      color: var(--muted);
      font-size: 14px;
      letter-spacing: 3px;
      text-transform: uppercase;
      font-weight: 300;
      margin-bottom: 36px;
    }

    .email-pill {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: rgba(201, 146, 42, .08);
      border: 1px solid rgba(201, 146, 42, .2);
      border-radius: 50px;
      padding: 12px 22px;
      color: var(--parchment);
      font-size: 14px;
      margin-bottom: 32px;
    }

    .email-pill i {
      color: var(--gold);
    }

    /* countdown ring */
    .ring-wrap {
      text-align: center;
    }

    .ring-wrap svg {
      display: block;
      margin: 0 auto 10px;
    }

    .ring-label {
      font-size: 13px;
      color: var(--muted);
    }

    .ring-label strong {
      color: var(--gold-light);
    }

    /* right panel */
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
      left: 0;
      top: 0;
      bottom: 0;
      width: 1px;
      background: linear-gradient(to bottom, transparent, var(--gold), transparent);
    }

    .login-box {
      width: 100%;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--muted);
      font-size: 13px;
      text-decoration: none;
      margin-bottom: 28px;
      transition: color .2s;
    }

    .back-link:hover {
      color: var(--gold);
    }

    .login-header {
      margin-bottom: 28px;
    }

    .icon-wrap {
      width: 54px;
      height: 54px;
      background: #f0e8d8;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 18px;
      font-size: 22px;
      color: var(--gold);
    }

    .login-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 26px;
      color: var(--ink);
      font-weight: 700;
      margin-bottom: 6px;
    }

    .login-header p {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.5;
    }

    .sent-to {
      font-weight: 600;
      color: var(--ink);
    }

    .alert {
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-error {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    .alert-success {
      background: #f0fdf4;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    /* OTP single input */
    .otp-label {
      font-size: 12px;
      font-weight: 500;
      color: var(--ink);
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: 12px;
      display: block;
    }

    .otp-single {
      width: 100%;
      height: 64px;
      text-align: center;
      font-size: 28px;
      font-weight: 700;
      letter-spacing: 10px;
      color: var(--ink);
      background: #fff;
      border: 1.5px solid #ddd4c0;
      border-radius: 10px;
      outline: none;
      font-family: monospace;
      transition: all .2s;
      caret-color: var(--gold);
      margin-bottom: 24px;
      padding: 0 16px;
    }

    .otp-single:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(201, 146, 42, .12);
    }

    .otp-single.filled {
      border-color: var(--gold);
      background: #fffaf0;
    }


    .btn-submit {
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
      transition: all .2s;
    }

    .btn-submit:hover {
      background: #2a1e0a;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(26, 18, 8, .25);
    }

    .btn-submit:disabled {
      opacity: .5;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .resend-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 20px;
      font-size: 13px;
      color: var(--muted);
    }

    .resend-btn {
      background: none;
      border: none;
      color: var(--gold);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      padding: 0;
      text-decoration: underline;
    }

    .resend-btn:disabled {
      opacity: .4;
      cursor: not-allowed;
      text-decoration: none;
    }

    #cdLabel {
      font-weight: 600;
      color: var(--ink);
    }

    @media(max-width:900px) {
      .bg-panel {
        display: none;
      }

      .login-panel {
        width: 100%;
        min-width: unset;
      }

      .login-panel::before {
        display: none;
      }
    }
  </style>
</head>

<body>

  <div class="bg-panel">
    <div class="brand-section">
      <div class="brand-icon"><i class="fas fa-book-open"></i></div>
      <div class="brand-name">Libra<span>Sync</span></div>
      <div class="brand-tagline">Library Management System</div>

      <div class="email-pill">
        <i class="fas fa-envelope-open-text"></i>
        OTP sent to <strong>&nbsp;<?= htmlspecialchars(maskEmail($email)) ?></strong>
      </div>

      <div class="ring-wrap">
        <svg width="90" height="90" viewBox="0 0 90 90">
          <circle cx="45" cy="45" r="38" fill="none" stroke="rgba(201,146,42,.15)" stroke-width="7" />
          <circle id="ringCircle" cx="45" cy="45" r="38" fill="none" stroke="#c9922a" stroke-width="7"
            stroke-linecap="round" stroke-dasharray="238.76" stroke-dashoffset="0" transform="rotate(-90 45 45)"
            style="transition:stroke-dashoffset 1s linear" />
          <text id="svgTime" x="45" y="51" text-anchor="middle" fill="#e8b84b" font-family="DM Sans,sans-serif"
            font-size="17" font-weight="600">10:00</text>
        </svg>
        <p class="ring-label">OTP expires in <strong id="ringLabel">10:00</strong></p>
      </div>
    </div>
  </div>

  <div class="login-panel">
    <div class="login-box">
      <a href="forgot-password.php" class="back-link"><i class="fas fa-arrow-left"></i> Change Email</a>

      <div class="login-header">
        <div class="icon-wrap"><i class="fas fa-shield-check"></i></div>
        <h2>Enter OTP</h2>
        <p>A 6-digit code was sent to <span class="sent-to"><?= htmlspecialchars(maskEmail($email)) ?></span>. Enter it
          below.</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['resend'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> A fresh OTP has been sent to your email.
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="otpForm">
        <span class="otp-label">Verification Code</span>
        <input type="text" name="otp" id="otpInput" class="otp-single" maxlength="6"
          inputmode="numeric" pattern="[0-9]{6}" placeholder="——————"
          autocomplete="one-time-code" autofocus required>
        <button type="submit" class="btn-submit" id="verifyBtn">
          <i class="fas fa-check-circle"></i> &nbsp; Verify OTP
        </button>
      </form>

      <div class="resend-row">
        Didn't receive it?
        <form method="POST" style="display:inline">
          <button type="submit" name="resend" value="1" class="resend-btn" id="resendBtn" disabled>
            Resend OTP
          </button>
        </form>
        <span id="cdLabel">&nbsp;(0:30)</span>
      </div>
    </div>
  </div>

  <script>
    // ── OTP single input behaviour ───────────────────────────────────────────────
    const otpInput = document.getElementById('otpInput');
    otpInput.addEventListener('input', () => {
      otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
      otpInput.classList.toggle('filled', otpInput.value.length > 0);
    });
    otpInput.focus();

    // ── Resend countdown (30 s) ───────────────────────────────────────────────────
    let resendSec = 30;
    const resendBtn = document.getElementById('resendBtn');
    const cdLabel = document.getElementById('cdLabel');
    const resendTimer = setInterval(() => {
      resendSec--;
      if (resendSec <= 0) {
        clearInterval(resendTimer);
        resendBtn.disabled = false;
        cdLabel.textContent = '';
      } else {
        cdLabel.textContent = ' (0:' + String(resendSec).padStart(2, '0') + ')';
      }
    }, 1000);

    // ── OTP expiry ring (10 min) ──────────────────────────────────────────────────
    let totalSec = 600;
    const circ = document.getElementById('ringCircle');
    const svgTxt = document.getElementById('svgTime');
    const ringLbl = document.getElementById('ringLabel');
    const verifyBtn = document.getElementById('verifyBtn');
    const circumference = 238.76;

    const expTimer = setInterval(() => {
      totalSec--;
      if (totalSec <= 0) {
        clearInterval(expTimer);
        svgTxt.textContent = '0:00';
        ringLbl.textContent = '0:00';
        circ.style.stroke = '#dc2626';
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'OTP Expired';
        return;
      }
      const m = Math.floor(totalSec / 60);
      const s = String(totalSec % 60).padStart(2, '0');
      const lbl = m + ':' + s;
      svgTxt.textContent = lbl;
      ringLbl.textContent = lbl;
      circ.setAttribute('stroke-dashoffset', circumference * (1 - totalSec / 600));
      if (totalSec <= 60) circ.style.stroke = '#e24b4a';
    }, 1000);
  </script>
</body>

</html>