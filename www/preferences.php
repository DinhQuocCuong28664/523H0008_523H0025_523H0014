<?php
require_once 'config.php';
require_once 'languages.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch user preferences
$stmt = $conn->prepare("SELECT theme, language, font_size, note_color FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$preferences = $stmt->fetch();

// If no preferences exist, insert default
if (!$preferences) {
    $stmt = $conn->prepare("INSERT INTO user_preferences (user_id, theme, language, font_size, note_color) VALUES (?, 'light', 'en', '16px', '#ffffff')");
    $stmt->execute([$user_id]);
    $preferences = ['theme' => 'light', 'language' => 'en', 'font_size' => '16px', 'note_color' => '#ffffff'];
}

// Load language for translations
$language = $preferences['language'];

// Handle preferences update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = translate('csrf_invalid', $language);
    } else {
        $theme = $_POST['theme'];
        $language = $_POST['language'];
        $font_size = $_POST['font_size'];
        $note_color = $_POST['note_color'];

        // Update preferences
        $stmt = $conn->prepare("UPDATE user_preferences SET theme = ?, language = ?, font_size = ?, note_color = ? WHERE user_id = ?");
        $stmt->execute([$theme, $language, $font_size, $note_color, $user_id]);
        $message = translate('updated_success', $language);

        // Refresh preferences
        $stmt = $conn->prepare("SELECT theme, language, font_size, note_color FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $preferences = $stmt->fetch();
        $language = $preferences['language'];
    }
}

// Handle view mode switch
$viewMode = isset($_SESSION['view_mode']) ? $_SESSION['view_mode'] : 'list'; // Default to list view
if (isset($_GET['switch_view']) && in_array($_GET['switch_view'], ['list', 'grid'])) {
    $_SESSION['view_mode'] = $_GET['switch_view'];
    $viewMode = $_SESSION['view_mode'];
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>" data-theme="<?= htmlspecialchars($preferences['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
    <title><?= translate('user_preferences', $language) ?></title>
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 200px;
            background-color: #f8f9fa;
            padding-top: 20px;
            border-right: 1px solid #ddd;
        }
        .sidebar .btn-group {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .main-content {
            margin-left: 220px;
            padding: 20px;
            margin-top: 60px; /* Khoảng cách từ header */
            margin-bottom: 60px; /* Khoảng cách từ footer */
        }
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
            <h3><?= translate('user_preferences', $language) ?></h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="form-group">
                    <label for="theme"><?= translate('theme', $language) ?>:</label>
                    <select class="form-control" id="theme" name="theme">
                        <option value="light" <?= $preferences['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="dark" <?= $preferences['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="language"><?= translate('language', $language) ?>:</label>
                    <select class="form-control" id="language" name="language">
                        <option value="en" <?= $preferences['language'] === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="vi" <?= $preferences['language'] === 'vi' ? 'selected' : '' ?>>Vietnamese</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="font_size"><?= translate('font_size', $language) ?>:</label>
                    <select class="form-control" id="font_size" name="font_size">
                        <option value="12px" <?= $preferences['font_size'] === '12px' ? 'selected' : '' ?>>12px</option>
                        <option value="14px" <?= $preferences['font_size'] === '14px' ? 'selected' : '' ?>>14px</option>
                        <option value="16px" <?= $preferences['font_size'] === '16px' ? 'selected' : '' ?>>16px</option>
                        <option value="18px" <?= $preferences['font_size'] === '18px' ? 'selected' : '' ?>>18px</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="note_color"><?= translate('note_color', $language) ?>:</label>
                    <input type="color" class="form-control" id="note_color" name="note_color" value="<?= htmlspecialchars($preferences['note_color']) ?>">
                </div>
                <button type="submit" name="update_preferences" class="btn btn-primary"><?= translate('update', $language) ?></button>
            </form>

            <!-- View mode switch buttons -->
            <div class="mt-4">
                <h4><?= translate('view_mode', $language) ?>:</h4>
                <button class="btn btn-sm btn-secondary <?= $viewMode === 'list' ? 'active' : '' ?>" onclick="window.location.href='?switch_view=list'"><?= translate('list_view', $language) ?></button>
                <button class="btn btn-sm btn-secondary ml-2 <?= $viewMode === 'grid' ? 'active' : '' ?>" onclick="window.location.href='?switch_view=grid'"><?= translate('grid_view', $language) ?></button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Note-Taking App. All rights reserved.</p>
            <p>Developed by [Your Name]</p>
        </div>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="/main.js"></script>
</body>
</html>