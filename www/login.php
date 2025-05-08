<?php
require_once 'config.php';
require_once 'languages.php';

$message = '';
$theme = 'light';
$language = 'en';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// Load user preferences if available (e.g., after logout)
if (isset($_SESSION['temp_user_id'])) {
    $stmt = $conn->prepare("SELECT theme, language FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['temp_user_id']]);
    $preferences = $stmt->fetch();
    if ($preferences) {
        $theme = $preferences['theme'];
        $language = $preferences['language'];
    }
    unset($_SESSION['temp_user_id']); // Clear temp user ID
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $message = translate('email_password_required', $language);
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: /index.php');
                exit;
            } else {
                $message = translate('account_not_activated', $language);
            }
        } else {
            $message = translate('invalid_credentials', $language);
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
    <title><?= translate('login', $language) ?></title>
</head>
<body>
    <div class="container">
        <h3 class="mt-3 mb-3"><?= translate('login', $language) ?></h3>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email"><?= translate('email', $language) ?>:</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="<?= translate('enter_email', $language) ?>" required>
            </div>
            <div class="form-group">
                <label for="password"><?= translate('password', $language) ?>:</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="<?= translate('enter_password', $language) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= translate('login', $language) ?></button>
        </form>
        <p class="mt-3"><?= translate('no_account', $language) ?> <a href="/register.php"><?= translate('register', $language) ?></a> | <a href="/reset-password.php"><?= translate('forgot_password', $language) ?></a></p>
    </div>
</body>
</html>