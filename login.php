<?php
include 'includes/db.php';
include 'includes/header.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $mysqli->prepare('SELECT UserID,Name,Email,Password,Role FROM users WHERE Email=?');
    $stmt->bind_param('s',$email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($password === $row['Password']) {
            if ($row['Role'] === 'admin') {
                $_SESSION['admin'] = array('id'=>$row['UserID'],'name'=>$row['Name'],'email'=>$row['Email']);
                header('Location: admin/dashboard.php'); exit;
            } else {
                $_SESSION['user'] = array('id'=>$row['UserID'],'name'=>$row['Name'],'email'=>$row['Email']);
                header('Location: user/dashboard.php'); exit;
            }
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="mb-3">Login</h2>
        <?php if ($error) echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>'; ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input class="form-control" name="password" type="password" required></div>
          <button class="btn btn-primary" type="submit">Login</button>
          <a class="btn btn-link" href="register.php">Register</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
