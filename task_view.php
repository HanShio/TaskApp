<?php
require 'includes/auth.php';
require 'config/db.php';

$userId = $_SESSION['user_id'];
$id     = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT t.id, t.title, t.description, t.due_date, t.status, t.created_at, t.completed_at,
            p.name AS priority, c.name AS category, c.color
     FROM tasks t
     JOIN priorities p ON p.id = t.priority_id
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.id = ? AND t.user_id = ?"
);
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$note   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');

        if ($note === "") {
            $errors[] = "Note cannot be empty.";
        } elseif (strlen($note) > 500) {
            $errors[] = "Note must not exceed 500 characters.";
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO task_notes (task_id, user_id, note) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id, $userId, $note);
            $stmt->execute();
            header("Location: task_view.php?id=$id&msg=noted");
            exit;
        }
    } elseif ($action === 'delete_note') {
        $noteId = (int) ($_POST['note_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM task_notes WHERE id = ? AND task_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $noteId, $id, $userId);
        $stmt->execute();
        header("Location: task_view.php?id=$id&msg=note_deleted");
        exit;
    }
}

$stmt = $conn->prepare(
    "SELECT id, note, created_at FROM task_notes
     WHERE task_id = ? AND user_id = ? ORDER BY created_at DESC, id DESC"
);
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$notes = $stmt->get_result();

$messages = ['noted' => 'Note added.', 'note_deleted' => 'Note deleted.'];
$msg = $_GET['msg'] ?? '';

$badge = ['Pending' => 'badge-pending', 'In Progress' => 'badge-progress', 'Done' => 'badge-done'];
$priorityBadge = ['High' => 'bg-danger', 'Medium' => 'bg-warning text-dark', 'Low' => 'bg-secondary'];

$pageTitle = "Task Details";
include 'includes/header.php';
?>

<div class="mb-3">
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to tasks</a>
</div>

<?php if (isset($messages[$msg])): ?>
  <div class="alert alert-success"><?= $messages[$msg] ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-6">
    <div class="table-card">
      <h4><?= htmlspecialchars($task['title']) ?></h4>
      <p class="text-muted">
        <?= $task['description'] ? nl2br(htmlspecialchars($task['description'])) : 'No description.' ?>
      </p>
      <ul class="list-unstyled mb-3">
        <li><strong>Status:</strong>
          <span class="badge <?= $badge[$task['status']] ?>"><?= htmlspecialchars($task['status']) ?></span></li>
        <li><strong>Priority:</strong>
          <span class="badge <?= $priorityBadge[$task['priority']] ?? 'bg-secondary' ?>"><?= htmlspecialchars($task['priority']) ?></span></li>
        <li><strong>Category:</strong>
          <?php if ($task['category']): ?>
            <span class="badge" style="background-color: <?= htmlspecialchars($task['color']) ?>;"><?= htmlspecialchars($task['category']) ?></span>
          <?php else: ?>-<?php endif; ?></li>
        <li><strong>Due date:</strong>
          <?= $task['due_date'] ? date('d M Y', strtotime($task['due_date'])) : '-' ?></li>
        <li><strong>Created:</strong> <?= date('d M Y, H:i', strtotime($task['created_at'])) ?></li>
        <?php if ($task['completed_at']): ?>
          <li><strong>Completed:</strong> <?= date('d M Y, H:i', strtotime($task['completed_at'])) ?></li>
        <?php endif; ?>
      </ul>
      <a href="task_edit.php?id=<?= $task['id'] ?>" class="btn btn-outline-primary btn-sm">Edit task</a>
    </div>
  </div>

  <div class="col-md-6">
    <div class="table-card">
      <h5>Progress Notes</h5>

      <div id="clientErrors" class="alert alert-danger d-none"></div>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form name="noteForm" method="post" action="task_view.php?id=<?= $id ?>"
            onsubmit="return validateNote()" novalidate class="mb-3">
        <input type="hidden" name="action" value="add_note">
        <textarea class="form-control mb-2" name="note" rows="2" maxlength="500"
                  placeholder="Write a progress note..."><?= htmlspecialchars($note) ?></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
      </form>

      <?php if ($notes->num_rows === 0): ?>
        <p class="text-muted mb-0">No notes yet.</p>
      <?php else: ?>
        <?php while ($n = $notes->fetch_assoc()): ?>
          <div class="border rounded p-2 mb-2">
            <div><?= nl2br(htmlspecialchars($n['note'])) ?></div>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <small class="text-muted"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></small>
              <form method="post" action="task_view.php?id=<?= $id ?>" class="d-inline"
                    onsubmit="return confirm('Delete this note?')">
                <input type="hidden" name="action" value="delete_note">
                <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger py-0">Delete</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="js/validate.js"></script>

<?php include 'includes/footer.php'; ?>