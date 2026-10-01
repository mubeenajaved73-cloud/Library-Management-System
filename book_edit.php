<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

$pageTitle = 'Edit Book';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    header("Location: books.php?msg=Book not found");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '') ?: 'General';
    $publisher = trim($_POST['publisher'] ?? '');
    $shelf = trim($_POST['shelf_location'] ?? '');
    $totalCopies = (int) ($_POST['total_copies'] ?? 0);
    $availableCopies = (int) ($_POST['available_copies'] ?? 0);

    $issuedOut = $totalCopies - $availableCopies; // sanity check basis

    if ($title === '' || $author === '' || $isbn === '') {
        $error = 'Title, author, and ISBN are required.';
    } elseif ($totalCopies < 1) {
        $error = 'Total copies must be at least 1.';
    } elseif ($availableCopies < 0 || $availableCopies > $totalCopies) {
        $error = 'Available copies must be between 0 and total copies.';
    } else {
        // BUG-01 fix: proactively check for duplicate ISBN on another book before updating
        $isbnCheck = $conn->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
        $isbnCheck->bind_param("si", $isbn, $id);
        $isbnCheck->execute();
        $isbnCheck->store_result();

        if ($isbnCheck->num_rows > 0) {
            $error = 'A book with this ISBN already exists.';
        } else {
            // BUG-02 fix: total_copies must never drop below currently issued copies
            $issuedCheck = $conn->prepare("SELECT COUNT(*) AS c FROM transactions WHERE book_id = ? AND status = 'issued'");
            $issuedCheck->bind_param("i", $id);
            $issuedCheck->execute();
            $issuedNow = $issuedCheck->get_result()->fetch_assoc()['c'];

            if ($totalCopies < $issuedNow) {
                $error = "Total copies cannot be less than the $issuedNow copies currently issued.";
            } else {
        $title = sanitize($conn, $title);
        $author = sanitize($conn, $author);
        $isbn = sanitize($conn, $isbn);
        $category = sanitize($conn, $category);
        $publisher = sanitize($conn, $publisher);
        $shelf = sanitize($conn, $shelf);

        $update = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, publisher=?, total_copies=?, available_copies=?, shelf_location=? WHERE id=?");
        $update->bind_param("sssssiisi", $title, $author, $isbn, $category, $publisher, $totalCopies, $availableCopies, $shelf, $id);

        if ($update->execute()) {
            header("Location: books.php?msg=Book updated successfully");
            exit();
        } else {
            $error = 'Something went wrong. Please try again.';
        }
            }
        }
    }
    // keep edited values on screen if validation failed
    $book = array_merge($book, compact('title', 'author', 'isbn', 'category', 'publisher', 'shelf') );
    $book['total_copies'] = $totalCopies;
    $book['available_copies'] = $availableCopies;
    $book['shelf_location'] = $shelf;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Book</h1>
        <p class="subtitle">Update details for "<?php echo htmlspecialchars($book['title']); ?>".</p>
    </div>
    <a href="books.php" class="btn btn-outline">← Back to Books</a>
</div>

<div class="card" style="max-width:640px;">
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" action="book_edit.php?id=<?php echo $id; ?>" novalidate>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($book['title']); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" required value="<?php echo htmlspecialchars($book['author']); ?>">
            </div>
            <div class="form-group">
                <label for="isbn">ISBN *</label>
                <input type="text" id="isbn" name="isbn" required value="<?php echo htmlspecialchars($book['isbn']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($book['category']); ?>">
            </div>
            <div class="form-group">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($book['publisher']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="total_copies">Total Copies *</label>
                <input type="number" id="total_copies" name="total_copies" min="1" required value="<?php echo htmlspecialchars($book['total_copies']); ?>">
            </div>
            <div class="form-group">
                <label for="available_copies">Available Copies *</label>
                <input type="number" id="available_copies" name="available_copies" min="0" required value="<?php echo htmlspecialchars($book['available_copies']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="shelf_location">Shelf Location</label>
            <input type="text" id="shelf_location" name="shelf_location" value="<?php echo htmlspecialchars($book['shelf_location']); ?>">
        </div>
        <button type="submit" class="btn btn-brass">Update Book</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
