<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $old['address'] = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // ---- Validation ----
    if ($old['full_name'] === '' || $old['email'] === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) {
        // BUG-05 fix: reject non-numeric / malformed phone numbers
        $error = 'Please enter a valid phone number (digits, spaces, +, - only).';
    } else {
        $email = sanitize($conn, $old['email']);
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $stmt->close();
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $full_name = sanitize($conn, $old['full_name']);
            $phone = sanitize($conn, $old['phone']);
            $address = sanitize($conn, $old['address']);

            $insert = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, role, status) VALUES (?, ?, ?, ?, ?, 'member', 'active')");
            $insert->bind_param("sssss", $full_name, $email, $hashed, $phone, $address);

            if ($insert->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - LibraTrack</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand-mark">📚</div>
        <h1>Create an account</h1>
        <p class="tagline">Register as a library member</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <div class="form-group">
                <label for="full_name">Full name *</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($old['full_name']); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email address *</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($old['email']); ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <div class="field-hint">Minimum 6 characters</div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
            </div>
            <div class="form-group">
                <label for="phone">Phone number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($old['phone']); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($old['address']); ?>">
            </div>
            <button type="submit" class="btn btn-brass btn-full">Create Account</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</div>
</body>
</html>
