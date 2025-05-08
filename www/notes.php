<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token invalid!');
    }

    // Add a new note
    if (isset($_POST['add'], $_POST['note_content'])) {
        $content = trim($_POST['note_content']);
        if ($content) {
            $stmt = $conn->prepare("INSERT INTO notes (user_id, content) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $content]);
        }
    }

    // Delete a note
    elseif (isset($_POST['delete'], $_POST['note_id'])) {
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['note_id'], $_SESSION['user_id']]);
    }

    // Edit a note
    elseif (isset($_POST['edit'], $_POST['note_id'], $_POST['note_content'])) {
        $note_id = $_POST['note_id'];
        $content = trim($_POST['note_content']);
        if ($content) {
            $stmt = $conn->prepare("UPDATE notes SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$content, $note_id, $_SESSION['user_id']]);
        }
    }
}

header('Location: /index.php');
exit;
?>