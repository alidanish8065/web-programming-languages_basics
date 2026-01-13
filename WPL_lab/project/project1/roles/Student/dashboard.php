<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
 header('Location: ' . url('public/login.php'));
    exit;
}

$id = $_SESSION['user_id'];
$user_code = $_SESSION['user_code'] ?? '';
$first_name = $_SESSION['first_name'] ?? 'Guest';
$last_name = $_SESSION['last_name'] ?? '';
$name = trim($first_name . ' ' . $last_name);

if ($name === '') {
    $name = 'Guest';
}
$role = $_SESSION['role'] ?? 'guest';

$courses = [];
$notifications = [];
$student_info = [];
$today_classes = [];
$announcements = [];

// Fetch student information (academic standing, credit hours)
if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT s.student_number, s.admission_year, s.current_semester, 
               s.enrollment_status, s.academic_standing,
               s.attempted_credit_hrs, s.completed_credit_hrs, s.remaining_credit_hrs,
               p.program_name, p.degree_level,
               u.email, u.contact_number
        FROM student s
        JOIN users u ON s.student_id = u.id
        JOIN program p ON s.program_id = p.program_id
        WHERE s.student_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $student_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    // Fetch today's scheduled classes from attendance_session
    $stmt = $conn->prepare("
        SELECT ats.session_id, ats.session_date, ats.start_time, ats.end_time,
               ats.session_type, ats.status,
               l.lesson_title, l.lesson_type,
               c.course_name, c.course_code
        FROM attendance_session ats
        JOIN lesson l ON ats.lesson_id = l.lesson_id
        JOIN module m ON l.module_id = m.module_id
        JOIN course_offering co ON m.offering_id = co.offering_id
        JOIN course c ON co.course_id = c.course_id
        JOIN enrollment e ON co.offering_id = e.offering_id
        WHERE e.student_id = ? 
          AND ats.session_date = CURDATE()
          AND ats.status IN ('scheduled', 'completed')
        ORDER BY ats.start_time
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $today_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch notifications
$stmt = $conn->prepare("
    SELECT n.title, n.message, n.notification_type, n.created_at, 
           un.is_read, un.read_at
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

// Fetch general announcements (is_general = TRUE)
$stmt = $conn->prepare("
    SELECT title, message, notification_type, created_at
    FROM notification
    WHERE is_general = TRUE
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dashboard content
ob_start();
?>

<!-- Profile Card -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?>
                </div>
            </div>
            <div class="col-md-10">
                <h4 class="mb-1"><?= htmlspecialchars($first_name . ' ' . $last_name) ?></h4>
                <p class="mb-1 text-muted">
                    <strong>Student Number:</strong> <?= htmlspecialchars($student_info['student_number'] ?? 'N/A') ?> | 
                    <strong>Email:</strong> <?= htmlspecialchars($student_info['email'] ?? 'N/A') ?>
                </p>
                <p class="mb-0">
                    <span class="badge bg-info"><?= htmlspecialchars($student_info['program_name'] ?? 'N/A') ?></span>
                    <span class="badge bg-secondary">Semester <?= htmlspecialchars($student_info['current_semester'] ?? 'N/A') ?></span>
                    <span class="badge bg-success"><?= ucfirst(str_replace('_', ' ', $student_info['enrollment_status'] ?? 'N/A')) ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Academic Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Academic Standing</h6>
                <h3 class="text-<?= ($student_info['academic_standing'] === 'honors') ? 'success' : 
                    (($student_info['academic_standing'] === 'good') ? 'primary' : 'warning') ?>">
                    <?= ucfirst($student_info['academic_standing'] ?? 'N/A') ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Attempted Credits</h6>
                <h3><?= htmlspecialchars($student_info['attempted_credit_hrs'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Completed Credits</h6>
                <h3 class="text-success"><?= htmlspecialchars($student_info['completed_credit_hrs'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Remaining Credits</h6>
                <h3 class="text-danger"><?= htmlspecialchars($student_info['remaining_credit_hrs'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- My Courses -->
<h4 class="mb-3">My Courses</h4>
<?php include __DIR__ . '/courses/courses.php'; ?>


<!-- Today's Schedule -->
<h4 class="mb-3">Today's Classes</h4>
<?php if (!empty($today_classes)): ?>
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Lesson</th>
                        <th>Session Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_classes as $class): ?>
                        <tr>
                            <td>
                                <?php if ($class['start_time'] && $class['end_time']): ?>
                                    <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Time TBA</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($class['course_code']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($class['course_name']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($class['lesson_title']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($class['session_type']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $class['status'] === 'completed' ? 'success' : ($class['status'] === 'cancelled' ? 'danger' : 'primary') ?>">
                                    <?= ucfirst($class['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-success mb-4">No classes scheduled for today</div>
<?php endif; ?>

<!-- Notifications -->
<?php if (!empty($notifications)): ?>
<h4 class="mb-3">My Notifications</h4>
<div class="row mb-4">
    <?php foreach ($notifications as $note): ?>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm <?= $note['is_read'] ? '' : 'border-primary' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= htmlspecialchars($note['title']) ?></h5>
                        <?php if (!$note['is_read']): ?>
                            <span class="badge bg-primary">New</span>
                        <?php endif; ?>
                    </div>
                    <p class="card-text"><?= htmlspecialchars($note['message']) ?></p>
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> <?= date('M d, Y g:i A', strtotime($note['created_at'])) ?>
                    </small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Announcements Section -->
<h4 class="mb-3">Announcements</h4>
<?php if (!empty($announcements)): ?>
<div class="card shadow-sm mb-4">
    <div class="list-group list-group-flush">
        <?php foreach ($announcements as $announcement): ?>
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h6>
                    <small class="text-muted"><?= date('M d, Y', strtotime($announcement['created_at'])) ?></small>
                </div>
                <p class="mb-1"><?= htmlspecialchars($announcement['message']) ?></p>
                <small>
                    <span class="badge bg-<?= 
                        $announcement['notification_type'] === 'alert' ? 'danger' : 
                        ($announcement['notification_type'] === 'warning' ? 'warning' : 'info') 
                    ?>">
                        <?= ucfirst($announcement['notification_type']) ?>
                    </span>
                </small>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-secondary mb-4">No announcements at this time.</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>