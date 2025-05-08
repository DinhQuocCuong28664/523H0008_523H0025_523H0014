<?php
require_once 'config.php';
require_once 'languages.php';

// Load user preferences if logged in
$theme = 'light';
$language = 'en';
$font_size = '16px';
$note_color = '#ffffff';
$viewMode = isset($_SESSION['view_mode']) ? $_SESSION['view_mode'] : 'list'; // Default to list view
$is_active = isset($_SESSION['is_active']) ? $_SESSION['is_active'] : 1; // Default to active if not set

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT theme, language, font_size, note_color FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $preferences = $stmt->fetch();
    if ($preferences) {
        $theme = $preferences['theme'];
        $language = $preferences['language'];
        $font_size = $preferences['font_size'];
        $note_color = $preferences['note_color'];
    }

    // Fetch user activation status
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $is_active = $user['is_active'];
        $_SESSION['is_active'] = $is_active;
    }
}

// Handle view mode switch
if (isset($_GET['switch_view']) && in_array($_GET['switch_view'], ['list', 'grid'])) {
    $_SESSION['view_mode'] = $_GET['switch_view'];
    $viewMode = $_SESSION['view_mode'];
}

// Handle note actions (add/edit/delete with image)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add']) && isset($_FILES['note_image'])) {
        $content = trim($_POST['note_content']);
        $image = $_FILES['note_image'];

        if (!empty($content) && $image['error'] === UPLOAD_ERR_OK) {
            $imageName = uniqid() . '_' . basename($image['name']);
            $targetDir = 'uploads/';
            $targetFile = $targetDir . $imageName;

            // Kiểm tra định dạng file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($image['type'], $allowedTypes) && $image['size'] < 5000000) { // Giới hạn 5MB
                if (move_uploaded_file($image['tmp_name'], $targetFile)) {
                    $stmt = $conn->prepare("INSERT INTO notes (user_id, content, image) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $content, $imageName]);
                }
            }
        }
    } elseif (isset($_POST['edit']) && isset($_POST['note_id']) && isset($_FILES['note_image'])) {
        $noteId = $_POST['note_id'];
        $content = trim($_POST['note_content']);
        $image = $_FILES['note_image'];

        $stmt = $conn->prepare("SELECT image FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$noteId, $_SESSION['user_id']]);
        $existingNote = $stmt->fetch();

        if (!empty($content)) {
            if ($image['error'] === UPLOAD_ERR_OK) {
                $imageName = uniqid() . '_' . basename($image['name']);
                $targetDir = 'uploads/';
                $targetFile = $targetDir . $imageName;

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($image['type'], $allowedTypes) && $image['size'] < 5000000) {
                    if (move_uploaded_file($image['tmp_name'], $targetFile)) {
                        // Xóa ảnh cũ nếu có
                        if ($existingNote['image'] && file_exists($targetDir . $existingNote['image'])) {
                            unlink($targetDir . $existingNote['image']);
                        }
                        $stmt = $conn->prepare("UPDATE notes SET content = ?, image = ? WHERE id = ? AND user_id = ?");
                        $stmt->execute([$content, $imageName, $noteId, $_SESSION['user_id']]);
                    }
                }
            } else {
                $stmt = $conn->prepare("UPDATE notes SET content = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $noteId, $_SESSION['user_id']]);
            }
        }
    } elseif (isset($_POST['delete']) && isset($_POST['note_id'])) {
        $noteId = $_POST['note_id'];
        $stmt = $conn->prepare("SELECT image FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$noteId, $_SESSION['user_id']]);
        $note = $stmt->fetch();

        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$noteId, $_SESSION['user_id']]);

        if ($note['image'] && file_exists('uploads/' . $note['image'])) {
            unlink('uploads/' . $note['image']);
        }
    }
    header('Location: /index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language) ?>" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/style.css">
    <title>Note-Taking App</title>
    <style>
        .note-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 10px;
        }
        .note-item-grid {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background-color: <?= htmlspecialchars($note_color) ?>;
            font-size: <?= htmlspecialchars($font_size) ?>;
        }
        .note-content {
            word-wrap: break-word;
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
        .main-content {
            margin-top: 60px;
            margin-bottom: 60px;
            padding: 20px;
        }
        .note-image {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
        }
        .notes-list .note-item {
            background-color: <?= htmlspecialchars($note_color) ?>;
            font-size: <?= htmlspecialchars($font_size) ?>;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
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
            <h3 class="mt-3 mb-3"><?= translate('welcome', $language) ?></h3>
            <?php
            if (isset($_SESSION['user_id'])) {
                // Display registration success message if exists
                if (isset($_SESSION['message'])) {
                    echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['message']) . '</div>';
                    unset($_SESSION['message']);
                }

                // Display activation notice if account is not activated
                if (!$is_active) {
                    echo '<div class="alert alert-warning">' . translate('account_not_activated', $language) . '</div>';
                }

                echo '<p>' . sprintf(translate('welcome_user', $language), htmlspecialchars($_SESSION['username'])) . '</p>';

                // View mode switch buttons
                echo '<div class="mb-3">';
                echo '<button class="btn btn-sm btn-secondary ' . ($viewMode === 'list' ? 'active' : '') . '" onclick="window.location.href=\'?switch_view=list\'">' . translate('list_view', $language) . '</button>';
                echo '<button class="btn btn-sm btn-secondary ml-2 ' . ($viewMode === 'grid' ? 'active' : '') . '" onclick="window.location.href=\'?switch_view=grid\'">' . translate('grid_view', $language) . '</button>';
                echo '</div>';

                // Form to add a new note
                echo '<form id="note-form" class="mb-3" method="POST" enctype="multipart/form-data">';
                echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
                echo '<div class="form-group">';
                echo '<textarea class="form-control" name="note_content" rows="4" placeholder="' . translate('write_note', $language) . '" required></textarea>';
                echo '</div>';
                echo '<div class="form-group">';
                echo '<label for="note_image">' . translate('attach_image', $language) . '</label>';
                echo '<input type="file" class="form-control-file" id="note_image" name="note_image" accept="image/jpeg,image/png,image/gif">';
                echo '</div>';
                echo '<button type="submit" name="add" class="btn btn-primary">' . translate('save_note', $language) . '</button>';
                echo '</form>';

                // Display user's notes
                $stmt = $conn->prepare("SELECT id, content, created_at, image FROM notes WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$_SESSION['user_id']]);
                $notes = $stmt->fetchAll();

                if ($notes) {
                    echo '<h4>' . translate('your_notes', $language) . ':</h4>';
                    if ($viewMode === 'list') {
                        echo '<div class="notes-list">';
                        foreach ($notes as $note) {
                            $note_id = $note['id'];
                            echo '<div class="note-item card mb-3">';
                            echo '<div class="card-body">';
                            echo '<p class="note-content">' . htmlspecialchars($note['content']) . '</p>';
                            if ($note['image']) {
                                echo '<img src="uploads/' . htmlspecialchars($note['image']) . '" alt="Note Image" class="note-image">';
                            }
                            echo '<small class="text-muted">' . translate('created', $language) . ': ' . $note['created_at'] . '</small>';
                            echo '<div class="note-actions mt-2">';
                            echo '<button class="btn btn-sm btn-warning edit-note-btn" data-id="' . $note_id . '" data-content="' . htmlspecialchars($note['content'], ENT_QUOTES) . '" data-image="' . htmlspecialchars($note['image'] ?? '', ENT_QUOTES) . '">' . translate('edit', $language) . '</button>';
                            echo '<form class="d-inline ml-2" method="POST" action="/index.php" onsubmit="return confirm(\'Delete this note?\');">';
                            echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
                            echo '<input type="hidden" name="note_id" value="' . $note_id . '">';
                            echo '<button type="submit" name="delete" class="btn btn-sm btn-danger">' . translate('delete', $language) . '</button>';
                            echo '</form>';
                            echo '</div>';

                            // Hidden edit form (revealed when clicking Edit)
                            echo '<form class="edit-note-form mt-3" id="edit-form-' . $note_id . '" method="POST" action="/index.php" enctype="multipart/form-data" style="display: none;">';
                            echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
                            echo '<input type="hidden" name="note_id" value="' . $note_id . '">';
                            echo '<div class="form-group">';
                            echo '<textarea class="form-control" name="note_content" rows="3" required>' . htmlspecialchars($note['content']) . '</textarea>';
                            echo '</div>';
                            echo '<div class="form-group">';
                            echo '<label for="note_image">' . translate('attach_image', $language) . '</label>';
                            echo '<input type="file" class="form-control-file" id="note_image" name="note_image" accept="image/jpeg,image/png,image/gif">';
                            if ($note['image']) {
                                echo '<p><img src="uploads/' . htmlspecialchars($note['image']) . '" alt="Current Image" class="note-image" style="max-width: 200px;"></p>';
                            }
                            echo '</div>';
                            echo '<button type="submit" name="edit" class="btn btn-success btn-sm">' . translate('update_note', $language) . '</button>';
                            echo '<button type="button" class="btn btn-secondary btn-sm cancel-edit" data-id="' . $note_id . '">' . translate('cancel', $language) . '</button>';
                            echo '</form>';

                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else { // Grid view
                        echo '<div class="note-grid">';
                        foreach ($notes as $note) {
                            $note_id = $note['id'];
                            echo '<div class="note-item-grid">';
                            echo '<p class="note-content">' . htmlspecialchars($note['content']) . '</p>';
                            if ($note['image']) {
                                echo '<img src="uploads/' . htmlspecialchars($note['image']) . '" alt="Note Image" class="note-image">';
                            }
                            echo '<small class="text-muted">' . translate('created', $language) . ': ' . $note['created_at'] . '</small>';
                            echo '<div class="note-actions mt-2">';
                            echo '<button class="btn btn-sm btn-warning edit-note-btn" data-id="' . $note_id . '" data-content="' . htmlspecialchars($note['content'], ENT_QUOTES) . '" data-image="' . htmlspecialchars($note['image'] ?? '', ENT_QUOTES) . '">' . translate('edit', $language) . '</button>';
                            echo '<form class="d-inline ml-2" method="POST" action="/index.php" onsubmit="return confirm(\'Delete this note?\');">';
                            echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
                            echo '<input type="hidden" name="note_id" value="' . $note_id . '">';
                            echo '<button type="submit" name="delete" class="btn btn-sm btn-danger">' . translate('delete', $language) . '</button>';
                            echo '</form>';
                            echo '</div>';
                            echo '<form class="edit-note-form mt-3" id="edit-form-' . $note_id . '" method="POST" action="/index.php" enctype="multipart/form-data" style="display: none;">';
                            echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
                            echo '<input type="hidden" name="note_id" value="' . $note_id . '">';
                            echo '<div class="form-group">';
                            echo '<textarea class="form-control" name="note_content" rows="3" required>' . htmlspecialchars($note['content']) . '</textarea>';
                            echo '</div>';
                            echo '<div class="form-group">';
                            echo '<label for="note_image">' . translate('attach_image', $language) . '</label>';
                            echo '<input type="file" class="form-control-file" id="note_image" name="note_image" accept="image/jpeg,image/png,image/gif">';
                            if ($note['image']) {
                                echo '<p><img src="uploads/' . htmlspecialchars($note['image']) . '" alt="Current Image" class="note-image" style="max-width: 200px;"></p>';
                            }
                            echo '</div>';
                            echo '<button type="submit" name="edit" class="btn btn-success btn-sm">' . translate('update_note', $language) . '</button>';
                            echo '<button type="button" class="btn btn-secondary btn-sm cancel-edit" data-id="' . $note_id . '">' . translate('cancel', $language) . '</button>';
                            echo '</form>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<p>' . translate('no_notes', $language) . '</p>';
                }
            } else {
                echo '<p>' . translate('login_register', $language) . '</p>';
                echo '<div><img src="/images/tdt-logo.png" /><p>' . translate('sample_image', $language) . '</p></div>';
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-note-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const form = document.getElementById('edit-form-' + this.dataset.id);
                    form.style.display = 'block';
                    document.querySelector('.note-item[data-id="' + this.dataset.id + '"]').style.display = 'none';
                });
            });

            document.querySelectorAll('.cancel-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const form = document.getElementById('edit-form-' + this.dataset.id);
                    form.style.display = 'none';
                    document.querySelector('.note-item[data-id="' + this.dataset.id + '"]').style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>