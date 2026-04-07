<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Reports';

// ── TIMELINE FILTER ──────────────────────────────────────────────────────────
$filter    = $_GET['filter'] ?? 'all';
$dateFrom  = clean($_GET['date_from'] ?? '');
$dateTo    = clean($_GET['date_to']   ?? '');

// Build date condition for book_issues
switch ($filter) {
    case 'today':
        $dateCondition = "DATE(issue_date) = CURDATE()";
        $filterLabel   = 'Today — ' . date('d M Y');
        break;
    case 'week':
        $dateCondition = "issue_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $filterLabel   = 'Last 7 Days';
        break;
    case 'month':
        $dateCondition = "issue_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $filterLabel   = 'Last 30 Days';
        break;
    case 'year':
        $dateCondition = "YEAR(issue_date) = YEAR(CURDATE())";
        $filterLabel   = 'This Year — ' . date('Y');
        break;
    case 'custom':
        if ($dateFrom && $dateTo) {
            $dateFrom_safe = $conn->real_escape_string($dateFrom);
            $dateTo_safe   = $conn->real_escape_string($dateTo);
            $dateCondition = "DATE(issue_date) BETWEEN '$dateFrom_safe' AND '$dateTo_safe'";
            $filterLabel   = date('d M Y', strtotime($dateFrom)) . ' → ' . date('d M Y', strtotime($dateTo));
        } else {
            $dateCondition = "1=1";
            $filterLabel   = 'All Time';
        }
        break;
    default: // all
        $dateCondition = "1=1";
        $filterLabel   = 'All Time';
        break;
}

$dc = $dateCondition; // shorthand

// ── STATS (filtered by timeline) ────────────────────────────────────────────
$totalBooks     = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='active'")->fetch_assoc()['c'];
$totalStudents  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student' AND status='active'")->fetch_assoc()['c'];
$totalIssued    = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued' AND $dc")->fetch_assoc()['c'];
$totalReturned  = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='returned' AND $dc")->fetch_assoc()['c'];
$totalOverdue   = $conn->query("SELECT COUNT(*) as c FROM book_issues WHERE status='issued' AND due_date<CURDATE() AND $dc")->fetch_assoc()['c'];
$totalFinesColl = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE fine_paid='yes' AND $dc")->fetch_assoc()['t'];
$totalFinesPend = $conn->query("SELECT COALESCE(SUM(fine_amount),0) as t FROM book_issues WHERE fine_paid='no' AND fine_amount>0 AND $dc")->fetch_assoc()['t'];

