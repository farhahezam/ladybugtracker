
<?php
require_once __DIR__ . "/functions.php";

$currentPage = basename($_SERVER["PHP_SELF"]);
$user = current_user();

function nav_active($page, $currentPage)
{
    return $page === $currentPage ? "active" : "";
}

$unreadCount = $user
    ? unread_notification_count($user["id"])
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LadyBug Tracker</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="app-container">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-bug-fill"></i>
            </div>
            <div>
                <h2>LadyBug</h2>
                <span>Project Tracker</span>
            </div>
        </div>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="navigation">
            <a href="index.php" class="<?= nav_active('index.php', $currentPage) ?>">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>

            <a href="report_bug.php" class="<?= nav_active('report_bug.php', $currentPage) ?>">
                <i class="bi bi-plus-circle"></i><span>Report Bug</span>
            </a>

            <a href="active_bugs.php" class="<?= nav_active('active_bugs.php', $currentPage) ?>">
                <i class="bi bi-bug"></i><span>Active Bugs</span>
            </a>

            <a href="resolved_bugs.php" class="<?= nav_active('resolved_bugs.php', $currentPage) ?>">
                <i class="bi bi-check-circle"></i><span>Resolved Bugs</span>
            </a>

            <a href="search.php" class="<?= nav_active('search.php', $currentPage) ?>">
                <i class="bi bi-search"></i><span>Advanced Search</span>
            </a>

            <a href="board.php" class="<?= nav_active('board.php', $currentPage) ?>">
                <i class="bi bi-kanban"></i><span>Kanban Board</span>
            </a>

            <a href="analytics.php" class="<?= nav_active('analytics.php', $currentPage) ?>">
                <i class="bi bi-bar-chart-line"></i><span>Analytics</span>
            </a>

            <a href="bug_history.php" class="<?= nav_active('bug_history.php', $currentPage) ?>">
                <i class="bi bi-clock-history"></i><span>Bug History</span>
            </a>

            <a href="notifications.php" class="<?= nav_active('notifications.php', $currentPage) ?>">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="nav-count"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>

            <a href="export_bugs.php">
                <i class="bi bi-file-earmark-spreadsheet"></i><span>Export CSV</span>
            </a>

            <?php if (is_admin()): ?>
                <a href="users.php" class="<?= nav_active('users.php', $currentPage) ?>">
                    <i class="bi bi-people"></i><span>User Management</span>
                </a>
            <?php endif; ?>

            <a href="verify.php" class="<?= nav_active('verify.php', $currentPage) ?>">
                <i class="bi bi-database-check"></i><span>System Verification</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-status">
                <span class="online-dot"></span>
                System Online
            </div>
            <p>LadyBug Tracker v2.0</p>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <div class="topbar-label">WORKSPACE / PROJECT MANAGEMENT</div>
                <h3>Bug Tracking System</h3>
            </div>

            <div class="topbar-right">
                <span class="date-display">
                    <i class="bi bi-calendar3"></i>
                    <?= date("d M Y") ?>
                </span>

                <?php if ($user): ?>
                    <span class="topbar-username"><?= e($user["username"]) ?></span>
                    <a class="btn btn-light btn-small" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                <?php else: ?>
                    <a class="btn btn-primary btn-small" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="page-content">
            <?php show_message(); ?>
