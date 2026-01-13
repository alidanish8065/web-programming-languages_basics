<?php
// faculty/course_students.php - View Enrolled Students
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') exit;

$teacher_id = $_SESSION['user_id'];
$offering_id = $_GET['offering_id'] ?? null;

if (!$offering_id) {
    header('Location: my_courses.php');
    exit;
}

// Verify access
$stmt = $conn->prepare("
    SELECT c.course_code, c.course_name, c.credit_hrs
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

// Fetch enrolled students with stats
$stmt = $conn->prepare("
    SELECT 
        s.student_id, s.student_number,
        CONCAT(u.first_name, ' ', u.last_name) as name,
        u.email, u.contact_number,
        e.enrollment_date, e.status, e.grade, e.grade_point,
        p.program_name,
        -- Attendance stats
        (SELECT COUNT(*) FROM attendance_record ar
         JOIN attendance_session ats ON ar.session_id = ats.session_id
         JOIN lesson l ON ats.lesson_id = l.lesson_id
         JOIN module m ON l.module_id = m.module_id
         WHERE m.offering_id = ? AND ar.student_id = s.student_id 
           AND ar.attendance_status = 'present') as present_count,
        (SELECT COUNT(*) FROM attendance_record ar
         JOIN attendance_session ats ON ar.session_id = ats.session_id
         JOIN lesson l ON ats.lesson_id = l.lesson_id
         JOIN module m ON l.module_id = m.module_id
         WHERE m.offering_id = ? AND ar.student_id = s.student_id) as total_sessions
    FROM enrollment e
    JOIN student s ON e.student_id = s.student_id
    JOIN users u ON s.student_id = u.id
    JOIN program p ON s.program_id = p.program_id
    WHERE e.offering_id = ? AND e.status = 'enrolled'
    ORDER BY u.first_name
");
$stmt->bind_param("iii", $offering_id, $offering_id, $offering_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<div class="mb-3">
    <a href="../../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">👥 Students - <?= htmlspecialchars($course['course_code']) ?></h5>
        <small><?= htmlspecialchars($course['course_name']) ?> | <?= count($students) ?> Enrolled</small>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="alert alert-info">No students enrolled yet.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Attendance</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $attendance_percent = $student['total_sessions'] > 0 
                                ? round(($student['present_count'] / $student['total_sessions']) * 100, 1)
                                : 0;
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($student['student_number']) ?></strong></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><small><?= htmlspecialchars($student['program_name']) ?></small></td>
                                <td><small><?= htmlspecialchars($student['email']) ?></small></td>
                                <td><small><?= htmlspecialchars($student['contact_number'] ?? '-') ?></small></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $attendance_percent >= 75 ? 'success' : 
                                        ($attendance_percent >= 50 ? 'warning' : 'danger') 
                                    ?>">
                                        <?= $attendance_percent ?>%
                                    </span>
                                    <br><small class="text-muted">
                                        <?= $student['present_count'] ?>/<?= $student['total_sessions'] ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($student['grade']): ?>
                                        <span class="badge bg-success"><?= $student['grade'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $student['status'] === 'enrolled' ? 'primary' : 'secondary' ?>">
                                        <?= ucfirst($student['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Enrolled</h6>
                    <h2><?= count($students) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted">Average Attendance</h6>
                    <h2>
                        <?php
                        $avg_attendance = 0;
                        $count_with_sessions = 0;
                        foreach ($students as $s) {
                            if ($s['total_sessions'] > 0) {
                                $avg_attendance += ($s['present_count'] / $s['total_sessions']) * 100;
                                $count_with_sessions++;
                            }
                        }
                        echo $count_with_sessions > 0 ? round($avg_attendance / $count_with_sessions, 1) : 0;
                        ?>%
                    </h2>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Course Students';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>