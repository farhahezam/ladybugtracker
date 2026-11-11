<?php

require_once "includes/functions.php";

$sql = "
    SELECT
        h.id,
        h.task_id,
        h.old_value,
        h.new_value,
        h.changed_at,
        t.title AS bug_title
    FROM task_history h
    INNER JOIN tasks t ON h.task_id = t.id
    WHERE h.field_name = 'status'
      AND t.type = 'Bug'
    ORDER BY h.changed_at DESC
";

$stmt = $pdo->query($sql);
$history = $stmt->fetchAll();

require_once "includes/header.php";

?>

<div class="page-heading">
    <div>
        <h1>Bug History</h1>
        <p>Track the status changes made to reported bugs.</p>
    </div>
</div>

<div class="section-heading">
    <div>
        <h2>Status Change Records</h2>
        <p><?= count($history) ?> status change(s) recorded.</p>
    </div>
</div>

<div class="table-card">

    <?php if (count($history) > 0): ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Bug</th>
                        <th>Previous Status</th>
                        <th>New Status</th>
                        <th>Date and Time Changed</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($history as $record): ?>

                        <tr>
                            <td>
                                <strong><?= e($record["bug_title"]) ?></strong>
                                <small class="table-description">
                                    Bug ID: #<?= (int)$record["task_id"] ?>
                                </small>
                            </td>

                            <td>
                                <span class="status-badge <?= get_status_class($record["old_value"]) ?>">
                                    <?= e($record["old_value"]) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge <?= get_status_class($record["new_value"]) ?>">
                                    <?= e($record["new_value"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= e(date("d M Y, h:i A", strtotime($record["changed_at"]))) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-clock-history"></i>
            <h3>No history available</h3>
            <p>Status changes will automatically appear here.</p>
        </div>

    <?php endif; ?>

</div>

<?php require_once "includes/footer.php"; ?>