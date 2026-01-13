<?php
// master_base.php - LMS Master Template
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('public/login.php'));
    exit;
}

// Session variables - FIXED
$id         = $_SESSION['user_id'];
$user_code  = $_SESSION['user_code'] ?? '';
$first_name = $_SESSION['first_name'] ?? 'Guest';
$last_name  = $_SESSION['last_name'] ?? '';
$name       = $first_name . ' ' . $last_name;  // BUILD IT FROM first_name + last_name
$role       = $_SESSION['role'] ?? 'guest';

// Optional: Set profile image if exists
$profile_image = null;
if ($role === 'student') {
    // Fetch profile image from database if needed
    $stmt = $conn->prepare("SELECT u.profile_image FROM users u WHERE u.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $profile_image = $row['profile_image'];
    }

}

// Set default active page if not set
if (!isset($active_page)) {
    $active_page = 'home';
}

// Set default page title if not set
if (!isset($page_title)) {
    $page_title = ucfirst($role) . ' Dashboard';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - LMS</title>
    <link href="<?= url('node_modules/bootstrap/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('node_modules/bootstrap-icons/font/bootstrap-icons.css') ?>">
    <link rel="stylesheet" href="<?= url('css/styles.css') ?>">
</head>
<body>
<div class="d-flex" id="wrapper">

    <!-- Sidebar -->
    <?php include include_file('templates/layout/sidebar.php'); ?>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100">

        <!-- Navbar -->
        <?php include include_file('templates/layout/navbar.php'); ?>

        <!-- Dynamic Content -->
        <div class="container-fluid px-4 mt-3">
            <?= $content ?? '' ?>
        </div>

        <!-- Footer -->
        <?php include include_file('templates/layout/footer.php'); ?>

    </div>
</div>

<script src="<?= url('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('js/script.js') ?>"></script>
<?= $extra_scripts ?? '' ?>
</body>
</html>