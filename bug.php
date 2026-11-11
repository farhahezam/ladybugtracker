
<?php
require_once "includes/functions.php";
require_login();

$user = current_user();
$bugId = (int)($_GET["id"] ?? 0);

if ($bugId < 1) {
    redirect("search.php");
}

function get_bug_record($pdo, $bugId)
{
    $stmt = $pdo->prepare("
        SELECT
            t.*,
            p.name AS project_name,
            creator.username AS creator_name,
            assignee.username AS assignee_name
        FROM tasks t
        LEFT JOIN projects p ON p.id = t.project_id
        LEFT JOIN users creator ON creator.id = t.creator_id
        LEFT JOIN users assignee ON assignee.id = t.assigned_to_id
        WHERE t.id = ? AND t.type = 'Bug'
        LIMIT 1
    ");

    $stmt->execute([$bugId]);
    return $stmt->fetch();
}

$bug = get_bug_record($pdo, $bugId);

if (!$bug) {
    http_response_code(404);
    exit("Bug report not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $action = $_POST["action"] ?? "";

    if ($action === "update_bug") {
        $newStatus = $_POST["status"] ?? "";
        $newPriority = $_POST["priority"] ?? "";
        $newAssignee = (int)($_POST["assigned_to_id"] ?? 0);

        if (
            !in_array($newStatus, ["Open", "In Progress", "Resolved"], true) ||
            !in_array($newPriority, ["Low", "Medium", "High"], true)
        ) {
            set_message("Invalid status or priority.", "error");
            redirect("bug.php?id=" . $bugId);
        }

        $assignee = null;

        if ($newAssignee > 0) {
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $check->execute([$newAssignee]);
            $assignee = $check->fetchColumn();

            if (!$assignee) {
                set_message("Invalid developer selected.", "error");
                redirect("bug.php?id=" . $bugId);
            }
        }

        try {
            $pdo->beginTransaction();

            $oldStatus = $bug["status"];
            $oldPriority = $bug["priority"];
            $oldAssignee = $bug["assigned_to_id"];

            $stmt = $pdo->prepare("
                UPDATE tasks
                SET status = ?, priority = ?, assigned_to_id = ?, updated_at = NOW()
                WHERE id = ? AND type = 'Bug'
            ");

            $stmt->execute([
                $newStatus,
                $newPriority,
                $assignee,
                $bugId
            ]);

            $history = $pdo->prepare("
                INSERT INTO task_history
                    (task_id, user_id, field_name, old_value, new_value)
                VALUES (?, ?, ?, ?, ?)
            ");

            if ($oldStatus !== $newStatus) {
                $history->execute([
                    $bugId, $user["id"], "status", $oldStatus, $newStatus
                ]);
            }

            if ($oldPriority !== $newPriority) {
                $history->execute([
                    $bugId, $user["id"], "priority", $oldPriority, $newPriority
                ]);
            }

            if ((int)$oldAssignee !== (int)$assignee) {
                $oldName = $bug["assignee_name"] ?? "Unassigned";

                $newName = "Unassigned";

                if ($assignee) {
                    $nameStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $nameStmt->execute([$assignee]);
                    $newName = $nameStmt->fetchColumn() ?: "Unassigned";
                }

                $history->execute([
                    $bugId, $user["id"], "assignee", $oldName, $newName
                ]);

                if ($assignee && (int)$assignee !== (int)$user["id"]) {
                    create_notification(
                        $assignee,
                        $bugId,
                        "Bug assigned to you",
                        "You have been assigned bug #" . $bugId . ": " . $bug["title"],
                        "bug.php?id=" . $bugId
                    );
                }
            }

            if ($oldStatus !== $newStatus) {
                $recipients = array_unique(array_filter([
                    (int)$bug["creator_id"],
                    $assignee ? (int)$assignee : null
                ]));

                foreach ($recipients as $recipientId) {
                    if ($recipientId !== (int)$user["id"]) {
                        create_notification(
                            $recipientId,
                            $bugId,
                            "Bug status updated",
                            "Bug #" . $bugId . " is now " . $newStatus . ".",
                            "bug.php?id=" . $bugId
                        );
                    }
                }
            }

            log_activity(
                "Bug Updated",
                "Updated bug #" . $bugId
            );

            $pdo->commit();

            set_message("Bug details updated successfully.");

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_message("Unable to update the bug.", "error");
        }

        redirect("bug.php?id=" . $bugId);
    }

    if ($action === "add_comment") {
        $comment = trim($_POST["comment"] ?? "");

        if ($comment === "" || strlen($comment) > 10000) {
            set_message("Enter a comment of up to 10,000 characters.", "error");
            redirect("bug.php?id=" . $bugId);
        }

        $stmt = $pdo->prepare("
            INSERT INTO comments (task_id, user_id, comment)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$bugId, $user["id"], $comment]);

        log_activity("Comment Added", "Comment added to bug #" . $bugId);

        $recipients = array_unique(array_filter([
            (int)$bug["creator_id"],
            $bug["assigned_to_id"] ? (int)$bug["assigned_to_id"] : null
        ]));

        foreach ($recipients as $recipientId) {
            if ($recipientId !== (int)$user["id"]) {
                create_notification(
                    $recipientId,
                    $bugId,
                    "New bug comment",
                    $user["username"] . " commented on bug #" . $bugId,
                    "bug.php?id=" . $bugId
                );
            }
        }

        set_message("Comment added.");
        redirect("bug.php?id=" . $bugId);
    }

    if ($action === "upload_attachment") {
        if (
            !isset($_FILES["attachment"]) ||
            $_FILES["attachment"]["error"] !== UPLOAD_ERR_OK
        ) {
            set_message("Select a valid file to upload.", "error");
            redirect("bug.php?id=" . $bugId);
        }

        $file = $_FILES["attachment"];
        $maxSize = 5 * 1024 * 1024;

        if ($file["size"] > $maxSize) {
            set_message("Maximum file size is 5 MB.", "error");
            redirect("bug.php?id=" . $bugId);
        }

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "application/pdf" => "pdf",
            "text/plain" => "txt"
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file["tmp_name"]);

        if (!isset($allowedTypes[$mime])) {
            set_message("Only JPG, PNG, GIF, PDF and TXT files are allowed.", "error");
            redirect("bug.php?id=" . $bugId);
        }

        $uploadDirectory = __DIR__ . "/uploads/bug_attachments/";

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mime];
        $destination = $uploadDirectory . $storedName;
        $relativePath = "uploads/bug_attachments/" . $storedName;

        if (!move_uploaded_file($file["tmp_name"], $destination)) {
            set_message("Unable to save the uploaded file.", "error");
            redirect("bug.php?id=" . $bugId);
        }

        $originalName = basename($file["name"]);

        $stmt = $pdo->prepare("
            INSERT INTO attachments
                (parent_type, parent_id, file_path, original_filename)
            VALUES ('task', ?, ?, ?)
        ");

        $stmt->execute([
            $bugId,
            $relativePath,
            $originalName
        ]);

        log_activity("Attachment Added", "File attached to bug #" . $bugId);

        set_message("Attachment uploaded successfully.");
        redirect("bug.php?id=" . $bugId);
    }
}

$bug = get_bug_record($pdo, $bugId);

$developers = $pdo->query("
    SELECT id, username FROM users ORDER BY username
")->fetchAll();

$stmt = $pdo->prepare("
    SELECT c.*, u.username
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.task_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$bugId]);
$comments = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT *
    FROM attachments
    WHERE parent_type = 'task' AND parent_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$bugId]);
