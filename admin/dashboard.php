<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (empty($_SESSION['admin'])) { header('Location: /cms/admin/admin_login.php'); exit; }
$total = $mysqli->query('SELECT COUNT(*) AS c FROM complaints')->fetch_assoc()['c'];
$pending = $mysqli->query("SELECT COUNT(*) AS c FROM complaints WHERE Status='Pending'")->fetch_assoc()['c'];
$inprog = $mysqli->query("SELECT COUNT(*) AS c FROM complaints WHERE Status='In-Progress'")->fetch_assoc()['c'];
$resolved = $mysqli->query("SELECT COUNT(*) AS c FROM complaints WHERE Status='Resolved'")->fetch_assoc()['c'];
?>
<h2>Admin Dashboard</h2>
<div class="row g-3">
  <?php foreach ([['Total',$total],['Pending',$pending],['In-Progress',$inprog],['Resolved',$resolved]] as $card): ?>
    <div class="col-md-3">
      <div class="p-4 bg-white rounded-4 shadow-sm">
        <div class="text-muted small"><?= htmlspecialchars($card[0]) ?></div>
        <div class="fs-2 fw-bold"><?= htmlspecialchars($card[1]) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<a class="btn btn-primary mt-3" href="view_complaints.php">Manage Complaints</a>
<?php include __DIR__ . '/../includes/footer.php'; ?>
