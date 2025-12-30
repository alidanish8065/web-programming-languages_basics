<?php
// dashboard.php - LMS Dashboard Content
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../public/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/login.php');
    exit;
}

$id        = $_SESSION['user_id'];
$user_code = $_SESSION['user_code'] ?? '';
$name      = $_SESSION['name'] ?? 'Guest';
$role      = $_SESSION['role'] ?? 'guest';

$courses = [];
$notifications = [];

// Fetch courses
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT c.course_name, c.course_details 
                            FROM courses c 
                            INNER JOIN student_courses sc ON c.id = sc.course_id 
                            WHERE sc.user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT c.course_name, c.course_details 
                            FROM courses c 
                            INNER JOIN course_instructors ci ON c.id = ci.course_id 
                            WHERE ci.user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch notifications
$stmt = $conn->prepare("SELECT n.title, n.message, n.created_at 
                        FROM notifications n 
                        LEFT JOIN notification_users t ON n.id = t.notification_id 
                        WHERE n.target_role IN (?, 'all') OR t.user_id = ?");
$stmt->bind_param("si", $role, $id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Capture content
ob_start();
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($name) ?></h5>
        <?php if ($role === 'student'): ?>
            <p class="card-text">Student </p>
            <p class="card-text"><small class="text-muted">Roll No: <?= htmlspecialchars($user_code) ?></small></p>
        <?php else: ?>
            <p class="card-text">Welcome, <?= ucfirst($role) ?>. Use the sidebar to manage the system.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($courses)): ?>
<h4>My Courses</h4>
<div class="row mb-4">
    <?php foreach ($courses as $course): ?>
        <div class="col-md-6">
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