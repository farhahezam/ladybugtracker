<?php

require_once "includes/functions.php";

$sql = "
    SELECT id, title, description, priority, status, created_at, updated_at
    FROM tasks
    WHERE type = 'Bug'
      AND is_archived = 0
      AND status = 'Resolved'
    ORDER BY updated_at DESC
";

$stmt = $pdo->query($sql);
$bugs = $stmt->fetchAll();

require_once "includes/header.php";

?>

<div class="page-heading">
    <div>
        <h1>Resolved Bugs</h1>
        <p>View issues that have been successfully resolved.</p>
    </div>
</div>

<div class="section-heading">
    <div>
        <h2>Completed Issues</h2>
        <p><?= count($bugs) ?> resolved issue(s) found.</p>
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
                        <th>Date Resolved</th>
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
                                <span class="status-badge status-resolved">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Resolved
                                </span>
                            </td>

                            <td>
                                <?= e(date("d M Y", strtotime($bug["created_at"]))) ?>
                            </td>

                            <td>
                                <?= e(date("d M Y", strtotime($bug["updated_at"]))) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-clipboard-check"></i>
            <h3>No resolved bugs yet</h3>
            <p>Resolved issues will appear here after their status is updated.</p>
        </div>

    <?php endif; ?>

</div>

<?php require_once "includes/footer.php"; ?>