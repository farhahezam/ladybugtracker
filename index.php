<?php

require_once "includes/functions.php";

$counts = get_bug_counts($pdo);

$sql = "
    SELECT id, title, description, priority, status, created_at
    FROM tasks
    WHERE type = 'Bug' AND is_archived = 0
    ORDER BY created_at DESC
    LIMIT 6
";

$stmt = $pdo->query($sql);
$recentBugs = $stmt->fetchAll();

require_once "includes/header.php";

?>

<div class="page-heading">
    <div>
        <h1>Dashboard</h1>
        <p>Overview of your project's reported issues and progress.</p>
    </div>

    <a href="report_bug.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Report New Bug
    </a>
</div>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-top">
            <span>Total Bugs</span>
            <div class="stat-icon purple">
                <i class="bi bi-bug-fill"></i>
            </div>
        </div>

        <h2><?= $counts["total"] ?></h2>
        <p>All reported issues</p>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Open Bugs</span>
            <div class="stat-icon orange">
                <i class="bi bi-exclamation-circle"></i>
            </div>
        </div>

        <h2><?= $counts["open"] ?></h2>
        <p>Waiting for action</p>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>In Progress</span>
            <div class="stat-icon blue">
                <i class="bi bi-arrow-repeat"></i>
            </div>
        </div>

        <h2><?= $counts["progress"] ?></h2>
        <p>Currently being handled</p>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Resolved Bugs</span>
            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
        </div>

        <h2><?= $counts["resolved"] ?></h2>
        <p>Successfully resolved</p>
    </div>

</div>

<div class="section-heading">
    <div>
        <h2>Recent Bug Reports</h2>
        <p>Latest issues recorded in the system.</p>
    </div>

    <a href="active_bugs.php" class="text-link">
        View Active Bugs <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div class="table-card">

    <?php if (count($recentBugs) > 0): ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Bug Details</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Reported</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($recentBugs as $bug): ?>

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

                            <td><?= e(date("d M Y, h:i A", strtotime($bug["created_at"]))) ?></td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>No bug reports yet</h3>
            <p>Start by reporting your first project issue.</p>
            <a href="report_bug.php" class="btn btn-primary">Report a Bug</a>
        </div>

    <?php endif; ?>

</div>

<?php require_once "includes/footer.php"; ?>