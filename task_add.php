<?php
require 'includes/auth.php';
require 'config/db.php';
require 'includes/functions.php';

$userId     = $_SESSION['user_id'];
$priorities = getPriorities($conn);
$categories = getCategories($conn, $userId);

$errors = [];
$task = [
    'title' => '', 'description' => '', 'due_date' => '',
    'status' => 'Pending', 'priority_id' => 2, 'category_id' => ''
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
        $completedAt = ($task['status'] === 'Done') ? date('Y-m-d H:i:s') : null;

        $stmt = $conn->prepare(
            "INSERT INTO tasks (user_id, category_id, priority_id, title, description, due_date, status, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiisssss", $userId, $categoryId, $task['priority_id'],
                          $task['title'], $task['description'], $task['due_date'],
                          $task['status'], $completedAt);
        $stmt->execute();

        $newTaskId = $conn->insert_id;
        logActivity($conn, $userId, $newTaskId, $task['title'], 'created');

        header("Location: dashboard.php?msg=added");
        exit;
    }
}

$pageTitle   = "Add Task";
$formTitle   = "Add New Task";
$formAction  = "task_add.php";
$submitLabel = "Save Task";

include 'includes/header.php';
include 'includes/task_form.php';
include 'includes/footer.php';