<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user'])) { header('Location: /cms/login.php'); exit; }
$errors = array(); $success='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    if ($title === '' || $description === '' || $category === '') $errors[] = 'All fields required';
    if (empty($errors)) {
        $stmt = $mysqli->prepare('INSERT INTO complaints (UserID,Title,Description,Category,Status,Reply) VALUES (?,?,?,?,"Pending","")');
        $stmt->bind_param('isss', $_SESSION['user']['id'], $title, $description, $category);
        $stmt->execute();
        $success = 'Complaint submitted.';
    }
}
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2>File a Complaint</h2>
        <?php if ($errors) echo '<div class="alert alert-danger"><ul>'.implode('',array_map('htmlspecialchars',$errors)).'</ul></div>'; ?>
        <?php if ($success) echo '<div class="alert alert-success">'.htmlspecialchars($success).'</div>'; ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="5" required></textarea></div>
          <div class="mb-3"><label class="form-label">Category</label><select class="form-select" name="category" required><option value="">--Select--</option><option>IT</option><option>Hostel</option><option>Accounts</option><option>Library</option><option>Other</option></select></div>
          <button class="btn btn-primary" type="submit">Submit</button>
          <a class="btn btn-link" href="dashboard.php">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
