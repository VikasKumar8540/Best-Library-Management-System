<?php
require_once '../includes/config.php';
requireLogin('admin');
$pageTitle = 'Books Management';

$msg = $err = '';

// Add Book
if (isset($_POST['add_book'])) {
  $title = clean($_POST['title']);
  $author = clean($_POST['author']);
  $isbn = clean($_POST['isbn']);
  $category = clean($_POST['category']);
  $publisher = clean($_POST['publisher']);
  $year = (int) $_POST['year'];
  $copies = (int) $_POST['total_copies'];
  $desc = clean($_POST['description']);
  $uid = $_SESSION['user_id'];

  if (!$title || !$author) {
    $err = 'Title and Author are required.';
  } elseif ($isbn && $conn->query("SELECT id FROM books WHERE isbn='$isbn' AND status='active'")->num_rows) {
    $err = 'A book with this ISBN already exists.';
  } else {
    $stmt = $conn->prepare("INSERT INTO books (title,author,isbn,category,publisher,year,total_copies,available_copies,description,added_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssiisi", $title, $author, $isbn, $category, $publisher, $year, $copies, $copies, $desc, $uid);
    if ($stmt->execute())
      $msg = 'Book added successfully!';
    else
      $err = 'Error: ' . $conn->error;
  }
}

// Delete Book
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $conn->query("UPDATE books SET status='inactive' WHERE id=$id");
  $msg = 'Book removed.';
}

// Edit Book
if (isset($_POST['edit_book'])) {
  $id = (int) $_POST['book_id'];
  $title = clean($_POST['title']);
  $author = clean($_POST['author']);
  $isbn = clean($_POST['isbn']);
  $category = clean($_POST['category']);
  $publisher = clean($_POST['publisher']);
  $year = (int) $_POST['year'];
  $copies = (int) $_POST['total_copies'];
  $desc = clean($_POST['description']);

  if (!$title || !$author) {
    $err = 'Title and Author are required.';
  } elseif ($isbn && $conn->query("SELECT id FROM books WHERE isbn='$isbn' AND id != $id AND status='active'")->num_rows) {
    $err = 'Another book with this ISBN already exists.';
  } else {
    $conn->query("UPDATE books SET title='$title',author='$author',isbn='$isbn',category='$category',publisher='$publisher',year=$year,total_copies=$copies,description='$desc' WHERE id=$id");
    $msg = 'Book updated!';
  }
}

// Search
$search = clean($_GET['search'] ?? '');
$where = "WHERE b.status='active'";
if ($search)
  $where .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR b.isbn LIKE '%$search%' OR b.category LIKE '%$search%')";

$books = $conn->query("SELECT b.*, u.name as added_by_name FROM books b LEFT JOIN users u ON b.added_by=u.id $where ORDER BY b.created_at DESC");

// ── NEW: fetch all distinct categories for the dropdown ──────────────────────
$catResult = $conn->query("SELECT DISTINCT category FROM books WHERE status='active' AND category IS NOT NULL AND category <> '' ORDER BY category ASC");
$categories = [];
while ($row = $catResult->fetch_assoc()) {
  $categories[] = $row['category'];
}
// ─────────────────────────────────────────────────────────────────────────────

// Get book for edit
$editBook = null;
if (isset($_GET['edit'])) {
  $eid = (int) $_GET['edit'];
  $er = $conn->query("SELECT * FROM books WHERE id=$eid");
  if ($er->num_rows)
    $editBook = $er->fetch_assoc();
}
?>
<?php include '../includes/header.php'; ?>

<?php if ($msg): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?>
  <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<!-- Add/Edit Book Form -->
