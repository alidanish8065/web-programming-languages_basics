<?php
// roles/student/dashboard.php

$page_title = "Student Dashboard";
$active_page = "home";

// Prepare content for dashboard
ob_start();
?>
<h4>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h4>
<p>Your courses and notifications will appear here.</p>

<?php if (!empty($notifications)): ?>
    <h5 class="mt-4">Notifications</h5>
    <div class="row">
        <?php foreach ($notifications as $note) : ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($note['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($note['message']) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($note['created_at']) ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

include '../../templates/layout/master_base.php';
