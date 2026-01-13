<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') exit;

$lesson_id = $_GET['lesson_id'] ?? null;
if (!$lesson_id) {
    header('Location: courses.php');
    exit;
}

// Fetch lesson details and resources
$stmt = $conn->prepare("
    SELECT l.lesson_title, l.description, l.lesson_type, l.scheduled_start,
           c.course_code, c.course_name, c.course_id,
           r.resource_id, r.resource_type, r.resource_url, r.resource_name
    FROM lesson l
    JOIN module m ON l.module_id = m.module_id
    JOIN course_offering co ON m.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    LEFT JOIN lesson_resource lr ON l.lesson_id = lr.lesson_id
    LEFT JOIN resource r ON lr.resource_id = r.resource_id
    WHERE l.lesson_id = ?
");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($results)) {
    header('Location: courses.php');
    exit;
}

$lesson = $results[0];
$resources = array_filter($results, fn($r) => $r['resource_id']);

ob_start();
?>

<a href="course_content.php?course_id=<?= $lesson['course_id'] ?>" class="btn btn-secondary mb-3">
    ← Back to Course
</a>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><?= htmlspecialchars($lesson['lesson_title']) ?></h4>
        <small><?= htmlspecialchars($lesson['course_code']) ?> - <?= htmlspecialchars($lesson['course_name']) ?></small>
    </div>
    <div class="card-body">
        <?php if ($lesson['description']): ?>
            <p><?= nl2br(htmlspecialchars($lesson['description'])) ?></p>
        <?php endif; ?>
        
        <?php if ($lesson['lesson_type'] === 'live' && $lesson['scheduled_start']): ?>
            <div class="alert alert-info">
                🔴 <strong>Live Session:</strong> Scheduled for 
                <?= date('l, F d, Y \a\t g:i A', strtotime($lesson['scheduled_start'])) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($resources)): ?>
            <div class="alert alert-secondary">No resources available for this lesson yet.</div>
        <?php else: ?>
            <h5 class="mt-4">📎 Resources</h5>
            <div class="list-group">
                <?php foreach ($resources as $resource): ?>
                    <a href="<?= htmlspecialchars($resource['resource_url']) ?>" 
                       target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <?php
                                $icons = ['video' => '🎥', 'pdf' => '📄', 'ppt' => '📊', 'doc' => '📝', 
                                         'link' => '🔗', 'image' => '🖼️', 'audio' => '🎵'];
                                echo $icons[$resource['resource_type']] ?? '📎';
                                ?>
                                <?= htmlspecialchars($resource['resource_name'] ?? 'Resource') ?>
                            </span>
                            <span class="badge bg-secondary"><?= strtoupper($resource['resource_type']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Lesson';
require_once '../../../templates/layout/master_base.php';
$conn->close();
?>