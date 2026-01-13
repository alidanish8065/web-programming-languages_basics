<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = $error = '';

// Filters
$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';

// --------------------------------------------------
// SOFT DELETE ASSIGNMENT
// --------------------------------------------------
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $assignment_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        SELECT a.assignment_id, m.offering_id
        FROM assignment a
        JOIN module m ON a.module_id = m.module_id
        JOIN course_offering co ON m.offering_id = co.offering_id
        JOIN course_teacher ct ON co.offering_id = ct.offering_id
        WHERE a.assignment_id = ? AND ct.teacher_id = ? AND a.deleted_at IS NULL
    ");
    $stmt->bind_param("ii", $assignment_id, $teacher_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $offering_id = $row['offering_id'];
        $conn->begin_transaction();
        try {
            $upd1 = $conn->prepare("UPDATE assignment SET deleted_at = NOW() WHERE assignment_id = ?");
            $upd1->bind_param("i", $assignment_id);
            $upd1->execute();
            $upd1->close();

            $upd2 = $conn->prepare("UPDATE assignment_submission SET deleted_at = NOW() WHERE assignment_id = ?");
            $upd2->bind_param("i", $assignment_id);
            $upd2->execute();
            $upd2->close();

            $conn->commit();
            $message = "Assignment deleted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to delete assignment.";
        }
    } else {
        $error = "Unauthorized or already deleted.";
    }
}

// --------------------------------------------------
// TOGGLE STATUS
// --------------------------------------------------
if (isset($_GET['toggle_status']) && ctype_digit($_GET['toggle_status'])) {
    $assignment_id = (int)$_GET['toggle_status'];

    $stmt = $conn->prepare("
        SELECT a.status
        FROM assignment a
        JOIN module m ON a.module_id = m.module_id
        JOIN course_offering co ON m.offering_id = co.offering_id
        JOIN course_teacher ct ON co.offering_id = ct.offering_id
        WHERE a.assignment_id = ? AND ct.teacher_id = ? AND a.deleted_at IS NULL
    ");
    $stmt->bind_param("ii", $assignment_id, $teacher_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $new_status = $row['status'] === 'published' ? 'draft' : 'published';
        $upd = $conn->prepare("UPDATE assignment SET status = ? WHERE assignment_id = ?");
        $upd->bind_param("si", $new_status, $assignment_id);
        $upd->execute();
        $upd->close();
        $message = "Status updated to " . ucfirst($new_status);
    }
}

// --------------------------------------------------
// FETCH ASSIGNMENTS
// --------------------------------------------------
$sql = "
SELECT
    a.assignment_id,
    a.assignment_title,
    a.description,
    a.max_marks,
    a.weightage,
    a.due_date,
    a.status,
    a.allow_late_submission,
    a.late_penalty_percent,
    m.module_name,
    c.course_code,
    c.course_name,
    co.semester,
    co.academic_year,
    co.term,
    COUNT(DISTINCT s.submission_id) AS submission_count,
    COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.submission_id END) AS graded_count
FROM assignment a
JOIN module m ON a.module_id = m.module_id
JOIN course_offering co ON m.offering_id = co.offering_id
JOIN course c ON co.course_id = c.course_id
JOIN course_teacher ct ON co.offering_id = ct.offering_id
LEFT JOIN assignment_submission s ON a.assignment_id = s.assignment_id AND s.deleted_at IS NULL
WHERE ct.teacher_id = ? AND a.deleted_at IS NULL
";

$params = [$teacher_id];
$types = "i";

if ($search) {
    $sql .= " AND (a.assignment_title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($course_filter) {
    $sql .= " AND c.course_id = ?";
    $params[] = (int)$course_filter;
    $types .= "i";
}

if ($status_filter) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " GROUP BY a.assignment_id ORDER BY a.due_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();

// --------------------------------------------------
// COURSES FOR FILTER
// --------------------------------------------------
$courses = $conn->prepare("
    SELECT DISTINCT c.course_id, c.course_code
    FROM course c
    JOIN course_offering co ON c.course_id = co.course_id
    JOIN course_teacher ct ON co.offering_id = ct.offering_id
    WHERE ct.teacher_id = ?
");
$courses->bind_param("i", $teacher_id);
$courses->execute();
$courseList = $courses->get_result();
$courses->close();

ob_start();
?>

<div class="container-fluid py-4">
    <h3 class="mb-3">Manage Assignments</h3>

    <!-- BACK BUTTON -->
    <a href="<?= url('roles/Faculty/Faculty_tools/Courses/my_courses.php') ?>" 
       class="btn btn-secondary mb-3">← Back to Courses</a>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- FILTERS -->
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="course" class="form-select">
                <option value="">All Courses</option>
                <?php while ($c = $courseList->fetch_assoc()): ?>
                    <option value="<?= $c['course_id'] ?>" <?= $course_filter == $c['course_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['course_code']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- ASSIGNMENT LIST -->
    <?php if ($assignments->num_rows > 0): ?>
        <?php while ($a = $assignments->fetch_assoc()): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5>
                        <?= htmlspecialchars($a['assignment_title']) ?>
                        <span class="badge bg-<?= $a['status'] === 'published' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </h5>
                    <p class="text-muted mb-1"><?= htmlspecialchars($a['course_code']) ?> — <?= htmlspecialchars($a['module_name']) ?></p>
                    <small>Due: <?= date('M d, Y H:i', strtotime($a['due_date'])) ?></small>

                    <div class="mt-2">
                        <span class="badge bg-info">Submissions: <?= $a['submission_count'] ?></span>
                        <span class="badge bg-success">Graded: <?= $a['graded_count'] ?></span>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <a href="edit_assignment.php?assignment_id=<?= $a['assignment_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="?toggle_status=<?= $a['assignment_id'] ?>" class="btn btn-sm btn-outline-warning">Toggle Status</a>
                        <a href="?delete=<?= $a['assignment_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Soft delete this assignment?')">Delete</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">No assignments found.</div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Manage Assignments';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>
