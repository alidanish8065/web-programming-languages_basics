<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') exit;

$teacher_id = $_SESSION['user_id'];
$offering_id = $_GET['offering_id'] ?? null;

// If no offering selected, show all courses
if (!$offering_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT co.offering_id, c.course_code, c.course_name,
               co.academic_year, co.semester, co.term
        FROM course_teacher ct
        JOIN course_offering co ON ct.offering_id = co.offering_id
        JOIN course c ON co.course_id = c.course_id
        WHERE ct.teacher_id = ?
        ORDER BY co.academic_year DESC
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    ob_start();
    ?>
    <h3>📋 Select Course to Manage Exams</h3>
    <div class="row">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5><?= htmlspecialchars($course['course_code']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($course['course_name']) ?></p>
                        <a href="?offering_id=<?= $course['offering_id'] ?>" class="btn btn-primary btn-sm">
                            Manage Exams →
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    $content = ob_get_clean();
    $page_title = 'Manage Exams';
    require_once include_file('templates/layout/master_base.php'); 
    $conn->close();
    exit;
}

// Verify access
$stmt = $conn->prepare("
    SELECT c.course_code, c.course_name
    FROM course_teacher ct
    JOIN course_offering co ON ct.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    WHERE co.offering_id = ? AND ct.teacher_id = ?
");
$stmt->bind_param("ii", $offering_id, $teacher_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) exit;

// Fetch exams
$stmt = $conn->prepare("
    SELECT e.exam_id, e.exam_title, e.exam_type, e.max_marks, e.weightage,
           e.exam_mode, e.scheduled_start, e.scheduled_end, e.status,
           (SELECT COUNT(*) FROM exam_attempt WHERE exam_id = e.exam_id) as attempt_count
    FROM exam e
    WHERE e.offering_id = ?
    ORDER BY e.scheduled_start DESC
");
$stmt->bind_param("i", $offering_id);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<div class="mb-3">
    <a href="manage_exam.php" class="btn btn-secondary">← Back to Courses</a>
    <a href="create_exam.php?offering_id=<?= $offering_id ?>" class="btn btn-primary">➕ Create New Exam</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📋 Exams for <?= htmlspecialchars($course['course_code']) ?></h5>
        <small><?= htmlspecialchars($course['course_name']) ?></small>
    </div>
</div>

<?php if (empty($exams)): ?>
    <div class="alert alert-info">No exams created yet. Click "Create New Exam" to add one.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($exams as $exam): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-0"><?= htmlspecialchars($exam['exam_title']) ?></h5>
                            <span class="badge bg-<?= 
                                $exam['status'] === 'completed' ? 'success' : 
                                ($exam['status'] === 'ongoing' ? 'warning' : 'primary') 
                            ?>">
                                <?= ucfirst($exam['status']) ?>
                            </span>
                        </div>
                        
                        <p class="small mb-2">
                            <span class="badge bg-secondary"><?= ucfirst($exam['exam_type']) ?></span>
                            <span class="badge bg-info"><?= ucfirst($exam['exam_mode']) ?></span>
                            <span class="badge bg-primary"><?= $exam['max_marks'] ?> marks</span>
                            <span class="badge bg-success"><?= $exam['weightage'] ?>% weight</span>
                        </p>
                        
                        <p class="small mb-2">
                            <strong>Scheduled:</strong><br>
                            <?= date('M d, Y g:i A', strtotime($exam['scheduled_start'])) ?> - 
                            <?= date('g:i A', strtotime($exam['scheduled_end'])) ?>
                        </p>
                        
                        <p class="small text-muted mb-3">
                            <?= $exam['attempt_count'] ?> student attempt(s)
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="exam_results.php?exam_id=<?= $exam['exam_id'] ?>" 
                               class="btn btn-sm btn-outline-primary">View Results</a>
                            <a href="grade_exam.php?exam_id=<?= $exam['exam_id'] ?>" 
                               class="btn btn-sm btn-outline-success">Grade Attempts</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Manage Exams';
require_once include_file('templates/layout/master_base.php'); 
$conn->close();
?>