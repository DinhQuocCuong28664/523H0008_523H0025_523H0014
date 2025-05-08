<?php
require_once 'config.php';
require_once 'languages.php';

$message = '';
$theme = 'light';
$language = 'en';

// Load user preferences if available
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT theme, language FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $preferences = $stmt->fetch();
    if ($preferences) {
        $theme = $preferences['theme'];
        $language = $preferences['language'];
    }
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE activation_token = ? AND is_active = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $stmt = $conn->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        $_SESSION['is_active'] = 1; // Update session
        $message = translate('account_activated', $language);
    } else {
        $message = translate('invalid_token', $language);
    }
} else {
    $message = translate('no_token', $language);
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
    <title><?= translate('activate_account', $language) ?></title>
</head>
<body>
    <div class="container">
        <h3 class="mt-3"><?= translate('activate_account', $language) ?></h3>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    </div>
</body>
</html>