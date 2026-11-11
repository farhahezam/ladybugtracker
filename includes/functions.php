
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

/* AUTHENTICATION */

function current_user()
{
    global $pdo;

    if (empty($_SESSION["user_id"])) {
        return null;
    }

    static $cachedUser = false;

    if ($cachedUser !== false) {
        return $cachedUser;
    }

    $stmt = $pdo->prepare("
        SELECT id, username, email, is_admin
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([(int)$_SESSION["user_id"]]);
    $cachedUser = $stmt->fetch();

    if (!$cachedUser) {
        unset($_SESSION["user_id"]);
        return null;
    }

    return $cachedUser;
}

function require_login()
{
    if (!current_user()) {
        set_message("Please log in to continue.", "error");
        redirect("login.php");
    }
}

function is_admin()
{
    $user = current_user();

    return $user && (int)$user["is_admin"] === 1;
}

function require_admin()
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit("Access denied. Administrator permission is required.");
    }
}

/* CSRF PROTECTION */

function csrf_token()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $submitted = $_POST["csrf_token"] ?? "";
    $stored = $_SESSION["csrf_token"] ?? "";

    if (
        !$submitted ||
        !$stored ||
        !hash_equals($stored, $submitted)
    ) {
        http_response_code(419);
        exit("Invalid or expired form token. Please refresh the page.");
    }
}

/* ACTIVITY LOG */

function log_activity($action, $details = "")
{
    global $pdo;

    $user = current_user();
    $userId = $user["id"] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER["REMOTE_ADDR"] ?? null
    ]);
}

/* NOTIFICATIONS */

function create_notification($userId, $taskId, $title, $message, $link)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO notifications
            (user_id, task_id, title, message, link)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $taskId,
        $title,
        $message,
        $link
    ]);
}

function unread_notification_count($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");

    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

/* EXISTING BUG HELPERS */

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

    $result = $pdo->query($sql)->fetch();

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
