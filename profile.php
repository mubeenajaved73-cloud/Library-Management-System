<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
requireLogin();

$pageTitle = 'My Profile';
$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        $error = 'Full name is required.';
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        // BUG-05 fix: reject non-numeric / malformed phone numbers
        $error = 'Please enter a valid phone number (digits, spaces, +, - only).';
    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } elseif ($newPassword !== '' && !password_verify($currentPassword, $user['password'])) {
        // BUG-03 fix: require and verify the current password before allowing a change
        $error = 'Current password is incorrect.';
    } else {
        $full_name = sanitize($conn, $full_name);
        $phone = sanitize($conn, $phone);
        $address = sanitize($conn, $address);

        if ($newPassword !== '') {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=?, password=? WHERE id=?");
            $update->bind_param("ssssi", $full_name, $phone, $address, $hashed, $uid);
        } else {
            $update = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?");
            $update->bind_param("sssi", $full_name, $phone, $address, $uid);
        }

        if ($update->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = 'Profile updated successfully.';
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="subtitle">View and update your account information.</p>
    </div>
</div>

<div class="card" style="max-width:560px;">
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <form method="POST" action="profile.php" novalidate>
        <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            <div class="field-hint">Email address cannot be changed.</div>
        </div>
        <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" disabled>
        </div>
        <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" placeholder="Required only when setting a new password">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="6" placeholder="Leave blank to keep current">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6">
            </div>
        </div>
        <button type="submit" class="btn btn-brass">Save Changes</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
