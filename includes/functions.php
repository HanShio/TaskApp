<?php
function validateTask($title, $description, $due, $status) {
    $errors = [];

    if ($title === "") {
        $errors[] = "Title is required.";
    } elseif (strlen($title) < 3) {
        $errors[] = "Title must be at least 3 characters.";
    } elseif (strlen($title) > 150) {
        $errors[] = "Title must not exceed 150 characters.";
    }

    if (strlen($description) > 500) {
        $errors[] = "Description must not exceed 500 characters.";
    }

    if ($due === "") {
        $errors[] = "Due date is required.";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $due);
        if (!$d || $d->format('Y-m-d') !== $due) {
            $errors[] = "Due date is not a valid date.";
        }
    }

    if (!in_array($status, ['Pending', 'In Progress', 'Done'], true)) {
        $errors[] = "Invalid status.";
    }

    return $errors;
}

function getPriorities($conn) {
    $res = $conn->query("SELECT id, name FROM priorities ORDER BY level");
    return $res->fetch_all(MYSQLI_ASSOC);
}

function getCategories($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function validateTaskRefs($conn, $userId, $priorityId, $categoryId) {
    $errors = [];

    $stmt = $conn->prepare("SELECT id FROM priorities WHERE id = ?");
    $stmt->bind_param("i", $priorityId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $errors[] = "Invalid priority.";
    }

    if ($categoryId !== null) {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $categoryId, $userId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $errors[] = "Invalid category.";
        }
    }

    return $errors;
}   

function logActivity($conn, $userId, $taskId, $taskTitle, $action) {
    $stmt = $conn->prepare(
        "INSERT INTO activity_log (user_id, task_id, task_title, action) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iiss", $userId, $taskId, $taskTitle, $action);
    $stmt->execute();
}


function categoryBadge($name, $color) {
    if (!$name) {
        return '<span class="text-muted">-</span>';
    }
    return '<span class="badge" style="background-color: ' . htmlspecialchars($color) . ';">'
         . htmlspecialchars($name) . '</span>';
}