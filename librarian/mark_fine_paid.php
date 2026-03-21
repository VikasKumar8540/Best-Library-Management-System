<?php
require_once '../includes/config.php';
requireLogin('librarian');
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE book_issues SET fine_paid='yes' WHERE id=$id");
}
redirect(APP_URL . '/librarian/issued_books.php');
?>
