<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'TaskApp' ?> | TaskApp</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-md navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="index.php">TaskApp</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto">
        <?php if ($loggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard.php">My Tasks</a></li>
          <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
          <li class="nav-item"><a class="nav-link" href="task_add.php">Add Task</a></li>
          <li class="nav-item"><a class="nav-link" href="profile.php">Profile (<?= htmlspecialchars($_SESSION['fullname']) ?>)</a></li>          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">