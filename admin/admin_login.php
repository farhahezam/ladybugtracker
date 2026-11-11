<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $mysqli->prepare('SELECT UserID,Name,Email,Password,Role FROM users WHERE Email=? AND Role="admin"');
    $stmt->bind_param('s',$email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($password === $row['Password']) {
            $_SESSION['admin'] = array('id'=>$row['UserID'],'name'=>$row['Name'],'email'=>$row['Email']);
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Invalid admin credentials';
        }
    } else {
        $error = 'Invalid admin credentials';
    }
}
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2>Admin Login</h2>
        <?php if ($error) echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>'; ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input class="form-control" name="password" type="password" required></div>
          <button class="btn btn-primary" type="submit">Login</button>
          <a class="btn btn-link" href="/cms/login.php">User Login</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
