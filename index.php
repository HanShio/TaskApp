<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$pageTitle = "Home";
include 'includes/header.php';
?>

<div class="text-center py-5">
  <h1>Welcome to TaskApp</h1>
  <p class="lead">Organise your tasks in one place.</p>
  <a href="register.php" class="btn btn-primary me-2">Get Started</a>
  <a href="login.php" class="btn btn-outline-primary">Login</a>
</div>

<?php include 'includes/footer.php'; ?>