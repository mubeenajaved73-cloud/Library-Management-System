<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

$pageTitle = 'Issue Book';
$error = '';
$preselectBook = (int) ($_GET['book_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    $dueDate = trim($_POST['due_date'] ?? '');

    if (!$bookId || !$userId || $dueDate === '') {
        $error = 'Please select a book, a member, and a due date.';
    } else {
        $bookStmt = $conn->prepare("SELECT available_copies FROM books WHERE id = ?");
        $bookStmt->bind_param("i", $bookId);
        $bookStmt->execute();
        $bookRow = $bookStmt->get_result()->fetch_assoc();

        if (!$bookRow) {
            $error = 'Selected book not found.';
        } elseif ($bookRow['available_copies'] < 1) {
            $error = 'No available copies of this book to issue.';
        } else {
            $conn->begin_transaction();
            try {
                $issueDate = date('Y-m-d');
                $insert = $conn->prepare("INSERT INTO transactions (book_id, user_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued')");
                $insert->bind_param("iiss", $bookId, $userId, $issueDate, $dueDate);
                $insert->execute();

                $dec = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0");
                $dec->bind_param("i", $bookId);
                $dec->execute();

                $conn->commit();
                header("Location: transactions.php?msg=" . urlencode("Book issued successfully."));
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Something went wrong while issuing the book.';
            }
        }
    }
}

$books = $conn->query("SELECT id, title, available_copies FROM books WHERE available_copies > 0 ORDER BY title ASC");
$members = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'member' AND status = 'active' ORDER BY full_name ASC");

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Issue Book</h1>
        <p class="subtitle">Record a new book checkout for a member.</p>
    </div>
    <a href="transactions.php" class="btn btn-outline">← Back to Transactions</a>
</div>

<div class="card" style="max-width:560px;">
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($books->num_rows === 0): ?>
        <div class="empty-state">No books are currently available to issue.</div>
    <?php elseif ($members->num_rows === 0): ?>
        <div class="empty-state">No active members are registered yet.</div>
    <?php else: ?>
    <form method="POST" action="transaction_issue.php" novalidate>
        <div class="form-group">
            <label for="book_id">Book *</label>
            <select id="book_id" name="book_id" required>
                <option value="">Select a book</option>
                <?php while ($b = $books->fetch_assoc()): ?>
                    <option value="<?php echo $b['id']; ?>" <?php echo ($b['id'] === $preselectBook) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($b['title']); ?> (<?php echo $b['available_copies']; ?> available)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="user_id">Member *</label>
            <select id="user_id" name="user_id" required>
                <option value="">Select a member</option>
                <?php while ($m = $members->fetch_assoc()): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?> (<?php echo htmlspecialchars($m['email']); ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="due_date">Due Date *</label>
            <input type="date" id="due_date" name="due_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
        </div>
        <button type="submit" class="btn btn-brass">Issue Book</button>
    </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
