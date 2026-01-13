<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid notification ID.");
}

$notification_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch notification details
$sql = "
    SELECT 
        n.*,
        un.is_read,
        un.read_at,
        u.full_name as created_by_name
    FROM notification n
    JOIN user_notification un ON n.notification_id = un.notification_id
    LEFT JOIN users u ON n.created_by = u.id
    WHERE n.notification_id = ? AND un.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $notification_id, $user_id);
$stmt->execute();
$notification = $stmt->get_result()->fetch_assoc();

if (!$notification) {
    die("Notification not found or you don't have access to it.");
}

// Mark as read if not already
if (!$notification['is_read']) {
    $update_stmt = $conn->prepare("
        UPDATE user_notification 
        SET is_read = TRUE, read_at = NOW() 
        WHERE notification_id = ? AND user_id = ?
    ");
    $update_stmt->bind_param('ii', $notification_id, $user_id);
    $update_stmt->execute();
}

$active_page = 'notifications';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-<?= 
                    $notification['notification_type'] === 'alert' ? 'danger' : 
                    ($notification['notification_type'] === 'warning' ? 'warning' : 
                    ($notification['notification_type'] === 'assignment' ? 'primary' : 
                    ($notification['notification_type'] === 'exam' ? 'info' : 'secondary'))) 
                ?> text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-bell-fill"></i> 
                            <?= htmlspecialchars($notification['title']) ?>
                        </h4>
                        <a href="notification_list.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <span class="badge bg-<?= 
                            $notification['notification_type'] === 'alert' ? 'danger' : 
                            ($notification['notification_type'] === 'warning' ? 'warning' : 
                            ($notification['notification_type'] === 'assignment' ? 'primary' : 
                            ($notification['notification_type'] === 'exam' ? 'info' : 'secondary'))) 
                        ?> me-2">
                            <?= ucfirst($notification['notification_type']) ?>
                        </span>
                        
                        <small class="text-muted">
                            <i class="bi bi-clock"></i>
                            <?= date('l, F d, Y \a\t h:i A', strtotime($notification['created_at'])) ?>
                        </small>
                        
                        <?php if ($notification['created_by_name']): ?>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-person"></i>
                                From: <?= htmlspecialchars($notification['created_by_name']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-message p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($notification['message'])) ?>
                    </div>
                    
                    <?php if ($notification['read_at']): ?>
                        <div class="mt-4 text-muted">
                            <small>
                                <i class="bi bi-check2-circle text-success"></i>
                                Read on <?= date('M d, Y \a\t h:i A', strtotime($notification['read_at'])) ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = "Notification - LMS";
require_once '../../templates/layout/master_base.php';
?>