<?php
require_once '../includes/config.php';
requireLogin('librarian');
$pageTitle = 'Books';

$search = clean($_GET['search'] ?? '');
$where  = "WHERE b.status='active'";
if ($search) $where .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR b.isbn LIKE '%$search%' OR b.category LIKE '%$search%')";

$books = $conn->query("SELECT * FROM books b $where ORDER BY b.title");
?>
<?php include '../includes/header.php'; ?>

<div class="card" style="margin-bottom:18px">
  <form method="GET" style="display:flex;gap:8px">
    <input type="text" name="search" class="form-control" style="max-width:300px" placeholder="Search title, author, ISBN..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
    <?php if ($search): ?><a href="books.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-book"></i> Book Catalog</div>
    <span class="badge badge-info"><?= $books->num_rows ?> books</span>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Category</th><th>ISBN</th><th>Total</th><th>Available</th></tr></thead>
      <tbody>
      <?php if ($books->num_rows): $i=1; while($b=$books->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($b['title']) ?></strong><br><small style="color:var(--muted)"><?= $b['year'] ?: '' ?></small></td>
          <td><?= htmlspecialchars($b['author']) ?></td>
          <td><?= $b['category'] ? '<span class="badge badge-gold">'.htmlspecialchars($b['category']).'</span>' : '—' ?></td>
          <td><code style="font-size:12px"><?= $b['isbn'] ?: '—' ?></code></td>
          <td><?= $b['total_copies'] ?></td>
          <td><?= $b['available_copies'] > 0 ? '<span class="badge badge-success">'.$b['available_copies'].'</span>' : '<span class="badge badge-danger">0</span>' ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-book"></i><p>No books found.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
