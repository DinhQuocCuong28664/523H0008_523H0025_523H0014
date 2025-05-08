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

// Fetch user data
$stmt = $conn->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'CSRF token invalid!';
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        // Validate inputs
        if (empty($username) || empty($email)) {
            $message = 'Username and email are required!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format!';
        } else {
            // Check if username or email already exists (excluding current user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                $message = 'Username or email already exists!';
            } else {
                // Handle avatar upload
                $avatar_path = $user['avatar'];
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $avatar_name = $user_id . '_' . time() . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $avatar_path = $upload_dir . $avatar_name;

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                        // Delete old avatar if exists
                        if ($user['avatar'] && file_exists($user['avatar'])) {
                            unlink($user['avatar']);
                        }
                    } else {
                        $message = 'Failed to upload avatar!';
                        $avatar_path = $user['avatar'];
                    }
                }

                // Update profile
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$username, $email, $avatar_path, $user_id]);
                $message = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                $_SESSION['username'] = $user['username'];
            }
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
    <title>User Profile</title>
</head>
<body>
    <div class="container">
        <h3 class="mt-3 mb-3">User Profile</h3>
        <p><a href="/index.php"><?= translate('back_to_notes', $language) ?></a></p>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <h4>Profile Picture</h4>
                <img src="<?= $user['avatar'] ? htmlspecialchars($user['avatar']) : '/images/default-avatar.png' ?>" alt="Avatar" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px;">
            </div>
            <div class="col-md-8">
                <h4>Profile Details</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="avatar">Upload New Avatar:</label>
                        <input type="file" class="form-control-file" id="avatar" name="avatar" accept="image/*">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>