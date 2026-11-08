<?php
// task_delete.php: deletes one task (POST only) and records it in the activity log
require 'includes/auth.php';
require 'config/db.php';
require 'includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION['user_id'];
    $id     = (int) ($_POST['id'] ?? 0);

    $stmt = $conn->prepare("SELECT title FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        logActivity($conn, $userId, $id, $row['title'], 'deleted');

        $del = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $del->bind_param("ii", $id, $userId);
        $del->execute();
    }

    header("Location: dashboard.php?msg=deleted");
    exit;
}

header("Location: dashboard.php");
exit;