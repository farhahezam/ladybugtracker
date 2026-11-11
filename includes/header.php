<?php

require_once __DIR__ . "/functions.php";

$currentPage = basename($_SERVER["PHP_SELF"]);

function nav_active($page, $currentPage)
{
    return $page === $currentPage ? "active" : "";
}

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

            <a href="index.php"
               class="<?= nav_active('index.php', $currentPage) ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

            <a href="report_bug.php"
               class="<?= nav_active('report_bug.php', $currentPage) ?>">
                <i class="bi bi-plus-circle"></i>
                <span>Report Bug</span>
            </a>

            <a href="active_bugs.php"
               class="<?= nav_active('active_bugs.php', $currentPage) ?>">
                <i class="bi bi-bug"></i>
                <span>Active Bugs</span>
            </a>

            <a href="resolved_bugs.php"
               class="<?= nav_active('resolved_bugs.php', $currentPage) ?>">
                <i class="bi bi-check-circle"></i>
                <span>Resolved Bugs</span>
            </a>

            <a href="bug_history.php"
               class="<?= nav_active('bug_history.php', $currentPage) ?>">
                <i class="bi bi-clock-history"></i>
                <span>Bug History</span>
            </a>

            <a href="verify.php"
               class="<?= nav_active('verify.php', $currentPage) ?>">
                <i class="bi bi-database-check"></i>
                <span>System Verification</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-status">
                <span class="online-dot"></span>
                System Online
            </div>

            <p>LadyBug Tracker v1.0</p>
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

                <div class="user-avatar">LB</div>
            </div>
        </header>

        <div class="page-content">

            <?php show_message(); ?>