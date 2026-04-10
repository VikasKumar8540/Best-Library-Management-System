<?php
require_once '../includes/config.php';
requireLogin('student');

header('Content-Type: application/json');

$uid    = (int)$_SESSION['user_id'];
$uname  = $_SESSION['name'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Update last active ───────────────────────────────────────────────────────
$conn->query("UPDATE users SET last_active = NOW() WHERE id = $uid");

// ── Mark chat as visited in DB — clears sidebar badge on every page load ────
$conn->query("UPDATE users SET last_chat_seen = NOW() WHERE id = $uid");

// ── SEND message ─────────────────────────────────────────────────────────────
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg      = trim($_POST['message'] ?? '');
    $replyTo  = !empty($_POST['reply_to']) ? (int)$_POST['reply_to'] : null;

    if ($msg === '' || mb_strlen($msg) > 1000) {
        echo json_encode(['ok' => false]);
        exit;
    }

    if ($replyTo) {
        $stmt = $conn->prepare("INSERT INTO student_messages (student_id, message, reply_to) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $uid, $msg, $replyTo);
    } else {
        $stmt = $conn->prepare("INSERT INTO student_messages (student_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $uid, $msg);
    }
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok' => true]);
    exit;
}

// ── TYPING indicator ─────────────────────────────────────────────────────────
if ($action === 'typing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->query("UPDATE users SET typing_at = NOW() WHERE id = $uid");
    echo json_encode(['ok' => true]);
    exit;
}

// ── FETCH messages ───────────────────────────────────────────────────────────
if ($action === 'messages') {
    $since = (int)($_GET['since'] ?? 0);

    $msgs = $conn->query(
        "SELECT sm.id, sm.student_id, sm.message, sm.created_at,
                sm.reply_to,
                u.name, u.avatar,
                rm.message AS reply_message,
                ru.name    AS reply_name
         FROM student_messages sm
         JOIN users u ON sm.student_id = u.id
         LEFT JOIN student_messages rm ON sm.reply_to = rm.id
         LEFT JOIN users ru ON rm.student_id = ru.id
         WHERE sm.id > $since
         ORDER BY sm.id ASC
         LIMIT 50"
    );

    $messages = [];
    while ($row = $msgs->fetch_assoc()) {
        $messages[] = $row;
    }

    // Online students (active last 3 min)
    $onlineRes = $conn->query(
        "SELECT COUNT(*) as c FROM users
         WHERE role='student' AND status='active'
         AND last_active >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)"
    );
    $online = (int)$onlineRes->fetch_assoc()['c'];

    // Who is typing (last 3 seconds, not current user)
    $typingRes = $conn->query(
        "SELECT name FROM users
         WHERE id != $uid AND role='student'
         AND typing_at >= DATE_SUB(NOW(), INTERVAL 3 SECOND)"
    );
    $typing = [];
    while ($t = $typingRes->fetch_assoc()) $typing[] = $t['name'];

    echo json_encode([
        'ok'       => true,
        'messages' => $messages,
        'online'   => $online,
        'typing'   => $typing,
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);
