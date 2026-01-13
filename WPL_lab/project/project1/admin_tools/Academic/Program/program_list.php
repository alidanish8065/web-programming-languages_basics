<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$active_page = 'academic';

// Fetch all programs with department and faculty info
$sql = "
    SELECT 
        p.*,
        d.department_name,
        d.department_code,
        f.faculty_name,
        COUNT(DISTINCT s.student_id) as student_count,
        COUNT(DISTINCT c.course_id) as course_count
    FROM program p
    JOIN department d ON p.department_id = d.department_id
    JOIN faculty f ON d.faculty_id = f.faculty_id
    LEFT JOIN student s ON p.program_id = s.program_id
    LEFT JOIN program_course pc ON p.program_id = pc.program_id
    LEFT JOIN course c ON pc.course_id = c.course_id
    WHERE p.is_deleted = FALSE
    GROUP BY p.program_id
    ORDER BY f.faculty_name, d.department_name, p.program_name
";
$result = $conn->query($sql);
$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-book"></i> Program Management</h4>
        <a href="create_program.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Program
        </a>
    </div>

    <?php if (empty($programs)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No programs found. Create your first program to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Program Name</th>
                                <th>Department</th>
                                <th>Degree Level</th>
                                <th>Duration</th>
                                <th>Credits</th>
                                <th>Students</th>
                                <th>Courses</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; foreach ($programs as $program): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td><code><?= htmlspecialchars($program['program_code']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($program['program_name']) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($program['department_code']) ?> -
                                            <?= htmlspecialchars($program['department_name']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= ucfirst(htmlspecialchars($program['degree_level'])) ?>
                                        </span>
                                    </td>
                                    <td><?= $program['duration'] ?> years</td>
                                    <td>
                                        <?= $program['minimum_credit_hrs'] ?> credits
                                        <small class="text-muted">(<?= $program['minimum_semesters'] ?> sem)</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?= $program['student_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $program['course_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $program['program_status'] === 'active' ? 'success' : ($program['program_status'] === 'inactive' ? 'secondary' : 'warning') ?>">
                                            <?= ucfirst(str_replace('_', ' ', $program['program_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_program.php?id=<?= $program['program_id'] ?>" 
                                               class="btn btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_program.php?id=<?= $program['program_id'] ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "Program Management - LMS";
require_once '../../../templates/layout/master_base.php';
?>