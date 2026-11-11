
<?php
require_once "includes/functions.php";
require_login();

$dateFrom = $_GET["date_from"] ?? date("Y-m-d", strtotime("-29 days"));
$dateTo = $_GET["date_to"] ?? date("Y-m-d");

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ||
    $dateFrom > $dateTo
) {
    $dateFrom = date("Y-m-d", strtotime("-29 days"));
    $dateTo = date("Y-m-d");
}

$filter = "
    type = 'Bug'
    AND is_archived = 0
    AND created_at >= :date_from
    AND created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)
";

$params = [
    "date_from" => $dateFrom,
    "date_to" => $dateTo
];

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Open') AS open_count,
        SUM(status = 'In Progress') AS progress_count,
        SUM(status = 'Resolved') AS resolved_count,
        SUM(priority = 'High') AS high_count
    FROM tasks
    WHERE $filter
");
$stmt->execute($params);
$stats = $stmt->fetch();

$total = (int)$stats["total"];
$open = (int)$stats["open_count"];
$progress = (int)$stats["progress_count"];
$resolved = (int)$stats["resolved_count"];
$high = (int)$stats["high_count"];

$resolutionRate = $total > 0
    ? round(($resolved / $total) * 100, 1)
    : 0;

$stmt = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM tasks
    WHERE $filter
    GROUP BY status
");
$stmt->execute($params);
$statusRows = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT priority, COUNT(*) AS total
    FROM tasks
    WHERE $filter
    GROUP BY priority
");
$stmt->execute($params);
$priorityRows = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        DATE_FORMAT(created_at, '%b %Y') AS month_label,
        COUNT(*) AS total
    FROM tasks
    WHERE $filter
    GROUP BY month_key, month_label
    ORDER BY month_key
");
$stmt->execute($params);
$trendRows = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT id, title, priority, status, created_at
    FROM tasks
    WHERE $filter AND priority = 'High'
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute($params);
$highBugs = $stmt->fetchAll();

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Analytics Dashboard</h1>
        <p>Monitor bug reports and project performance.</p>
    </div>
</div>

<div class="form-card analytics-filter-card">
    <form method="GET" class="analytics-filter-form">
        <div class="form-group">
            <label>From</label>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" required>
        </div>

        <div class="form-group">
            <label>To</label>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" required>
        </div>

        <button class="btn btn-primary" type="submit">
            <i class="bi bi-filter"></i> Apply Filter
        </button>

        <a href="analytics.php" class="btn btn-light">Reset</a>
    </form>
</div>

<div class="stats-grid analytics-stats">
    <div class="stat-card">
        <div class="stat-top">
            <span>Total Bugs</span>
            <div class="stat-icon purple"><i class="bi bi-bug"></i></div>
        </div>
        <h2><?= $total ?></h2>
        <p>Reported in selected period</p>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Open</span>
            <div class="stat-icon orange"><i class="bi bi-exclamation-circle"></i></div>
        </div>
        <h2><?= $open ?></h2>
        <p>Awaiting action</p>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>In Progress</span>
            <div class="stat-icon blue"><i class="bi bi-arrow-repeat"></i></div>
        </div>
        <h2><?= $progress ?></h2>
        <p>Currently being handled</p>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Resolved</span>
            <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
        </div>
        <h2><?= $resolved ?></h2>
        <p>Successfully resolved</p>
    </div>
</div>

<div class="analytics-highlight-grid">
    <div class="analytics-highlight">
        <span>Resolution Rate</span>
        <h2><?= $resolutionRate ?>%</h2>
        <p>Based on current bug status</p>
    </div>

    <div class="analytics-highlight">
        <span>High Priority Bugs</span>
        <h2><?= $high ?></h2>
        <p>Require closer attention</p>
    </div>
</div>

<div class="analytics-chart-grid">
    <div class="analytics-chart-card">
        <h3>Bug Report Trend</h3>
        <p>Number of bugs reported by month</p>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <div class="analytics-chart-card">
        <h3>Status Distribution</h3>
        <p>Current status of reported bugs</p>
        <div class="chart-container chart-small">
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <div class="analytics-chart-card">
        <h3>Priority Distribution</h3>
        <p>Bug reports grouped by priority</p>
        <div class="chart-container chart-small">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>
</div>

<div class="section-heading">
    <div>
        <h2>Recent High Priority Bugs</h2>
        <p>Latest high-priority reports in this period.</p>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Bug</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Date Reported</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$highBugs): ?>
                    <tr><td colspan="5">No high-priority bugs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($highBugs as $bug): ?>
                        <tr>
                            <td><strong>#<?= (int)$bug["id"] ?> <?= e($bug["title"]) ?></strong></td>
                            <td><span class="priority-badge priority-high">High</span></td>
                            <td>
                                <span class="status-badge <?= e(get_status_class($bug["status"])) ?>">
                                    <?= e($bug["status"]) ?>
                                </span>
                            </td>
                            <td><?= e(date("d M Y", strtotime($bug["created_at"]))) ?></td>
                            <td><a class="text-link" href="bug.php?id=<?= (int)$bug["id"] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const trendLabels = <?= json_encode(array_column($trendRows, "month_label")) ?>;
const trendValues = <?= json_encode(array_map("intval", array_column($trendRows, "total"))) ?>;

const statusLabels = <?= json_encode(array_column($statusRows, "status")) ?>;
const statusValues = <?= json_encode(array_map("intval", array_column($statusRows, "total"))) ?>;

const priorityLabels = <?= json_encode(array_column($priorityRows, "priority")) ?>;
const priorityValues = <?= json_encode(array_map("intval", array_column($priorityRows, "total"))) ?>;

new Chart(document.getElementById("trendChart"), {
    type: "bar",
    data: {
        labels: trendLabels,
        datasets: [{
            label: "Bugs Reported",
            data: trendValues,
            backgroundColor: "#7657e8",
            borderRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById("statusChart"), {
    type: "doughnut",
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: ["#ed963d", "#448ce7", "#31a879"]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } }
    }
});

new Chart(document.getElementById("priorityChart"), {
    type: "doughnut",
    data: {
        labels: priorityLabels,
        datasets: [{
            data: priorityValues,
            backgroundColor: ["#e45b65", "#d68a2d", "#35a574"]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } }
    }
});
</script>

<?php require_once "includes/footer.php"; ?>
