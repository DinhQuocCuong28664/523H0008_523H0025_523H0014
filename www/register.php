<?php
require_once 'config.php';
require_once 'languages.php';

$message = '';
$theme = 'light';
$language = 'en';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = translate('all_fields_required', $language);
    } elseif ($password !== $confirm_password) {
        $message = translate('passwords_not_match', $language);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = translate('invalid_email', $language);
    } elseif (strlen($password) < 6) {
        $message = translate('password_too_short', $language);
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $message = translate('username_email_exists', $language);
        } else {
            // Generate activation token
            $activation_token = bin2hex(random_bytes(16));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, activation_token, is_active) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$username, $email, $hashed_password, $activation_token]);

            // Get the newly created user ID
            $user_id = $conn->lastInsertId();

            // Auto-login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['is_active'] = 0;

            // Send activation email
            if (sendActivationEmail($email, $activation_token)) {
                $_SESSION['message'] = translate('activation_email_sent', $language);
            } else {
                $_SESSION['message'] = translate('email_sending_failed', $language);
            }

            // Redirect to index.php
            header('Location: /index.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
    <title><?= translate('register', $language) ?></title>
</head>
<body>
    <div class="container">
        <h3 class="mt-3 mb-3"><?= translate('register', $language) ?></h3>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username"><?= translate('username', $language) ?>:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email"><?= translate('email', $language) ?>:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password"><?= translate('password', $language) ?>:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><?= translate('confirm_password', $language) ?>:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= translate('register', $language) ?></button>
        </form>
        <p class="mt-3"><?= translate('have_account', $language) ?> <a href="/login.php"><?= translate('login', $language) ?></a></p>
    </div>
</body>
</html>