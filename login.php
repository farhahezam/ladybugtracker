
<?php
require_once "includes/functions.php";

if (current_user()) {
    redirect("index.php");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $identity = trim($_POST["identity"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash
        FROM users
        WHERE username = :username OR email = :email
        LIMIT 1
    ");

    $stmt->execute([
        "username" => $identity,
        "email" => $identity
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password_hash"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = (int) $user["id"];
        unset($_SESSION["csrf_token"]);

        log_activity("User Login", "User logged in.");

        redirect("index.php");
    }

    $error = "Invalid username/email or password.";
}

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Welcome Back</h1>
        <p>Log in to access your LadyBug Tracker workspace.</p>
    </div>
</div>

<div class="form-card">
    <div class="form-card-heading">
        <div class="form-heading-icon">
            <i class="bi bi-person-lock"></i>
        </div>
        <div>
            <h2>Account Login</h2>
            <p>Enter your account details below.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Username or Email</label>
            <input
                type="text"
                name="identity"
                value="<?= e($_POST["identity"] ?? "") ?>"
                required
            >
        </div>

        <div class="form-group">
            <label>Password</label>
            <input
                type="password"
                name="password"
                required
            >
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i>
                Login
            </button>
        </div>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>
