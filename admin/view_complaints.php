<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (empty($_SESSION['admin'])) { header('Location: /cms/admin/admin_login.php'); exit; }
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$where = array(); $sql = 'SELECT c.*, u.Name AS UserName, u.Email AS UserEmail FROM complaints c JOIN users u ON c.UserID=u.UserID';
if ($status !== '') $where[] = "c.Status='".$mysqli->real_escape_string($status)."'";
if ($category !== '') $where[] = "c.Category='".$mysqli->real_escape_string($category)."'";
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= ' ORDER BY c.Date DESC';
$res = $mysqli->query($sql);
?>
<h2>All Complaints</h2>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-3">
    <select name="status" class="form-select"><option value="">Status: Any</option><?php foreach(['Pending','In-Progress','Resolved'] as $s) echo '<option'.(($status===$s)?' selected':'').'>'.htmlspecialchars($s).'</option>'; ?></select>
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select"><option value="">Category: Any</option><?php foreach(['IT','Hostel','Accounts','Library','Other'] as $cat) echo '<option'.(($category===$cat)?' selected':'').'>'.htmlspecialchars($cat).'</option>'; ?></select>
  </div>
  <div class="col-md-3"><button class="btn btn-secondary" type="submit">Filter</button> <a class="btn btn-outline-secondary" href="view_complaints.php">Clear</a></div>
</form>
<div class="table-responsive bg-white rounded-4 shadow-sm p-3">
  <table class="table table-striped align-middle">
    <thead><tr><th>User</th><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><div class="small fw-bold"><?= htmlspecialchars($r['UserName']) ?></div><div class="small text-muted"><?= htmlspecialchars($r['UserEmail']) ?></div></td>
          <td><?= htmlspecialchars($r['Title']) ?></td>
          <td><?= htmlspecialchars($r['Category']) ?></td>
          <td><?= htmlspecialchars($r['Status']) ?></td>
          <td><?= htmlspecialchars($r['Date']) ?></td>
          <td><a class="btn btn-sm btn-primary" href="reply_complaint.php?id=<?= $r['ComplaintID'] ?>">Reply / Update</a></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<a class="btn btn-link mt-3" href="dashboard.php">Back</a>
<?php include __DIR__ . '/../includes/footer.php'; ?>
