<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$active_page = 'notifications';

// Determine what to show based on role
$show_all = in_array($user_role, ['admin', 'superadmin']);

if ($show_all) {
    // Admin: Show all notifications in the system
    $sql = "
        SELECT 
            n.*,
            u.full_name as created_by_name,
            COUNT(DISTINCT un.user_id) as recipient_count,
            SUM(CASE WHEN un.is_read = TRUE THEN 1 ELSE 0 END) as read_count
        FROM notification n
        LEFT JOIN users u ON n.created_by = u.id
        LEFT JOIN user_notification un ON n.notification_id = un.notification_id
        GROUP BY n.notification_id
        ORDER BY n.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
} else {
    // Regular users: Show only notifications sent TO them
    $sql = "
        SELECT 
            n.*,
            un.is_read,
            un.read_at,
            u.full_name as created_by_name
        FROM user_notification un
        JOIN notification n ON un.notification_id = n.notification_id
        LEFT JOIN users u ON n.created_by = u.id
        WHERE un.user_id = ?
        ORDER BY n.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$notifications = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Count unread for current user (even for admins)
$unread_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM user_notification 
    WHERE user_id = ? AND is_read = FALSE
");
$unread_stmt->bind_param('i', $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['count'];

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4>
                <i class="bi bi-bell-fill"></i> 
                <?= $show_all ? 'All Notifications' : 'My Notifications' ?>
            </h4>
            <?php if (!$show_all && $unread_count > 0): ?>
                <p class="text-muted">You have <strong class="text-danger"><?= $unread_count ?></strong> unread notification(s)</p>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
            <?php if (in_array($user_role, ['admin', 'superadmin', 'faculty', 'teacher'])): ?>
                <a href="create_notification.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Notification
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
                <h5 class="mt-3 text-muted">No notifications found</h5>
                <p class="text-muted"><?= $show_all ? 'No notifications have been created yet.' : "You're all caught up!" ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Type</th>
                                <?php if ($show_all): ?>
                                    <th>Scope</th>
                                    <th>Recipients</th>
                                    <th>Read Rate</th>
                                    <th>Created By</th>
                                <?php else: ?>
                                    <th>Status</th>
                                    <th>From</th>
                                <?php endif; ?>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; foreach ($notifications as $notif): ?>
                                <tr class="<?= (!$show_all && !$notif['is_read']) ? 'table-primary' : '' ?>">
                                    <td><?= $serial++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                        <?php if (!$show_all && !$notif['is_read']): ?>
                                            <span class="badge bg-danger ms-2">New</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(substr($notif['message'], 0, 50)) ?>...
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $notif['notification_type'] === 'alert' ? 'danger' : 
                                            ($notif['notification_type'] === 'warning' ? 'warning' : 
                                            ($notif['notification_type'] === 'assignment' ? 'primary' : 
                                            ($notif['notification_type'] === 'exam' ? 'info' : 'secondary'))) 
                                        ?>">
                                            <?= ucfirst($notif['notification_type']) ?>
                                        </span>
                                    </td>
                                    
                                    <?php if ($show_all): ?>
                                        <!-- Admin view -->
                                        <td>
                                            <?php if ($notif['is_general']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-people-fill"></i> All Users
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-person"></i> Specific
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= $notif['recipient_count'] ?> users</span>
                                        </td>
                                        <td>
                                            <?php 
                                            $read_percentage = $notif['recipient_count'] > 0 
                                                ? round(($notif['read_count'] / $notif['recipient_count']) * 100) 
                                                : 0;
                                            ?>
                                            <div class="progress" style="height: 20px; width: 100px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?= $read_percentage ?>%">
                                                    <?= $read_percentage ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($notif['created_by_name'] ?? 'System') ?></small>
                                        </td>
                                    <?php else: ?>
                                        <!-- Regular user view -->
                                        <td>
                                            <?php if ($notif['is_read']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check2-circle"></i> Read
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-circle"></i> Unread
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($notif['created_by_name'] ?? 'System') ?></small>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($notif['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_notification.php?id=<?= $notif['notification_id'] ?>" 
                                               class="btn btn-info"
                                               title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($show_all): ?>
                                                <a href="delete_notification.php?id=<?= $notif['notification_id'] ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure?')"
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "Notifications - LMS";
require_once '../../templates/layout/master_base.php';
?>