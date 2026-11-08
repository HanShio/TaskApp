<?php
session_start();
require 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$email  = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $errors[] = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);         
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}

$pageTitle = "Login";
include 'includes/header.php';
?>

<div class="form-card">
  <h2>Login</h2>

  <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">Registration successful! Please log in.</div>
  <?php endif; ?>
  <?php if (isset($_GET['loggedout'])): ?>
    <div class="alert alert-info">You have been logged out.</div>
  <?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-info">Your account has been deleted.</div>
  <?php endif; ?>

  <div id="clientErrors" class="alert alert-danger d-none"></div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form name="loginForm" method="post" action="login.php" onsubmit="return validateLogin()" novalidate>
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email" maxlength="300"
             value="<?= htmlspecialchars($email) ?>">
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password">
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>

  <p class="text-center mt-3 mb-0">No account yet? <a href="register.php">Register</a></p>
</div>

<script src="js/validate.js"></script>

<?php include 'includes/footer.php'; ?>