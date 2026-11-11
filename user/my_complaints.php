<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user'])) { header('Location: /cms/login.php'); exit; }
$stmt = $mysqli->prepare('SELECT * FROM complaints WHERE UserID=? ORDER BY Date DESC');
$stmt->bind_param('i', $_SESSION['user']['id']);
$stmt->execute();
$res = $stmt->get_result();
?>
<h2>My Complaints</h2>
<div class="table-responsive bg-white rounded-4 shadow-sm p-3">
  <table class="table table-striped align-middle">
    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Reply</th><th>Date</th></tr></thead>
    <tbody>
      <?php while ($c = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($c['Title']) ?></td>
          <td><?= htmlspecialchars($c['Category']) ?></td>
          <td><?= htmlspecialchars($c['Status']) ?></td>
          <td><?= nl2br(htmlspecialchars($c['Reply'])) ?></td>
          <td><?= htmlspecialchars($c['Date']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<a class="btn btn-link mt-3" href="dashboard.php">Back</a>
<?php include __DIR__ . '/../includes/footer.php'; ?>
