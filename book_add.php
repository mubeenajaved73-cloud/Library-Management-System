<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

$pageTitle = 'Add Book';
$error = '';
$old = ['title' => '', 'author' => '', 'isbn' => '', 'category' => '', 'publisher' => '', 'total_copies' => 1, 'shelf_location' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $v) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $totalCopies = (int) $old['total_copies'];

    if ($old['title'] === '' || $old['author'] === '' || $old['isbn'] === '') {
        $error = 'Title, author, and ISBN are required.';
    } elseif ($totalCopies < 1) {
        $error = 'Total copies must be at least 1.';
    } else {
        $title = sanitize($conn, $old['title']);
        $author = sanitize($conn, $old['author']);
        $isbn = sanitize($conn, $old['isbn']);
        $category = sanitize($conn, $old['category']) ?: 'General';
        $publisher = sanitize($conn, $old['publisher']);
        $shelf = sanitize($conn, $old['shelf_location']);

        $check = $conn->prepare("SELECT id FROM books WHERE isbn = ?");
        $check->bind_param("s", $isbn);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'A book with this ISBN already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, publisher, total_copies, available_copies, shelf_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiis", $title, $author, $isbn, $category, $publisher, $totalCopies, $totalCopies, $shelf);
            if ($stmt->execute()) {
                header("Location: books.php?msg=Book added successfully");
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Add Book</h1>
        <p class="subtitle">Add a new title to the library catalog.</p>
    </div>
    <a href="books.php" class="btn btn-outline">← Back to Books</a>
</div>

<div class="card" style="max-width:640px;">
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" action="book_add.php" novalidate>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($old['title']); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" required value="<?php echo htmlspecialchars($old['author']); ?>">
            </div>
            <div class="form-group">
                <label for="isbn">ISBN *</label>
                <input type="text" id="isbn" name="isbn" required value="<?php echo htmlspecialchars($old['isbn']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($old['category']); ?>" placeholder="e.g. Fiction, Computer Science">
            </div>
            <div class="form-group">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($old['publisher']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="total_copies">Total Copies *</label>
                <input type="number" id="total_copies" name="total_copies" min="1" required value="<?php echo htmlspecialchars($old['total_copies']); ?>">
            </div>
            <div class="form-group">
                <label for="shelf_location">Shelf Location</label>
                <input type="text" id="shelf_location" name="shelf_location" value="<?php echo htmlspecialchars($old['shelf_location']); ?>" placeholder="e.g. A1-05">
            </div>
        </div>
        <button type="submit" class="btn btn-brass">Save Book</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