$attachments = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT h.*, u.username
    FROM task_history h
    LEFT JOIN users u ON u.id = h.user_id
    WHERE h.task_id = ?
    ORDER BY h.changed_at DESC
");
$stmt->execute([$bugId]);
$history = $stmt->fetchAll();

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Bug #<?= (int)$bug["id"] ?></h1>
        <p>Bug details, discussion and activity history.</p>
    </div>
    <a href="search.php" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to Search
    </a>
</div>

<div class="bug-detail-grid">
    <section class="form-card bug-main-card">
        <div class="bug-detail-title">
            <h2><?= e($bug["title"]) ?></h2>
            <div class="bug-badges">
                <span class="priority-badge <?= e(get_priority_class($bug["priority"])) ?>">
                    <?= e($bug["priority"]) ?> Priority
                </span>
                <span class="status-badge <?= e(get_status_class($bug["status"])) ?>">
                    <?= e($bug["status"]) ?>
                </span>
            </div>
        </div>

        <div class="bug-description">
            <?= nl2br(e($bug["description"])) ?>
        </div>

        <div class="bug-meta-grid">
            <div><span>Project</span><strong><?= e($bug["project_name"]) ?></strong></div>
            <div><span>Reported By</span><strong><?= e($bug["creator_name"]) ?></strong></div>
            <div><span>Assigned To</span><strong><?= e($bug["assignee_name"] ?? "Unassigned") ?></strong></div>
            <div><span>Created</span><strong><?= e(date("d M Y, h:i A", strtotime($bug["created_at"]))) ?></strong></div>
            <div><span>Last Updated</span><strong><?= e(date("d M Y, h:i A", strtotime($bug["updated_at"]))) ?></strong></div>
        </div>

        <div class="detail-section">
            <h3>Update Bug</h3>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_bug">

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (["Open", "In Progress", "Resolved"] as $value): ?>
                            <option value="<?= e($value) ?>" <?= $bug["status"] === $value ? "selected" : "" ?>>
                                <?= e($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <?php foreach (["High", "Medium", "Low"] as $value): ?>
                            <option value="<?= e($value) ?>" <?= $bug["priority"] === $value ? "selected" : "" ?>>
                                <?= e($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Assigned Developer</label>
                    <select name="assigned_to_id">
                        <option value="0">Unassigned</option>
                        <?php foreach ($developers as $developer): ?>
                            <option value="<?= (int)$developer["id"] ?>"
                                <?= (int)$bug["assigned_to_id"] === (int)$developer["id"] ? "selected" : "" ?>>
                                <?= e($developer["username"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </form>
        </div>
    </section>

    <aside class="bug-side-column">
        <section class="form-card">
            <h3 class="detail-section-title">Attachments</h3>

            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="upload_attachment">

                <div class="form-group">
                    <label>Select File</label>
                    <input
                        type="file"
                        name="attachment"
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.txt"
                        required
                    >
                    <small class="form-hint">JPG, PNG, GIF, PDF or TXT. Maximum 5 MB.</small>
                </div>

                <button class="btn btn-primary btn-small" type="submit">
                    <i class="bi bi-paperclip"></i> Upload File
                </button>
            </form>

            <div class="attachment-list">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="attachment-item">
                        <i class="bi bi-file-earmark"></i>
                        <a href="<?= e($attachment["file_path"]) ?>" target="_blank" rel="noopener">
                            <?= e($attachment["original_filename"] ?? "Attachment") ?>
                        </a>
                    </div>
                <?php endforeach; ?>

                <?php if (!$attachments): ?>
                    <p class="muted-text">No attachments uploaded.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-card">
            <h3 class="detail-section-title">Activity Timeline</h3>

            <div class="activity-list">
                <div class="activity-item">
                    <span class="activity-dot"></span>
                    <div>
                        <strong>Bug reported</strong>
                        <p>By <?= e($bug["creator_name"]) ?></p>
                        <small><?= e(date("d M Y, h:i A", strtotime($bug["created_at"]))) ?></small>
                    </div>
                </div>

                <?php foreach ($history as $entry): ?>
                    <div class="activity-item">
                        <span class="activity-dot"></span>
                        <div>
                            <strong><?= e(ucfirst($entry["field_name"])) ?> changed</strong>
                            <p>
                                <?= e($entry["old_value"] ?? "None") ?>
                                &rarr;
                                <?= e($entry["new_value"] ?? "None") ?>
                            </p>
                            <small>
                                <?= e($entry["username"] ?? "System") ?> ·
                                <?= e(date("d M Y, h:i A", strtotime($entry["changed_at"]))) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </aside>
</div>

<section class="form-card discussion-card">
    <h3 class="detail-section-title">Discussion (<?= count($comments) ?>)</h3>

    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_comment">

        <div class="form-group">
            <label>Add a Comment</label>
            <textarea name="comment" rows="4" maxlength="10000"
                placeholder="Write your comment..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-chat-dots"></i> Post Comment
        </button>
    </form>

    <div class="comment-list">
        <?php foreach ($comments as $comment): ?>
            <article class="comment-item">
                <div class="comment-avatar">
                    <?= e(strtoupper(substr($comment["username"], 0, 1))) ?>
                </div>
                <div>
                    <div class="comment-heading">
                        <strong><?= e($comment["username"]) ?></strong>
                        <small><?= e(date("d M Y, h:i A", strtotime($comment["created_at"]))) ?></small>
                    </div>
                    <p><?= nl2br(e($comment["comment"])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$comments): ?>
            <p class="muted-text">No comments yet. Start the discussion above.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once "includes/footer.php"; ?>
