<?php

require_once "includes/functions.php";

$databaseName = $pdo->query("SELECT DATABASE()")->fetchColumn();

$totalBugs = $pdo->query("
    SELECT COUNT(*)
    FROM tasks
    WHERE type = 'Bug'
")->fetchColumn();

$totalHistory = $pdo->query("
    SELECT COUNT(*)
    FROM task_history h
    INNER JOIN tasks t ON h.task_id = t.id
    WHERE t.type = 'Bug'
      AND h.field_name = 'status'
")->fetchColumn();

$latestBug = $pdo->query("
    SELECT id, title, status, created_at
    FROM tasks
    WHERE type = 'Bug'
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();

$latestHistory = $pdo->query("
    SELECT
        h.old_value,
        h.new_value,
        h.changed_at,
        t.title AS bug_title
    FROM task_history h
    INNER JOIN tasks t ON h.task_id = t.id
    WHERE h.field_name = 'status'
      AND t.type = 'Bug'
    ORDER BY h.changed_at DESC
    LIMIT 1
")->fetch();

require_once "includes/header.php";

?>

<div class="page-heading">
    <div>
        <h1>System Verification</h1>
        <p>Verify the connection and latest records stored in MySQL.</p>
    </div>

    <span class="connection-badge">
        <span class="online-dot"></span>
        Connected
    </span>
</div>

<div class="verification-grid">

    <div class="verification-card">
        <div class="verification-icon green">
            <i class="bi bi-database-check"></i>
        </div>

        <div>
            <span>Database Connection</span>
            <h3>Connected</h3>
            <small>Successfully connected through PDO</small>
        </div>
    </div>

    <div class="verification-card">
        <div class="verification-icon purple">
            <i class="bi bi-server"></i>
        </div>

        <div>
            <span>Database Name</span>
            <h3><?= e($databaseName) ?></h3>
            <small>Current MySQL database</small>
        </div>
    </div>

    <div class="verification-card">
        <div class="verification-icon blue">
            <i class="bi bi-bug"></i>
        </div>

        <div>
            <span>Total Bug Records</span>
            <h3><?= (int)$totalBugs ?></h3>
            <small>Records stored in MySQL</small>
        </div>
    </div>

    <div class="verification-card">
        <div class="verification-icon orange">
            <i class="bi bi-clock-history"></i>
        </div>

        <div>
            <span>Total Bug History Records</span>
            <h3><?= (int)$totalHistory ?></h3>
            <small>Recorded status changes</small>
        </div>
    </div>

</div>

<div class="verification-details">

    <div class="detail-card">
        <h2>
            <i class="bi bi-file-earmark-text"></i>
            Latest Bug Record
        </h2>

        <?php if ($latestBug): ?>

            <div class="detail-content">
                <span class="detail-label">Bug ID</span>
                <strong>#<?= (int)$latestBug["id"] ?></strong>
            </div>

            <div class="detail-content">
                <span class="detail-label">Bug Title</span>
                <strong><?= e($latestBug["title"]) ?></strong>
            </div>

            <div class="detail-content">
                <span class="detail-label">Current Status</span>
                <span class="status-badge <?= get_status_class($latestBug["status"]) ?>">
                    <?= e($latestBug["status"]) ?>
                </span>
            </div>

            <div class="detail-content">
                <span class="detail-label">Date Reported</span>
                <strong>
                    <?= e(date("d M Y, h:i A", strtotime($latestBug["created_at"]))) ?>
                </strong>
            </div>

        <?php else: ?>

            <p class="muted-text">No bug records found.</p>

        <?php endif; ?>
    </div>

    <div class="detail-card">
        <h2>
            <i class="bi bi-arrow-left-right"></i>
            Latest Status Change
        </h2>

        <?php if ($latestHistory): ?>

            <div class="detail-content">
                <span class="detail-label">Bug Title</span>
                <strong><?= e($latestHistory["bug_title"]) ?></strong>
            </div>

            <div class="detail-content">
                <span class="detail-label">Previous Status</span>
                <span class="status-badge <?= get_status_class($latestHistory["old_value"]) ?>">
                    <?= e($latestHistory["old_value"]) ?>
                </span>
            </div>

            <div class="detail-content">
                <span class="detail-label">New Status</span>
                <span class="status-badge <?= get_status_class($latestHistory["new_value"]) ?>">
                    <?= e($latestHistory["new_value"]) ?>
                </span>
            </div>

            <div class="detail-content">
                <span class="detail-label">Date and Time</span>
                <strong>
                    <?= e(date("d M Y, h:i A", strtotime($latestHistory["changed_at"]))) ?>
                </strong>
            </div>

        <?php else: ?>

            <p class="muted-text">No status changes have been recorded yet.</p>

        <?php endif; ?>
    </div>

</div>

<div class="verification-note">
    <i class="bi bi-info-circle"></i>
    <div>
        <strong>Live Database Verification</strong>
        <p>
            The information displayed on this page is retrieved directly from
            the MySQL database whenever the page is loaded.
        </p>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>