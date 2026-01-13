<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

// Session check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'teacher'])) {
    header('Location: ' . url('public/login.php'));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$active_page = 'my_courses';

// Fetch teacher's assigned courses
$sql = "
    SELECT 
        co.offering_id,
        c.course_id,
        c.course_code,
        c.course_name,
        c.credit_hrs,
        co.academic_year,
        co.semester,
        co.term,
        co.location,
        co.max_enrollment,
        ct.role as teacher_role,
        d.department_name,
        COUNT(DISTINCT e.student_id) as enrolled_students,
        COUNT(DISTINCT m.module_id) as module_count,
        COUNT(DISTINCT a.assignment_id) as assignment_count
    FROM course_teacher ct
    JOIN course_offering co ON ct.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    LEFT JOIN enrollment e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
    LEFT JOIN module m ON co.offering_id = m.offering_id
    LEFT JOIN assignment a ON m.module_id = a.module_id
    WHERE ct.teacher_id = ?
    GROUP BY co.offering_id
    ORDER BY co.academic_year DESC, co.semester DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$courses = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h4><i class="bi bi-journal-text"></i> My Courses</h4>
            <p class="text-muted">Courses assigned to you</p>
        </div>
    </div>

    <?php if (empty($courses)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You don't have any assigned courses yet. Please contact the administrator.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <strong><?= htmlspecialchars($course['course_code']) ?></strong>
                            </h5>
                            <small><?= htmlspecialchars($course['department_name']) ?></small>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h6>
                            
                            <div class="mb-2">
                                <span class="badge bg-info"><?= $course['credit_hrs'] ?> Credits</span>
                                <span class="badge bg-secondary"><?= ucfirst($course['teacher_role']) ?></span>
                            </div>
                            
                            <p class="text-muted mb-2">
                                <i class="bi bi-calendar"></i>
                                <?= htmlspecialchars($course['academic_year']) ?> | 
                                Semester <?= $course['semester'] ?> | 
                                <?= $course['term'] ?>
                            </p>
                            
                            <?php if ($course['location']): ?>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= htmlspecialchars($course['location']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <strong><?= $course['enrolled_students'] ?></strong>
                                    <br>
                                    <small class="text-muted">Students</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= $course['module_count'] ?></strong>
                                    <br>
                                    <small class="text-muted">Modules</small>
                                </div>
                                <div class="col-4">
                                    <strong><?= $course['assignment_count'] ?></strong>
                                    <br>
                                    <small class="text-muted">Assignments</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="course_content.php?id=<?= $course['offering_id'] ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-box"></i> Manage Content
                                </a>
                                <a href="../Courses/course_student.php?id=<?= $course['offering_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-people"></i> View Students
                                </a>
                                <a href="../grade_book.php?id=<?= $course['offering_id'] ?>" 
                                   class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-clipboard-check"></i> Gradebook
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "My Courses - LMS";
require_once include_file('templates/layout/master_base.php');
?>