<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'Student Chat';

$uid   = $_SESSION['user_id'];
$uname = $_SESSION['name'];

// ── Clear badge immediately on page load — update DB ────────────────────────
$conn->query("UPDATE users SET last_chat_seen = NOW() WHERE id = $uid");

// Fetch current user avatar
$me = $conn->query("SELECT avatar FROM users WHERE id=$uid")->fetch_assoc();
$myAvatar = !empty($me['avatar']) && file_exists('../' . $me['avatar'])
    ? APP_URL . '/' . $me['avatar']
    : null;
?>
<?php include '../includes/header.php'; ?>

<style>
.chat-wrap {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 140px);
  background: var(--white);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}

/* ── Header ── */
.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 22px;
  background: var(--ink);
  border-bottom: 2px solid var(--gold);
  flex-shrink: 0;
}
.chat-header-left { display:flex; align-items:center; gap:12px; }
.chat-header-icon {
  width:38px; height:38px;
  background:rgba(201,146,42,0.15);
  border:1px solid rgba(201,146,42,0.3);
  border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  color:var(--gold); font-size:16px;
}
.chat-header-title { font-family:'Playfair Display',serif; font-size:17px; color:var(--parchment); font-weight:600; }
.chat-header-sub   { font-size:11px; color:var(--muted); margin-top:1px; }
.online-dot {
  width:8px; height:8px; background:#22c55e; border-radius:50%;
  display:inline-block; margin-right:5px;
  box-shadow:0 0 6px rgba(34,197,94,0.5);
  animation:pulse-dot 2s infinite;
}
@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:0.4} }
.online-count { font-size:12px; color:#86efac; display:flex; align-items:center; }

/* ── Messages area ── */
.chat-messages {
  flex:1; overflow-y:auto;
  padding:20px 22px;
  display:flex; flex-direction:column; gap:14px;
  background:#f9f5ed;
  background-image:
    radial-gradient(circle at 20% 80%, rgba(201,146,42,0.04) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(201,146,42,0.03) 0%, transparent 50%);
}
.chat-messages::-webkit-scrollbar { width:5px; }
.chat-messages::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }

