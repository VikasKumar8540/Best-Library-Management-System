<?php
require_once '../includes/config.php';
requireLogin('student');
$pageTitle = 'Book Catalog';

$search   = clean($_GET['search'] ?? '');
$category = clean($_GET['category'] ?? '');

$where = "WHERE b.status='active'";
if ($search)   $where .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR b.isbn LIKE '%$search%')";
if ($category) $where .= " AND b.category='$category'";

$books = $conn->query("SELECT * FROM books b $where ORDER BY b.title");

// Get all categories for filter
$cats = $conn->query("SELECT DISTINCT category FROM books WHERE status='active' AND category!='' ORDER BY category");
?>
<?php include '../includes/header.php'; ?>

<!-- Search & Filter Bar -->
<div class="card" style="margin-bottom:20px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
    <div class="form-group" style="flex:1;min-width:200px;margin:0">
      <label class="form-label">Search</label>
      <input type="text" name="search" class="form-control" placeholder="Search by title, author, ISBN..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group" style="min-width:180px;margin:0">
      <label class="form-label">Category</label>
      <select name="category" class="form-select">
        <option value="">All Categories</option>
        <?php while($c=$cats->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category===$c['category']?'selected':'' ?>><?= htmlspecialchars($c['category']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:18px">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <?php if ($search||$category): ?><a href="catalog.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- Books Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:18px">
<?php if ($books->num_rows): while($b=$books->fetch_assoc()): ?>
  <div style="background:#fff;border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:transform 0.2s,box-shadow 0.2s" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(26,18,8,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="background:linear-gradient(135deg,#1a1208,#3d2b0e);height:100px;display:flex;align-items:center;justify-content:center">
      <i class="fas fa-book" style="font-size:36px;color:rgba(201,146,42,0.6)"></i>
    </div>
    <div style="padding:16px">
      <h4 style="font-family:'Playfair Display',serif;font-size:15px;color:var(--ink);margin-bottom:4px;line-height:1.3"><?= htmlspecialchars($b['title']) ?></h4>
      <p style="font-size:12px;color:var(--muted);margin-bottom:8px"><?= htmlspecialchars($b['author']) ?></p>
      <?php if ($b['category']): ?>
      <span class="badge badge-gold" style="margin-bottom:8px"><?= htmlspecialchars($b['category']) ?></span>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid var(--border)">
        <span style="font-size:12px;color:var(--muted)"><?= $b['year'] ?: '' ?></span>
        <?php if ($b['available_copies'] > 0): ?>
          <span class="badge badge-success"><?= $b['available_copies'] ?> Available</span>
        <?php else: ?>
          <span class="badge badge-danger">Not Available</span>
        <?php endif; ?>
      </div>
      <?php if ($b['description']): ?>
      <p style="font-size:12px;color:var(--muted);margin-top:8px;line-height:1.5"><?= htmlspecialchars(substr($b['description'],0,70)) ?>...</p>
      <?php endif; ?>
      <?php if ($b['available_copies'] > 0): ?>
      <a href="requests.php?book_id=<?= $b['id'] ?>" class="btn btn-outline btn-sm" style="margin-top:10px;width:100%;justify-content:center">
        <i class="fas fa-paper-plane"></i> Request This Book
      </a>
      <?php endif; ?>
    </div>
  </div>
<?php endwhile; else: ?>
  <div style="grid-column:1/-1">
    <div class="empty-state card"><i class="fas fa-search"></i><p>No books found matching your search.</p></div>
  </div>
<?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
