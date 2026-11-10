<?php
// dashboard.php: lists the logged-in user's tasks, with a search bar
require 'includes/auth.php';
require 'config/db.php';

$userId = $_SESSION['user_id'];

// ---- Search keyword from the search bar (sent with GET) ----
$q = trim($_GET['q'] ?? '');
$q = substr($q, 0, 100);                 // limit the length

$sql = "SELECT t.id, t.title, t.description, t.due_date, t.status,
               p.name AS priority, c.name AS category, c.color
        FROM tasks t
        JOIN priorities p ON p.id = t.priority_id
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = ?";

if ($q !== '') {
    // Search in title, description, category, priority and status
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR c.name LIKE ?
                   OR p.name LIKE ? OR t.status LIKE ?)";
}
$sql .= " ORDER BY t.due_date ASC, t.id DESC";

$stmt = $conn->prepare($sql);
if ($q !== '') {
    // Escape % and _ so they are searched as normal characters
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $stmt->bind_param("isssss", $userId, $like, $like, $like, $like, $like);
} else {
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$tasks = $stmt->get_result();

// Feedback messages (only these fixed texts can ever be shown)
$messages = [
    'added'   => 'Task added successfully.',
    'updated' => 'Task updated successfully.',
    'deleted' => 'Task deleted.'
];
$msg = $_GET['msg'] ?? '';

$badge = [
    'Pending'     => 'badge-pending',
    'In Progress' => 'badge-progress',
    'Done'        => 'badge-done'
];
$priorityBadge = [
    'High'   => 'bg-danger',
    'Medium' => 'bg-warning text-dark',
    'Low'    => 'bg-secondary'
];

$pageTitle = "My Tasks";
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">My Tasks</h2>
  <a href="task_add.php" class="btn btn-primary">+ Add Task</a>
</div>

<?php if (isset($messages[$msg])): ?>
  <div class="alert alert-success"><?= $messages[$msg] ?></div>
<?php endif; ?>

<div class="table-card">

  <!-- Search bar -->
  <form method="get" action="dashboard.php" class="mb-3">
    <div class="input-group">
      <input type="search" class="form-control" name="q" maxlength="100"
             placeholder="Search by title, description, category, priority or status..."
             value="<?= htmlspecialchars($q) ?>">
      <button class="btn btn-primary" type="submit">Search</button>
      <?php if ($q !== ''): ?>
        <a href="dashboard.php" class="btn btn-outline-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($q !== ''): ?>
    <p class="text-muted small">
      <?= $tasks->num_rows ?> task(s) found for “<?= htmlspecialchars($q) ?>”.
    </p>
  <?php endif; ?>

  <?php if ($tasks->num_rows === 0): ?>
    <p class="text-center text-muted my-4">
      <?= $q !== '' ? 'No tasks match your search.' : 'No tasks yet. Click “Add Task” to create your first one.' ?>
    </p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>#</th><th>Title</th><th>Category</th><th>Priority</th>
            <th>Due Date</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($t = $tasks->fetch_assoc()): ?>
            <tr class="<?= ($t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'Done') ? 'table-danger' : '' ?>">
              <td><?= $no++ ?></td>
              <td><a href="task_view.php?id=<?= $t['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($t['title']) ?></a></td>
              <td>
                <?php if ($t['category']): ?>
                  <span class="badge" style="background-color: <?= htmlspecialchars($t['color']) ?>;">
                    <?= htmlspecialchars($t['category']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><span class="badge <?= $priorityBadge[$t['priority']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($t['priority']) ?></span></td>
              <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '-' ?></td>
              <td><span class="badge <?= $badge[$t['status']] ?>"><?= htmlspecialchars($t['status']) ?></span></td>
              <td>
                <a href="task_edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="post" action="task_delete.php" class="d-inline"
                      onsubmit="return confirm('Delete this task?')">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>