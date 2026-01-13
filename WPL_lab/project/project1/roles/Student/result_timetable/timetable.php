<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../../public/login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$active_page = 'timetable';

// Get current enrollments with timetable
$sql = "
    SELECT 
        c.course_code,
        c.course_name,
        t.day_of_week,
        t.start_time,
        t.end_time,
        r.room_name,
        CONCAT(u.first_name, ' ', u.last_name) as teacher_name
    FROM enrollment e
    JOIN course_offering co ON e.offering_id = co.offering_id
    JOIN module m ON co.offering_id = m.offering_id
    JOIN lesson l ON m.module_id = l.module_id
    JOIN timetable t ON l.lesson_id = t.lesson_id
    JOIN course c ON co.course_id = c.course_id
    LEFT JOIN room r ON t.room_id = r.room_id
    LEFT JOIN users u ON t.teacher_id = u.id
    WHERE e.student_id = ? AND e.status = 'enrolled' AND t.status = 'scheduled'
    ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), t.start_time
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by day
$days = ['Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 'Thursday' => [], 'Friday' => [], 'Saturday' => [], 'Sunday' => []];
foreach ($schedule as $class) {
    $days[$class['day_of_week']][] = $class;
}

ob_start();
?>

<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-calendar-week"></i> My Timetable</h4>

    <?php if (empty($schedule)): ?>
        <div class="alert alert-info">No classes scheduled yet.</div>
    <?php else: ?>
        
        <div class="card">
            <div class="card-body">
                <?php foreach ($days as $day => $classes): ?>
                    <?php if (!empty($classes)): ?>
                        <h5 class="mt-3 mb-2 text-primary"><?= $day ?></h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Time</th>
                                        <th width="20%">Course</th>
                                        <th width="30%">Course Name</th>
                                        <th width="15%">Room</th>
                                        <th width="20%">Instructor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?></td>
                                            <td><strong><?= htmlspecialchars($class['course_code']) ?></strong></td>
                                            <td><?= htmlspecialchars($class['course_name']) ?></td>
                                            <td><?= htmlspecialchars($class['room_name'] ?? 'TBA') ?></td>
                                            <td><small><?= htmlspecialchars($class['teacher_name'] ?? 'TBA') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "My Timetable - LMS";
require_once '../../../templates/layout/master_base.php';
?><