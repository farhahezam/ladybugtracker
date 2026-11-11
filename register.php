
<?php
require_once "includes/functions.php";

if (current_user()) {
    redirect("index.php");
}

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (!preg_match('/^[a-zA-Z0-9_]{3,80}$/', $username)) {
        $errors[] = "Username must be 3 to 80 characters and use letters, numbers or underscores.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (!$errors) {
        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        $check->execute([$username, $email]);

        if ($check->fetch()) {
            $errors[] = "Username or email is already registered.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO users
                    (username, email, password_hash, is_admin)
                VALUES (?, ?, ?, 0)
            ");

            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $success = true;
        }
    }
}

require_once "includes/header.php";
?>

<div class="page-heading">
    <div>
        <h1>Create Account</h1>
        <p>Register a developer account for LadyBug Tracker.</p>
    </div>
</div>

<div class="form-card">
    <div class="form-card-heading">
        <div class="form-heading-icon">
            <i class="bi bi-person-plus"></i>
        </div>
        <div>
            <h2>New Developer Account</h2>
            <p>Complete the details to register.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Registration successful.
            <a href="login.php">Log in here</a>.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
        <form method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Username</label>
                <input
                    type="text"
                    name="username"
                    maxlength="80"
                    required
                >
            </div>

            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    maxlength="190"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Create Account
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>
