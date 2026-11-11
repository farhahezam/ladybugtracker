<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

function e($value)
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function redirect($url)
{
    header("Location: " . $url);
    exit;
}

function set_message($message, $type = "success")
{
    $_SESSION["message"] = $message;
    $_SESSION["message_type"] = $type;
}

function show_message()
{
    if (isset($_SESSION["message"])) {
        $message = $_SESSION["message"];
        $type = $_SESSION["message_type"] ?? "success";

        echo '<div class="alert alert-' . e($type) . '">'
            . e($message) .
            '</div>';

        unset($_SESSION["message"], $_SESSION["message_type"]);
    }
}

function get_bug_counts($pdo)
{
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(status = 'Open') AS open_count,
            SUM(status = 'In Progress') AS progress_count,
            SUM(status = 'Resolved') AS resolved_count
        FROM tasks
        WHERE type = 'Bug' AND is_archived = 0
    ";

    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();

    return [
        "total" => (int)($result["total"] ?? 0),
        "open" => (int)($result["open_count"] ?? 0),
        "progress" => (int)($result["progress_count"] ?? 0),
        "resolved" => (int)($result["resolved_count"] ?? 0)
    ];
}

function get_priority_class($priority)
{
    switch (strtolower($priority)) {
        case "high":
            return "priority-high";

        case "medium":
            return "priority-medium";

        case "low":
            return "priority-low";

        default:
            return "priority-medium";
    }
}

function get_status_class($status)
{
    switch ($status) {
        case "Open":
            return "status-open";

        case "In Progress":
            return "status-progress";

        case "Resolved":
            return "status-resolved";

        default:
            return "";
    }
}