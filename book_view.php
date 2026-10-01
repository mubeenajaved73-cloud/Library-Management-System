<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireLogin();

$pageTitle = 'Book Details';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    header("Location: books.php?msg=" . urlencode("Book not found."));
    exit();
}

// Transaction history for this book
$hist = $conn->prepare("
    SELECT t.issue_date, t.due_date, t.return_date, t.status, u.full_name
    FROM transactions t JOIN users u ON t.user_id = u.id
    WHERE t.book_id = ? ORDER BY t.id DESC LIMIT 10
");
$hist->bind_param("i", $id);
$hist->execute();
$history = $hist->get_result();

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?php echo htmlspecialchars($book['title']); ?></h1>
        <p class="subtitle">by <?php echo htmlspecialchars($book['author']); ?></p>
    </div>
    <a href="books.php" class="btn btn-outline">← Back to Books</a>
</div>

<div class="card" style="max-width:640px;">
    <h2>Book Information</h2>
    <table>
        <tr><td style="width:180px;color:var(--text-muted);">ISBN</td><td><?php echo htmlspecialchars($book['isbn']); ?></td></tr>
        <tr><td style="color:var(--text-muted);">Category</td><td><?php echo htmlspecialchars($book['category']); ?></td></tr>
        <tr><td style="color:var(--text-muted);">Publisher</td><td><?php echo htmlspecialchars($book['publisher'] ?: '—'); ?></td></tr>
        <tr><td style="color:var(--text-muted);">Shelf Location</td><td><?php echo htmlspecialchars($book['shelf_location'] ?: '—'); ?></td></tr>
        <tr><td style="color:var(--text-muted);">Availability</td><td>
            <?php if ($book['available_copies'] > 0): ?>
                <span class="badge badge-success"><?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> available</span>
            <?php else: ?>
                <span class="badge badge-danger">All copies issued</span>
            <?php endif; ?>
        </td></tr>
    </table>
    <?php if (isAdmin() && $book['available_copies'] > 0): ?>
        <div style="margin-top:16px;">
            <a href="transaction_issue.php?book_id=<?php echo $book['id']; ?>" class="btn btn-brass btn-sm">Issue This Book</a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Transaction History</h2>
    <?php if ($history->num_rows === 0): ?>
        <div class="empty-state">No transaction history for this book yet.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Member</th><th>Issue Date</th><th>Due Date</th><th>Return Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($h = $history->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($h['full_name']); ?></td>
                <td><?php echo htmlspecialchars($h['issue_date']); ?></td>
                <td><?php echo htmlspecialchars($h['due_date']); ?></td>
                <td><?php echo htmlspecialchars($h['return_date'] ?: '—'); ?></td>
                <td>
                    <?php if ($h['status'] === 'issued'): ?><span class="badge badge-neutral">Issued</span>
                    <?php elseif ($h['status'] === 'overdue'): ?><span class="badge badge-danger">Overdue</span>
                    <?php else: ?><span class="badge badge-success">Returned</span><?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
