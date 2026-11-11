<?php

require_once "includes/functions.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $priority = $_POST["priority"] ?? "";

    $allowedPriorities = ["Low", "Medium", "High"];

    if ($title === "" || $description === "") {

        set_message("Please complete all required fields.", "error");
        redirect("report_bug.php");

    } elseif (!in_array($priority, $allowedPriorities, true)) {

        set_message("Please select a valid priority.", "error");
        redirect("report_bug.php");

    } else {

        try {

            $sql = "
                INSERT INTO tasks
                (
                    title,
                    description,
                    type,
                    creator_id,
                    assigned_to_id,
                    status,
                    priority,
                    created_at,
                    updated_at,
                    is_archived
                )
                VALUES
                (
                    :title,
                    :description,
                    'Bug',
                    NULL,
                    NULL,
                    'Open',
                    :priority,
                    NOW(),
                    NOW(),
                    0
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":title" => $title,
                ":description" => $description,
                ":priority" => $priority
            ]);

            $bugId = $pdo->lastInsertId();

            set_message("Bug report submitted successfully. Initial status: Open.");

            redirect("active_bugs.php");

        } catch (PDOException $e) {

            set_message("Unable to submit the bug report. Please check your database structure.", "error");
            redirect("report_bug.php");
        }
    }
}

require_once "includes/header.php";

?>

<div class="page-heading">
    <div>
        <h1>Report a Bug</h1>
        <p>Submit a new issue identified during project development.</p>
    </div>
</div>

<div class="form-card">

    <div class="form-card-heading">
        <div class="form-heading-icon">
            <i class="bi bi-bug"></i>
        </div>

        <div>
            <h2>New Bug Report</h2>
            <p>Provide details about the issue you encountered.</p>
        </div>
    </div>

    <form method="POST" action="report_bug.php">

        <div class="form-group">
            <label for="title">Bug Title <span>*</span></label>

            <input
                type="text"
                id="title"
                name="title"
                placeholder="e.g. Login button does not respond"
                maxlength="255"
                required
            >
        </div>

        <div class="form-group">
            <label for="description">Description <span>*</span></label>

            <textarea
                id="description"
                name="description"
                rows="6"
                placeholder="Describe the problem, what happened and how it affects the system..."
                required
            ></textarea>
        </div>

        <div class="form-group">
            <label for="priority">Priority <span>*</span></label>

            <select id="priority" name="priority" required>
                <option value="">Select priority</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>

            <small class="form-hint">
                High: Requires urgent attention. Medium: Normal attention.
                Low: Minor issue.
            </small>
        </div>

        <div class="form-group">
            <label>Initial Status</label>

            <div class="initial-status">
                <span class="status-badge status-open">Open</span>
                <span>All newly reported bugs automatically start as Open.</span>
            </div>
        </div>

        <div class="form-group">
            <label>Date/Time Reported</label>

            <input
                type="text"
                value="<?= date('d M Y, h:i A') ?>"
                disabled
            >
            <small class="form-hint">
                The date and time will be recorded automatically when submitted.
            </small>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-light">Cancel</a>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send"></i>
                Submit Bug Report
            </button>
        </div>

    </form>

</div>

<?php require_once "includes/footer.php"; ?>