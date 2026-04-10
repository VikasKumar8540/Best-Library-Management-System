<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'LibraSync' ?> — LibraSync</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --ink: #1a1208;
  --gold: #c9922a;
  --gold-light: #e8b84b;
  --cream: #fdf8ef;
  --parchment: #f5eedc;
  --muted: #7a6a52;
  --border: #e8dcc8;
  --sidebar-w: 250px;
  --white: #ffffff;
  --danger: #dc2626;
  --success: #16a34a;
  --info: #0284c7;
  --warning: #d97706;
  --shadow: 0 2px 12px rgba(26,18,8,0.09);
  --shadow-lg: 0 8px 32px rgba(26,18,8,0.13);
  --radius: 10px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
  font-family: 'DM Sans', sans-serif;
  background: #f3ede0;
  color: var(--ink);
  min-height: 100vh;
  display: flex;
}

/* Sidebar */
.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: var(--ink);
  position: fixed;
  top: 0; left: 0;
  display: flex;
  flex-direction: column;
  z-index: 100;
  transition: transform 0.3s;
}
.sidebar-brand {
  padding: 24px 22px;
  border-bottom: 1px solid rgba(201,146,42,0.2);
  display: flex; align-items: center; gap: 12px;
}
.sidebar-brand .logo {
  width: 38px; height: 38px;
  background: rgba(201,146,42,0.15);
  border: 1px solid rgba(201,146,42,0.3);
  border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  color: var(--gold);
  font-size: 16px;
  flex-shrink: 0;
}
.sidebar-brand .brand-text h3 {
  font-family: 'Playfair Display', serif;
  color: var(--parchment);
  font-size: 18px;
  line-height: 1;
}
.sidebar-brand .brand-text small {
  color: var(--muted);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* ── Clickable sidebar user ───────────────────────────────────────────────── */
.sidebar-user {
  padding: 18px 22px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex; align-items: center; gap: 12px;
  text-decoration: none;
  transition: background 0.15s;
  cursor: pointer;
}
.sidebar-user:hover {
  background: rgba(201,146,42,0.08);
}
.sidebar-user:hover .user-name {
  color: var(--gold-light);
}
.sidebar-user:hover .profile-hint {
  opacity: 1;
}
.profile-hint {
  margin-left: auto;
  color: var(--muted);
  font-size: 11px;
  opacity: 0;
  transition: opacity 0.15s;
  flex-shrink: 0;
}
/* ─────────────────────────────────────────────────────────────────────────── */

.user-avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--gold), var(--rust, #8b3a1e));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 0;
  overflow: hidden;
}
.user-avatar img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
}
.user-info .user-name {
  color: var(--parchment);
  font-size: 13px;
  font-weight: 500;
  line-height: 1.2;
  max-width: 130px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.15s;
}
.user-info .user-role {
  color: var(--gold);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.sidebar-nav { flex: 1; padding: 16px 0; overflow-y: auto; }
.nav-section-title {
  padding: 8px 22px 4px;
  font-size: 10px;
  color: rgba(122,106,82,0.7);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  font-weight: 600;
}
.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 22px;
  color: rgba(245,238,220,0.65);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 400;
  transition: all 0.15s;
  position: relative;
  border-left: 3px solid transparent;
}
.nav-link:hover {
  color: var(--parchment);
  background: rgba(201,146,42,0.07);
  border-left-color: rgba(201,146,42,0.4);
}
.nav-link.active {
  color: var(--gold-light);
  background: rgba(201,146,42,0.12);
  border-left-color: var(--gold);
}
.nav-link i { width: 18px; text-align: center; font-size: 13px; flex-shrink: 0; }
.nav-badge {
  margin-left: auto;
  background: var(--danger);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  min-width: 20px;
  text-align: center;
}

.sidebar-footer {
  padding: 16px 22px;
  border-top: 1px solid rgba(255,255,255,0.05);
}
.btn-logout {
  display: flex; align-items: center; gap: 10px;
  width: 100%;
  padding: 10px 14px;
  background: rgba(220,38,38,0.1);
  border: 1px solid rgba(220,38,38,0.2);
  border-radius: 7px;
  color: #fca5a5;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s;
}
.btn-logout:hover {
  background: rgba(220,38,38,0.2);
  color: #fff;
}

