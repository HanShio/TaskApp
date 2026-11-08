<div class="form-card" style="max-width: 600px;">
  <h2><?= htmlspecialchars($formTitle) ?></h2>

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

  <form name="taskForm" method="post" action="<?= htmlspecialchars($formAction) ?>"
        onsubmit="return validateTask()" novalidate>
    <div class="mb-3">
      <label for="title" class="form-label">Title</label>
      <input type="text" class="form-control" id="title" name="title" maxlength="150"
             value="<?= htmlspecialchars($task['title']) ?>">
    </div>
    <div class="mb-3">
      <label for="description" class="form-label">Description (optional)</label>
      <textarea class="form-control" id="description" name="description" maxlength="500"
                rows="3"><?= htmlspecialchars($task['description']) ?></textarea>
    </div>
    <div class="mb-3">
      <label for="due_date" class="form-label">Due Date</label>
      <input type="date" class="form-control" id="due_date" name="due_date"
             value="<?= htmlspecialchars($task['due_date']) ?>">
    </div>
    <div class="mb-3">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status">
        <?php foreach (['Pending', 'In Progress', 'Done'] as $s): ?>
          <option value="<?= $s ?>" <?= $task['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="row">
  <div class="col-md-6 mb-3">
    <label for="priority_id" class="form-label">Priority</label>
    <select class="form-select" id="priority_id" name="priority_id">
      <?php foreach ($priorities as $p): ?>
        <option value="<?= $p['id'] ?>" <?= (int) $task['priority_id'] === (int) $p['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6 mb-3">
    <label for="category_id" class="form-label">Category</label>
    <select class="form-select" id="category_id" name="category_id">
      <option value="">None</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (string) $task['category_id'] === (string) $c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

</div>

    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($submitLabel) ?></button>
    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>

<script src="js/validate.js"></script>