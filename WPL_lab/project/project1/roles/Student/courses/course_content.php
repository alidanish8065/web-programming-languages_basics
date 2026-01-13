<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') exit;

$student_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    header('Location: courses.php');
    exit;
}

// Verify enrollment
$stmt = $conn->prepare("
    SELECT c.course_name, c.course_code, c.description
    FROM enrollment e
    JOIN course_offering co ON e.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    WHERE e.student_id = ? AND c.course_id = ? AND e.status = 'enrolled'
");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Fetch modules and lessons
$stmt = $conn->prepare("
    SELECT m.module_id, m.module_name, m.description as module_desc, m.sequence_number,
           l.lesson_id, l.lesson_title, l.lesson_type, l.status as lesson_status,
           l.scheduled_start, l.scheduled_end
    FROM module m
    LEFT JOIN lesson l ON m.module_id = l.module_id
    JOIN course_offering co ON m.offering_id = co.offering_id
    WHERE co.course_id = ? AND m.status = 'active'
    ORDER BY m.sequence_number, l.sequence_number
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by modules
$modules = [];
foreach ($results as $row) {
    $mid = $row['module_id'];
    if (!isset($modules[$mid])) {
        $modules[$mid] = [
            'module_name' => $row['module_name'],
            'module_desc' => $row['module_desc'],
            'sequence' => $row['sequence_number'],
            'lessons' => []
        ];
    }
    if ($row['lesson_id']) {
        $modules[$mid]['lessons'][] = $row;
    }
}

ob_start();
?>

<div class="mb-3">
    <a href="../dashboard.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h3><?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?></h3>
        <p class="text-muted"><?= htmlspecialchars($course['description']) ?></p>
    </div>
</div>

<?php if (empty($modules)): ?>
    <div class="alert alert-info">No course content available yet. Check back later!</div>
<?php else: ?>
    <?php foreach ($modules as $module): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📖 Module <?= $module['sequence'] ?>: <?= htmlspecialchars($module['module_name']) ?></h5>
            </div>
            <div class="card-body">
                <?php if ($module['module_desc']): ?>
                    <p class="text-muted"><?= htmlspecialchars($module['module_desc']) ?></p>
                <?php endif; ?>
                
                <?php if (empty($module['lessons'])): ?>
                    <p class="text-muted">No lessons available yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($module['lessons'] as $lesson): ?>
                            <a href="lesson.php?lesson_id=<?= $lesson['lesson_id'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>
                                            <?php
                                            $icons = ['video' => '🎥', 'live' => '🔴', 'text' => '📄', 'slides' => '📊'];
                                            echo $icons[$lesson['lesson_type']] ?? '📝';
                                            ?>
                                            <?= htmlspecialchars($lesson['lesson_title']) ?>
                                        </strong>
                                        <?php if ($lesson['lesson_type'] === 'live' && $lesson['scheduled_start']): ?>
                                            <br><small class="text-muted">
                                                📅 <?= date('M d, Y g:i A', strtotime($lesson['scheduled_start'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-<?= $lesson['lesson_status'] === 'published' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($lesson['lesson_status']) ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Course Content';
require_once '../../../templates/layout/master_base.php';
$conn->close();
?>