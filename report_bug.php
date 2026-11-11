
<?php
require_once "includes/functions.php";
require_login();

$user = current_user();

$projects = $pdo->query("
    SELECT id, name
    FROM projects
    ORDER BY name
")->fetchAll();

$developers = $pdo->query("
    SELECT id, username
    FROM users
    ORDER BY username
")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $priority = $_POST["priority"] ?? "";
    $projectId = (int)($_POST["project_id"] ?? 0);
    $assignedTo = (int)($_POST["assigned_to_id"] ?? 0);

    $allowedPriorities = ["Low", "Medium", "High"];

    $projectCheck = $pdo->prepare("
        SELECT id FROM projects WHERE id = ?
    ");
    $projectCheck->execute([$projectId]);

    $assigneeValid = true;

    if ($assignedTo > 0) {
        $assigneeCheck = $pdo->prepare("
            SELECT id FROM users WHERE id = ?
        ");
        $assigneeCheck->execute([$assignedTo]);
        $assigneeValid = (bool)$assigneeCheck->fetchColumn();
    }

    if ($title === "" || strlen($title) > 200 || $description === "") {
        set_message("Enter a title of up to 200 characters and a description.", "error");
        redirect("report_bug.php");
    }

    if (!in_array($priority, $allowedPriorities, true)) {
        set_message("Select a valid priority.", "error");
        redirect("report_bug.php");
    }

    if (!$projectCheck->fetchColumn() || !$assigneeValid) {
        set_message("Select a valid project and developer.", "error");
        redirect("report_bug.php");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                project_id,
                title,
                description,
                type,
                creator_id,
                assigned_to_id,
                status,
                priority,
                created_at,
                updated_at,
                is_archived
            )
            VALUES (?, ?, ?, 'Bug', ?, ?, 'Open', ?, NOW(), NOW(), 0)
        ");

        $stmt->execute([
            $projectId,
            $title,
            $description,
            $user["id"],
            $assignedTo > 0 ? $assignedTo : null,
            $priority
        ]);

        $bugId = (int)$pdo->lastInsertId();

        $history = $pdo->prepare("
            INSERT INTO task_history
                (task_id, user_id, field_name, old_value, new_value)
            VALUES (?, ?, 'status', NULL, 'Open')
        ");

        $history->execute([$bugId, $user["id"]]);

        if ($assignedTo > 0) {
            create_notification(
                $assignedTo,
                $bugId,
                "New bug assigned",
                "You have been assigned: " . $title,
                "bug.php?id=" . $bugId
            );
        }

        log_activity("Bug Created", "Created bug #" . $bugId . ": " . $title);

        $pdo->commit();

        set_message("Bug report submitted successfully.");
        redirect("bug.php?id=" . $bugId);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_message("Unable to submit the bug report. Please try again.", "error");
        redirect("report_bug.php");
    }
}

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Report a Bug</h1>
        <p>Submit a new issue identified during project development.</p>
    </div>
</div>

<div class="form-card">
    <div class="form-card-heading">
        <div class="form-heading-icon">
            <i class="bi bi-bug"></i>
        </div>
        <div>
            <h2>New Bug Report</h2>
            <p>Provide details about the issue.</p>
        </div>
    </div>

    <form method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Bug Title <span>*</span></label>
            <input
                type="text"
                name="title"
                maxlength="200"
                placeholder="e.g. Login button does not respond"
                required
            >
        </div>

        <div class="form-group">
            <label>Description <span>*</span></label>
            <textarea
                name="description"
                rows="6"
                placeholder="Describe the problem and how to reproduce it..."
                required
            ></textarea>
        </div>

        <div class="form-group">
            <label>Project <span>*</span></label>
            <select name="project_id" required>
                <option value="">Select project</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= (int)$project["id"] ?>">
                        <?= e($project["name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Priority <span>*</span></label>
            <select name="priority" required>
                <option value="">Select priority</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
        </div>

        <div class="form-group">
            <label>Assign To</label>
            <select name="assigned_to_id">
                <option value="0">Unassigned</option>
                <?php foreach ($developers as $developer): ?>
                    <option value="<?= (int)$developer["id"] ?>">
                        <?= e($developer["username"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Initial Status</label>
            <div class="initial-status">
                <span class="status-badge status-open">Open</span>
                <span>New bug reports automatically start as Open.</span>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send"></i> Submit Bug Report
            </button>
        </div>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>
