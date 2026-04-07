<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin')         redirect(APP_URL . '/admin/dashboard.php');
    elseif ($_SESSION['role'] === 'librarian') redirect(APP_URL . '/librarian/dashboard.php');
    else                                       redirect(APP_URL . '/student/dashboard.php');
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close(); // ── close once, here only ──

            // Generate 6-digit OTP
            $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 600);

            // Remove old OTPs
            $esc = $conn->real_escape_string($email);
            $conn->query("DELETE FROM password_resets WHERE email = '$esc'");

            // Store OTP in DB
            $stmt2 = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
            $stmt2->bind_param("sss", $email, $otp, $expires);
            $stmt2->execute();
            $stmt2->close();

            // Store email in session
            $_SESSION['reset_email'] = $email;

            // Try to send email
            $mailError = '';
            $sent = sendOtpEmail($email, $user['name'], $otp, $mailError);

            if ($sent) {
                redirect(APP_URL . '/verify-otp.php');
            } else {
                // DEV MODE: email failed — show OTP on screen
                // Remove this block before going live!
                $error = 'DEV_OTP:' . $otp . '|' . $mailError;
            }
        } else {
            $stmt->close(); // ── close in the else branch too ──
            $error = 'No active account found with this email address.';
        }
    }
}

// ── Parse dev OTP error ───────────────────────────────────────────────────────
$devOtp = '';
$smtpErr = '';
if (str_starts_with($error, 'DEV_OTP:')) {
    $parts   = explode('|', substr($error, 8), 2);
    $devOtp  = $parts[0];
    $smtpErr = $parts[1] ?? '';
    $error   = '';
}

// ── Mailer ────────────────────────────────────────────────────────────────────
function sendOtpEmail($toEmail, $toName, $otp, &$errorMsg = '') {
    $smtpHost = 'ssl://smtp.gmail.com';
    $smtpPort = 465;
    $username = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
    $password = defined('MAIL_PASSWORD') ? str_replace(' ', '', MAIL_PASSWORD) : '';
    $subject  = 'Your LibraSync Password Reset OTP';
    $htmlBody = getOtpEmailHtml($toName, $otp);
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode('LibraSync') . "?= <{$username}>\r\n";
    $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";
    $body = chunk_split(base64_encode($htmlBody));

    $errno = 0; $errstr = '';
    $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 15);
    if (!$socket) {
        $errorMsg = "Socket failed: [{$errno}] {$errstr}";
        return false;
    }

    $log = [];
    $cmd = function($command, $expectCode = null) use ($socket, &$log) {
        if ($command !== null) fwrite($socket, $command . "\r\n");
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $log[] = ($command ?? '[GREETING]') . ' => ' . trim($response);
        if ($expectCode && substr(trim($response), 0, 3) !== (string)$expectCode) {
            return false;
        }
        return $response;
    };

    $ok =
        $cmd(null,                      220) !== false &&
        $cmd("EHLO localhost",          250) !== false &&
        $cmd("AUTH LOGIN",              334) !== false &&
        $cmd(base64_encode($username),  334) !== false &&
        $cmd(base64_encode($password),  235) !== false &&
        $cmd("MAIL FROM:<{$username}>", 250) !== false &&
        $cmd("RCPT TO:<{$toEmail}>",    250) !== false &&
        $cmd("DATA",                    354) !== false;

    if ($ok) {
        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $ok = $cmd(null, 250) !== false;
    }

    $cmd("QUIT", null);
    fclose($socket);

    if (!$ok) {
        $errorMsg = "SMTP log: " . implode(' | ', array_slice($log, -3));
    }
    return $ok;
}