/* Main content */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.topbar {
  background: var(--white);
  padding: 0 28px;
  height: 62px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
  box-shadow: var(--shadow);
  position: sticky; top: 0; z-index: 50;
}
.topbar-title {
  font-family: 'Playfair Display', serif;
  font-size: 20px;
  color: var(--ink);
  font-weight: 700;
}
.topbar-right {
  display: flex; align-items: center; gap: 14px;
}
.topbar-greeting {
  font-size: 13px;
  color: var(--muted);
}
.topbar-greeting strong { color: var(--ink); }

.page-content {
  padding: 28px;
  flex: 1;
}

/* Cards */
.card {
  background: var(--white);
  border-radius: var(--radius);
  padding: 22px 26px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
}
.card-title {
  font-family: 'Playfair Display', serif;
  font-size: 17px;
  color: var(--ink);
  display: flex; align-items: center; gap: 10px;
}
.card-title i { color: var(--gold); font-size: 15px; }

/* Stat cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 18px;
  margin-bottom: 26px;
}
.stat-card {
  background: var(--white);
  border-radius: var(--radius);
  padding: 20px 22px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  display: flex; align-items: center; gap: 16px;
  transition: transform 0.2s, box-shadow 0.2s;
  text-decoration: none;
  color: inherit;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}
.stat-icon {
  width: 50px; height: 50px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
.stat-icon.gold { background: rgba(201,146,42,0.12); color: var(--gold); }
.stat-icon.blue { background: rgba(2,132,199,0.1); color: var(--info); }
.stat-icon.green { background: rgba(22,163,74,0.1); color: var(--success); }
.stat-icon.red { background: rgba(220,38,38,0.1); color: var(--danger); }
.stat-icon.purple { background: rgba(124,58,237,0.1); color: #7c3aed; }
.stat-icon.orange { background: rgba(217,119,6,0.1); color: var(--warning); }

.stat-info .stat-value {
  font-size: 26px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1;
  margin-bottom: 3px;
}
.stat-info .stat-label {
  font-size: 12px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Tables */
.table-wrap { overflow-x: auto; }
table.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
}
table.data-table th {
  text-align: left;
  padding: 11px 14px;
  background: var(--parchment);
  color: var(--muted);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  font-weight: 600;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
table.data-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #f0e8d8;
  color: var(--ink);
  vertical-align: middle;
}
table.data-table tr:last-child td { border-bottom: none; }
table.data-table tr:hover td { background: rgba(245,238,220,0.5); }

