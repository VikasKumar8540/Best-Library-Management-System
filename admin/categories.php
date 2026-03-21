<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Categories';
$msg = $err = '';

// ── RENAME category ──────────────────────────────────────────────────────────
if (isset($_POST['edit_category'])) {
  $old = clean($_POST['old_category']);
  $new = clean($_POST['new_category']);
  if (!$new) {
    $err = 'Category name cannot be empty.';
  } elseif ($old === $new) {
    $err = 'New name is the same as the old name.';
  } else {
    $check = $conn->query("SELECT COUNT(*) as c FROM books WHERE category='$new' AND status='active'");
    $exists = $check->fetch_assoc()['c'];
    if ($exists) {
      $err = 'A category with that name already exists.';
    } else {
      $conn->query("UPDATE books SET category='$new' WHERE category='$old'");
      $msg = "Category renamed from &ldquo;$old&rdquo; to &ldquo;$new&rdquo; successfully!";
    }
  }
}

// ── DELETE category ──────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
  $cat = clean($_GET['delete']);
  $conn->query("UPDATE books SET category='' WHERE category='$cat' AND status='active'");
  $msg = "Category &ldquo;$cat&rdquo; deleted. Books have been left uncategorised.";
}

// ── Fetch categories ─────────────────────────────────────────────────────────
$cats = $conn->query("SELECT category, COUNT(*) as total,
    SUM(available_copies) as available
    FROM books WHERE status='active' AND category!=''
    GROUP BY category ORDER BY category");

$editCat = isset($_GET['edit']) ? clean($_GET['edit']) : null;
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
  <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
<?php endif; ?>

<?php if ($editCat): ?>
<!-- ── Inline Rename Form ────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-pen"></i> Rename Category</div>
    <a href="categories.php" class="btn btn-outline btn-sm">Cancel</a>
  </div>
  <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="old_category" value="<?= htmlspecialchars($editCat) ?>">
    <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
      <label class="form-label">Current Name</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($editCat) ?>" disabled>
    </div>
    <div style="display:flex;align-items:center;padding-bottom:4px;color:var(--muted)">
      <i class="fas fa-arrow-right"></i>
    </div>
    <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
      <label class="form-label">New Name *</label>
      <input type="text" name="new_category" class="form-control" required autofocus
        value="<?= htmlspecialchars($editCat) ?>" placeholder="Enter new category name">
    </div>
    <button type="submit" name="edit_category" class="btn btn-primary" style="margin-bottom:1px">
      <i class="fas fa-save"></i> Save
    </button>
  </form>
</div>
<?php endif; ?>

<!-- ── Categories Grid ───────────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-tags"></i> Book Categories</div>
    <span style="font-size:13px;color:var(--muted)">Rename or remove categories. All books are updated automatically.</span>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;padding:6px 0">
    <?php if ($cats->num_rows): while ($c = $cats->fetch_assoc()):
      $catEnc  = urlencode($c['category']);
      $catHtml = htmlspecialchars($c['category']);
    ?>
    <div style="background:var(--parchment);border:1px solid var(--border);border-radius:8px;padding:16px;display:flex;align-items:center;gap:14px">
      <!-- Icon -->
      <div style="width:40px;height:40px;flex-shrink:0;background:rgba(201,146,42,0.12);border-radius:8px;display:flex;align-items:center;justify-content:center">
        <i class="fas fa-tag" style="color:var(--gold)"></i>
      </div>
      <!-- Info -->
      <div style="flex:1;min-width:0">
        <strong style="font-size:14px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= $catHtml ?>">
          <?= $catHtml ?>
        </strong>
        <small style="color:var(--muted)"><?= $c['total'] ?> books &middot; <?= $c['available'] ?> available</small>
      </div>
      <!-- Actions -->
      <div style="display:flex;gap:6px;flex-shrink:0">
        <a href="categories.php?edit=<?= $catEnc ?>"
           class="btn btn-outline btn-sm" title="Rename category">
          <i class="fas fa-pen"></i>
        </a>
        <a href="categories.php?delete=<?= $catEnc ?>"
           class="btn btn-danger btn-sm"
           title="Delete category"
           data-confirm="Delete category &quot;<?= $catHtml ?>&quot;? Books will be left uncategorised.">
          <i class="fas fa-trash"></i>
        </a>
      </div>
    </div>
    <?php endwhile; else: ?>
      <div style="grid-column:1/-1">
        <div class="empty-state">
          <i class="fas fa-tags"></i>
          <p>No categories yet. Add books with categories first.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
