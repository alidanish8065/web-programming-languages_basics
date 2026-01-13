<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'teacher'])) {
    header('Location: ../../../public/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid course offering ID.");
}

$offering_id = intval($_GET['id']);
$teacher_id = $_SESSION['user_id'];

// Verify teacher has access to this course
$verify_sql = "
    SELECT 
        co.*,
        c.course_code,
        c.course_name,
        c.credit_hrs
    FROM course_teacher ct
    JOIN course_offering co ON ct.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    WHERE ct.offering_id = ? AND ct.teacher_id = ?
";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param('ii', $offering_id, $teacher_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    die("You don't have access to this course.");
}

// Fetch modules with their lessons and assignments
$modules_sql = "
    SELECT 
        m.*,
        COUNT(DISTINCT l.lesson_id) as lesson_count,
        COUNT(DISTINCT a.assignment_id) as assignment_count
    FROM module m
    LEFT JOIN lesson l ON m.module_id = l.module_id
    LEFT JOIN assignment a ON m.module_id = a.module_id
    WHERE m.offering_id = ?
    GROUP BY m.module_id
    ORDER BY m.sequence_number
";
$stmt = $conn->prepare($modules_sql);
$stmt->bind_param('i', $offering_id);
$stmt->execute();
$modules = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}

$active_page = 'my_courses';

ob_start();
?>

<div class="container-fluid">
    <!-- Course Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>
                        <i class="bi bi-journal-text"></i>
                        <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                    </h4>
                    <p class="text-muted">
                        <?= htmlspecialchars($course['academic_year']) ?> | 
                        Semester <?= $course['semester'] ?> | 
                        <?= $course['term'] ?>
                    </p>
                </div>
                <div>
                    <a href="my_courses.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Courses
                    </a>
                    <a href="../create_module.php?offering_id=<?= $offering_id ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Module
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Quick Actions</h6>
                    <div class="btn-group" role="group">
                        <a href="../manage_assignment.php?offering_id=<?= $offering_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-file-text"></i> Assignments
                        </a>
                        
                        <a href="../mark_attendance.php?offering_id=<?= $offering_id ?>" class="btn btn-outline-success">
                            <i class="bi bi-calendar-check"></i> Attendance
                        </a>
                        <a href="course_student.php?id=<?= $offering_id ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-people"></i> Students
                        </a>
                        <a href="../grade_book.php?id=<?= $offering_id ?>" class="btn btn-outline-warning">
                            <i class="bi bi-bar-chart"></i> Gradebook
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Modules -->
    <?php if (empty($modules)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No modules created yet. 
            <a href="../modules/create_module.php?offering_id=<?= $offering_id ?>" class="alert-link">Create your first module</a>
        </div>
    <?php else: ?>
        <div class="accordion" id="modulesAccordion">
            <?php foreach ($modules as $index => $module): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $module['module_id'] ?>">
                        <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?= $module['module_id'] ?>">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                <div>
                                    <strong>Module <?= $module['sequence_number'] ?>:</strong>
                                    <?= htmlspecialchars($module['module_name']) ?>
                                    <span class="badge bg-<?= $module['status'] === 'active' ? 'success' : 'secondary' ?> ms-2">
                                        <?= ucfirst($module['status']) ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="badge bg-primary me-2"><?= $module['lesson_count'] ?> Lessons</span>
                                    <span class="badge bg-info"><?= $module['assignment_count'] ?> Assignments</span>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse<?= $module['module_id'] ?>" 
                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                         data-bs-parent="#modulesAccordion">
                        <div class="accordion-body">
                            <?php if ($module['description']): ?>
                                <p class="text-muted"><?= htmlspecialchars($module['description']) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($module['start_date'] || $module['end_date']): ?>
                                <p class="text-muted">
                                    <i class="bi bi-calendar-range"></i>
                                    <?= $module['start_date'] ? date('M d, Y', strtotime($module['start_date'])) : 'No start' ?>
                                    - 
                                    <?= $module['end_date'] ? date('M d, Y', strtotime($module['end_date'])) : 'No end' ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2 mb-3">
                                <a href="../create_lesson.php?module_id=<?= $module['module_id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus"></i> Add Lesson
                                </a>
                                <a href="../create_assignment.php?module_id=<?= $module['module_id'] ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-file-plus"></i> Add Assignment
                                </a>
                                <a href="../edit_module.php?id=<?= $module['module_id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Edit Module
                                </a>
                            </div>
                            
                            <!-- Lessons List -->
                            <?php
                            $lessons_sql = "SELECT * FROM lesson WHERE module_id = ? ORDER BY sequence_number";
                            $lesson_stmt = $conn->prepare($lessons_sql);
                            $lesson_stmt->bind_param('i', $module['module_id']);
                            $lesson_stmt->execute();
                            $lessons = $lesson_stmt->get_result();
                            ?>
                            
                            <?php if ($lessons->num_rows > 0): ?>
                                <h6 class="mt-3">Lessons:</h6>
                                <div class="list-group">
                                    <?php while ($lesson = $lessons->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-secondary"><?= $lesson['sequence_number'] ?></span>
                                                    <strong><?= htmlspecialchars($lesson['lesson_title']) ?></strong>
                                                    <span class="badge bg-info ms-2"><?= ucfirst($lesson['lesson_type']) ?></span>
                                                    <span class="badge bg-<?= $lesson['status'] === 'published' ? 'success' : ($lesson['status'] === 'draft' ? 'warning' : 'secondary') ?> ms-1">
                                                        <?= ucfirst($lesson['status']) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <a href="edit_lesson.php?id=<?= $lesson['lesson_id'] ?>" class="btn btn-sm btn-outline-warning">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                            <?php if ($lesson['description']): ?>
                                                <small class="text-muted d-block mt-1"><?= htmlspecialchars($lesson['description']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Assignments List -->
                            <?php
                            $assignments_sql = "SELECT * FROM assignment WHERE module_id = ? ORDER BY due_date";
                            $assign_stmt = $conn->prepare($assignments_sql);
                            $assign_stmt->bind_param('i', $module['module_id']);
                            $assign_stmt->execute();
                            $assignments = $assign_stmt->get_result();
                            ?>
                            
                            <?php if ($assignments->num_rows > 0): ?>
                                <h6 class="mt-3">Assignments:</h6>
                                <div class="list-group">
                                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($assignment['assignment_title']) ?></strong>
                                                    <span class="badge bg-warning ms-2"><?= $assignment['max_marks'] ?> marks</span>
                                                    <span class="badge bg-<?= $assignment['status'] === 'published' ? 'success' : 'warning' ?> ms-1">
                                                        <?= ucfirst($assignment['status']) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <small class="text-muted me-2">Due: <?= date('M d, Y', strtotime($assignment['due_date'])) ?></small>
                                                    <a href="grade_assignments.php?assignment_id=<?= $assignment['assignment_id'] ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-check-circle"></i> Grade
                                                    </a>
                                                    <a href="edit_assignment.php?id=<?= $assignment['assignment_id'] ?>" class="btn btn-sm btn-outline-warning">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "Course Content - LMS";
require_once include_file('templates/layout/master_base.php');
?>