<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

$pageTitle = 'Members';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT * FROM users WHERE role='member' AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY full_name ASC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $members = $stmt->get_result();
} else {
    $members = $conn->query("SELECT * FROM users WHERE role='member' ORDER BY full_name ASC");
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Members</h1>
        <p class="subtitle">View and manage registered library members.</p>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="toolbar">
    <form method="GET" action="members.php" class="search-box">
        <input type="text" name="q" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
    </form>
</div>

<div class="card">
    <?php if ($members->num_rows === 0): ?>
        <div class="empty-state">No members found<?php echo $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($m = $members->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($m['full_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($m['email']); ?></td>
                <td><?php echo htmlspecialchars($m['phone'] ?: '—'); ?></td>
                <td>
                    <?php if ($m['status'] === 'active'): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars(date('M j, Y', strtotime($m['created_at']))); ?></td>
                <td class="table-actions">
                    <a href="member_edit.php?id=<?php echo $m['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="POST" action="member_delete.php" onsubmit="return confirm('Delete this member permanently?');">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
