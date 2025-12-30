<?php
session_start();
require "dbconfig.php";

if (!isset($_SESSION['user_code'])) {
    die("Access denied. Please log in.");
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department = $_SESSION['department'] ?? null;

// Fetch notifications for user:
// 1. Targeted directly via notification_targets
// 2. Targeted by role
// 3. Targeted by department
$sql = "SELECT n.* 
        FROM notifications n
        LEFT JOIN notification_targets nt ON n.id = nt.notification_id
        WHERE nt.user_id = ? 
           OR (n.target_role IS NULL OR n.target_role = ?) 
           OR (n.department IS NULL OR n.department = ?)
        GROUP BY n.id
        ORDER BY n.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $role, $department);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - LMS</title>
    <link href="../node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2>Notifications</h2>
    <div class="list-group mt-3">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="list-group-item">
                    <h5><?= htmlspecialchars($row['title']) ?></h5>
                    <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                    <small class="text-muted">Posted: <?= $row['created_at'] ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="list-group-item">
                No notifications found.
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
