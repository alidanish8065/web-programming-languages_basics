<?php
// master_base.php - LMS Template
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../public/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php');
    exit;
}

// Session variables
$id        = $_SESSION['user_id'];
$user_code = $_SESSION['user_code'] ?? '';
$name      = $_SESSION['name'] ?? 'Guest';
$role      = $_SESSION['role'] ?? 'guest';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ucfirst($role) ?> Dashboard</title>
<link href="../../node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
<div class="d-flex" id="wrapper">

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="user-profile text-center p-3">
            <img src="../../public/male_avatar.jpeg" alt="User Avatar" class="rounded-circle mb-2">
            <h6 class="mb-0"><?= htmlspecialchars($name) ?></h6>
            <?php if ($role === 'student') : ?>
                <small class="text-muted"><?= htmlspecialchars($user_code) ?></small>
            <?php endif; ?>
        </div>
        <div class="sidebar-heading px-3 py-2">LMS - <?= ucfirst($role) ?> Portal</div>
        <div class="list-group list-group-flush">
            <a href="../../roles/admin/dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
            <?php if($role === 'admin' || $role === 'superadmin'): ?>
                <a href="../../admin_tools/User/user_list.php" class="list-group-item list-group-item-action">Manage Users</a>
                <a href="../../admin_tools/create_course.php" class="list-group-item list-group-item-action">Create Course</a>
                <a href="../../admin_tools/manage_courses.php" class="list-group-item list-group-item-action">Manage Courses</a>
                <a href="../../public/notification.php" class="list-group-item list-group-item-action">Notifications</a>
            <?php endif; ?>
            <a href="../../public/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100">

        <!-- Navbar -->
        <?php include 'navbar.php'; ?>

        <!-- Dynamic Content -->
        <div class="container-fluid px-4 mt-3">
            <?= $content ?? '' ?>
        </div>

    </div>
</div>

<script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../js/script.js"></script>
</body>
</html>
