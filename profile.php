<?php
require 'includes/auth.php';
require 'config/db.php';
require 'includes/functions.php';

$userId        = $_SESSION['user_id'];
$profileErrors = [];
$deleteErrors  = [];

$stmt = $conn->prepare("SELECT fullname, email, phone, bio, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$form = [
    'fullname' => $user['fullname'],
    'phone'    => $user['phone'] ?? '',
    'bio'      => $user['bio'] ?? ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $form['fullname'] = trim($_POST['fullname'] ?? '');
        $form['phone']    = trim($_POST['phone'] ?? '');
        $form['bio']      = trim($_POST['bio'] ?? '');

        if ($form['fullname'] === "") {
            $profileErrors[] = "Full name is required.";
        } elseif (strlen($form['fullname']) < 3) {
            $profileErrors[] = "Full name must be at least 3 characters.";
        } elseif (strlen($form['fullname']) > 100) {
            $profileErrors[] = "Full name must not exceed 100 characters.";
        }
        if ($form['phone'] !== "" && !preg_match('/^[0-9+\-\s]{7,20}$/', $form['phone'])) {
            $profileErrors[] = "Phone number must be 7 to 20 characters (digits, +, - and spaces only).";
        }
        if (strlen($form['bio']) > 255) {
            $profileErrors[] = "Bio must not exceed 255 characters.";
        }

        if (empty($profileErrors)) {
            $phone = ($form['phone'] === '') ? null : $form['phone'];
            $bio   = ($form['bio'] === '')   ? null : $form['bio'];

            $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, bio = ? WHERE id = ?");
            $stmt->bind_param("sssi", $form['fullname'], $phone, $bio, $userId);
            $stmt->execute();

            $_SESSION['fullname'] = $form['fullname'];   
            header("Location: profile.php?msg=updated");
            exit;
        }
    }

    if ($action === 'delete_account') {
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
}

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

$stmt = $conn->prepare(
    "SELECT task_title, action, created_at FROM activity_log
     WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

<div class="row g-4 mb-4">
  <div class="col-md-5">
    <div class="table-card h-100">
      <h5>User Information</h5>
      <ul class="list-unstyled mb-0">
        <li class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($user['fullname']) ?></li>
        <li class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></li>
        <li class="mb-2"><strong>Phone:</strong> <?= $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-muted">Not set</span>' ?></li>
        <li class="mb-2"><strong>Bio:</strong> <?= $user['bio'] ? htmlspecialchars($user['bio']) : '<span class="text-muted">Not set</span>' ?></li>
        <li><strong>Member since:</strong> <?= date('d M Y', strtotime($user['created_at'])) ?></li>
      </ul>
    </div>
  </div>

  <div class="col-md-7">
    <div class="table-card h-100">
      <h5>Edit Profile</h5>

      <div id="profileClientErrors" class="alert alert-danger d-none"></div>
      <?php if (!empty($profileErrors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($profileErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form name="profileForm" method="post" action="profile.php" onsubmit="return validateProfile()" novalidate>
        <input type="hidden" name="action" value="update_profile">
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
          <textarea class="form-control" id="bio" name="bio" rows="2" maxlength="255"><?= htmlspecialchars($form['bio']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div>

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

<div class="table-card mb-4">
  <h5>My Work</h5>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-current" type="button">
        Current (<?= count($current) ?>)
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-done" type="button">
        Completed (<?= count($completed) ?>)
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button">
        Activity History (<?= count($activity) ?>)
      </button>
    </li>
  </ul>

  <div class="tab-content">

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
      <?php endif; ?>
    </div>

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
      <?php endif; ?>
    </div>

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
      <?php endif; ?>
    </div>

  </div>
</div>

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

<?php include 'includes/footer.php'; ?>