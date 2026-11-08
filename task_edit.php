<?php
require 'includes/auth.php';
require 'config/db.php';
require 'includes/functions.php';

$userId     = $_SESSION['user_id'];
$id         = (int) ($_GET['id'] ?? 0);
$priorities = getPriorities($conn);
$categories = getCategories($conn, $userId);

$stmt = $conn->prepare(
    "SELECT id, title, description, due_date, status, priority_id, category_id
     FROM tasks WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$task = [
    'title'       => $row['title'],
    'description' => $row['description'] ?? '',
    'due_date'    => $row['due_date'] ?? '',
    'status'      => $row['status'],
    'priority_id' => $row['priority_id'],
    'category_id' => $row['category_id'] ?? ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $task['title']       = trim($_POST['title'] ?? '');
    $task['description'] = trim($_POST['description'] ?? '');
    $task['due_date']    = $_POST['due_date'] ?? '';
    $task['status']      = $_POST['status'] ?? '';
    $task['priority_id'] = (int) ($_POST['priority_id'] ?? 0);
    $task['category_id'] = $_POST['category_id'] ?? '';
    $categoryId = ($task['category_id'] === '') ? null : (int) $task['category_id'];

    $errors = validateTask($task['title'], $task['description'], $task['due_date'], $task['status']);
    $errors = array_merge($errors, validateTaskRefs($conn, $userId, $task['priority_id'], $categoryId));

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "UPDATE tasks
             SET category_id = ?, priority_id = ?, title = ?, description = ?, due_date = ?, status = ?,
                 completed_at = CASE WHEN ? = 'Done' THEN COALESCE(completed_at, NOW()) ELSE NULL END
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("iisssssii", $categoryId, $task['priority_id'], $task['title'],
                          $task['description'], $task['due_date'], $task['status'],
                          $task['status'], $id, $userId);
        $stmt->execute();
        
        $action = ($task['status'] === 'Done' && $row['status'] !== 'Done') ? 'completed' : 'updated';
        logActivity($conn, $userId, $id, $task['title'], $action);

        header("Location: dashboard.php?msg=updated");
        exit;
    }
}

$pageTitle   = "Edit Task";
$formTitle   = "Edit Task";
$formAction  = "task_edit.php?id=" . $id;
$submitLabel = "Update Task";

include 'includes/header.php';
include 'includes/task_form.php';
include 'includes/footer.php';