/* Badges */
.badge {
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.3px;
  white-space: nowrap;
}
.badge-success { background: #dcfce7; color: #166534; }
.badge-danger  { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info    { background: #e0f2fe; color: #075985; }
.badge-muted   { background: #f3f4f6; color: #6b7280; }
.badge-gold    { background: rgba(201,146,42,0.12); color: #92400e; }

/* Buttons */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px;
  border-radius: 7px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  text-decoration: none;
  transition: all 0.15s;
  white-space: nowrap;
}
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-primary { background: var(--ink); color: var(--gold-light); }
.btn-primary:hover { background: #2a1e0a; box-shadow: 0 4px 12px rgba(26,18,8,0.2); }
.btn-gold { background: var(--gold); color: #fff; }
.btn-gold:hover { background: #b8821f; }
.btn-success { background: var(--success); color: #fff; }
.btn-success:hover { background: #15803d; }
.btn-danger { background: var(--danger); color: #fff; }
.btn-danger:hover { background: #b91c1c; }
.btn-outline { background: transparent; color: var(--ink); border: 1.5px solid var(--border); }
.btn-outline:hover { background: var(--parchment); border-color: var(--gold); }
.btn-info { background: var(--info); color: #fff; }
.btn-warning { background: var(--warning); color: #fff; }

/* Forms */
.form-group { margin-bottom: 18px; }
.form-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: var(--ink);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.form-control, .form-select {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid var(--border);
  border-radius: 7px;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--ink);
  background: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
}
.form-control:focus, .form-select:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(201,146,42,0.08);
}
textarea.form-control { resize: vertical; min-height: 90px; }

/* Alerts */
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-size: 13.5px;
  display: flex; align-items: center; gap: 10px;
}
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.alert-info    { background: #f0f9ff; color: #075985; border: 1px solid #bae6fd; }

/* Grid */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }

/* Empty state */
.empty-state {
  text-align: center;
  padding: 50px 20px;
  color: var(--muted);
}
.empty-state i { font-size: 40px; margin-bottom: 12px; color: var(--border); display: block; }
.empty-state p { font-size: 14px; }

/* Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--muted); }
</style>
</head>
<body>

<?php
$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'];
$initials = strtoupper(substr($name, 0, 1));
if (strpos($name, ' ') !== false) {
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0],0,1).substr($parts[1],0,1));
}

// Load fresh avatar from DB for sidebar
$avatarRow = $conn->query("SELECT avatar FROM users WHERE id=$uid")->fetch_assoc();
$sidebarAvatar = !empty($avatarRow['avatar']) && file_exists(__DIR__ . '/../' . $avatarRow['avatar'])
    ? APP_URL . '/' . $avatarRow['avatar']
    : null;

// Profile page URL per role
$profileUrl = match($role) {
    'admin'     => APP_URL . '/admin/profile.php',
    'librarian' => APP_URL . '/librarian/profile.php',
    'student'   => APP_URL . '/student/profile.php',
    default     => '#'
};

// Build nav based on role
$navItems = [];
if ($role === 'admin') {
    $navItems = [
        'main' => [
            ['href'=>'dashboard.php','icon'=>'fa-gauge-high','label'=>'Dashboard'],
            ['href'=>'librarians.php','icon'=>'fa-user-tie','label'=>'Librarians'],
            ['href'=>'students.php','icon'=>'fa-graduation-cap','label'=>'Students'],
        ],
        'books' => [
            ['href'=>'books.php','icon'=>'fa-book','label'=>'Books'],
            ['href'=>'categories.php','icon'=>'fa-tags','label'=>'Categories'],
        ],
        'settings' => [
            ['href'=>'fine_settings.php','icon'=>'fa-coins','label'=>'Fine Settings'],
            ['href'=>'reports.php','icon'=>'fa-chart-bar','label'=>'Reports'],
        ],
        'account' => [
            ['href'=>'profile.php','icon'=>'fa-user-pen','label'=>'My Profile'],
        ],
    ];
} elseif ($role === 'librarian') {
    $pendingReq = getPendingRequests();
    $navItems = [
        'main' => [
            ['href'=>'dashboard.php','icon'=>'fa-gauge-high','label'=>'Dashboard'],
            ['href'=>'students.php','icon'=>'fa-graduation-cap','label'=>'Students'],
        ],
        'books' => [
            ['href'=>'books.php','icon'=>'fa-book','label'=>'Books'],
            ['href'=>'issue_book.php','icon'=>'fa-arrow-right-from-bracket','label'=>'Issue Book'],
            ['href'=>'return_book.php','icon'=>'fa-rotate-left','label'=>'Return Book'],
            ['href'=>'issued_books.php','icon'=>'fa-list-check','label'=>'Issued Books'],
        ],
        'communicate' => [
            ['href'=>'requests.php','icon'=>'fa-message-dots','label'=>'Book Requests','badge'=>$pendingReq],
            ['href'=>'notifications.php','icon'=>'fa-bell','label'=>'Send Notifications'],
        ],
        'account' => [
            ['href'=>'profile.php','icon'=>'fa-user-pen','label'=>'My Profile'],
        ],
    ];
} else {
    $unread = getUnreadNotifications($uid);
    $unreadChat  = 0;
    $currentPage = basename($_SERVER['PHP_SELF']);

    if ($currentPage === 'chat.php') {
        // ON chat page — update last_chat_seen in DB to NOW, badge = 0
        $conn->query("UPDATE users SET last_chat_seen = NOW() WHERE id = $uid");
    } else {
        // OFF chat page — count messages from others AFTER last_chat_seen
        $seenRow  = $conn->query("SELECT last_chat_seen FROM users WHERE id = $uid")->fetch_assoc();
        $lastSeen = $seenRow['last_chat_seen'] ?? '2000-01-01 00:00:00';
        if (!$lastSeen) $lastSeen = '2000-01-01 00:00:00';
        $chatRes  = $conn->query("SELECT COUNT(*) as c FROM student_messages WHERE student_id != $uid AND created_at > '$lastSeen'");
        if ($chatRes) $unreadChat = (int)$chatRes->fetch_assoc()['c'];
    }

    $navItems = [
        'main' => [
            ['href'=>'dashboard.php','icon'=>'fa-gauge-high','label'=>'Dashboard'],
            ['href'=>'catalog.php','icon'=>'fa-book-open','label'=>'Book Catalog'],
            ['href'=>'my_books.php','icon'=>'fa-bookmark','label'=>'My Books'],
        ],
        'communicate' => [
            ['href'=>'notifications.php','icon'=>'fa-bell','label'=>'Notifications','badge'=>$unread],
            ['href'=>'requests.php','icon'=>'fa-paper-plane','label'=>'Send Request'],
            ['href'=>'chat.php','icon'=>'fa-comments','label'=>'Student Chat','badge'=>$unreadChat],
        ],
        'account' => [
            ['href'=>'fines.php','icon'=>'fa-coins','label'=>'My Fines'],
            ['href'=>'profile.php','icon'=>'fa-user-pen','label'=>'My Profile'],
        ],
    ];
}

// Current file for active nav
$currentFile = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo"><i class="fas fa-book-open"></i></div>
    <div class="brand-text">
      <h3>LibraSync</h3>
      <small><?= ucfirst($role) ?> Portal</small>
    </div>
  </div>

  <!-- ── Clickable user block → profile page ──────────────────────────────── -->
  <a href="<?= $profileUrl ?>" class="sidebar-user" title="Edit your profile">
    <div class="user-avatar">
      <?php if ($sidebarAvatar): ?>
        <img src="<?= htmlspecialchars($sidebarAvatar) ?>" alt="Avatar">
      <?php else: ?>
        <?= $initials ?>
      <?php endif; ?>
    </div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($name) ?></div>
      <div class="user-role"><?= ucfirst($role) ?></div>
    </div>
    <span class="profile-hint"><i class="fas fa-pen" style="font-size:10px"></i></span>
  </a>
  <!-- ─────────────────────────────────────────────────────────────────────── -->

  <nav class="sidebar-nav">
    <?php foreach ($navItems as $sectionKey => $items): ?>
    <div class="nav-section-title"><?= ucfirst($sectionKey) ?></div>
    <?php foreach ($items as $item): ?>
    <?php $isActive = ($currentFile === $item['href']) ? 'active' : ''; ?>
    <a href="<?= $item['href'] ?>" class="nav-link <?= $isActive ?>">
      <i class="fas <?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
      <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
      <span class="nav-badge"><?= $item['badge'] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/logout.php" class="btn-logout">
      <i class="fas fa-right-from-bracket"></i> Sign Out
    </a>
  </div>
</aside>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
    <div class="topbar-right">
      <!-- Topbar profile link -->
      <a href="<?= $profileUrl ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none;padding:6px 10px;border-radius:8px;transition:background 0.15s" onmouseover="this.style.background='var(--parchment)'" onmouseout="this.style.background='transparent'">
        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--gold),#8b3a1e);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:600;overflow:hidden;flex-shrink:0">
          <?php if ($sidebarAvatar): ?>
            <img src="<?= htmlspecialchars($sidebarAvatar) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?= $initials ?>
          <?php endif; ?>
        </div>
        <div class="topbar-greeting">
          Good <?= (date('H')<12)?'morning':((date('H')<17)?'afternoon':'evening') ?>,
          <strong><?= explode(' ', $name)[0] ?></strong>
        </div>
      </a>
    </div>
  </div>
  <div class="page-content">
