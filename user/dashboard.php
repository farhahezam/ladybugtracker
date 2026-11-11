<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user'])) { header('Location: /cms/login.php'); exit; }
?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="p-4 bg-white rounded-4 shadow-sm h-100">
      <h3>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></h3>
      <p class="text-muted">File and view your complaints.</p>
      <a class="btn btn-primary me-2" href="file_complaint.php">File a Complaint</a>
      <a class="btn btn-outline-secondary" href="my_complaints.php">My Complaints</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
