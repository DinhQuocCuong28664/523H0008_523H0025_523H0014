<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$message = '';
$reset_method = $_POST['reset_method'] ?? 'link'; // Default to link

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'CSRF token invalid!';
    } else {
        $email = trim($_POST['email']);
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            if ($reset_method === 'link') {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(16));
                $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
                $stmt->execute([$reset_token, $user['id']]);
                $reset_link = "http://localhost:8080/reset_password.php?token=$reset_token";
                sendEmail($email, 'Password Reset', "Click here to reset your password: $reset_link");
                $message = 'Password reset link sent to your email!';
            } elseif ($reset_method === 'otp') {
                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_user_id'] = $user['id'];
                sendEmail($email, 'Password Reset OTP', "Your OTP to reset your password is: $otp");
                $message = 'OTP sent to your email! <a href="/reset_password.php?method=otp">Enter OTP here</a>';
            }
        } else {
            $message = 'Email not found or account not activated!';
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
    <title>Password Reset Request</title>
</head>
<body>
    <div class="container">
        <h3 class="mt-3">Reset Password</h3>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
                <label for="reset_method">Reset Method</label>
                <select class="form-control" id="reset_method" name="reset_method">
                    <option value="link">Send Reset Link</option>
                    <option value="otp">Send OTP</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
            <p class="mt-2"><a href="/login.php">Back to Login</a></p>
        </form>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/main.js"></script>
</body>
</html>