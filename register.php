<?php
require 'config/db.php';

$errors   = [];
$fullname = "";
$email    = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($fullname === "") {
        $errors[] = "Full name is required.";
    } elseif (strlen($fullname) < 3) {
        $errors[] = "Full name must be at least 3 characters.";
    }

    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain both letters and numbers.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $fullname, $email, $hash);
            $insert->execute();

              $newUserId = $conn->insert_id;
              $defaults  = ['Work' => '#0d6efd', 'Study' => '#198754', 'Personal' => '#fd7e14'];
              $catStmt   = $conn->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
              foreach ($defaults as $catName => $catColor) {
              $catStmt->bind_param("iss", $newUserId, $catName, $catColor);
              $catStmt->execute();
              }

            header("Location: login.php?registered=1");
            exit;
        }
    }
}

$pageTitle = "Register";
include 'includes/header.php';
?>

<div class="form-card">
  <h2>Create Account</h2>

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

  <form name="regForm" method="post" action="register.php" onsubmit="return validateRegister()" novalidate>
    <div class="mb-3">
      <label for="fullname" class="form-label">Full Name</label>
      <input type="text" class="form-control" id="fullname" name="fullname" maxlength="200"
             value="<?= htmlspecialchars($fullname) ?>">
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email" maxlength="200"
             value="<?= htmlspecialchars($email) ?>">
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password">
      <div class="form-text">At least 8 characters, with letters and numbers.</div>
    </div>
    <div class="mb-3">
      <label for="confirm" class="form-label">Confirm Password</label>
      <input type="password" class="form-control" id="confirm" name="confirm">
    </div>
    <button type="submit" class="btn btn-primary w-100">Register</button>
  </form>

  <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php">Login</a></p>
</div>

<script src="js/validate.js"></script>

<?php include 'includes/footer.php'; ?>