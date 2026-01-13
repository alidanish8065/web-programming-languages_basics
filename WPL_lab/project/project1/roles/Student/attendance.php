<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../public/login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$selected_course = $_GET['course_id'] ?? null;

// Fetch enrolled courses for filter
$courses_query = "
    SELECT DISTINCT c.course_id, c.course_code, c.course_name, co.offering_id
    FROM enrollment e
    JOIN course_offering co ON e.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    WHERE e.student_id = ? AND e.status = 'enrolled'
    ORDER BY c.course_code
";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch attendance records
$attendance_query = "
    SELECT 
        ar.record_id,
        ar.attendance_status,
        ar.remarks,
        ats.session_date,
        ats.start_time,
        ats.end_time,
        ats.session_type,
        l.lesson_title,
        c.course_code,
        c.course_name,
        c.course_id
    FROM attendance_record ar
    JOIN attendance_session ats ON ar.session_id = ats.session_id
    JOIN lesson l ON ats.lesson_id = l.lesson_id
    JOIN module m ON l.module_id = m.module_id
    JOIN course_offering co ON m.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    WHERE ar.student_id = ?
";

if ($selected_course) {
    $attendance_query .= " AND c.course_id = ?";
}

$attendance_query .= " ORDER BY ats.session_date DESC, ats.start_time DESC";

$stmt = $conn->prepare($attendance_query);
if ($selected_course) {
    $stmt->bind_param("ii", $student_id, $selected_course);
} else {
    $stmt->bind_param("i", $student_id);
}
$stmt->execute();
$attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_sessions = count($attendance_records);
$present_count = count(array_filter($attendance_records, fn($r) => $r['attendance_status'] === 'present'));
$absent_count = count(array_filter($attendance_records, fn($r) => $r['attendance_status'] === 'absent'));
$late_count = count(array_filter($attendance_records, fn($r) => $r['attendance_status'] === 'late'));
$excused_count = count(array_filter($attendance_records, fn($r) => $r['attendance_status'] === 'excused'));
$attendance_percentage = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100, 1) : 0;

// Group by course for summary
$course_stats = [];
foreach ($attendance_records as $record) {
    $cid = $record['course_id'];
    if (!isset($course_stats[$cid])) {
        $course_stats[$cid] = [
            'course_code' => $record['course_code'],
            'course_name' => $record['course_name'],
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ];
    }
    $course_stats[$cid]['total']++;
    $course_stats[$cid][$record['attendance_status']]++;
}

ob_start();
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Overall Attendance</h6>
                <h2 class="text-<?= $attendance_percentage >= 75 ? 'success' : ($attendance_percentage >= 50 ? 'warning' : 'danger') ?>">
                    <?= $attendance_percentage ?>%
                </h2>
                <small class="text-muted"><?= $present_count ?>/<?= $total_sessions ?> sessions</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Present</h6>
                <h2 class="text-success"><?= $present_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Absent</h6>
                <h2 class="text-danger"><?= $absent_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Late</h6>
                <h2 class="text-warning"><?= $late_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Excused</h6>
                <h2 class="text-info"><?= $excused_count ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Course Filter -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-10">
                <label class="form-label">Filter by Course</label>
                <select name="course_id" class="form-select">
                    <option value="">All Courses</option>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <option value="<?= $course['course_id'] ?>" <?= $selected_course == $course['course_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Course-wise Summary -->
<?php if (!$selected_course && !empty($course_stats)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📊 Course-wise Attendance Summary</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Total Sessions</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($course_stats as $cid => $stats): 
                        $course_percent = round(($stats['present'] / $stats['total']) * 100, 1);
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($stats['course_code']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($stats['course_name']) ?></small>
                            </td>
                            <td><?= $stats['total'] ?></td>
                            <td><span class="badge bg-success"><?= $stats['present'] ?></span></td>
                            <td><span class="badge bg-danger"><?= $stats['absent'] ?></span></td>
                            <td><span class="badge bg-warning"><?= $stats['late'] ?></span></td>
                            <td>
                                <strong class="text-<?= $course_percent >= 75 ? 'success' : ($course_percent >= 50 ? 'warning' : 'danger') ?>">
                                    <?= $course_percent ?>%
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Attendance Records -->
<div class="card shadow-sm">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">📋 Attendance Records</h5>
    </div>
    <div class="card-body">
        <?php if (empty($attendance_records)): ?>
            <div class="alert alert-info">
                No attendance records found. Attendance will be marked by your instructors during class sessions.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Lesson</th>
                            <th>Session Type</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($record['session_date'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($record['course_code']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($record['course_name']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($record['lesson_title']) ?></td>
                                <td><span class="badge bg-secondary"><?= ucfirst($record['session_type']) ?></span></td>
                                <td>
                                    <?php if ($record['start_time']): ?>
                                        <?= date('g:i A', strtotime($record['start_time'])) ?>
                                        <?php if ($record['end_time']): ?>
                                            - <?= date('g:i A', strtotime($record['end_time'])) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'present' => 'success',
                                        'absent' => 'danger',
                                        'late' => 'warning',
                                        'excused' => 'info'
                                    ];
                                    $badge_color = $status_badges[$record['attendance_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge_color ?>">
                                        <?= ucfirst($record['attendance_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($record['remarks']): ?>
                                        <small><?= htmlspecialchars($record['remarks']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
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
$page_title = 'My Attendance';
require_once '../../templates/layout/master_base.php';
$conn->close();
?>