<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Fine Settings';
$msg = '';

if (isset($_POST['save_settings'])) {
    $fpd = (float)$_POST['fine_per_day'];
    $mid = (int)$_POST['max_issue_days'];
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE fine_settings SET fine_per_day=$fpd, max_issue_days=$mid, updated_by=$uid");
    $msg = 'Settings saved!';
}

$settings = $conn->query("SELECT * FROM fine_settings LIMIT 1")->fetch_assoc();
?>
<?php include '../includes/header.php'; ?>
<?php if ($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>

<div class="card" style="max-width:500px">
  <div class="card-header"><div class="card-title"><i class="fas fa-coins"></i> Fine & Issue Settings</div></div>
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Fine Per Day (₹)</label>
      <input type="number" step="0.5" name="fine_per_day" class="form-control" value="<?= $settings['fine_per_day'] ?>" min="0">
      <small style="color:var(--muted);font-size:12px">Amount charged per day after due date</small>
    </div>
    <div class="form-group">
      <label class="form-label">Maximum Issue Days</label>
      <input type="number" name="max_issue_days" class="form-control" value="<?= $settings['max_issue_days'] ?>" min="1">
      <small style="color:var(--muted);font-size:12px">Default lending period in days</small>
    </div>
    <button type="submit" name="save_settings" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
  </form>
</div>
<?php include '../includes/footer.php'; ?>
