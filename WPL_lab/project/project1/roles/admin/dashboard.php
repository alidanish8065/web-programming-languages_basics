<?php
// dashboard.php - Simplified LMS Dashboard Content
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php');
    exit;
}

$id        = $_SESSION['user_id'];
$user_code = $_SESSION['user_code'] ?? '';
$first_name = $_SESSION['first_name'] ?? 'Guest';
$last_name  = $_SESSION['last_name'] ?? '';
$role      = $_SESSION['role'] ?? 'guest';

$courses = [];
$notifications = [];

// Fetch courses for student or faculty
// Fetch courses for student or faculty

// Fetch notifications targeted to role or specific user
$stmt = $conn->prepare("
    SELECT n.title, n.message, n.created_at, un.is_read, un.read_at
    FROM user_notification un
    JOIN notification n ON un.notification_id = n.notification_id
    WHERE un.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Dashboard content
ob_start();
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($first_name . ' ' . $last_name) ?></h5>
        <?php if ($role === 'student'): ?>
            <p class="card-text">Student</p>
            <p class="card-text"><small class="text-muted">Roll No: <?= htmlspecialchars($user_code) ?></small></p>
        <?php else: ?>
            <p class="card-text">Welcome, <?= ucfirst($role) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($courses)): ?>
<h4>My Courses</h4>
<div class="row mb-4">
    <?php foreach ($courses as $course): ?>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h5>
                    <p class="card-text"><?= htmlspecialchars($course['course_details']) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($notifications)): ?>
<h4>Notifications</h4>
<div class="row">
    <?php foreach ($notifications as $note): ?>
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
require_once '../../templates/layout/master_base.php';
require_once '../../templates/layout/navbar.php';
$conn->close();
?>
