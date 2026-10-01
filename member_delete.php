<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

// BUG-06 fix: require POST + valid CSRF token for this destructive action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: members.php?msg=" . urlencode("Invalid request. Please use the Delete button on the Members page."));
    exit();
}

$id = (int) ($_POST['id'] ?? 0);

$check = $conn->prepare("SELECT COUNT(*) AS c FROM transactions WHERE user_id = ? AND status = 'issued'");
$check->bind_param("i", $id);
$check->execute();
$activeCount = $check->get_result()->fetch_assoc()['c'];

if ($activeCount > 0) {
    header("Location: members.php?msg=" . urlencode("Cannot delete: this member has books currently issued."));
    exit();
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: members.php?msg=" . urlencode("Member deleted successfully."));
exit();
