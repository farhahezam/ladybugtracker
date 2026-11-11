
<?php
require_once "includes/functions.php";
require_login();

$user = current_user();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    if (isset($_POST["mark_all"])) {
        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
        ");
        $stmt->execute([$user["id"]]);

        set_message("All notifications marked as read.");
    } else {
        $notificationId = (int)($_POST["notification_id"] ?? 0);

        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $user["id"]]);
    }

    redirect("notifications.php");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute([$user["id"]]);
$notifications = $stmt->fetchAll();

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Notifications</h1>
        <p>Updates about your assigned bugs and discussions.</p>
    </div>

    <form method="POST">
        <?= csrf_field() ?>
        <button class="btn btn-light" type="submit" name="mark_all" value="1">
            <i class="bi bi-check2-all"></i> Mark All as Read
        </button>
    </form>
</div>

<div class="notification-list">
    <?php foreach ($notifications as $notification): ?>
        <article class="notification-card <?= (int)$notification["is_read"] === 0 ? "unread" : "" ?>">
            <div class="notification-icon">
                <i class="bi bi-bell"></i>
            </div>

            <div class="notification-content">
                <h3><?= e($notification["title"]) ?></h3>
                <p><?= e($notification["message"]) ?></p>
                <small><?= e(date("d M Y, h:i A", strtotime($notification["created_at"]))) ?></small>
            </div>

            <div class="notification-actions">
                <?php if (!empty($notification["link"])): ?>
                    <a href="<?= e($notification["link"]) ?>" class="text-link">Open</a>
                <?php endif; ?>

                <?php if (!(int)$notification["is_read"]): ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="notification_id" value="<?= (int)$notification["id"] ?>">
                        <button class="text-button" type="submit">Mark read</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$notifications): ?>
        <div class="form-card empty-state">
            <i class="bi bi-bell-slash"></i>
            <h3>No Notifications</h3>
            <p>You do not have any notifications yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>
