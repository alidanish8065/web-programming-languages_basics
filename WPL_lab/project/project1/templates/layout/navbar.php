<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/project1/bootstrap.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = 'Guest';
$role = 'guest';
$user_code = '';
$profile_image_url = url('public/male_avatar.jpeg');

// Fetch user info if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch full_name and role
    $stmt = $conn->prepare("
        SELECT u.full_name, r.role_name
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $name = $row['full_name'];
        $role = $row['role_name'] ?? 'guest';
        $_SESSION['role'] = $role;       // update session to avoid future inconsistencies
        $_SESSION['full_name'] = $name;
    }

    $stmt->close();

    $user_code = $_SESSION['user_code'] ?? '';



    // Fetch profile image if exists
    if (!empty($_SESSION['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'].'/project1/public/uploads/profiles/' . $_SESSION['profile_image'])) {
        $profile_image_url = url('public/uploads/profiles/' . $_SESSION['profile_image']);
    }

    // Notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_notification WHERE user_id=? AND is_read=FALSE");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $unread_count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT n.*, un.is_read
        FROM user_notification un
        JOIN notification n ON un.notification_id=n.notification_id
        WHERE un.user_id=?
        ORDER BY n.created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $unread_count = 0;
    $notifications = [];
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid">
        <button class="btn btn-primary " id="sidebarToggle">☰</button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        🔔
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7rem;">
                            <?= $unread_count > 9 ? '9+' : $unread_count ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 350px; max-height: 400px; overflow-y:auto;">
                        <?php if (empty($notifications)): ?>
                            <li class="text-center text-muted p-2">No notifications</li>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <li class="dropdown-item d-flex justify-content-between align-items-start 
                                           <?= $notif['is_read'] ? 'text-muted opacity-50' : 'fw-bold bg-light' ?>" 
                                    id="notif-<?= $notif['notification_id'] ?>" 
                                    style="border-radius:5px; margin-bottom:5px;">
                                    <div>
                                        <?= htmlspecialchars(substr($notif['title'], 0, 40)) ?>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge bg-danger ms-1">New</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></small>
                                    </div>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button class="btn btn-success btn-sm" onclick="markAsRead(<?= $notif['notification_id'] ?>)">✔</button>
                                        <button class="btn btn-info btn-sm" onclick="window.location='view_notification.php?id=<?= $notif['notification_id'] ?>'">👁</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteNotification(<?= $notif['notification_id'] ?>)">🗑</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- User Name -->
                <li class="nav-item me-2">
                    <span class="text-muted"><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($role) ?>)</span>
                </li>

                <!-- Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link p-0" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Profile Menu">
                        <img src="<?= $profile_image_url ?>" alt="Profile" class="rounded-circle border border-2 border-primary" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= url('public/profile.php') ?>">👤 My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= url('public/login_and_authentication/logout.php') ?>">🚪 Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
function markAsRead(notificationId) {
    fetch('mark_as_read.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'notification_id=' + notificationId
    }).then(res => res.json()).then(data => {
        if(data.success){
            const li = document.getElementById('notif-' + notificationId);
            li.classList.add('text-muted', 'opacity-50');
            li.classList.remove('fw-bold', 'bg-light');
            li.querySelector('.badge.bg-danger')?.remove();
        } else {
            alert(data.message);
        }
    });
}

function deleteNotification(notificationId) {
    if(!confirm('Are you sure you want to delete this notification?')) return;
    fetch('delete_notification.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'notification_id=' + notificationId
    }).then(res => res.json()).then(data => {
        if(data.success){
            const li = document.getElementById('notif-' + notificationId);
            li.remove();
        } else {
            alert(data.message);
        }
    });
}
</script>
