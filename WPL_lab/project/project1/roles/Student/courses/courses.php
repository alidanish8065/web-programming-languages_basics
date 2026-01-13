<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    echo "No student ID found.";
    exit;
}

// Fetch courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_code, c.course_name, c.credit_hrs, c.description,
           e.status, e.grade,
           co.academic_year, co.semester, co.term,
           CONCAT(u.first_name, ' ', u.last_name) as instructor
    FROM enrollment e
    JOIN course_offering co ON e.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    LEFT JOIN course_teacher ct ON co.offering_id = ct.offering_id AND ct.role = 'instructor'
    LEFT JOIN users u ON ct.teacher_id = u.id
    WHERE e.student_id = ? AND e.status IN ('enrolled', 'completed')
    ORDER BY e.status = 'enrolled' DESC, c.course_code
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📚 My Courses</h3>
</div>

<?php if (empty($courses)): ?>
    <div class="alert alert-info">
        You are not enrolled in any courses. <a href="../enrollment/enroll_courses.php">Enroll now</a>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($course['course_code']) ?></h5>
                            <span class="badge bg-<?= $course['status'] === 'enrolled' ? 'primary' : 'success' ?>">
                                <?= ucfirst($course['status']) ?>
                            </span>
                        </div>
                        <h6 class="text-muted"><?= htmlspecialchars($course['course_name']) ?></h6>
                        <p class="small"><?= htmlspecialchars(substr($course['description'] ?? '', 0, 100)) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    <?= $course['credit_hrs'] ?> Credits | 
                                    <?= $course['term'] ?> <?= $course['academic_year'] ?>
                                </small>
                                <?php if ($course['instructor']): ?>
                                    <br><small>👤 <?= htmlspecialchars($course['instructor']) ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if ($course['grade']): ?>
                                <span class="badge bg-success fs-6">Grade: <?= $course['grade'] ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($course['status'] === 'enrolled'): ?>
                            <a href="<?= url('roles/Student/courses/course_content.php?course_id=' . $course['course_id']) ?>">View Content →</a>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
