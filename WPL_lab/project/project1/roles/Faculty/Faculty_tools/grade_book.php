<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') exit;

$teacher_id = $_SESSION['user_id'];
$offering_id = $_GET['offering_id'] ?? null;


if (!$offering_id) {
    $redirect_url = url('roles/Faculty/Faculty_tools/Courses/my_courses.php');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    Swal.fire({
        icon: "error",
        title: "Oops!",
        text: "Course not found or invalid offering ID.",
        confirmButtonText: "Go Back"
    }).then(() => {
        window.location.href = "' . $redirect_url . '";
    });
</script>
</body>
</html>';
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

// Fetch students
$stmt = $conn->prepare("
    SELECT s.student_id, s.student_number,
           CONCAT(u.first_name, ' ', u.last_name) as name
    FROM enrollment e
    JOIN student s ON e.student_id = s.student_id
    JOIN users u ON s.student_id = u.id
    WHERE e.offering_id = ? AND e.status = 'enrolled'
    ORDER BY u.first_name
");
$stmt->bind_param("i", $offering_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch assignments
$stmt = $conn->prepare("
    SELECT a.assignment_id, a.assignment_title, a.max_marks, a.weightage
    FROM assignment a
    JOIN module m ON a.module_id = m.module_id
    WHERE m.offering_id = ? AND a.status = 'published'
    ORDER BY a.assignment_id
");
$stmt->bind_param("i", $offering_id);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch exams
$stmt = $conn->prepare("
    SELECT exam_id, exam_title, max_marks, weightage
    FROM exam
    WHERE offering_id = ? AND status IN ('completed', 'ongoing')
    ORDER BY exam_id
");
$stmt->bind_param("i", $offering_id);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all grades
$grades_data = [];

// Assignment grades
foreach ($students as $student) {
    $grades_data[$student['student_id']] = ['assignments' => [], 'exams' => []];
    
    foreach ($assignments as $assignment) {
        $stmt = $conn->prepare("
            SELECT marks_obtained FROM assignment_submission
            WHERE assignment_id = ? AND student_id = ? AND status = 'graded'
        ");
        $stmt->bind_param("ii", $assignment['assignment_id'], $student['student_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $grades_data[$student['student_id']]['assignments'][$assignment['assignment_id']] = 
            $result ? $result['marks_obtained'] : null;
        $stmt->close();
    }
    
    // Exam grades
    foreach ($exams as $exam) {
        $stmt = $conn->prepare("
            SELECT total_marks_obtained FROM exam_attempt
            WHERE exam_id = ? AND student_id = ? AND status = 'evaluated'
            ORDER BY attempt_number DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $exam['exam_id'], $student['student_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $grades_data[$student['student_id']]['exams'][$exam['exam_id']] = 
            $result ? $result['total_marks_obtained'] : null;
        $stmt->close();
    }
}

ob_start();
?>

<a href="../dashboard.php" class="btn btn-secondary mb-3">← Back</a>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📊 Gradebook - <?= htmlspecialchars($course['course_code']) ?></h5>
        <small><?= htmlspecialchars($course['course_name']) ?></small>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="alert alert-info">No students enrolled.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2">Student</th>
                            <th rowspan="2">ID</th>
                            <?php if (!empty($assignments)): ?>
                                <th colspan="<?= count($assignments) ?>" class="text-center bg-warning">Assignments</th>
                            <?php endif; ?>
                            <?php if (!empty($exams)): ?>
                                <th colspan="<?= count($exams) ?>" class="text-center bg-info">Exams</th>
                            <?php endif; ?>
                            <th rowspan="2">Total</th>
                            <th rowspan="2">Grade</th>
                        </tr>
                        <tr>
                            <?php foreach ($assignments as $a): ?>
                                <th class="text-center" style="font-size:0.8rem;">
                                    <?= htmlspecialchars(substr($a['assignment_title'], 0, 15)) ?>
                                    <br><small>(<?= $a['max_marks'] ?>)</small>
                                </th>
                            <?php endforeach; ?>
                            <?php foreach ($exams as $e): ?>
                                <th class="text-center" style="font-size:0.8rem;">
                                    <?= htmlspecialchars(substr($e['exam_title'], 0, 15)) ?>
                                    <br><small>(<?= $e['max_marks'] ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $total_obtained = 0;
                            $total_possible = 0;
                            
                            // Calculate total
                            foreach ($assignments as $a) {
                                $mark = $grades_data[$student['student_id']]['assignments'][$a['assignment_id']];
                                if ($mark !== null) {
                                    $total_obtained += ($mark / $a['max_marks']) * $a['weightage'];
                                    $total_possible += $a['weightage'];
                                }
                            }
                            foreach ($exams as $e) {
                                $mark = $grades_data[$student['student_id']]['exams'][$e['exam_id']];
                                if ($mark !== null) {
                                    $total_obtained += ($mark / $e['max_marks']) * $e['weightage'];
                                    $total_possible += $e['weightage'];
                                }
                            }
                            
                            $percentage = $total_possible > 0 ? ($total_obtained / $total_possible) * 100 : 0;
                            
                            // Calculate grade
                            $grade = '';
                            if ($percentage >= 90) $grade = 'A';
                            elseif ($percentage >= 80) $grade = 'B+';
                            elseif ($percentage >= 70) $grade = 'B';
                            elseif ($percentage >= 60) $grade = 'C+';
                            elseif ($percentage >= 50) $grade = 'C';
                            else $grade = 'F';
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['student_number']) ?></td>
                                
                                <?php foreach ($assignments as $a): 
                                    $mark = $grades_data[$student['student_id']]['assignments'][$a['assignment_id']];
                                ?>
                                    <td class="text-center <?= $mark === null ? 'text-muted' : '' ?>">
                                        <?= $mark !== null ? $mark : '-' ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <?php foreach ($exams as $e): 
                                    $mark = $grades_data[$student['student_id']]['exams'][$e['exam_id']];
                                ?>
                                    <td class="text-center <?= $mark === null ? 'text-muted' : '' ?>">
                                        <?= $mark !== null ? round($mark, 1) : '-' ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td class="text-center"><strong><?= round($percentage, 1) ?>%</strong></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= 
                                        $grade === 'A' ? 'success' : 
                                        ($grade === 'F' ? 'danger' : 'primary') 
                                    ?>">
                                        <?= $grade ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <strong>💡 Grading Scale:</strong> A (90-100%), B+ (80-89%), B (70-79%), C+ (60-69%), C (50-59%), F (<50%)
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Gradebook';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>