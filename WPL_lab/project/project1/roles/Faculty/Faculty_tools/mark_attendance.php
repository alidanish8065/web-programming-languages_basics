<?php
// faculty/attendance.php - Mark Attendance
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') exit;

$teacher_id = $_SESSION['user_id'];
$offering_id = $_GET['offering_id'] ?? null;
$message = $error = '';

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
    SELECT c.course_code, c.course_name, c.course_id
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

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id'])) {
    $session_id = (int)$_POST['session_id'];
    
    foreach ($_POST['attendance'] as $student_id => $status) {
        $remarks = $_POST['remarks'][$student_id] ?? '';
        
        $stmt = $conn->prepare("
            INSERT INTO attendance_record (session_id, student_id, attendance_status, remarks)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE attendance_status = VALUES(attendance_status), 
                                   remarks = VALUES(remarks)
        ");
        $stmt->bind_param("iiss", $session_id, $student_id, $status, $remarks);
        $stmt->execute();
        $stmt->close();
    }
    
    $message = "Attendance marked successfully!";
}

// Fetch lessons for session creation
$stmt = $conn->prepare("
    SELECT l.lesson_id, l.lesson_title, m.module_name
    FROM lesson l
    JOIN module m ON l.module_id = m.module_id
    WHERE m.offering_id = ? AND l.status = 'published'
    ORDER BY m.sequence_number, l.sequence_number
");
$stmt->bind_param("i", $offering_id);
$stmt->execute();
$lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Create session if requested
if (isset($_POST['create_session'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    $session_date = $_POST['session_date'];
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $session_type = $_POST['session_type'];
    
    $stmt = $conn->prepare("
        INSERT INTO attendance_session (lesson_id, session_date, start_time, end_time, session_type, status)
        VALUES (?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmt->bind_param("issss", $lesson_id, $session_date, $start_time, $end_time, $session_type);
    
    if ($stmt->execute()) {
        $message = "Session created!";
    }
    $stmt->close();
}

// Fetch recent sessions
$stmt = $conn->prepare("
    SELECT ats.session_id, ats.session_date, ats.start_time, ats.session_type, ats.status,
           l.lesson_title,
           (SELECT COUNT(*) FROM attendance_record WHERE session_id = ats.session_id) as marked_count,
           (SELECT COUNT(*) FROM enrollment WHERE offering_id = ? AND status = 'enrolled') as total_students
    FROM attendance_session ats
    JOIN lesson l ON ats.lesson_id = l.lesson_id
    JOIN module m ON l.module_id = m.module_id
    WHERE m.offering_id = ?
    ORDER BY ats.session_date DESC, ats.start_time DESC
    LIMIT 10
");
$stmt->bind_param("ii", $offering_id, $offering_id);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch enrolled students for marking
$stmt = $conn->prepare("
    SELECT s.student_id, CONCAT(u.first_name, ' ', u.last_name) as name, s.student_number
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

ob_start();
?>

<a href="../dashboard.php" class="btn btn-secondary mb-3">← Back</a>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📋 Attendance - <?= htmlspecialchars($course['course_code']) ?></h5>
        <small><?= htmlspecialchars($course['course_name']) ?></small>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<!-- Create Session -->
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h6 class="mb-0">➕ Create Attendance Session</h6>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <select name="lesson_id" class="form-select form-select-sm" required>
                        <option value="">Select Lesson</option>
                        <?php foreach ($lessons as $lesson): ?>
                            <option value="<?= $lesson['lesson_id'] ?>">
                                <?= htmlspecialchars($lesson['lesson_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="session_date" class="form-control form-control-sm" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="time" name="start_time" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="session_type" class="form-select form-select-sm" required>
                        <option value="lecture">Lecture</option>
                        <option value="lab">Lab</option>
                        <option value="practical">Practical</option>
                        <option value="tutorial">Tutorial</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" name="create_session" class="btn btn-primary btn-sm w-100">Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Recent Sessions -->
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h6 class="mb-0">Recent Sessions</h6>
    </div>
    <div class="card-body">
        <?php if (empty($sessions)): ?>
            <p class="text-muted">No sessions created yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Lesson</th>
                            <th>Type</th>
                            <th>Time</th>
                            <th>Marked</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($session['session_date'])) ?></td>
                                <td><?= htmlspecialchars($session['lesson_title']) ?></td>
                                <td><span class="badge bg-secondary"><?= ucfirst($session['session_type']) ?></span></td>
                                <td><?= $session['start_time'] ? date('g:i A', strtotime($session['start_time'])) : '-' ?></td>
                                <td><?= $session['marked_count'] ?>/<?= $session['total_students'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" 
                                            data-bs-target="#mark<?= $session['session_id'] ?>">
                                        Mark
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="mark<?= $session['session_id'] ?>">
                                <td colspan="6">
                                    <form method="POST" class="p-3 bg-light">
                                        <input type="hidden" name="session_id" value="<?= $session['session_id'] ?>">
                                        <table class="table table-sm">
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($student['name']) ?> (<?= $student['student_number'] ?>)</td>
                                                    <td>
                                                        <select name="attendance[<?= $student['student_id'] ?>]" class="form-select form-select-sm" required>
                                                            <option value="present">Present</option>
                                                            <option value="absent">Absent</option>
                                                            <option value="late">Late</option>
                                                            <option value="excused">Excused</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="remarks[<?= $student['student_id'] ?>]" 
                                                               class="form-control form-control-sm" placeholder="Remarks">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                        <button type="submit" class="btn btn-success btn-sm">Submit Attendance</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Mark Attendance';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>