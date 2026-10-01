<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

// BUG-06 fix: require POST + valid CSRF token for this destructive action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: transactions.php?error=" . urlencode("Invalid request. Please use the Mark Returned button."));
    exit();
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$txn = $stmt->get_result()->fetch_assoc();

if (!$txn || $txn['status'] === 'returned') {
    header("Location: transactions.php?error=" . urlencode("Transaction not found or already returned."));
    exit();
}

$conn->begin_transaction();
try {
    $returnDate = date('Y-m-d');
    $update = $conn->prepare("UPDATE transactions SET status = 'returned', return_date = ? WHERE id = ?");
    $update->bind_param("si", $returnDate, $id);
    $update->execute();

    $inc = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
    $inc->bind_param("i", $txn['book_id']);
    $inc->execute();

    $conn->commit();
    header("Location: transactions.php?msg=" . urlencode("Book marked as returned."));
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header("Location: transactions.php?error=" . urlencode("Something went wrong."));
    exit();
}
