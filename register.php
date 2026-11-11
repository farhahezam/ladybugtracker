<?php
include 'includes/db.php';
include 'includes/header.php';
$errors = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    if ($name === '') $errors[] = 'Name required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if (strlen($password) < 4) $errors[] = 'Password at least 4 chars';
    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT UserID FROM users WHERE Email=?');
        $stmt->bind_param('s',$email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered';
        } else {
            $stmt = $mysqli->prepare('INSERT INTO users (Name,Email,Password,Role) VALUES (?,?,?,"user")');
            $stmt->bind_param('sss',$name,$email,$password);
            $stmt->execute();
            echo '<div class="alert alert-success">Registration successful. <a href="login.php">Login</a></div>';
        }
    }
}
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="mb-3">Register</h2>
        <?php if ($errors): ?>
          <div class="alert alert-danger"><ul><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input class="form-control" name="password" type="password" required></div>
          <button class="btn btn-primary" type="submit">Register</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
