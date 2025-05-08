<?php
require_once 'config.php';
require_once 'languages.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

// Load user preferences
$theme = 'light';
$language = 'en';
$stmt = $conn->prepare("SELECT theme, language FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$preferences = $stmt->fetch();
if ($preferences) {
    $theme = $preferences['theme'];
    $language = $preferences['language'];
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
    <title><?= translate('admin_db', $language) ?></title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .note-image {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <a class="navbar-brand" href="/index.php"><?= translate('welcome', $language) ?></a>
                <div class="navbar-nav">
                    <a class="nav-link" href="/profile.php"><?= translate('profile', $language) ?></a>
                    <a class="nav-link" href="/preferences.php"><?= translate('preferences', $language) ?></a>
                    <a class="nav-link" href="/change-password.php"><?= translate('change_password', $language) ?></a>
                    <a class="nav-link" href="/logout.php"><?= translate('logout', $language) ?></a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2><?= translate('admin_db', $language) ?></h2>

            <!-- Display users table -->
            <h3><?= translate('users', $language) ?></h3>
            <?php
            $stmt = $conn->prepare("SELECT id, username, email, is_active, created_at FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll();

            if ($users) {
                echo "<table><tr><th>" . translate('id', $language) . "</th><th>" . translate('username', $language) . "</th><th>" . translate('email', $language) . "</th><th>" . translate('active', $language) . "</th><th>" . translate('created_at', $language) . "</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                    echo "<td>" . ($user['is_active'] ? translate('yes', $language) : translate('no', $language)) . "</td>";
                    echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>" . translate('no_users', $language) . "</p>";
            }
            ?>

            <!-- Display notes table -->
            <h3><?= translate('notes', $language) ?></h3>
            <?php
            $stmt = $conn->prepare("SELECT id, user_id, content, created_at, image FROM notes");
            $stmt->execute();
            $notes = $stmt->fetchAll();

            if ($notes) {
                echo "<table><tr><th>" . translate('id', $language) . "</th><th>" . translate('user_id', $language) . "</th><th>" . translate('content', $language) . "</th><th>Image</th><th>" . translate('created_at', $language) . "</th></tr>";
                foreach ($notes as $note) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($note['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($note['user_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($note['content']) . "</td>";
                    echo "<td>";
                    if ($note['image']) {
                        echo '<img src="uploads/' . htmlspecialchars($note['image']) . '" alt="Note Image" class="note-image">';
                    } else {
                        echo 'N/A';
                    }
                    echo "</td>";
                    echo "<td>" . htmlspecialchars($note['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No notes found.</p>";
            }
            ?>
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
    <script src="/main.js"></script>
</body>
</html>