<div class="card" style="margin-bottom:22px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-<?= $editBook ? 'pen' : 'plus-circle' ?>"></i>
      <?= $editBook ? 'Edit Book' : 'Add New Book' ?></div>
    <?php if ($editBook): ?><a href="books.php" class="btn btn-outline btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <form method="POST">
    <?php if ($editBook): ?>
      <input type="hidden" name="book_id" value="<?= $editBook['id'] ?>">
    <?php endif; ?>
    <div class="grid-3">
      <div class="form-group">
        <label class="form-label">Book Title *</label>
        <input type="text" name="title" class="form-control" required
          value="<?= htmlspecialchars($editBook['title'] ?? '') ?>" placeholder="e.g. Introduction to Algorithms">
      </div>
      <div class="form-group">
        <label class="form-label">Author *</label>
        <input type="text" name="author" class="form-control" required
          value="<?= htmlspecialchars($editBook['author'] ?? '') ?>" placeholder="Author name">
      </div>
      <div class="form-group">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($editBook['isbn'] ?? '') ?>"
          placeholder="978-0000000000">
      </div>

      <!-- ── UPDATED CATEGORY FIELD ──────────────────────────────────────── -->
      <div class="form-group" style="position:relative">
        <label class="form-label">Category</label>
        <input
          type="text"
          id="categoryInput"
          name="category"
          class="form-control"
          value="<?= htmlspecialchars($editBook['category'] ?? '') ?>"
          placeholder="e.g. Computer Science"
          autocomplete="off"
          oninput="filterCategories(this.value)"
          onfocus="showDropdown()"
          onblur="hideDropdown()">
        <div id="categoryDropdown" style="
          display:none;
          position:absolute;
          top:100%;
          left:0;
          right:0;
          background:#fff;
          border:1px solid #d1c9b8;
          border-top:none;
          border-radius:0 0 6px 6px;
          max-height:200px;
          overflow-y:auto;
          z-index:999;
          box-shadow:0 4px 12px rgba(0,0,0,.1);
        ">
          <?php if (empty($categories)): ?>
            <div style="padding:10px 14px;color:var(--muted);font-size:13px">No categories yet</div>
          <?php else: ?>
            <?php foreach ($categories as $cat): ?>
              <div
                class="cat-option"
                data-value="<?= htmlspecialchars($cat) ?>"
                onmousedown="selectCategory('<?= htmlspecialchars(addslashes($cat)) ?>')"
                style="padding:9px 14px;cursor:pointer;font-size:14px;border-bottom:1px solid #f0ebe0;transition:background .15s">
                <?= htmlspecialchars($cat) ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <!-- ─────────────────────────────────────────────────────────────────── -->

      <div class="form-group">
        <label class="form-label">Publisher</label>
        <input type="text" name="publisher" class="form-control"
          value="<?= htmlspecialchars($editBook['publisher'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Year</label>
        <input type="number" name="year" class="form-control" value="<?= $editBook['year'] ?? date('Y') ?>" min="1800"
          max="<?= date('Y') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Total Copies</label>
        <input type="number" name="total_copies" class="form-control" value="<?= $editBook['total_copies'] ?? 1 ?>"
          min="1">
      </div>
      <div class="form-group" style="grid-column: span 2">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control"
          value="<?= htmlspecialchars($editBook['description'] ?? '') ?>" placeholder="Brief description">
      </div>
    </div>
    <button type="submit" name="<?= $editBook ? 'edit_book' : 'add_book' ?>" class="btn btn-primary">
      <i class="fas fa-<?= $editBook ? 'save' : 'plus' ?>"></i> <?= $editBook ? 'Update Book' : 'Add Book' ?>
    </button>
  </form>
</div>

<!-- Books List -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-book"></i> All Books</div>
    <form method="GET" style="display:flex;gap:8px">
      <input type="text" name="search" class="form-control" style="width:220px"
        placeholder="Search title, author, ISBN..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
      <?php if ($search): ?><a href="books.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Author</th>
          <th>Category</th>
          <th>ISBN</th>
          <th>Copies</th>
          <th>Available</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($books && $books->num_rows > 0): ?>
          <?php $i = 1;
          while ($b = $books->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($b['title']) ?></strong><br><small
                  style="color:var(--muted)"><?= $b['year'] ? $b['year'] : '' ?></small></td>
              <td><?= htmlspecialchars($b['author']) ?></td>
              <td>
                <?= $b['category'] ? '<span class="badge badge-gold">' . htmlspecialchars($b['category']) . '</span>' : '—' ?>
              </td>
              <td><code style="font-size:12px"><?= $b['isbn'] ?: '—' ?></code></td>
              <td><?= $b['total_copies'] ?></td>
              <td>
                <?php if ($b['available_copies'] > 0): ?>
                  <span class="badge badge-success"><?= $b['available_copies'] ?></span>
                <?php else: ?>
                  <span class="badge badge-danger">0</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="books.php?edit=<?= $b['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i></a>
                <a href="books.php?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Delete this book?"><i
                    class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state"><i class="fas fa-book"></i>
                <p>No books found.</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Category dropdown JS ───────────────────────────────────────────────── -->
<script>
function showDropdown() {
  document.getElementById('categoryDropdown').style.display = 'block';
}

function hideDropdown() {
  // Small delay so onmousedown on options fires before blur hides the list
  setTimeout(() => {
    document.getElementById('categoryDropdown').style.display = 'none';
  }, 180);
}

function selectCategory(value) {
  document.getElementById('categoryInput').value = value;
  document.getElementById('categoryDropdown').style.display = 'none';
}

function filterCategories(query) {
  const options = document.querySelectorAll('#categoryDropdown .cat-option');
  const q = query.toLowerCase();
  let anyVisible = false;
  options.forEach(opt => {
    const match = opt.dataset.value.toLowerCase().includes(q);
    opt.style.display = match ? 'block' : 'none';
    if (match) anyVisible = true;
  });
  document.getElementById('categoryDropdown').style.display = 'block';
}

// Hover highlight
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#categoryDropdown .cat-option').forEach(opt => {
    opt.addEventListener('mouseenter', () => opt.style.background = '#f5f0e8');
    opt.addEventListener('mouseleave', () => opt.style.background = '');
  });
});
</script>
<!-- ─────────────────────────────────────────────────────────────────────────── -->

<?php include '../includes/footer.php'; ?>
