<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    exit;
}

$teacher_id = $_SESSION['user_id'];
$module_id  = $_GET['module_id'] ?? null;
$message = $error = '';

if (!$module_id || !ctype_digit($module_id)) {
    header('Location: my_courses.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify access + get offering_id (CRITICAL FIX)
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT 
        m.module_name,
        m.offering_id,
        c.course_code,
        c.course_name
    FROM module m
    JOIN course_offering co ON m.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    JOIN course_teacher ct ON co.offering_id = ct.offering_id
    WHERE m.module_id = ? AND ct.teacher_id = ?
");
$stmt->bind_param("ii", $module_id, $teacher_id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$module) {
    header('Location: my_courses.php');
    exit;
}

$offering_id = $module['offering_id'];

/*
|--------------------------------------------------------------------------
| Handle form submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title            = trim($_POST['lesson_title']);
    $type             = $_POST['lesson_type'];
    $description      = trim($_POST['description']);
    $sequence         = (int)$_POST['sequence_number'];
    $status           = $_POST['status'];
    $scheduled_start  = $_POST['scheduled_start'] ?: null;
    $scheduled_end    = $_POST['scheduled_end'] ?: null;

    if ($title === '') {
        $error = 'Lesson title is required.';
    } else {

        $stmt = $conn->prepare("
            INSERT INTO lesson (
                module_id,
                lesson_title,
                lesson_type,
                description,
                sequence_number,
                scheduled_start,
                scheduled_end,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssisss",
            $module_id,
            $title,
            $type,
            $description,
            $sequence,
            $scheduled_start,
            $scheduled_end,
            $status
        );

        if ($stmt->execute()) {
            header(
                'Location: ' .
                url('roles/Faculty/Faculty_tools/Courses/course_content.php?id=' . $offering_id)
            );
            exit;
        } else {
            $error = 'Failed to create lesson.';
        }

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| Get next sequence number
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT COALESCE(MAX(sequence_number), 0) + 1 AS next_seq
    FROM lesson
    WHERE module_id = ?
");
$stmt->bind_param("i", $module_id);
$stmt->execute();
$next_sequence = $stmt->get_result()->fetch_assoc()['next_seq'];
$stmt->close();

ob_start();
?>

<a href="<?= url('roles/Faculty/Faculty_tools/Courses/course_content.php?id=' . $offering_id) ?>"
   class="btn btn-secondary mb-3">← Back to Course</a>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Create New Lesson</h5>
        <small>
            <?= htmlspecialchars($module['course_code']) ?>
            — Module: <?= htmlspecialchars($module['module_name']) ?>
        </small>
    </div>

    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Lesson Title *</label>
                    <input type="text" name="lesson_title" class="form-control" required>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Type *</label>
                    <select name="lesson_type" class="form-select" required>
                        <option value="video">Video</option>
                        <option value="live">Live</option>
                        <option value="text">Text</option>
                        <option value="slides">Slides</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Sequence</label>
                    <input type="number"
                           name="sequence_number"
                           class="form-control"
                           value="<?= $next_sequence ?>"
                           min="1"
                           required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Scheduled Start</label>
                    <input type="datetime-local" name="scheduled_start" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Scheduled End</label>
                    <input type="datetime-local" name="scheduled_end" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Lesson</button>
                <a href="<?= url('roles/Faculty/Faculty_tools/Courses/course_content.php?id=' . $offering_id) ?>"
                   class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Create Lesson';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>