/* ── Message rows ── */
.msg-row {
  display:flex; align-items:flex-end; gap:10px;
  animation:fadeUp 0.25s ease;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.msg-row.mine { flex-direction:row-reverse; }

.msg-avatar {
  width:34px; height:34px; border-radius:50%;
  object-fit:cover; flex-shrink:0;
  border:2px solid var(--border);
}
.msg-avatar-initials {
  width:34px; height:34px; border-radius:50%;
  background:linear-gradient(135deg, var(--gold), #8b3a1e);
  display:flex; align-items:center; justify-content:center;
  color:#fff; font-size:13px; font-weight:600;
  flex-shrink:0; border:2px solid var(--border);
}

.msg-body { max-width:65%; }
.msg-name  { font-size:11px; font-weight:600; color:var(--muted); margin-bottom:4px; padding:0 4px; }
.msg-row.mine .msg-name { text-align:right; color:var(--gold); }

/* ── Reply preview inside bubble ── */
.reply-preview {
  background: rgba(201,146,42,0.1);
  border-left: 3px solid var(--gold);
  border-radius: 6px 6px 0 0;
  padding: 6px 10px;
  font-size: 12px;
  color: var(--muted);
  margin-bottom: -4px;
  line-height: 1.4;
}
.reply-preview strong { color: var(--gold); display:block; font-size:11px; margin-bottom:2px; }
.msg-row.mine .reply-preview {
  background: rgba(255,255,255,0.12);
  border-left-color: var(--gold-light);
  color: rgba(245,238,220,0.7);
}
.msg-row.mine .reply-preview strong { color: var(--gold-light); }

.msg-bubble {
  padding:10px 14px;
  border-radius:16px;
  font-size:14px; line-height:1.55;
  color:var(--ink);
  background:#fff;
  border:1px solid var(--border);
  border-bottom-left-radius:4px;
  word-break:break-word;
  box-shadow:0 1px 4px rgba(0,0,0,0.06);
  position:relative;
}
.msg-row.mine .msg-bubble {
  background:var(--ink); color:var(--parchment);
  border:none; border-bottom-right-radius:4px; border-bottom-left-radius:16px;
}

/* ── Reply button on hover ── */
.msg-actions {
  display:none;
  align-items:center;
  gap:4px;
  padding-bottom:6px;
}
.msg-row:hover .msg-actions { display:flex; }
.msg-row.mine .msg-actions { flex-direction:row-reverse; }

.btn-reply-msg {
  background:none; border:1px solid var(--border);
  border-radius:20px; padding:3px 10px;
  font-size:11px; color:var(--muted); cursor:pointer;
  display:flex; align-items:center; gap:4px;
  transition:all 0.15s;
  font-family:'DM Sans',sans-serif;
}
.btn-reply-msg:hover { background:var(--parchment); color:var(--gold); border-color:var(--gold); }

.msg-time { font-size:10px; color:var(--muted); margin-top:4px; padding:0 4px; }
.msg-row.mine .msg-time { text-align:right; }

/* ── Date divider ── */
.date-divider {
  text-align:center; font-size:11px; color:var(--muted);
  margin:6px 0; display:flex; align-items:center; gap:10px;
}
.date-divider::before,.date-divider::after {
  content:''; flex:1; height:1px; background:var(--border);
}

/* ── Typing bar ── */
.typing-bar {
  padding:6px 22px; font-size:12px; color:var(--muted);
  background:#f9f5ed; border-top:1px solid var(--border);
  min-height:28px; font-style:italic; flex-shrink:0;
}

/* ── Reply context bar (shown when replying) ── */
.reply-context {
  display:none;
  align-items:center;
  gap:10px;
  padding:8px 18px;
  background:#fff8ec;
  border-top:1px solid var(--gold);
  border-bottom:1px solid var(--border);
  flex-shrink:0;
}
.reply-context.active { display:flex; }
.reply-context-text {
  flex:1;
  font-size:12px;
  color:var(--muted);
  line-height:1.4;
}
.reply-context-text strong { color:var(--gold); display:block; font-size:11px; }
.reply-context-text span { color:var(--ink); }
.btn-cancel-reply {
  background:none; border:none; cursor:pointer;
  color:var(--muted); font-size:16px; flex-shrink:0;
  transition:color 0.15s; padding:2px;
}
.btn-cancel-reply:hover { color:var(--danger); }

/* ── Input bar ── */
.chat-input-bar {
  display:flex; align-items:flex-end; gap:10px;
  padding:14px 18px;
  background:var(--white);
  border-top:1px solid var(--border);
  flex-shrink:0;
}
.chat-input {
  flex:1; padding:11px 16px;
  border:1.5px solid var(--border); border-radius:24px;
  font-family:'DM Sans',sans-serif; font-size:14px; color:var(--ink);
  background:var(--parchment); outline:none; resize:none;
  line-height:1.5; max-height:120px;
  transition:border-color 0.2s, box-shadow 0.2s;
}
.chat-input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,146,42,0.08); background:#fff; }

.btn-send {
  width:44px; height:44px; border-radius:50%;
  background:var(--ink); border:none; color:var(--gold-light);
  font-size:16px; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0; transition:all 0.2s;
}
.btn-send:hover { background:#2a1e0a; transform:scale(1.05); }
.btn-send:disabled { opacity:0.4; cursor:not-allowed; transform:none; }

.chat-empty {
  flex:1; display:flex; flex-direction:column;
  align-items:center; justify-content:center;
  color:var(--muted); gap:10px;
}
.chat-empty i { font-size:40px; color:var(--border); }
</style>

<div class="chat-wrap">

  <!-- Header -->
  <div class="chat-header">
    <div class="chat-header-left">
      <div class="chat-header-icon"><i class="fas fa-comments"></i></div>
      <div>
        <div class="chat-header-title">Student Chat Room</div>
        <div class="chat-header-sub">Open group · all students</div>
      </div>
    </div>
    <div class="online-count">
      <span class="online-dot"></span>
      <span id="onlineCount">—</span> online
    </div>
  </div>

  <!-- Messages -->
  <div class="chat-messages" id="chatMessages">
    <div class="chat-empty" id="emptyState">
      <i class="fas fa-comment-dots"></i>
      <p>No messages yet. Be the first to say hello! 👋</p>
    </div>
  </div>

  <!-- Typing bar -->
  <div class="typing-bar" id="typingBar"></div>

  <!-- Reply context bar -->
  <div class="reply-context" id="replyContext">
    <i class="fas fa-reply" style="color:var(--gold);flex-shrink:0"></i>
    <div class="reply-context-text">
      <strong id="replyToName"></strong>
      <span id="replyToMsg"></span>
    </div>
    <button class="btn-cancel-reply" onclick="cancelReply()" title="Cancel reply">
      <i class="fas fa-xmark"></i>
    </button>
  </div>

  <!-- Input bar -->
  <div class="chat-input-bar">
    <textarea class="chat-input" id="chatInput"
      placeholder="Type a message… (Enter to send)"
      rows="1" maxlength="1000"></textarea>
    <button class="btn-send" id="sendBtn" title="Send">
      <i class="fas fa-paper-plane"></i>
    </button>
  </div>
</div>

<script>
const ME_ID     = <?= $uid ?>;
const ME_NAME   = <?= json_encode($uname) ?>;
const ME_AVATAR = <?= json_encode($myAvatar) ?>;
const BASE_URL  = '<?= APP_URL ?>/student';

let lastMsgId   = 0;
let typingTimer = null;
let isTyping    = false;
let replyToId   = null;
let replyToName = '';
let replyToMsg  = '';
let lastDate    = '';

// ── Helpers ──────────────────────────────────────────────────────────────────
function initials(name) {
  const p = name.trim().split(' ');
  return (p[0][0] + (p[1] ? p[1][0] : '')).toUpperCase();
}
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatTime(d) {
  return new Date(d.replace(' ','T')).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
}
function avatarHtml(msg) {
  const isMine = msg.student_id == ME_ID;
  if (isMine && ME_AVATAR) return `<img class="msg-avatar" src="${ME_AVATAR}" alt="">`;
  if (!isMine && msg.avatar) return `<img class="msg-avatar" src="${BASE_URL.replace('/student','')}/${msg.avatar}" alt="">`;
  return `<div class="msg-avatar-initials">${initials(msg.name)}</div>`;
}

// ── Reply ────────────────────────────────────────────────────────────────────
function setReply(id, name, message) {
  replyToId   = id;
  replyToName = name;
  replyToMsg  = message;
  document.getElementById('replyContext').classList.add('active');
  document.getElementById('replyToName').textContent = 'Replying to ' + name;
  document.getElementById('replyToMsg').textContent  = message.length > 80 ? message.slice(0,80)+'…' : message;
  document.getElementById('chatInput').focus();
}
function cancelReply() {
  replyToId   = null;
  replyToName = '';
  replyToMsg  = '';
  document.getElementById('replyContext').classList.remove('active');
}

// ── Render a message ─────────────────────────────────────────────────────────
function renderMessage(msg) {
  const isMine  = msg.student_id == ME_ID;
  const msgDate = msg.created_at.split(' ')[0];

  let divider = '';
  if (msgDate !== lastDate) {
    lastDate = msgDate;
    const label = new Date(msgDate).toLocaleDateString([],{weekday:'long',month:'short',day:'numeric'});
    divider = `<div class="date-divider">${label}</div>`;
  }

  // Reply preview
  let replyHtml = '';
  if (msg.reply_to && msg.reply_message) {
    const rName = (msg.reply_name === null) ? 'Unknown' : escHtml(msg.reply_name);
    replyHtml = `
      <div class="reply-preview">
        <strong>${rName}</strong>
        ${escHtml(msg.reply_message.length > 80 ? msg.reply_message.slice(0,80)+'…' : msg.reply_message)}
      </div>`;
  }

  const displayName = isMine ? 'You' : escHtml(msg.name);
  const replyBtn = `
    <div class="msg-actions">
      <button class="btn-reply-msg" onclick="setReply(${msg.id}, '${escHtml(msg.name).replace(/'/g,"\\'")}', '${escHtml(msg.message).replace(/'/g,"\\'")}')">
        <i class="fas fa-reply"></i> Reply
      </button>
    </div>`;

  const html = `
    ${divider}
    <div class="msg-row ${isMine ? 'mine' : ''}" id="msg-${msg.id}">
      ${avatarHtml(msg)}
      <div class="msg-body">
        <div class="msg-name">${displayName}</div>
        ${replyBtn}
        <div class="msg-bubble">${replyHtml}${escHtml(msg.message)}</div>
        <div class="msg-time">${formatTime(msg.created_at)}</div>
      </div>
    </div>`;

  const container = document.getElementById('chatMessages');
  const empty     = document.getElementById('emptyState');
  if (empty) empty.remove();
  container.insertAdjacentHTML('beforeend', html);
  scrollToBottom();
}

function scrollToBottom() {
  const c = document.getElementById('chatMessages');
  c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
}

// ── Fetch messages ───────────────────────────────────────────────────────────
async function fetchMessages() {
  try {
    const res  = await fetch(`${BASE_URL}/chat_api.php?action=messages&since=${lastMsgId}`);
    const data = await res.json();

    if (data.messages && data.messages.length) {
      data.messages.forEach(renderMessage);
      lastMsgId = data.messages[data.messages.length - 1].id;
    }
    if (data.online !== undefined) {
      document.getElementById('onlineCount').textContent = data.online;
    }
    const typingBar = document.getElementById('typingBar');
    if (data.typing && data.typing.length) {
      const names = data.typing.filter(n => n !== ME_NAME);
      typingBar.textContent = names.length
        ? names.join(', ') + (names.length > 1 ? ' are' : ' is') + ' typing…'
        : '';
    } else {
      typingBar.textContent = '';
    }
  } catch(e) {}
}

// ── Send message ─────────────────────────────────────────────────────────────
async function sendMessage() {
  const input = document.getElementById('chatInput');
  const msg   = input.value.trim();
  if (!msg) return;

  const fd = new FormData();
  fd.append('action', 'send');
  fd.append('message', msg);
  if (replyToId) fd.append('reply_to', replyToId);

  input.value = '';
  autoResize(input);
  cancelReply();
  document.getElementById('sendBtn').disabled = true;

  try {
    await fetch(`${BASE_URL}/chat_api.php`, { method:'POST', body:fd });
    await fetchMessages();
  } catch(e) {} finally {
    document.getElementById('sendBtn').disabled = false;
    input.focus();
  }
}

// ── Typing notification ──────────────────────────────────────────────────────
async function sendTyping() {
  try {
    const fd = new FormData();
    fd.append('action', 'typing');
    await fetch(`${BASE_URL}/chat_api.php`, { method:'POST', body:fd });
  } catch(e) {}
}

// ── Auto-resize textarea ─────────────────────────────────────────────────────
function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

// ── Event listeners ──────────────────────────────────────────────────────────
const input   = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');

input.addEventListener('input', () => {
  autoResize(input);
  if (!isTyping) { isTyping = true; sendTyping(); }
  clearTimeout(typingTimer);
  typingTimer = setTimeout(() => { isTyping = false; }, 2000);
});

input.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});
sendBtn.addEventListener('click', sendMessage);

// ── Init ─────────────────────────────────────────────────────────────────────
fetchMessages();
setInterval(fetchMessages, 2500);
</script>

<?php include '../includes/footer.php'; ?>
