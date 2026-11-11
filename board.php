
<?php
require_once "includes/functions.php";
require_login();

$user = current_user();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $bugId = (int)($_POST["bug_id"] ?? 0);
    $newStatus = $_POST["status"] ?? "";

    if (!in_array($newStatus, ["Open", "In Progress", "Resolved"], true)) {
        set_message("Invalid status selected.", "error");
        redirect("board.php");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT id, status, title, creator_id, assigned_to_id
            FROM tasks
            WHERE id = ? AND type = 'Bug' AND is_archived = 0
            FOR UPDATE
        ");

        $stmt->execute([$bugId]);
        $bug = $stmt->fetch();

        if (!$bug) {
            throw new Exception("Bug not found.");
        }

        $oldStatus = $bug["status"];

        if ($oldStatus !== $newStatus) {
            $update = $pdo->prepare("
                UPDATE tasks
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$newStatus, $bugId]);

            $history = $pdo->prepare("
                INSERT INTO task_history
                    (task_id, user_id, field_name, old_value, new_value)
                VALUES (?, ?, 'status', ?, ?)
            ");
            $history->execute([
                $bugId,
                $user["id"],
                $oldStatus,
                $newStatus
            ]);

            $recipients = array_unique(array_filter([
                (int)$bug["creator_id"],
                $bug["assigned_to_id"] ? (int)$bug["assigned_to_id"] : null
            ]));

            foreach ($recipients as $recipientId) {
                if ($recipientId !== (int)$user["id"]) {
                    create_notification(
                        $recipientId,
                        $bugId,
                        "Bug status updated",
                        "Bug #" . $bugId . " is now " . $newStatus . ".",
                        "bug.php?id=" . $bugId
                    );
                }
            }

            log_activity("Bug Status Updated", "Bug #" . $bugId . ": " . $oldStatus . " to " . $newStatus);
        }

        $pdo->commit();
        set_message("Bug status updated.");

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_message("Unable to update bug status.", "error");
    }

    redirect("board.php");
}

$stmt = $pdo->query("
    SELECT
        t.id,
        t.title,
        t.priority,
        t.status,
        t.created_at,
        u.username AS assignee_name
    FROM tasks t
    LEFT JOIN users u ON u.id = t.assigned_to_id
    WHERE t.type = 'Bug'
      AND t.is_archived = 0
      AND t.status IN ('Open', 'In Progress', 'Resolved')
    ORDER BY FIELD(t.priority, 'High', 'Medium', 'Low'), t.created_at DESC
");

$bugs = $stmt->fetchAll();

$columns = [
    "Open" => [],
    "In Progress" => [],
    "Resolved" => []
];

foreach ($bugs as $bug) {
    $columns[$bug["status"]][] = $bug;
}

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Kanban Board</h1>
        <p>Track bug reports through each stage of resolution.</p>
    </div>
    <a href="report_bug.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Bug
    </a>
</div>

<div class="kanban-board">
    <?php foreach ($columns as $statusName => $items): ?>
        <section class="kanban-column">
            <div class="kanban-column-heading">
                <div>
                    <h3><?= e($statusName) ?></h3>
                    <span><?= count($items) ?> bugs</span>
                </div>
                <span class="kanban-count"><?= count($items) ?></span>
            </div>

            <div class="kanban-cards">
                <?php foreach ($items as $bug): ?>
                    <article class="kanban-card">
                        <div class="kanban-card-top">
                            <span>#<?= (int)$bug["id"] ?></span>
                            <span class="priority-badge <?= e(get_priority_class($bug["priority"])) ?>">
                                <?= e($bug["priority"]) ?>
                            </span>
                        </div>

                        <h4>
                            <a href="bug.php?id=<?= (int)$bug["id"] ?>">
                                <?= e($bug["title"]) ?>
                            </a>
                        </h4>

                        <p class="kanban-assignee">
                            <i class="bi bi-person"></i>
                            <?= e($bug["assignee_name"] ?? "Unassigned") ?>
                        </p>

                        <form method="POST" class="kanban-status-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="bug_id" value="<?= (int)$bug["id"] ?>">

                            <select name="status" onchange="this.form.submit()">
                                <?php foreach (["Open", "In Progress", "Resolved"] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $statusName === $option ? "selected" : "" ?>>
                                        Move to <?= e($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </article>
                <?php endforeach; ?>

                <?php if (!$items): ?>
                    <div class="kanban-empty">No bugs in this stage.</div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<?php require_once "includes/footer.php"; ?>
