<?php
// edit_profile.php: form to update the user's name, phone and bio
require 'includes/auth.php';
require 'config/db.php';

$userId = $_SESSION['user_id'];
$errors = [];

// ---- Load the current details ----
$stmt = $conn->prepare("SELECT fullname, email, phone, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Values shown in the form (start with the saved details)
$form = [
    'fullname' => $user['fullname'],
    'phone'    => $user['phone'] ?? '',
    'bio'      => $user['bio'] ?? ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form['fullname'] = trim($_POST['fullname'] ?? '');
    $form['phone']    = trim($_POST['phone'] ?? '');
    $form['bio']      = trim($_POST['bio'] ?? '');

    // ---- Server-side validation ----
    if ($form['fullname'] === "") {
        $errors[] = "Full name is required.";
    } elseif (strlen($form['fullname']) < 3) {
        $errors[] = "Full name must be at least 3 characters.";
    } elseif (strlen($form['fullname']) > 100) {
        $errors[] = "Full name must not exceed 100 characters.";
    }
    if ($form['phone'] !== "" && !preg_match('/^[0-9+\-\s]{7,20}$/', $form['phone'])) {
        $errors[] = "Phone number must be 7 to 20 characters (digits, +, - and spaces only).";
    }
    if (strlen($form['bio']) > 255) {
        $errors[] = "Bio must not exceed 255 characters.";
    }

    if (empty($errors)) {
        // Empty phone/bio are stored as NULL
        $phone = ($form['phone'] === '') ? null : $form['phone'];
        $bio   = ($form['bio'] === '')   ? null : $form['bio'];

        $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("sssi", $form['fullname'], $phone, $bio, $userId);
        $stmt->execute();

        $_SESSION['fullname'] = $form['fullname'];   // keep the navbar name up to date
        header("Location: profile.php?msg=updated");
        exit;
    }
}

$pageTitle = "Edit Profile";
include 'includes/header.php';
?>

<div class="form-card" style="max-width: 600px;">
  <h2>Edit Profile</h2>

  <div id="profileClientErrors" class="alert alert-danger d-none"></div>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form name="profileForm" method="post" action="edit_profile.php" onsubmit="return validateProfile()" novalidate>
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
      <div class="form-text">Email cannot be changed.</div>
    </div>
    <div class="mb-3">
      <label for="fullname" class="form-label">Full Name</label>
      <input type="text" class="form-control" id="fullname" name="fullname" maxlength="100"
             value="<?= htmlspecialchars($form['fullname']) ?>">
    </div>
    <div class="mb-3">
      <label for="phone" class="form-label">Phone (optional)</label>
      <input type="text" class="form-control" id="phone" name="phone" maxlength="20"
             value="<?= htmlspecialchars($form['phone']) ?>">
    </div>
    <div class="mb-3">
      <label for="bio" class="form-label">Bio (optional)</label>
      <textarea class="form-control" id="bio" name="bio" rows="3" maxlength="255"><?= htmlspecialchars($form['bio']) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>

<script src="js/validate.js"></script>

<?php include 'includes/footer.php'; ?>