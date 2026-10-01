<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireLogin();

$pageTitle = 'Books';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ? ORDER BY title ASC");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query("SELECT * FROM books ORDER BY title ASC");
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Book Catalog</h1>
        <p class="subtitle">Browse, search, and manage the library's collection.</p>
    </div>
    <?php if (isAdmin()): ?>
        <a href="book_add.php" id="add-book-link" class="btn btn-brass">+ Add Book</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="toolbar">
    <form method="GET" action="books.php" class="search-box">
        <input type="text" id="search-input" name="q" placeholder="Search by title, author, ISBN, or category..." value="<?php echo htmlspecialchars($search); ?>">
    </form>
</div>

<div class="card">
    <?php if ($books->num_rows === 0): ?>
        <div class="empty-state">No books found<?php echo $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Availability</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($b = $books->fetch_assoc()): ?>
            <tr data-book-row="<?php echo $b['id']; ?>" data-book-title="<?php echo htmlspecialchars($b['title']); ?>">
                <td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td>
                <td><?php echo htmlspecialchars($b['author']); ?></td>
                <td><?php echo htmlspecialchars($b['category']); ?></td>
                <td>
                    <?php if ($b['available_copies'] > 0): ?>
                        <span class="badge badge-success"><?php echo $b['available_copies']; ?> / <?php echo $b['total_copies']; ?> available</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Unavailable</span>
                    <?php endif; ?>
                </td>
                <td class="table-actions">
                    <a href="book_view.php?id=<?php echo $b['id']; ?>" class="btn btn-outline btn-sm">View</a>
                    <?php if (isAdmin()): ?>
                        <a href="book_edit.php?id=<?php echo $b['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                        <form method="POST" action="book_delete.php" onsubmit="return confirm('Delete this book permanently?');">
                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
