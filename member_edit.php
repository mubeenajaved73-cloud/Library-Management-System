<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireAdmin();

$pageTitle = 'Edit Member';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
$stmt->bind_param("i", $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    header("Location: members.php?msg=" . urlencode("Member not found."));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($full_name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $error = 'Invalid status.';
    } else {
        $emailEsc = sanitize($conn, $email);
        $dupe = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $dupe->bind_param("si", $emailEsc, $id);
        $dupe->execute();
        $dupe->store_result();

        if ($dupe->num_rows > 0) {
            $error = 'Another account already uses this email.';
        } else {
            $full_name = sanitize($conn, $full_name);
            $phone = sanitize($conn, $phone);
            $address = sanitize($conn, $address);

            $update = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=?, status=? WHERE id=?");
            $update->bind_param("sssssi", $full_name, $emailEsc, $phone, $address, $status, $id);
            if ($update->execute()) {
                header("Location: members.php?msg=" . urlencode("Member updated successfully."));
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
    $member = array_merge($member, compact('full_name', 'phone', 'address', 'status'));
    $member['email'] = $email;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Member</h1>
        <p class="subtitle">Update information for <?php echo htmlspecialchars($member['full_name']); ?>.</p>
    </div>
    <a href="members.php" class="btn btn-outline">← Back to Members</a>
</div>

<div class="card" style="max-width:560px;">
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" action="member_edit.php?id=<?php echo $id; ?>" novalidate>
        <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($member['full_name']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($member['email']); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?php echo $member['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $member['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($member['address']); ?>">
        </div>
        <button type="submit" class="btn btn-brass">Update Member</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