// Most issued books (filtered)
$topBooksRes = $conn->query("SELECT b.title,b.author,COUNT(bi.id) as issue_count
    FROM book_issues bi JOIN books b ON bi.book_id=b.id
    WHERE $dc GROUP BY bi.book_id ORDER BY issue_count DESC LIMIT 8");
$topBooksData = [];
while ($r = $topBooksRes->fetch_assoc()) $topBooksData[] = $r;

// Most active students (filtered)
$topStudentsRes = $conn->query("SELECT u.name,u.student_id,COUNT(bi.id) as issue_count
    FROM book_issues bi JOIN users u ON bi.student_id=u.id
    WHERE $dc GROUP BY bi.student_id ORDER BY issue_count DESC LIMIT 8");
$topStudentsData = [];
while ($r = $topStudentsRes->fetch_assoc()) $topStudentsData[] = $r;

// Monthly trend (filtered to selected range, up to last 6 months)
$monthlyRes = $conn->query("SELECT DATE_FORMAT(issue_date,'%b %Y') as month,
    COUNT(*) as issued FROM book_issues
    WHERE $dc AND issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(issue_date,'%Y-%m') ORDER BY issue_date ASC");
$months = []; $counts = [];
while ($m = $monthlyRes->fetch_assoc()) {
    $months[] = $m['month'];
    $counts[] = $m['issued'];
}
$maxCount = !empty($counts) ? max($counts) : 1;

// ── BUILD PRINT HTML ─────────────────────────────────────────────────────────
ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LibraSync Report — <?= htmlspecialchars($filterLabel) ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #1a1208; background:#fff; padding: 30px; }
  .rpt-header { text-align:center; border-bottom: 3px solid #c9922a; padding-bottom: 16px; margin-bottom: 24px; }
  .rpt-header h1 { font-size: 22px; color: #1a1208; margin-bottom: 4px; }
  .rpt-header .period { display:inline-block; margin-top:6px; background:#fff3dc; border:1px solid #c9922a; border-radius:20px; padding:3px 14px; font-size:12px; color:#92400e; font-weight:600; }
  .rpt-header p { font-size: 12px; color: #7a6a52; margin-top:4px; }
  .stats-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 24px; }
  .stat-box { border: 1.5px solid #e8dcc8; border-radius: 8px; padding: 14px 16px; text-align:center; }
  .stat-box .val { font-size: 22px; font-weight: 700; color: #1a1208; }
  .stat-box .lbl { font-size: 11px; color: #7a6a52; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }
  .section { margin-bottom: 24px; }
  .section-title { font-size: 14px; font-weight: 700; color: #1a1208; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1.5px solid #e8dcc8; }
  table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
  th { background: #f5eedc; color: #7a6a52; text-align:left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e8dcc8; }
  td { padding: 9px 10px; border-bottom: 1px solid #f0e8d8; }
  tr:last-child td { border-bottom: none; }
  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
  .bar-chart { display: flex; align-items: flex-end; gap: 10px; height: 130px; margin-top: 8px; }
  .bar-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; }
  .bar-val { font-size: 11px; font-weight: 700; }
  .bar-fill { width: 100%; background: #c9922a; border-radius: 3px 3px 0 0; }
  .bar-lbl { font-size: 10px; color: #7a6a52; text-align:center; }
  .fine-total { font-weight:700; background:#f5eedc; }
  .rpt-footer { text-align:center; margin-top:30px; padding-top:12px; border-top:1px solid #e8dcc8; font-size:11px; color:#7a6a52; }
  .empty { text-align:center; color:#7a6a52; padding: 10px; }
</style>
</head>
<body>

<div class="rpt-header">
  <h1>📚 LibraSync — Library Management Report</h1>
  <div class="period">📅 Period: <?= htmlspecialchars($filterLabel) ?></div>
  <p>Generated on <?= date('l, d F Y \a\t h:i A') ?></p>
</div>

<div class="stats-grid">
  <div class="stat-box"><div class="val"><?= $totalBooks ?></div><div class="lbl">Total Books</div></div>
  <div class="stat-box"><div class="val"><?= $totalStudents ?></div><div class="lbl">Active Students</div></div>
  <div class="stat-box"><div class="val"><?= $totalIssued ?></div><div class="lbl">Books Issued</div></div>
  <div class="stat-box"><div class="val"><?= $totalReturned ?></div><div class="lbl">Books Returned</div></div>
  <div class="stat-box"><div class="val"><?= $totalOverdue ?></div><div class="lbl">Overdue Books</div></div>
  <div class="stat-box"><div class="val">₹<?= number_format($totalFinesColl + $totalFinesPend, 0) ?></div><div class="lbl">Total Fines</div></div>
</div>

<div class="two-col">
  <div class="section">
    <div class="section-title">🔥 Most Issued Books</div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Issues</th></tr></thead>
      <tbody>
        <?php if (!empty($topBooksData)): $i=1; foreach($topBooksData as $r): ?>
        <tr><td><?= $i++ ?></td><td><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['author']) ?></td><td><strong><?= $r['issue_count'] ?></strong></td></tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4" class="empty">No data for this period</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">🎓 Most Active Students</div>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Student ID</th><th>Issues</th></tr></thead>
      <tbody>
        <?php if (!empty($topStudentsData)): $i=1; foreach($topStudentsData as $r): ?>
        <tr><td><?= $i++ ?></td><td><?= htmlspecialchars($r['name']) ?></td><td><?= $r['student_id']?:'—' ?></td><td><strong><?= $r['issue_count'] ?></strong></td></tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4" class="empty">No data for this period</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section">
  <div class="section-title">💰 Fine Summary</div>
  <table>
    <thead><tr><th>Description</th><th>Amount (₹)</th></tr></thead>
    <tbody>
      <tr><td>Fines Collected</td><td>₹<?= number_format($totalFinesColl, 2) ?></td></tr>
      <tr><td>Fines Pending</td><td>₹<?= number_format($totalFinesPend, 2) ?></td></tr>
      <tr class="fine-total"><td>Grand Total</td><td>₹<?= number_format($totalFinesColl + $totalFinesPend, 2) ?></td></tr>
    </tbody>
  </table>
</div>

<?php if (!empty($months)): ?>
<div class="section">
  <div class="section-title">📊 Monthly Issue Trend</div>
  <div class="bar-chart">
    <?php for ($i = 0; $i < count($months); $i++): ?>
    <div class="bar-col">
      <span class="bar-val"><?= $counts[$i] ?></span>
      <div class="bar-fill" style="height:<?= round(($counts[$i]/$maxCount)*100) ?>px;min-height:4px"></div>
      <span class="bar-lbl"><?= $months[$i] ?></span>
    </div>
    <?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<div class="rpt-footer">
  LibraSync Library Management System &mdash; Report Period: <strong><?= htmlspecialchars($filterLabel) ?></strong> &mdash; <?= date('d M Y') ?>
</div>
</body>
</html>
<?php
$printHtml   = ob_get_clean();
$printHtmlJs = json_encode($printHtml);
?>
<?php include '../includes/header.php'; ?>

<!-- ── Timeline filter + print bar ─────────────────────────────────────────── -->
<div class="card" style="margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-calendar-days"></i> Report Period</div>
    <button onclick="openPrintWindow()" class="btn btn-primary">
      <i class="fas fa-print"></i> Print This Report
    </button>
  </div>

  <!-- ── FIX: Quick filter buttons are plain links, not form submits ── -->
  <!-- This eliminates the hidden-field conflict entirely. Each button  -->
  <!-- is just an <a> tag that navigates to ?filter=X directly.         -->
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
    <?php
    $quickFilters = [
      'all'   => 'All Time',
      'today' => 'Today',
      'week'  => 'Last 7 Days',
      'month' => 'Last 30 Days',
      'year'  => 'This Year',
      'custom'=> 'Custom Range',
    ];
    foreach ($quickFilters as $val => $label): ?>
      <a href="?filter=<?= $val ?>"
         class="btn btn-sm <?= $filter === $val ? 'btn-primary' : 'btn-outline' ?>"
         <?= $val === 'custom' ? 'onclick="showCustomRange(event)"' : '' ?>>
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Custom date range (only shown when filter=custom) -->
  <div id="customRange" style="display:<?= $filter === 'custom' ? 'flex' : 'none' ?>;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <input type="hidden" name="filter" value="custom">
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control" style="width:160px"
          value="<?= htmlspecialchars($dateFrom) ?>"
          max="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">To</label>
        <input type="date" name="date_to" class="form-control" style="width:160px"
          value="<?= htmlspecialchars($dateTo) ?>"
          max="<?= date('Y-m-d') ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-search"></i> Apply
      </button>
    </form>
  </div>

  <!-- Active period badge -->
  <div style="margin-top:12px">
    <span style="font-size:12px;color:var(--muted)">Showing data for: </span>
    <span class="badge badge-gold" style="font-size:12px">📅 <?= htmlspecialchars($filterLabel) ?></span>
  </div>
</div>

<!-- ── Stats ─────────────────────────────────────────────────────────────────-->
<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-books"></i></div><div class="stat-info"><div class="stat-value"><?= $totalBooks ?></div><div class="stat-label">Total Books</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div><div class="stat-info"><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">Students</div></div></div>
  <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-arrow-right-from-bracket"></i></div><div class="stat-info"><div class="stat-value"><?= $totalIssued ?></div><div class="stat-label">Books Issued</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-rotate-left"></i></div><div class="stat-info"><div class="stat-value"><?= $totalReturned ?></div><div class="stat-label">Returned</div></div></div>
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-clock"></i></div><div class="stat-info"><div class="stat-value"><?= $totalOverdue ?></div><div class="stat-label">Overdue</div></div></div>
  <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-coins"></i></div><div class="stat-info"><div class="stat-value">₹<?= number_format($totalFinesColl,0) ?> / ₹<?= number_format($totalFinesPend,0) ?></div><div class="stat-label">Fines (Collected / Pending)</div></div></div>
</div>

<!-- ── Tables ────────────────────────────────────────────────────────────────-->
<div class="grid-2" style="gap:20px">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-fire"></i> Most Issued Books</div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Book</th><th>Issues</th></tr></thead>
        <tbody>
        <?php if (!empty($topBooksData)): $i=1; foreach($topBooksData as $r): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><small style="color:var(--muted)"><?= htmlspecialchars($r['author']) ?></small></td>
            <td><span class="badge badge-gold"><?= $r['issue_count'] ?></span></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="3"><div class="empty-state"><i class="fas fa-book"></i><p>No data for this period.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-graduation-cap"></i> Most Active Students</div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Student</th><th>Total Issues</th></tr></thead>
        <tbody>
        <?php if (!empty($topStudentsData)): $i=1; foreach($topStudentsData as $r): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($r['name']) ?><br><small><?= $r['student_id']?:'—' ?></small></td>
            <td><span class="badge badge-info"><?= $r['issue_count'] ?></span></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="3"><div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No data for this period.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!empty($months)): ?>
<div class="card" style="margin-top:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Monthly Issue Trend</div></div>
  <div style="display:flex;align-items:flex-end;gap:14px;height:160px;padding:10px 0 0">
    <?php for ($i = 0; $i < count($months); $i++): ?>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px">
      <span style="font-size:12px;font-weight:600;color:var(--ink)"><?= $counts[$i] ?></span>
      <div style="width:100%;background:var(--gold);border-radius:4px 4px 0 0;height:<?= round(($counts[$i]/$maxCount)*120) ?>px;min-height:4px"></div>
      <span style="font-size:11px;color:var(--muted);text-align:center"><?= $months[$i] ?></span>
    </div>
    <?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<div class="card" style="margin-top:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-coins"></i> Fine Summary</div></div>
  <table class="data-table">
    <thead><tr><th>Description</th><th>Amount</th></tr></thead>
    <tbody>
      <tr><td>Total Fines Collected</td><td><span class="badge badge-success">₹<?= number_format($totalFinesColl,2) ?></span></td></tr>
      <tr><td>Total Fines Pending</td><td><span class="badge badge-danger">₹<?= number_format($totalFinesPend,2) ?></span></td></tr>
      <tr><td><strong>Grand Total</strong></td><td><strong>₹<?= number_format($totalFinesColl+$totalFinesPend,2) ?></strong></td></tr>
    </tbody>
  </table>
</div>

<script>
function showCustomRange(e) {
  e.preventDefault();
  document.getElementById('customRange').style.display = 'flex';
}

function openPrintWindow() {
  const html = <?= $printHtmlJs ?>;
  const win  = window.open('', '_blank', 'width=900,height=700');
  win.document.open();
  win.document.write(html);
  win.document.close();
  win.onload = function() {
    win.focus();
    win.print();
  };
}
</script>

<?php include '../includes/footer.php'; ?>
