
<?php
require_once "includes/functions.php";
require_admin();

$currentUser = current_user();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $targetId = (int)($_POST["user_id"] ?? 0);
    $newRole = $_POST["role"] ?? "";

    if (!in_array($newRole, ["Admin", "Developer"], true)) {
        set_message("Invalid role selected.", "error");
        redirect("users.php");
    }

    $stmt = $pdo->prepare("
        SELECT id, username, is_admin
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        set_message("User not found.", "error");
        redirect("users.php");
    }

    $makeAdmin = $newRole === "Admin" ? 1 : 0;

    if (
        (int)$target["is_admin"] === 1 &&
        $makeAdmin === 0
    ) {
        $adminCount = (int)$pdo->query("
            SELECT COUNT(*) FROM users WHERE is_admin = 1
        ")->fetchColumn();

        if ($adminCount <= 1) {
            set_message("The last administrator cannot be demoted.", "error");
            redirect("users.php");
        }

        if ((int)$targetId === (int)$currentUser["id"]) {
            set_message("You cannot remove your own administrator access here.", "error");
            redirect("users.php");
        }
    }

    $update = $pdo->prepare("
        UPDATE users
        SET is_admin = ?
        WHERE id = ?
    ");
    $update->execute([$makeAdmin, $targetId]);

    log_activity(
        "User Role Updated",
        "Changed role for user #" . $targetId . " to " . $newRole
    );

    set_message("User role updated.");
    redirect("users.php");
}

$users = $pdo->query("
    SELECT
        u.id,
        u.username,
        u.email,
        u.is_admin,
        u.created_at,
        COUNT(t.id) AS assigned_bugs
    FROM users u
    LEFT JOIN tasks t
        ON t.assigned_to_id = u.id
        AND t.type = 'Bug'
        AND t.is_archived = 0
    GROUP BY u.id, u.username, u.email, u.is_admin, u.created_at
    ORDER BY u.is_admin DESC, u.username
")->fetchAll();

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>User Management</h1>
        <p>Manage developer accounts and administrator access.</p>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Assigned Bugs</th>
                    <th>Registered</th>
                    <th>Update Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td>
                            <strong><?= e($account["username"]) ?></strong>
                            <span class="table-description">User ID: <?= (int)$account["id"] ?></span>
                        </td>
                        <td><?= e($account["email"]) ?></td>
                        <td>
                            <span class="role-badge">
                                <?= (int)$account["is_admin"] === 1 ? "Admin" : "Developer" ?>
                            </span>
                        </td>
                        <td><?= (int)$account["assigned_bugs"] ?></td>
                        <td><?= e(date("d M Y", strtotime($account["created_at"]))) ?></td>
                        <td>
                            <form method="POST" class="user-role-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$account["id"] ?>">

                                <select name="role">
                                    <option value="Developer" <?= (int)$account["is_admin"] === 0 ? "selected" : "" ?>>
                                        Developer
                                    </option>
                                    <option value="Admin" <?= (int)$account["is_admin"] === 1 ? "selected" : "" ?>>
                                        Admin
                                    </option>
                                </select>

                                <button class="btn btn-primary btn-small" type="submit">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
