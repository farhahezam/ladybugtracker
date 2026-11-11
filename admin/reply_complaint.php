<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (empty($_SESSION['admin'])) { header('Location: /cms/admin/admin_login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
$stmt = $mysqli->prepare('SELECT c.*, u.Name AS UserName, u.Email AS UserEmail FROM complaints c JOIN users u ON c.UserID=u.UserID WHERE ComplaintID=?');
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$c = $res->fetch_assoc();
if (!$c) { echo '<div class="alert alert-danger">Complaint not found</div>'; include __DIR__ . '/../includes/footer.php'; exit; }
$success=''; if ($_SERVER['REQUEST_METHOD']==='POST') {
    $status = $_POST['status']; $reply = trim($_POST['reply']);
    $upd = $mysqli->prepare('UPDATE complaints SET Status=?, Reply=? WHERE ComplaintID=?');
    $upd->bind_param('ssi',$status,$reply,$id); $upd->execute();
    $success = 'Updated.';
    $stmt->execute(); $res = $stmt->get_result(); $c = $res->fetch_assoc();
}
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2>Reply / Update</h2>
        <?php if ($success) echo '<div class="alert alert-success">'.htmlspecialchars($success).'</div>'; ?>
        <div class="mb-3 p-3 bg-light rounded">
          <div><strong>User:</strong> <?= htmlspecialchars($c['UserName']) ?> (<?= htmlspecialchars($c['UserEmail']) ?></div>
          <div><strong>Title:</strong> <?= htmlspecialchars($c['Title']) ?></div>
          <div class="mt-2"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($c['Description'])) ?></div>
        </div>
        <form method="post">
          <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['Pending','In-Progress','Resolved'] as $s) echo '<option'.(($c['Status']===$s)?' selected':'').'>'.htmlspecialchars($s).'</option>'; ?></select></div>
          <div class="mb-3"><label class="form-label">Reply</label><textarea name="reply" rows="5" class="form-control"><?= htmlspecialchars($c['Reply']) ?></textarea></div>
          <button class="btn btn-primary" type="submit">Save</button> <a class="btn btn-link" href="view_complaints.php">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
