<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') exit;

$teacher_id = $_SESSION['user_id'];
$message = $error = '';

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = (int)$_POST['submission_id'];
    $marks = (int)$_POST['marks_obtained'];
    $feedback = trim($_POST['feedback']);
    
    $stmt = $conn->prepare("
        UPDATE assignment_submission 
        SET marks_obtained = ?, feedback = ?, status = 'graded', 
            graded_by = ?, graded_at = NOW()
        WHERE submission_id = ?
    ");
    $stmt->bind_param("isii", $marks, $feedback, $teacher_id, $submission_id);
    
    if ($stmt->execute()) {
        $message = "Assignment graded successfully!";
    } else {
        $error = "Grading failed.";
    }
    $stmt->close();
}

// Fetch pending submissions
$stmt = $conn->prepare("
    SELECT asub.submission_id, asub.submitted_at, asub.is_late,
           a.assignment_title, a.max_marks, a.assignment_id,
           c.course_code, c.course_name,
           CONCAT(u.first_name, ' ', u.last_name) as student_name,
           s.student_number
    FROM assignment_submission asub
    JOIN assignment a ON asub.assignment_id = a.assignment_id
    JOIN module m ON a.module_id = m.module_id
    JOIN course_offering co ON m.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    JOIN course_teacher ct ON co.offering_id = ct.offering_id
    JOIN student s ON asub.student_id = s.student_id
    JOIN users u ON s.student_id = u.id
    WHERE ct.teacher_id = ? AND asub.status = 'submitted'
    ORDER BY asub.submitted_at ASC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<h3>📝 Grade Assignments</h3>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if (empty($submissions)): ?>
    <div class="alert alert-info">✅ No pending assignments to grade!</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted"><?= count($submissions) ?> submission(s) pending</p>
            
            <div class="accordion" id="submissionsAccordion">
                <?php foreach ($submissions as $idx => $sub): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" 
                                    type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#sub<?= $sub['submission_id'] ?>">
                                <div class="w-100 d-flex justify-content-between pe-3">
                                    <span>
                                        <strong><?= htmlspecialchars($sub['student_name']) ?></strong> 
                                        (<?= $sub['student_number'] ?>)
                                        <?php if ($sub['is_late']): ?>
                                            <span class="badge bg-warning">Late</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($sub['course_code']) ?> - 
                                        <?= htmlspecialchars($sub['assignment_title']) ?>
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="sub<?= $sub['submission_id'] ?>" 
                             class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" 
                             data-bs-parent="#submissionsAccordion">
                            <div class="accordion-body">
                                <p><strong>Course:</strong> <?= htmlspecialchars($sub['course_name']) ?></p>
                                <p><strong>Submitted:</strong> <?= date('M d, Y g:i A', strtotime($sub['submitted_at'])) ?></p>
                                
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="submission_id" value="<?= $sub['submission_id'] ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Marks Obtained *</label>
                                            <input type="number" name="marks_obtained" class="form-control" 
                                                   min="0" max="<?= $sub['max_marks'] ?>" required>
                                            <small class="text-muted">Max: <?= $sub['max_marks'] ?></small>
                                        </div>
                                        
                                        <div class="col-md-9 mb-3">
                                            <label class="form-label">Feedback</label>
                                            <textarea name="feedback" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success">Submit Grade</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="mt-3">
    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Grade Assignments';
require_once include_file('templates/layout/master_base.php');
$conn->close();
?>