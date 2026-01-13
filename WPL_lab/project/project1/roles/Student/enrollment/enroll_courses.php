<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') exit;

$student_id = $_SESSION['user_id'];
$message = $error = '';

// Get student's program
$stmt = $conn->prepare("SELECT program_id, current_semester FROM student WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offering_id'])) {
    $offering_id = $_POST['offering_id'];
    
    // Get course credit hours
    $stmt = $conn->prepare("
        SELECT c.credit_hrs FROM course_offering co 
        JOIN course c ON co.course_id = c.course_id 
        WHERE co.offering_id = ?
    ");
    $stmt->bind_param("i", $offering_id);
    $stmt->execute();
    $credit_hrs = $stmt->get_result()->fetch_assoc()['credit_hrs'];
    $stmt->close();
    
    // Enroll
    $stmt = $conn->prepare("
        INSERT INTO enrollment (student_id, offering_id, credit_hrs, status) 
        VALUES (?, ?, ?, 'enrolled')
    ");
    $stmt->bind_param("iii", $student_id, $offering_id, $credit_hrs);
    
    if ($stmt->execute()) {
        $message = 'Successfully enrolled in course!';
    } else {
        $error = 'Enrollment failed. You may already be enrolled.';
    }
    $stmt->close();
}

// Fetch available courses (not enrolled yet)
$stmt = $conn->prepare("
    SELECT co.offering_id, c.course_id, c.course_code, c.course_name, 
           c.credit_hrs, c.course_type, co.academic_year, co.semester, co.term,
           co.max_enrollment,
           (SELECT COUNT(*) FROM enrollment WHERE offering_id = co.offering_id) as enrolled_count
    FROM course_offering co
    JOIN course c ON co.course_id = c.course_id
    WHERE c.department_id = (
        SELECT department_id FROM program WHERE program_id = ?
    )
    AND co.offering_id NOT IN (
        SELECT offering_id FROM enrollment WHERE student_id = ?
    )
    ORDER BY c.course_code
");
$stmt->bind_param("ii", $student_info['program_id'], $student_id);
$stmt->execute();
$available_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<h3>➕ Enroll in Courses</h3>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<a href="courses.php" class="btn btn-secondary mb-3">← Back to My Courses</a>

<?php if (empty($available_courses)): ?>
    <div class="alert alert-info">No courses available for enrollment at this time.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($available_courses as $course): 
            $is_full = $course['max_enrollment'] && $course['enrolled_count'] >= $course['max_enrollment'];
        ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5><?= htmlspecialchars($course['course_code']) ?></h5>
                            <span class="badge bg-info"><?= $course['credit_hrs'] ?> Credits</span>
                        </div>
                        <h6 class="text-muted"><?= htmlspecialchars($course['course_name']) ?></h6>
                        <p class="small mb-2">
                            <span class="badge bg-secondary"><?= ucfirst($course['course_type']) ?></span>
                            <?= $course['term'] ?> <?= $course['academic_year'] ?> (Sem <?= $course['semester'] ?>)
                        </p>
                        
                        <?php if ($course['max_enrollment']): ?>
                            <small class="text-muted">
                                Seats: <?= $course['enrolled_count'] ?>/<?= $course['max_enrollment'] ?>
                            </small>
                        <?php endif; ?>
                        
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="offering_id" value="<?= $course['offering_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm w-100" 
                                    <?= $is_full ? 'disabled' : '' ?>>
                                <?= $is_full ? 'Course Full' : 'Enroll Now' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Enroll in Courses';
require_once '../../../templates/layout/master_base.php';
$conn->close();
?>