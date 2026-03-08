<?php
$conn = new mysqli('localhost', 'root', '', 'my_database');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$action = $_GET['action'] ?? '';
$tab    = $_GET['tab'] ?? 'books';

if ($action === 'delete_book') {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM books WHERE book_id = $id");
    header("Location: ?tab=books"); exit;
}
if ($action === 'delete_author') {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM authors WHERE author_id = $id");
    header("Location: ?tab=authors"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    $id    = (int)$_POST['book_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $page  = (int)$_POST['page'] ?: 'NULL';
    $year  = (int)$_POST['publish_year'] ?: 'NULL';
    $cat   = $conn->real_escape_string($_POST['category']);
    $aids  = $_POST['author_ids'] ?? [];

    if ($id > 0) {
        $conn->query("UPDATE books SET title='$title', page=$page, publish_year=$year, category='$cat' WHERE book_id=$id");
        $conn->query("UPDATE book_details SET is_deleted=1 WHERE book_id=$id");
    } else {
        $conn->query("INSERT INTO books (title,page,publish_year,category) VALUES ('$title',$page,$year,'$cat')");
        $id = $conn->insert_id;
    }
    foreach ($aids as $aid) {
        $aid = (int)$aid;
        $conn->query("INSERT INTO book_details (book_id,author_id,is_deleted) VALUES ($id,$aid,0) ON DUPLICATE KEY UPDATE is_deleted=0");
    }
    header("Location: ?tab=books"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_author'])) {
    $id      = (int)$_POST['author_id'];
    $name    = $conn->real_escape_string($_POST['name']);
    $email   = $conn->real_escape_string($_POST['email']);
    $address = $conn->real_escape_string($_POST['address']);

    if ($id > 0)
        $conn->query("UPDATE authors SET name='$name', email='$email', address='$address' WHERE author_id=$id");
    else
        $conn->query("INSERT INTO authors (name,email,address) VALUES ('$name','$email','$address')");

    header("Location: ?tab=authors"); exit;
}

$books = $conn->query("
    SELECT b.*, GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
    FROM books b
    LEFT JOIN book_details bd ON b.book_id = bd.book_id AND bd.is_deleted = 0
    LEFT JOIN authors a ON bd.author_id = a.author_id
    GROUP BY b.book_id ORDER BY b.book_id DESC");

$authors     = $conn->query("SELECT * FROM authors ORDER BY name");
$all_authors = $conn->query("SELECT * FROM authors ORDER BY name");

$edit_book   = null;
$edit_author = null;
$sel_aids    = [];

if ($action === 'edit_book') {
    $id = (int)$_GET['id'];
    $edit_book = $conn->query("SELECT * FROM books WHERE book_id=$id")->fetch_assoc();
    $r = $conn->query("SELECT author_id FROM book_details WHERE book_id=$id AND is_deleted=0");
    while ($row = $r->fetch_assoc()) $sel_aids[] = $row['author_id'];
    $tab = 'books';
}
if ($action === 'edit_author') {
    $edit_author = $conn->query("SELECT * FROM authors WHERE author_id=".(int)$_GET['id'])->fetch_assoc();
    $tab = 'authors';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Library CRUD</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f0f0f0; }
  h2 { padding: 16px 20px; background: #2c3e50; color: #fff; }
  .tabs { display: flex; background: #34495e; }
  .tabs a { padding: 12px 24px; color: #ccc; text-decoration: none; font-size: 14px; }
  .tabs a.active { background: #fff; color: #2c3e50; font-weight: bold; }
  .container { padding: 20px; }
  form { background: #fff; padding: 16px; margin-bottom: 20px;
         border: 1px solid #ddd; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
  form input, form select { padding: 7px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
  form label { font-size: 12px; color: #555; display: block; margin-bottom: 3px; }
  .field { display: flex; flex-direction: column; }
  table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ddd; }
  th { background: #2c3e50; color: #fff; padding: 10px 14px; text-align: left; font-size: 13px; }
  td { padding: 9px 14px; font-size: 13px; border-bottom: 1px solid #eee; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #f9f9f9; }
  .btn { padding: 7px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
  .btn-blue   { background: #2980b9; color: #fff; }
  .btn-green  { background: #27ae60; color: #fff; }
  .btn-yellow { background: #f39c12; color: #fff; }
  .btn-red    { background: #e74c3c; color: #fff; }
  .btn:hover  { opacity: .85; }
</style>
</head>
<body>

<h2>📚 Library Manager</h2>

<div class="tabs">
  <a href="?tab=books"   class="<?= $tab==='books'  ?'active':'' ?>">Books</a>
  <a href="?tab=authors" class="<?= $tab==='authors'?'active':'' ?>">Authors</a>
</div>

<div class="container">

<?php if ($tab === 'books'): ?>

  <form method="POST">
    <input type="hidden" name="book_id" value="<?= $edit_book['book_id'] ?? '' ?>">
    <div class="field">
      <label>Title *</label>
      <input type="text" name="title" value="<?= htmlspecialchars($edit_book['title'] ?? '') ?>" required style="width:220px">
    </div>
    <div class="field">
      <label>Category</label>
      <select name="category" style="width:140px">
        <option value="">— none —</option>
        <?php foreach (['Fiction','Non-Fiction','Science','Technology','History','Biography','Other'] as $c): ?>
          <option value="<?= $c ?>" <?= ($edit_book['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="field">
      <label>Pages</label>
      <input type="number" name="page" value="<?= $edit_book['page'] ?? '' ?>" style="width:80px">
    </div>
    <div class="field">
      <label>Year</label>
      <input type="number" name="publish_year" value="<?= $edit_book['publish_year'] ?? '' ?>" style="width:80px">
    </div>
    <div class="field">
      <label>Authors</label>
      <select name="author_ids[]" style="width:180px">
        <option value="">— none —</option>
        <?php while ($a = $all_authors->fetch_assoc()): ?>
          <option value="<?= $a['author_id'] ?>" <?= in_array($a['author_id'], $sel_aids) ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['name']) ?>
          </option>
        <?php endwhile ?>
      </select>
    </div>
    <button type="submit" name="save_book" class="btn <?= $edit_book ? 'btn-yellow' : 'btn-green' ?>">
      <?= $edit_book ? 'Update Book' : 'Add Book' ?>
    </button>
    <?php if ($edit_book): ?>
      <a href="?tab=books" class="btn btn-blue">Cancel</a>
    <?php endif ?>
  </form>

  <table>
    <thead>
      <tr><th>#</th><th>Title</th><th>Category</th><th>Pages</th><th>Year</th><th>Authors</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php $i = 1; while ($b = $books->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($b['title']) ?></td>
        <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
        <td><?= $b['page'] ?: '—' ?></td>
        <td><?= $b['publish_year'] ?: '—' ?></td>
        <td><?= htmlspecialchars($b['authors'] ?? '—') ?></td>
        <td>
          <a href="?action=edit_book&id=<?= $b['book_id'] ?>" class="btn btn-yellow">Edit</a>
          <a href="?action=delete_book&id=<?= $b['book_id'] ?>" class="btn btn-red"
             onclick="return confirm('Delete this book?')">Delete</a>
        </td>
      </tr>
    <?php endwhile ?>
    </tbody>
  </table>

<?php else: ?>

  <form method="POST">
    <input type="hidden" name="author_id" value="<?= $edit_author['author_id'] ?? '' ?>">
    <div class="field">
      <label>Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($edit_author['name'] ?? '') ?>" required style="width:180px">
    </div>
    <div class="field">
      <label>Email *</label>
      <input type="email" name="email" value="<?= htmlspecialchars($edit_author['email'] ?? '') ?>" required style="width:200px">
    </div>
    <div class="field">
      <label>Address</label>
      <input type="text" name="address" value="<?= htmlspecialchars($edit_author['address'] ?? '') ?>" style="width:220px">
    </div>
    <button type="submit" name="save_author" class="btn <?= $edit_author ? 'btn-yellow' : 'btn-green' ?>">
      <?= $edit_author ? 'Update Author' : 'Add Author' ?>
    </button>
    <?php if ($edit_author): ?>
      <a href="?tab=authors" class="btn btn-blue">Cancel</a>
    <?php endif ?>
  </form>

  <table>
    <thead>
      <tr><th>#</th><th>Name</th><th>Email</th><th>Address</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php $i = 1; while ($a = $authors->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($a['name']) ?></td>
        <td><?= htmlspecialchars($a['email']) ?></td>
        <td><?= htmlspecialchars($a['address'] ?? '—') ?></td>
        <td>
          <a href="?action=edit_author&id=<?= $a['author_id'] ?>&tab=authors" class="btn btn-yellow">Edit</a>
          <a href="?action=delete_author&id=<?= $a['author_id'] ?>" class="btn btn-red"
             onclick="return confirm('Delete this author?')">Delete</a>
        </td>
      </tr>
    <?php endwhile ?>
    </tbody>
  </table>

<?php endif ?>
</div>
</body>
</html>