function getOtpEmailHtml($name, $otp) {
    $year = date('Y');
    $n = htmlspecialchars($name);
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5eedc;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5eedc;padding:40px 0">
  <tr><td align="center">
    <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10)">
      <tr><td style="background:#1a1208;padding:28px 36px;text-align:center">
        <span style="font-size:28px;font-weight:700;color:#f5eedc;font-family:Georgia,serif">Libra<span style="color:#c9922a;font-style:italic">Sync</span></span>
      </td></tr>
      <tr><td style="padding:36px">
        <p style="font-size:16px;color:#1a1208;margin:0 0 8px">Hello, <strong>{$n}</strong>!</p>
        <p style="font-size:14px;color:#7a6a52;line-height:1.7;margin:0 0 28px">Your one-time password reset code is below. It expires in <strong>10 minutes</strong>.</p>
        <div style="text-align:center;margin:0 0 28px">
          <div style="display:inline-block;background:#fdf8ef;border:2px dashed #c9922a;border-radius:12px;padding:20px 44px">
            <span style="font-size:42px;font-weight:700;letter-spacing:12px;color:#1a1208">{$otp}</span>
          </div>
        </div>
        <p style="font-size:12px;color:#7a6a52">If you did not request this, please ignore this email.</p>
      </td></tr>
      <tr><td style="background:#f5eedc;padding:16px 36px;text-align:center;border-top:1px solid #e8dcc8">
        <p style="font-size:11px;color:#7a6a52;margin:0">&copy; {$year} LibraSync</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LibraSync — Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{--ink:#1a1208;--parchment:#f5eedc;--gold:#c9922a;--gold-light:#e8b84b;--rust:#8b3a1e;--cream:#fdf8ef;--muted:#7a6a52;--shadow:rgba(26,18,8,0.18);}
  *{margin:0;padding:0;box-sizing:border-box;}
  body{min-height:100vh;background:var(--ink);display:flex;font-family:'DM Sans',sans-serif;overflow:hidden;}
  .bg-panel{flex:1;position:relative;background:radial-gradient(ellipse at 30% 50%,rgba(201,146,42,.12) 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,rgba(139,58,30,.10) 0%,transparent 50%),#0f0b06;display:flex;align-items:center;justify-content:center;padding:60px;overflow:hidden;}
  .bg-panel::before{content:'';position:absolute;inset:0;background-image:repeating-linear-gradient(0deg,transparent,transparent 60px,rgba(201,146,42,.03) 60px,rgba(201,146,42,.03) 61px),repeating-linear-gradient(90deg,transparent,transparent 60px,rgba(201,146,42,.03) 60px,rgba(201,146,42,.03) 61px);}
  .brand-section{position:relative;z-index:2;text-align:center;max-width:400px;}
  .brand-icon{width:80px;height:80px;border:2px solid var(--gold);border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto 28px;font-size:36px;color:var(--gold);position:relative;}
  .brand-icon::before{content:'';position:absolute;inset:4px;border:1px solid rgba(201,146,42,.3);border-radius:2px;}
  .brand-name{font-family:'Playfair Display',serif;font-size:52px;color:var(--parchment);letter-spacing:-1px;line-height:1;margin-bottom:12px;}
  .brand-name span{color:var(--gold);font-style:italic;}
  .brand-tagline{color:var(--muted);font-size:14px;letter-spacing:3px;text-transform:uppercase;font-weight:300;margin-bottom:40px;}
  .steps-box{background:rgba(201,146,42,.06);border:1px solid rgba(201,146,42,.15);border-radius:10px;padding:28px;text-align:left;}
  .steps-box h3{font-family:'Playfair Display',serif;color:var(--gold);font-size:17px;margin-bottom:18px;}
  .step-row{display:flex;gap:14px;align-items:flex-start;margin-bottom:16px;color:rgba(245,238,220,.75);font-size:13px;line-height:1.6;}
  .step-row:last-child{margin-bottom:0;}
  .step-num{width:26px;height:26px;border-radius:50%;flex-shrink:0;background:rgba(201,146,42,.18);border:1px solid rgba(201,146,42,.4);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;margin-top:1px;}
  .login-panel{width:460px;min-width:460px;background:var(--cream);display:flex;align-items:center;justify-content:center;padding:60px 50px;position:relative;}
  .login-panel::before{content:'';position:absolute;left:0;top:0;bottom:0;width:1px;background:linear-gradient(to bottom,transparent,var(--gold),transparent);}
  .login-box{width:100%;}
  .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;text-decoration:none;margin-bottom:28px;transition:color .2s;}
  .back-link:hover{color:var(--gold);}
  .login-header{margin-bottom:30px;}
  .icon-wrap{width:54px;height:54px;background:#f0e8d8;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;font-size:22px;color:var(--gold);}
  .login-header h2{font-family:'Playfair Display',serif;font-size:26px;color:var(--ink);font-weight:700;margin-bottom:6px;}
  .login-header p{color:var(--muted);font-size:14px;line-height:1.5;}
  .alert{padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:13px;display:flex;align-items:flex-start;gap:10px;}
  .alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
  .alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}
  .dev-box{background:#1a1208;border-radius:10px;padding:20px 22px;margin-bottom:20px;border:2px dashed var(--gold);}
  .dev-box p{color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;}
  .dev-otp{font-size:36px;font-weight:700;letter-spacing:10px;color:var(--gold-light);display:block;margin-bottom:10px;}
  .dev-box a{color:var(--gold);font-size:13px;}
  .dev-smtp{font-size:11px;color:#7a6a52;margin-top:8px;word-break:break-all;}
  .form-group{margin-bottom:20px;}
  .form-label{display:block;font-size:12px;font-weight:500;color:var(--ink);letter-spacing:.5px;text-transform:uppercase;margin-bottom:7px;}
  .input-icon{position:relative;}
  .input-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;}
  .form-control{width:100%;padding:12px 16px 12px 40px;border:1.5px solid #ddd4c0;border-radius:7px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--ink);background:#fff;outline:none;transition:all .2s;}
  .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,146,42,.1);}
  .btn-submit{width:100%;padding:14px;background:var(--ink);color:var(--gold-light);border:none;border-radius:7px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:all .2s;margin-top:6px;}
  .btn-submit:hover{background:#2a1e0a;transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,18,8,.25);}
  .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none;}
  @media(max-width:900px){.bg-panel{display:none;}.login-panel{width:100%;min-width:unset;}.login-panel::before{display:none;}}
</style>
</head>
<body>
<div class="bg-panel">
  <div class="brand-section">
    <div class="brand-icon"><i class="fas fa-book-open"></i></div>
    <div class="brand-name">Libra<span>Sync</span></div>
    <div class="brand-tagline">Library Management System</div>
    <div class="steps-box">
      <h3><i class="fas fa-key"></i> &nbsp;How it works</h3>
      <div class="step-row"><div class="step-num">1</div><div>Enter the email address linked to your account.</div></div>
      <div class="step-row"><div class="step-num">2</div><div>We'll send a 6-digit OTP — valid for 10 minutes.</div></div>
      <div class="step-row"><div class="step-num">3</div><div>Enter the OTP and set your new password instantly.</div></div>
    </div>
  </div>
</div>

<div class="login-panel">
  <div class="login-box">
    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
    <div class="login-header">
      <div class="icon-wrap"><i class="fas fa-lock-open"></i></div>
      <h2>Forgot Password?</h2>
      <p>Enter your registered email and we'll send you a one-time verification code.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:1px"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($devOtp): ?>
    <!-- DEV MODE BOX — remove before going live -->
    <div class="dev-box">
      <p>⚠️ Dev mode — email not sent. Your OTP:</p>
      <span class="dev-otp"><?= htmlspecialchars($devOtp) ?></span>
      <a href="verify-otp.php"><i class="fas fa-arrow-right"></i> Go to verify page and enter this code</a>
      <?php if ($smtpErr): ?>
      <div class="dev-smtp">SMTP: <?= htmlspecialchars($smtpErr) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="fpForm">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control"
            placeholder="Enter your registered email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required autofocus>
        </div>
      </div>
      <button type="submit" class="btn-submit" id="sendBtn">
        <i class="fas fa-paper-plane"></i> &nbsp; Send OTP
      </button>
    </form>
  </div>
</div>

<script>
document.getElementById('fpForm').addEventListener('submit', function() {
  var btn = document.getElementById('sendBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> &nbsp; Sending…';
});
</script>
</body>
</html>
