<?php

require_once "includes/functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bugId = (int)($_POST["bug_id"] ?? 0);
    $newStatus = $_POST["status"] ?? "";

    $allowedStatuses = ["In Progress", "Resolved"];

    if ($bugId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {

        set_message("Invalid status update.", "error");
        redirect("active_bugs.php");
    }

    try {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT id, status
            FROM tasks
            WHERE id = :id
              AND type = 'Bug'
              AND is_archived = 0
            FOR UPDATE
        ");

        $stmt->execute([":id" => $bugId]);
        $bug = $stmt->fetch();

        if (!$bug || !in_array($bug["status"], ["Open", "In Progress"], true)) {

            $pdo->rollBack();

            set_message("Bug not found or it is no longer active.", "error");
            redirect("active_bugs.php");
        }

        $oldStatus = $bug["status"];

        if ($oldStatus !== $newStatus) {

            $update = $pdo->prepare("
                UPDATE tasks
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $update->execute([
                ":status" => $newStatus,
                ":id" => $bugId
            ]);

            $history = $pdo->prepare("
                INSERT INTO task_history
                (
                    task_id,
                    user_id,
                    field_name,
                    old_value,
                    new_value,
                    changed_at
                )
                VALUES
                (
                    :task_id,
                    NULL,
                    'status',
                    :old_value,
                    :new_value,
                    NOW()
                )
            ");

            $history->execute([
                ":task_id" => $bugId,
                ":old_value" => $oldStatus,
                ":new_value" => $newStatus
            ]);
        }

        $pdo->commit();

        set_message("Bug status updated successfully.");

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_message("Unable to update bug status. Please check your database.", "error");
    }

    redirect("active_bugs.php");
}

$sql = "
    SELECT id, title, description, priority, status, created_at
    FROM tasks
    WHERE type = 'Bug'
      AND is_archived = 0
      AND status IN ('Open', 'In Progress')
    ORDER BY
        CASE priority
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            WHEN 'Low' THEN 3
            ELSE 4
        END,
        created_at DESC
";

$stmt = $pdo->query($sql);
$bugs = $stmt->fetchAll();

require_once "includes/header.php";

?>

<div class="page-heading">
    <div>
        <h1>Active Bugs</h1>
        <p>Monitor and manage bugs that still require attention.</p>
    </div>

    <a href="report_bug.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Report Bug
    </a>
</div>

<div class="section-heading">
    <div>
        <h2>Current Issues</h2>
        <p><?= count($bugs) ?> active issue(s) found.</p>
    </div>
</div>

<div class="table-card">

    <?php if (count($bugs) > 0): ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Bug Details</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Reported</th>
                        <th>Update Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($bugs as $bug): ?>

                        <tr>
                            <td>
                                <strong><?= e($bug["title"]) ?></strong>

                                <small class="table-description">
                                    <?= e(mb_strimwidth($bug["description"], 0, 65, "...")) ?>
                                </small>
                            </td>

                            <td>
                                <span class="priority-badge <?= get_priority_class($bug["priority"]) ?>">
                                    <?= e($bug["priority"]) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge <?= get_status_class($bug["status"]) ?>">
                                    <?= e($bug["status"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= e(date("d M Y", strtotime($bug["created_at"]))) ?>
                            </td>

                            <td>
                                <form method="POST" class="status-form">

                                    <input type="hidden" name="bug_id"
                                           value="<?= (int)$bug["id"] ?>">

                                    <select name="status" required>

                                        <?php if ($bug["status"] === "Open"): ?>
                                            <option value="In Progress">In Progress</option>
                                        <?php else: ?>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Resolved">Resolved</option>
                                        <?php endif; ?>

                                    </select>

                                    <button type="submit" class="btn btn-small btn-primary">
                                        Update
                                    </button>

                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-check2-circle"></i>
            <h3>No active bugs</h3>
            <p>There are currently no bugs requiring action.</p>
        </div>

    <?php endif; ?>

</div>

<?php require_once "includes/footer.php"; ?>