<?php
// profile.php: user information, task statistics, work history and account deletion
require 'includes/auth.php';
require 'config/db.php';
require 'includes/functions.php';

$userId       = $_SESSION['user_id'];
$deleteErrors = [];

// ---- Load the user's details ----
$stmt = $conn->prepare("SELECT fullname, email, phone, bio, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ---- Delete account ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'delete_account') {
    $password = $_POST['password'] ?? '';

    if ($password === '') {
        $deleteErrors[] = "Enter your password to confirm.";
    }
    if (!isset($_POST['confirm_delete'])) {
        $deleteErrors[] = "Tick the box to confirm you understand.";
    }

    if (empty($deleteErrors)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || !password_verify($password, $row['password'])) {
            $deleteErrors[] = "Incorrect password. Your account was not deleted.";
        } else {
            // ON DELETE CASCADE removes this user's tasks, categories, notes and log
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $_SESSION = [];
            session_destroy();
            header("Location: login.php?deleted=1");
            exit;
        }
    }
}

// ---- Load all of the user's tasks, then split them into current and completed ----
$stmt = $conn->prepare(
    "SELECT t.id, t.title, t.due_date, t.status, t.completed_at,
            p.name AS priority, c.name AS category, c.color
     FROM tasks t
     JOIN priorities p ON p.id = t.priority_id
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = ?
     ORDER BY t.completed_at DESC, t.due_date ASC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$counts    = ['Pending' => 0, 'In Progress' => 0, 'Done' => 0];
$current   = [];
$completed = [];
while ($t = $result->fetch_assoc()) {
    $counts[$t['status']]++;
    if ($t['status'] === 'Done') {
        $completed[] = $t;
    } else {
        $current[] = $t;
    }
}

// ---- Load the activity history (latest 50 actions) ----
$stmt = $conn->prepare(
    "SELECT task_title, action, created_at FROM activity_log
     WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---- Display helpers ----
$badge         = ['Pending' => 'badge-pending', 'In Progress' => 'badge-progress', 'Done' => 'badge-done'];
$priorityBadge = ['High' => 'bg-danger', 'Medium' => 'bg-warning text-dark', 'Low' => 'bg-secondary'];
$actionBadge   = [
    'created'   => ['bg-primary', 'Created'],
    'updated'   => ['bg-info text-dark', 'Updated'],
    'completed' => ['bg-success', 'Completed'],
    'deleted'   => ['bg-danger', 'Deleted']
];
$msg = $_GET['msg'] ?? '';

$pageTitle = "My Profile";
include 'includes/header.php';
?>

<h2 class="mb-3">My Profile</h2>

<?php if ($msg === 'updated'): ?>
  <div class="alert alert-success">Profile updated successfully.</div>
<?php endif; ?>

<!-- ===== 1. User information (full-width card) ===== -->
<div class="table-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">User Information</h5>
    <a href="edit_profile.php" class="btn btn-primary btn-sm">Edit Profile</a>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="text-muted small">Name</div>
      <div><?= htmlspecialchars($user['fullname']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="text-muted small">Email</div>
      <div><?= htmlspecialchars($user['email']) ?></div>
    </div>
    <div class="col-md-6">
      <div class="text-muted small">Phone</div>
      <div><?= $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-muted">Not set</span>' ?></div>
    </div>
    <div class="col-md-6">
      <div class="text-muted small">Member since</div>
      <div><?= date('d M Y', strtotime($user['created_at'])) ?></div>
    </div>
    <div class="col-12">
      <div class="text-muted small">Bio</div>
      <div><?= $user['bio'] ? htmlspecialchars($user['bio']) : '<span class="text-muted">Not set</span>' ?></div>
    </div>
  </div>
</div>

<!-- ===== 2. Three stat cards ===== -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card stat-completed">
      <div class="stat-number"><?= $counts['Done'] ?></div>
      <div>Completed</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card stat-pending">
      <div class="stat-number"><?= $counts['Pending'] ?></div>
      <div>Pending</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card stat-progress">
      <div class="stat-number"><?= $counts['In Progress'] ?></div>
      <div>In Progress</div>
    </div>
  </div>
</div>

<!-- ===== 3. All work: current, completed and history ===== -->
<div class="table-card mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="mb-0">My Work</h5>
    <input type="search" id="workSearch" class="form-control" style="max-width: 320px;"
           placeholder="Search all my tasks..." aria-label="Search my work" oninput="filterWork()">
  </div>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-current"
              data-label="Current" type="button">Current (<?= count($current) ?>)</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-done"
              data-label="Completed" type="button">Completed (<?= count($completed) ?>)</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity"
              data-label="Activity History" type="button">Activity History (<?= count($activity) ?>)</button>
    </li>
  </ul>

  <div class="tab-content" id="workTabContent">

    <!-- Current tasks -->
    <div class="tab-pane fade show active" id="tab-current">
      <?php if (empty($current)): ?>
        <p class="text-muted mb-0">No current tasks.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Due Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($current as $t): ?>
                <tr>
                  <td><a href="task_view.php?id=<?= $t['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($t['title']) ?></a></td>
                  <td><?= categoryBadge($t['category'], $t['color']) ?></td>
                  <td><span class="badge <?= $priorityBadge[$t['priority']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($t['priority']) ?></span></td>
                  <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '-' ?></td>
                  <td><span class="badge <?= $badge[$t['status']] ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="no-match text-muted text-center my-3 d-none">No tasks match your search.</p>
      <?php endif; ?>
    </div>

    <!-- Completed tasks (past work) -->
    <div class="tab-pane fade" id="tab-done">
      <?php if (empty($completed)): ?>
        <p class="text-muted mb-0">No completed tasks yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Completed On</th></tr></thead>
            <tbody>
              <?php foreach ($completed as $t): ?>
                <tr>
                  <td><a href="task_view.php?id=<?= $t['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($t['title']) ?></a></td>
                  <td><?= categoryBadge($t['category'], $t['color']) ?></td>
                  <td><span class="badge <?= $priorityBadge[$t['priority']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($t['priority']) ?></span></td>
                  <td><?= $t['completed_at'] ? date('d M Y, H:i', strtotime($t['completed_at'])) : '-' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="no-match text-muted text-center my-3 d-none">No tasks match your search.</p>
      <?php endif; ?>
    </div>

    <!-- Activity history -->
    <div class="tab-pane fade" id="tab-activity">
      <?php if (empty($activity)): ?>
        <p class="text-muted mb-0">No activity recorded yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead><tr><th>Date and Time</th><th>Action</th><th>Task</th></tr></thead>
            <tbody>
              <?php foreach ($activity as $a): ?>
                <?php $style = $actionBadge[$a['action']] ?? ['bg-secondary', ucfirst($a['action'])]; ?>
                <tr>
                  <td><?= date('d M Y, H:i', strtotime($a['created_at'])) ?></td>
                  <td><span class="badge <?= $style[0] ?>"><?= htmlspecialchars($style[1]) ?></span></td>
                  <td><?= htmlspecialchars($a['task_title'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="no-match text-muted text-center my-3 d-none">No activity matches your search.</p>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ===== 4. Delete account (bottom of the page) ===== -->
<div class="card border-danger mb-4">
  <div class="card-header bg-danger text-white">Delete Account</div>
  <div class="card-body">
    <p>
      Deleting your account is <strong>permanent</strong>. All your tasks, categories, notes
      and activity history will be removed and cannot be recovered.
    </p>

    <div id="deleteClientErrors" class="alert alert-danger d-none"></div>
    <?php if (!empty($deleteErrors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($deleteErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form name="deleteForm" method="post" action="profile.php" onsubmit="return validateDelete()" novalidate>
      <input type="hidden" name="action" value="delete_account">
      <div class="mb-3" style="max-width: 360px;">
        <label for="del_password" class="form-label">Enter your password to confirm</label>
        <input type="password" class="form-control" id="del_password" name="password">
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete">
        <label class="form-check-label" for="confirm_delete">
          I understand that this will permanently delete my account and all my data.
        </label>
      </div>
      <button type="submit" class="btn btn-danger">Delete My Account</button>
    </form>
  </div>
</div>

<script src="js/validate.js"></script>
<script src="js/search.js"></script>

<?php include 'includes/footer.php'; ?>