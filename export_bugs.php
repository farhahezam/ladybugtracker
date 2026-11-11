
<?php
require_once "includes/functions.php";
require_login();

$status = $_GET["status"] ?? "";
$priority = $_GET["priority"] ?? "";

$where = [
    "t.type = 'Bug'",
    "t.is_archived = 0"
];

$params = [];

if (in_array($status, ["Open", "In Progress", "Resolved"], true)) {
    $where[] = "t.status = ?";
    $params[] = $status;
}

if (in_array($priority, ["Low", "Medium", "High"], true)) {
    $where[] = "t.priority = ?";
    $params[] = $priority;
}

$sql = "
    SELECT
        t.id,
        t.title,
        t.description,
        t.priority,
        t.status,
        p.name AS project,
        creator.username AS created_by,
        assignee.username AS assigned_to,
        t.created_at,
        t.updated_at
    FROM tasks t
    LEFT JOIN projects p ON p.id = t.project_id
    LEFT JOIN users creator ON creator.id = t.creator_id
    LEFT JOIN users assignee ON assignee.id = t.assigned_to_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY t.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$filename = "ladybug_bugs_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "Bug ID",
    "Title",
    "Description",
    "Priority",
    "Status",
    "Project",
    "Created By",
    "Assigned To",
    "Date Created",
    "Last Updated"
]);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row["id"],
        $row["title"],
        $row["description"],
        $row["priority"],
        $row["status"],
        $row["project"],
        $row["created_by"],
        $row["assigned_to"] ?? "Unassigned",
        $row["created_at"],
        $row["updated_at"]
    ]);
}

fclose($output);
exit;
