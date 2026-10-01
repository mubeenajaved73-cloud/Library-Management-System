<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

// BUG-06 fix: require POST + valid CSRF token for this destructive action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: books.php?msg=" . urlencode("Invalid request. Please use the Delete button on the Books page."));
    exit();
}

$id = (int) ($_POST['id'] ?? 0);

// Prevent deleting a book that currently has active issued copies
$check = $conn->prepare("SELECT COUNT(*) AS c FROM transactions WHERE book_id = ? AND status = 'issued'");
$check->bind_param("i", $id);
$check->execute();
$activeCount = $check->get_result()->fetch_assoc()['c'];

if ($activeCount > 0) {
    header("Location: books.php?msg=" . urlencode("Cannot delete: this book has copies currently issued."));
    exit();
}

$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: books.php?msg=" . urlencode("Book deleted successfully."));
exit();
