<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$message = '';
$method = $_GET['method'] ?? 'token'; // Default to token method
$token = $_GET['token'] ?? '';

if ($method === 'token' && !$token) {
    $message = 'No reset token provided!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'CSRF token invalid!';
    } else {
        if ($method === 'otp') {
            $otp = $_POST['otp'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($otp) || empty($password)) {
                $message = 'OTP and password are required!';
            } elseif ($otp != ($_SESSION['reset_otp'] ?? '')) {
                $message = 'Invalid OTP!';
            } elseif (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters!';
            } else {
                $user_id = $_SESSION['reset_user_id'] ?? 0;
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_user_id']);
                $message = 'Password reset successful! <a href="/login.php">Login</a> now.';
            }
        } else {
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters!';
            } else {
                $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ?");
                $stmt->execute([$token]);
                $user = $stmt->fetch();
                if ($user) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
                    $stmt->execute([$hashed_password, $user['id']]);
                    $message = 'Password reset successful! <a href="/login.php">Login</a> now.';
                } else {
                    $message = 'Invalid or expired reset token!';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
    <title>Reset Password</title>
</head>
<body>
    <div class="container">
        <h3 class="mt-3">Reset Password</h3>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!$message || strpos($message, 'successful') === false): ?>
        <form method="POST" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if ($method === 'otp'): ?>
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <input type="text" class="form-control" name="otp" required>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/main.js"></script>
</body>
</html>