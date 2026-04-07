<?php
// Start session FIRST before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');

// App Config
define('APP_NAME', 'LibraSync');

// Dynamic APP_URL — works on any port, any subfolder depth
// config.php is always at: <app_root>/includes/config.php
// So app root = dirname(__DIR__)
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST']; // e.g. localhost:88
$_appRoot  = str_replace('\\', '/', dirname(__DIR__)); // e.g. C:/xampp/htdocs/library_management
$_docRoot  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')); // e.g. C:/xampp/htdocs
$_appPath  = str_replace($_docRoot, '', $_appRoot); // e.g. /library_management
define('APP_URL', $_protocol . '://' . $_host . $_appPath);

define('FINE_PER_DAY', 2.00); // Default Rs 2/day

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Helper: redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper: sanitize input
function clean($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

// Helper: check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: require login with role check
function requireLogin($role = null) {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/index.php');
    }
    if ($role && $_SESSION['role'] !== $role) {
        redirect(APP_URL . '/index.php?error=unauthorized');
    }
}

// Helper: get fine settings
function getFinePerDay() {
    global $conn;
    $r = $conn->query("SELECT fine_per_day FROM fine_settings LIMIT 1");
    if ($r && $r->num_rows > 0) return $r->fetch_assoc()['fine_per_day'];
    return FINE_PER_DAY;
}

// Helper: calculate fine
function calculateFine($dueDate, $returnDate = null) {
    $due  = strtotime($dueDate);
    $ret  = $returnDate ? strtotime($returnDate) : time();
    $diff = ceil(($ret - $due) / 86400);
    if ($diff <= 0) return 0;
    return $diff * getFinePerDay();
}

// Helper: format date
function fmtDate($d) {
    return $d ? date('d M Y', strtotime($d)) : '-';
}

// Helper: get unread notifications count for student
function getUnreadNotifications($userId) {
    global $conn;
    $uid = (int)$userId;
    $r = $conn->query("SELECT COUNT(*) as cnt FROM notifications
        WHERE is_read='no' AND (target_type='all' OR target_student_id=$uid)");
    return $r ? $r->fetch_assoc()['cnt'] : 0;
}

// Helper: get pending requests count for librarian
function getPendingRequests() {
    global $conn;
    $r = $conn->query("SELECT COUNT(*) as cnt FROM book_requests WHERE status='pending'");
    return $r ? $r->fetch_assoc()['cnt'] : 0;
}

// Mail Configuration (PHPMailer)
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_USERNAME',   'kvikas2302@gmail.com');   // Your Gmail address
define('MAIL_PASSWORD',   'xuofkdpjdosajhfg');    // Your Gmail App Password
define('MAIL_PORT',       587);
define('MAIL_FROM_NAME',  APP_NAME);

?>
