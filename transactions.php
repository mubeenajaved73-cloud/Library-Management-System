<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireLogin();

$pageTitle = 'Transactions';

if (isAdmin()) {
    $txns = $conn->query("
        SELECT t.*, b.title, u.full_name
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        JOIN users u ON t.user_id = u.id
        ORDER BY t.id DESC
    ");
} else {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT t.*, b.title
        FROM transactions t JOIN books b ON t.book_id = b.id
        WHERE t.user_id = ? ORDER BY t.id DESC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $txns = $stmt->get_result();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Transactions</h1>
        <p class="subtitle"><?php echo isAdmin() ? 'All book issue and return records.' : 'Your book issue and return history.'; ?></p>
    </div>
    <?php if (isAdmin()): ?>
        <a href="transaction_issue.php" class="btn btn-brass">+ Issue Book</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="card">
    <?php if ($txns->num_rows === 0): ?>
        <div class="empty-state">No transactions recorded yet.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Book</th>
                <?php if (isAdmin()): ?><th>Member</th><?php endif; ?>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <?php if (isAdmin()): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php while ($t = $txns->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($t['title']); ?></td>
                <?php if (isAdmin()): ?><td><?php echo htmlspecialchars($t['full_name']); ?></td><?php endif; ?>
                <td><?php echo htmlspecialchars($t['issue_date']); ?></td>
                <td><?php echo htmlspecialchars($t['due_date']); ?></td>
                <td><?php echo htmlspecialchars($t['return_date'] ?: '—'); ?></td>
                <td>
                    <?php if ($t['status'] === 'issued'): ?><span class="badge badge-neutral">Issued</span>
                    <?php elseif ($t['status'] === 'overdue'): ?><span class="badge badge-danger">Overdue</span>
                    <?php else: ?><span class="badge badge-success">Returned</span><?php endif; ?>
                </td>
                <?php if (isAdmin()): ?>
                <td>
                    <?php if ($t['status'] !== 'returned'): ?>
                        <form method="POST" action="transaction_return.php" onsubmit="return confirm('Mark this book as returned?');">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-outline btn-sm">Mark Returned</button>
                        </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
