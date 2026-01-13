<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    exit;
}

session_start();

$teacher_id = $_SESSION['user_id'];
$module_id  = $_GET['module_id'] ?? null;
$message = $error = '';

if (!$module_id || !ctype_digit($module_id)) {
    header('Location: ' . url('roles/Faculty/Faculty_tools/Courses/my_courses.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify access + fetch offering_id (IMPORTANT)
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
    exit;
}

$offering_id = $module['offering_id'];

/*
|--------------------------------------------------------------------------
| Handle form submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['assignment_title']);
    $description = trim($_POST['description']);
    $max_marks   = (int)$_POST['max_marks'];
    $weightage   = (float)$_POST['weightage'];
    $due_date    = $_POST['due_date'];
    $allow_late  = isset($_POST['allow_late_submission']) ? 1 : 0;
    $late_penalty = $allow_late ? (float)$_POST['late_penalty_percent'] : null;
    $status      = $_POST['status'];

    if ($title === '' || $max_marks <= 0) {
        $error = 'Title and max marks are required.';
    } else {

        $stmt = $conn->prepare("
            INSERT INTO assignment (
                module_id,
                assignment_title,
                description,
                max_marks,
                weightage,
                due_date,
                allow_late_submission,
                late_penalty_percent,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issidsiis",
            $module_id,
            $title,
            $description,
            $max_marks,
            $weightage,
            $due_date,
            $allow_late,
            $late_penalty,
            $status
        );

        if ($stmt->execute()) {
            header(
                'Location: ' .
                url('roles/Faculty/Faculty_tools/Courses/course_content.php?id=' . $offering_id)
            );
            exit;
        } else {
            $error = "Failed to create assignment.";
        }

        $stmt->close();
    }
}

ob_start();
?>

<a href="<?= url('roles/Faculty/Faculty_tools/Courses/course_content.php?id=' . $offering_id) ?>"
   class="btn btn-secondary mb-3">← Back</a>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Create Assignment</h5>
        <small>
            <?= htmlspecialchars($module['course_code']) ?>
            —
            <?= htmlspecialchars($module['module_name']) ?>
        </small>
    </div>

    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Assignment Title *</label>
                <input type="text" name="assignment_title" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Max Marks *</label>
                    <input type="number" name="max_marks" class="form-control" min="1" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Weightage (%) *</label>
                    <input type="number" name="weightage" class="form-control" min="0" max="100" step="0.1" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Due Date *</label>
                    <input type="datetime-local" name="due_date" class="form-control" required>
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox"
                       name="allow_late_submission"
                       class="form-check-input"
                       id="allowLate"
                       onchange="document.getElementById('penaltyDiv').style.display = this.checked ? 'block' : 'none'">
                <label class="form-check-label" for="allowLate">Allow Late Submissions</label>
            </div>

            <div id="penaltyDiv" style="display:none" class="mb-3">
                <label class="form-label">Late Penalty (%)</label>
                <input type="number" name="late_penalty_percent" class="form-control" min="0" max="100" step="0.1">
            </div>

            <div class="mb-3">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Create Assignment</button>

            <a href="<?= url('roles/Faculty/Faculty_tools/Courses/course_content.php?id=' . $offering_id) ?>"
               class="btn btn-secondary">Cancel</a>

        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Create Assignment';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>
