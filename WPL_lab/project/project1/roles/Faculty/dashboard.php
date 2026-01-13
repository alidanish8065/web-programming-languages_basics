<?php
// =====================================================
// FILE 1: faculty/dashboard.php - Faculty Dashboard with Course Cards
// =====================================================
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'teacher'])) {
    header('Location: ' . url('public/login.php'));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

// Fetch teacher info
$stmt = $conn->prepare("
    SELECT t.designation, t.employee_number, d.department_name
    FROM teacher t
    JOIN department d ON t.department_id = d.department_id
    WHERE t.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch assigned courses
$sql = "
    SELECT co.offering_id, c.course_code, c.course_name, c.credit_hrs,
           co.academic_year, co.semester, co.term, d.department_name,
           COUNT(DISTINCT e.student_id) as student_count,
           COUNT(DISTINCT m.module_id) as module_count,
           (SELECT COUNT(*) FROM assignment a 
            JOIN module m2 ON a.module_id = m2.module_id 
            WHERE m2.offering_id = co.offering_id) as assignment_count,
           (SELECT COUNT(*) FROM assignment_submission asub 
            JOIN assignment a2 ON asub.assignment_id = a2.assignment_id 
            JOIN module m3 ON a2.module_id = m3.module_id 
            WHERE m3.offering_id = co.offering_id AND asub.status = 'submitted') as pending_grading
    FROM course_teacher ct
    JOIN course_offering co ON ct.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    LEFT JOIN enrollment e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
    LEFT JOIN module m ON co.offering_id = m.offering_id
    WHERE ct.teacher_id = ?
    GROUP BY co.offering_id
    ORDER BY co.academic_year DESC, co.semester DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$active_page = 'dashboard';
ob_start();
?>

<!-- Teacher Profile Card -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    👨‍🏫
                </div>
            </div>
            <div class="col-md-10">
                <h4 class="mb-1"><?= htmlspecialchars($name) ?></h4>
                <p class="mb-1 text-muted">
                    <strong>Employee ID:</strong> <?= htmlspecialchars($teacher_info['employee_number']) ?> | 
                    <strong>Designation:</strong> <?= ucfirst(str_replace('_', ' ', $teacher_info['designation'])) ?>
                </p>
                <p class="mb-0">
                    <span class="badge bg-info"><?= htmlspecialchars($teacher_info['department_name']) ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Total Courses</h6>
                <h2 class="text-primary"><?= count($courses) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Total Students</h6>
                <h2 class="text-success"><?= array_sum(array_column($courses, 'student_count')) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Pending Grading</h6>
                <h2 class="text-warning"><?= array_sum(array_column($courses, 'pending_grading')) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Total Modules</h6>
                <h2 class="text-info"><?= array_sum(array_column($courses, 'module_count')) ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- My Courses -->
<h4 class="mb-3"><i class="bi bi-book"></i> My Courses</h4>
<?php if (empty($courses)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No courses assigned yet. Contact administration.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm h-100 course-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><strong><?= htmlspecialchars($course['course_code']) ?></strong></h5>
                        <small><?= htmlspecialchars($course['department_name']) ?></small>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h6>
                        
                        <div class="mb-2">
                            <span class="badge bg-info"><?= $course['credit_hrs'] ?> Credits</span>
                            <span class="badge bg-secondary"><?= $course['term'] ?> <?= $course['academic_year'] ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <strong class="text-primary"><?= $course['student_count'] ?></strong>
                                <br><small class="text-muted">Students</small>
                            </div>
                            <div class="col-4">
                                <strong class="text-success"><?= $course['module_count'] ?></strong>
                                <br><small class="text-muted">Modules</small>
                            </div>
                            <div class="col-4">
                                <strong class="text-warning"><?= $course['pending_grading'] ?></strong>
                                <br><small class="text-muted">Pending</small>
                            </div>
                        </div>
                        
                        <a href="Faculty_tools/manage_course.php?id=<?= $course['offering_id'] ?>" 
                           class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Manage Course
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.course-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}
</style>

<?php
$content = ob_get_clean();
$page_title = 'Faculty Dashboard';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>

<?php
// =====================================================
// FILE 2: faculty/Faculty_tools/manage_course.php - UNIFIED COURSE MANAGEMENT PAGE
// =====================================================
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'teacher'])) {
    header('Location: ' . url('public/login.php'));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$offering_id = $_GET['id'] ?? null;
$tab = $_GET['tab'] ?? 'content'; // Default to content tab

if (!$offering_id) {
    header('Location: ../dashboard.php');
    exit;
}

// Verify access
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_code, c.course_name, d.department_name,
           co.academic_year, co.semester, co.term
    FROM course_teacher ct
    JOIN course_offering co ON ct.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    WHERE co.offering_id = ? AND ct.teacher_id = ?
");
$stmt->bind_param("ii", $offering_id, $teacher_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    die('Access denied or course not found.');
}

$active_page = 'my_courses';
ob_start();
?>

<!-- Course Header -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><i class="bi bi-book"></i> <?= htmlspecialchars($course['course_code']) ?></h4>
                <p class="mb-0"><?= htmlspecialchars($course['course_name']) ?> | <?= htmlspecialchars($course['department_name']) ?></p>
                <small><?= $course['term'] ?> <?= $course['academic_year'] ?> | Semester <?= $course['semester'] ?></small>
            </div>
            <a href="../dashboard.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $tab === 'content' ? 'active' : '' ?>" href="?id=<?= $offering_id ?>&tab=content">
            <i class="bi bi-journal-text"></i> Course Content
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $tab === 'students' ? 'active' : '' ?>" href="?id=<?= $offering_id ?>&tab=students">
            <i class="bi bi-people"></i> Students
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $tab === 'attendance' ? 'active' : '' ?>" href="?id=<?= $offering_id ?>&tab=attendance">
            <i class="bi bi-calendar-check"></i> Attendance
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $tab === 'assignments' ? 'active' : '' ?>" href="?id=<?= $offering_id ?>&tab=assignments">
            <i class="bi bi-clipboard-check"></i> Assignments
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $tab === 'gradebook' ? 'active' : '' ?>" href="?id=<?= $offering_id ?>&tab=gradebook">
            <i class="bi bi-bar-chart"></i> Gradebook
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <?php if ($tab === 'content'): ?>
        <!-- COURSE CONTENT TAB -->
        <div class="alert alert-info">
            <strong><i class="bi bi-info-circle"></i> Course Content Management</strong>
            <p class="mb-0">Create modules, add lessons, upload lecture materials (PDFs, videos, presentations), and manage all course content here.</p>
        </div>
        <iframe src="course_content.php?id=<?= $offering_id ?>" 
                style="width:100%; height:800px; border:none;" 
                title="Course Content"></iframe>
        
    <?php elseif ($tab === 'students'): ?>
        <!-- STUDENTS TAB -->
        <div class="alert alert-info">
            <strong><i class="bi bi-people"></i> Students Management</strong>
            <p class="mb-0">View enrolled students, check their performance, attendance records, and grades.</p>
        </div>
        <iframe src="../Courses/course_student.php?id=<?= $offering_id ?>" 
                style="width:100%; height:800px; border:none;" 
                title="Students"></iframe>
        
    <?php elseif ($tab === 'attendance'): ?>
        <!-- ATTENDANCE TAB -->
        <div class="alert alert-info">
            <strong><i class="bi bi-calendar-check"></i> Attendance Management</strong>
            <p class="mb-0">Mark attendance for class sessions, view attendance reports, and manage attendance records.</p>
        </div>
        <iframe src="../attendance/mark_attendance.php?id=<?= $offering_id ?>" 
                style="width:100%; height:800px; border:none;" 
                title="Attendance"></iframe>
        
    <?php elseif ($tab === 'assignments'): ?>
        <!-- ASSIGNMENTS TAB -->
        <div class="alert alert-info">
            <strong><i class="bi bi-clipboard-check"></i> Assignment Management</strong>
            <p class="mb-0">Create assignments, review submissions, grade student work, and provide feedback.</p>
        </div>
        <iframe src="../modules/grade_assignment.php?id=<?= $offering_id ?>" 
                style="width:100%; height:800px; border:none;" 
                title="Assignments"></iframe>
        
    <?php elseif ($tab === 'gradebook'): ?>
        <!-- GRADEBOOK TAB -->
        <div class="alert alert-info">
            <strong><i class="bi bi-bar-chart"></i> Gradebook</strong>
            <p class="mb-0">View complete gradebook with all assignments, exams, and final grades for students.</p>
        </div>
        <iframe src="../modules/grade_book.php?id=<?= $offering_id ?>" 
                style="width:100%; height:800px; border:none;" 
                title="Gradebook"></iframe>
        
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Manage Course - ' . htmlspecialchars($course['course_code']);
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>