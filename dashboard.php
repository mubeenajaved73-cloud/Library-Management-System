<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireLogin();

$pageTitle = 'Dashboard';

// ---- Stats ----
$totalBooks = $conn->query("SELECT COUNT(*) AS c FROM books")->fetch_assoc()['c'];
$availableCopies = $conn->query("SELECT COALESCE(SUM(available_copies),0) AS c FROM books")->fetch_assoc()['c'];
$issuedCount = $conn->query("SELECT COUNT(*) AS c FROM transactions WHERE status = 'issued'")->fetch_assoc()['c'];
$totalMembers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'member'")->fetch_assoc()['c'];

// ---- Recent transactions (admin) or my transactions (member) ----
if (isAdmin()) {
    $recent = $conn->query("
        SELECT t.id, b.title, u.full_name, t.issue_date, t.due_date, t.status
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        JOIN users u ON t.user_id = u.id
        ORDER BY t.id DESC LIMIT 6
    ");
} else {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT t.id, b.title, t.issue_date, t.due_date, t.status
        FROM transactions t JOIN books b ON t.book_id = b.id
        WHERE t.user_id = ? ORDER BY t.id DESC LIMIT 6
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $recent = $stmt->get_result();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        <p class="subtitle"><?php echo isAdmin() ? 'Librarian overview of the collection and activity.' : 'Here is a summary of the library and your account.'; ?></p>
    </div>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-error">You don't have permission to access that page.</div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalBooks; ?></div>
        <div class="stat-label">Total Book Titles</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $availableCopies; ?></div>
        <div class="stat-label">Available Copies</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $issuedCount; ?></div>
        <div class="stat-label">Currently Issued</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalMembers; ?></div>
        <div class="stat-label">Total Members</div>
    </div>
</div>

<div class="card">
    <h2><?php echo isAdmin() ? 'Recent Transactions' : 'Your Recent Activity'; ?></h2>
    <?php if ($recent->num_rows === 0): ?>
        <div class="empty-state">No transactions yet. <?php echo isAdmin() ? 'Issue a book from the Transactions page.' : 'Browse the Books page to get started.'; ?></div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Book</th>
                <?php if (isAdmin()): ?><th>Member</th><?php endif; ?>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $recent->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <?php if (isAdmin()): ?><td><?php echo htmlspecialchars($row['full_name']); ?></td><?php endif; ?>
                <td><?php echo htmlspecialchars($row['issue_date']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td>
                    <?php if ($row['status'] === 'issued'): ?>
                        <span class="badge badge-neutral">Issued</span>
                    <?php elseif ($row['status'] === 'overdue'): ?>
                        <span class="badge badge-danger">Overdue</span>
                    <?php else: ?>
                        <span class="badge badge-success">Returned</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
