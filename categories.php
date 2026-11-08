<?php
require 'includes/auth.php';
require 'config/db.php';

$userId = $_SESSION['user_id'];
$errors = [];
$name   = "";
$color  = "#0d6efd";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '';

        if ($name === "") {
            $errors[] = "Category name is required.";
        } elseif (strlen($name) > 50) {
            $errors[] = "Category name must not exceed 50 characters.";
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors[] = "Invalid colour.";
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
            $stmt->bind_param("is", $userId, $name);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "You already have a category with this name.";
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $userId, $name, $color);
                $stmt->execute();
                header("Location: categories.php?msg=added");
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        header("Location: categories.php?msg=deleted");
        exit;
    }
}

$stmt = $conn->prepare(
    "SELECT c.id, c.name, c.color, COUNT(t.id) AS task_count
     FROM categories c
     LEFT JOIN tasks t ON t.category_id = c.id
     WHERE c.user_id = ?
     GROUP BY c.id, c.name, c.color
     ORDER BY c.name"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$categories = $stmt->get_result();

$messages = ['added' => 'Category added.', 'deleted' => 'Category deleted.'];
$msg = $_GET['msg'] ?? '';

$pageTitle = "Categories";
include 'includes/header.php';
?>

<h2 class="mb-3">My Categories</h2>

<?php if (isset($messages[$msg])): ?>
  <div class="alert alert-success"><?= $messages[$msg] ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Add form -->
  <div class="col-md-5">
    <div class="table-card">
      <h5>Add Category</h5>

      <div id="clientErrors" class="alert alert-danger d-none"></div>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form name="catForm" method="post" action="categories.php" onsubmit="return validateCategory()" novalidate>
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input type="text" class="form-control" id="name" name="name" maxlength="50"
                 value="<?= htmlspecialchars($name) ?>">
        </div>
        <div class="mb-3">
          <label for="color" class="form-label">Colour</label>
          <input type="color" class="form-control form-control-color" id="color" name="color"
                 value="<?= htmlspecialchars($color) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Add Category</button>
      </form>
    </div>
  </div>

  <div class="col-md-7">
    <div class="table-card">
      <?php if ($categories->num_rows === 0): ?>
        <p class="text-center text-muted my-3">No categories yet.</p>
      <?php else: ?>
        <table class="table align-middle mb-0">
          <thead><tr><th>Category</th><th>Tasks</th><th>Action</th></tr></thead>
          <tbody>
            <?php while ($c = $categories->fetch_assoc()): ?>
              <tr>
                <td>
                  <span class="badge" style="background-color: <?= htmlspecialchars($c['color']) ?>;">
                    <?= htmlspecialchars($c['name']) ?>
                  </span>
                </td>
                <td><?= (int) $c['task_count'] ?></td>
                <td>
                  <form method="post" action="categories.php" class="d-inline"
                        onsubmit="return confirm('Delete this category? Its tasks will be kept but become uncategorised.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="js/validate.js"></script>

<?php include 'includes/footer.php'; ?>