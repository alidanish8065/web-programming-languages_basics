<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../../public/login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$active_page = 'results';

// Get all enrollments with grades
$sql = "
    SELECT 
        e.*,
        c.course_code,
        c.course_name,
        c.credit_hrs,
        co.academic_year,
        co.semester,
        co.term,
        CONCAT(u.first_name, ' ', u.last_name) as teacher_name
    FROM enrollment e
    JOIN course_offering co ON e.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    LEFT JOIN course_teacher ct ON co.offering_id = ct.offering_id AND ct.role = 'instructor'
    LEFT JOIN users u ON ct.teacher_id = u.id
    WHERE e.student_id = ?
    ORDER BY co.academic_year DESC, co.semester DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate semester-wise stats
$semesters = [];
foreach ($enrollments as $e) {
    $key = $e['academic_year'] . '-S' . $e['semester'];
    if (!isset($semesters[$key])) {
        $semesters[$key] = ['courses' => [], 'total_credits' => 0, 'earned_credits' => 0, 'total_points' => 0];
    }
    $semesters[$key]['courses'][] = $e;
    $semesters[$key]['total_credits'] += $e['credit_hrs'];
    if ($e['status'] === 'completed' && $e['grade_point']) {
        $semesters[$key]['earned_credits'] += $e['credit_hrs'];
        $semesters[$key]['total_points'] += $e['grade_point'] * $e['credit_hrs'];
    }
}

// Calculate CGPAs
foreach ($semesters as &$sem) {
    $sem['sgpa'] = $sem['earned_credits'] > 0 ? round($sem['total_points'] / $sem['earned_credits'], 2) : 0;
}

ob_start();
?>

<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-award"></i> Academic Results</h4>

    <?php if (empty($enrollments)): ?>
        <div class="alert alert-info">No results available yet.</div>
    <?php else: ?>
        
        <!-- Overall Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">CGPA</h6>
                        <h2 class="text-primary"><?= number_format($enrollments[0]['cgpa'] ?? 0, 2) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Credits</h6>
                        <h2><?= array_sum(array_column($enrollments, 'credit_hrs')) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Completed</h6>
                        <h2 class="text-success"><?= array_sum(array_column($enrollments, 'credit_earned')) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Courses</h6>
                        <h2><?= count($enrollments) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Semester-wise Results -->
        <?php foreach ($semesters as $semKey => $sem): ?>
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <span><?= $semKey ?></span>
                    <span>SGPA: <strong><?= $sem['sgpa'] ?></strong></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Credits</th>
                                <th>Grade</th>
                                <th>Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sem['courses'] as $e): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($e['course_code']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($e['course_name']) ?></small>
                                    </td>
                                    <td><?= $e['credit_hrs'] ?></td>
                                    <td>
                                        <?php if ($e['grade']): ?>
                                            <span class="badge bg-<?= in_array($e['grade'], ['A', 'A-', 'B+']) ? 'success' : 'secondary' ?>">
                                                <?= $e['grade'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $e['grade_point'] ?? '-' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $e['status'] === 'completed' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($e['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "Academic Results - LMS";
require_once '../../../templates/layout/master_base.php';
?>