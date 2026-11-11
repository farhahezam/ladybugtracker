
<?php
require_once "includes/functions.php";
require_login();

$keyword = trim($_GET["keyword"] ?? "");
$status = $_GET["status"] ?? "";
$priority = $_GET["priority"] ?? "";
$projectId = (int)($_GET["project_id"] ?? 0);
$assigneeId = (int)($_GET["assigned_to_id"] ?? 0);
$dateFrom = $_GET["date_from"] ?? "";
$dateTo = $_GET["date_to"] ?? "";

$sortOptions = [
    "newest" => "t.created_at DESC",
    "oldest" => "t.created_at ASC",
    "priority" => "FIELD(t.priority, 'High', 'Medium', 'Low'), t.created_at DESC",
    "updated" => "t.updated_at DESC"
];

$sort = $_GET["sort"] ?? "newest";
$orderBy = $sortOptions[$sort] ?? $sortOptions["newest"];

$where = [
    "t.type = 'Bug'",
    "t.is_archived = 0"
];

$params = [];

if ($keyword !== "") {
    $where[] = "(
        CAST(t.id AS CHAR) LIKE :keyword
        OR t.title LIKE :keyword
        OR t.description LIKE :keyword
    )";
    $params["keyword"] = "%" . $keyword . "%";
}

if (in_array($status, ["Open", "In Progress", "Resolved"], true)) {
    $where[] = "t.status = :status";
    $params["status"] = $status;
}

if (in_array($priority, ["Low", "Medium", "High"], true)) {
    $where[] = "t.priority = :priority";
    $params["priority"] = $priority;
}

if ($projectId > 0) {
    $where[] = "t.project_id = :project_id";
    $params["project_id"] = $projectId;
}

if ($assigneeId > 0) {
    $where[] = "t.assigned_to_id = :assignee_id";
    $params["assignee_id"] = $assigneeId;
}

if (
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)
) {
    $where[] = "t.created_at >= :date_from";
    $params["date_from"] = $dateFrom . " 00:00:00";
}

if (
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)
) {
    $where[] = "t.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)";
    $params["date_to"] = $dateTo;
}

$sql = "
    SELECT
        t.id,
        t.title,
        t.priority,
        t.status,
        t.created_at,
        t.updated_at,
        p.name AS project_name,
        u.username AS assignee_name
    FROM tasks t
    LEFT JOIN projects p ON p.id = t.project_id
    LEFT JOIN users u ON u.id = t.assigned_to_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY $orderBy
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bugs = $stmt->fetchAll();

$projects = $pdo->query("SELECT id, name FROM projects ORDER BY name")->fetchAll();
$developers = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Advanced Bug Search</h1>
        <p>Find and filter bug reports using multiple criteria.</p>
    </div>
    <a href="report_bug.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Report Bug
    </a>
</div>

<div class="form-card search-filter-card">
    <form method="GET">
        <div class="search-grid">
            <div class="form-group search-wide">
                <label>Search</label>
                <input
                    type="text"
                    name="keyword"
                    value="<?= e($keyword) ?>"
                    placeholder="Enter bug ID, title or description..."
                >
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (["Open", "In Progress", "Resolved"] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? "selected" : "" ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <option value="">All priorities</option>
                    <?php foreach (["High", "Medium", "Low"] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $priority === $option ? "selected" : "" ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Project</label>
                <select name="project_id">
                    <option value="0">All projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= (int)$project["id"] ?>" <?= $projectId === (int)$project["id"] ? "selected" : "" ?>>
                            <?= e($project["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Assigned Developer</label>
                <select name="assigned_to_id">
                    <option value="0">All developers</option>
                    <?php foreach ($developers as $developer): ?>
                        <option value="<?= (int)$developer["id"] ?>" <?= $assigneeId === (int)$developer["id"] ? "selected" : "" ?>>
                            <?= e($developer["username"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Reported From</label>
                <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
            </div>

            <div class="form-group">
                <label>Reported To</label>
                <input type="date" name="date_to" value="<?= e($dateTo) ?>">
            </div>

            <div class="form-group">
                <label>Sort By</label>
                <select name="sort">
                    <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Newest first</option>
                    <option value="oldest" <?= $sort === "oldest" ? "selected" : "" ?>>Oldest first</option>
                    <option value="priority" <?= $sort === "priority" ? "selected" : "" ?>>Priority</option>
                    <option value="updated" <?= $sort === "updated" ? "selected" : "" ?>>Recently updated</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <a href="search.php" class="btn btn-light">Clear Filters</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Search Bugs
            </button>
        </div>
    </form>
</div>

<div class="section-heading">
    <div>
        <h2>Search Results</h2>
        <p><?= count($bugs) ?> result(s), maximum 200 displayed.</p>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bug</th>
                    <th>Project</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Reported</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$bugs): ?>
                    <tr><td colspan="8">No matching bug reports found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bugs as $bug): ?>
                        <tr>
                            <td>#<?= (int)$bug["id"] ?></td>
                            <td><strong><?= e($bug["title"]) ?></strong></td>
                            <td><?= e($bug["project_name"] ?? "N/A") ?></td>
                            <td>
                                <span class="priority-badge <?= e(get_priority_class($bug["priority"])) ?>">
                                    <?= e($bug["priority"]) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= e(get_status_class($bug["status"])) ?>">
                                    <?= e($bug["status"]) ?>
                                </span>
                            </td>
                            <td><?= e($bug["assignee_name"] ?? "Unassigned") ?></td>
                            <td><?= e(date("d M Y", strtotime($bug["created_at"]))) ?></td>
                            <td><a class="text-link" href="bug.php?id=<?= (int)$bug["id"] ?>">View Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
