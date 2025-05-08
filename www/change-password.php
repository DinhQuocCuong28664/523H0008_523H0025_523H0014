<?php
require_once 'config.php';
require_once 'languages.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Load user preferences
$theme = 'light';
$language = 'en';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT theme, language FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $preferences = $stmt->fetch();
    if ($preferences) {
        $theme = $preferences['theme'];
        $language = $preferences['language'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = translate('csrf_invalid', $language);
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Fetch current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Validate inputs
        if (!password_verify($current_password, $user['password'])) {
            $message = translate('current_password_incorrect', $language);
        } elseif ($new_password !== $confirm_password) {
            $message = translate('passwords_not_match', $language);
        } elseif (strlen($new_password) < 6) {
            $message = translate('password_too_short', $language);
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $message = translate('password_changed', $language);
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
    <title><?= translate('change_password', $language) ?></title>
    <style>
        .header {
            background-color: #343a40;
            color: white;
            padding: 10px 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }
        .header .navbar-brand {
            color: white;
            font-size: 1.5rem;
        }
        .header .nav-link {
            color: white !important;
            margin-left: 15px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 10px 0;
            text-align: center;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }
        .main-content {
            margin-top: 60px;
            margin-bottom: 60px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <a class="navbar-brand" href="/index.php"><?= translate('welcome', $language) ?></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="navbar-nav">
                        <a class="nav-link" href="/profile.php"><?= translate('profile', $language) ?></a>
                        <a class="nav-link" href="/preferences.php"><?= translate('preferences', $language) ?></a>
                        <a class="nav-link" href="/change-password.php"><?= translate('change_password', $language) ?></a>
                        <a class="nav-link" href="/logout.php"><?= translate('logout', $language) ?></a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h3 class="mb-3"><?= translate('change_password', $language) ?></h3>
            <p><a href="/index.php"><?= translate('back_to_notes', $language) ?></a></p>

            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="form-group">
                    <label for="current_password"><?= translate('current_password', $language) ?>:</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password"><?= translate('new_password', $language) ?>:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><?= translate('confirm_password', $language) ?>:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary"><?= translate('change_password', $language) ?></button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Note-Taking App. All rights reserved.</p>
            <p>Developed by [Your Name]</p>
        </div>
    </footer>
</body>
</html>