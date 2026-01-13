<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$active_page = 'academic';

// Fetch all departments with faculty info
$sql = "
    SELECT 
        d.*,
        f.faculty_name,
        f.faculty_code,
        COUNT(DISTINCT p.program_id) as program_count
    FROM department d
    JOIN faculty f ON d.faculty_id = f.faculty_id
    LEFT JOIN program p ON d.department_id = p.department_id AND p.is_deleted = FALSE
    WHERE d.is_deleted = FALSE
    GROUP BY d.department_id
    ORDER BY f.faculty_name, d.department_name
";
$result = $conn->query($sql);
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Department Management</h4>
        <a href="create_department.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Department
        </a>
    </div>

    <?php if (empty($departments)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No departments found. Create your first department to get started.
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
                                <th>Department Name</th>
                                <th>Faculty</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Programs</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; foreach ($departments as $dept): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td><code><?= htmlspecialchars($dept['department_code']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($dept['department_name']) ?></strong></td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($dept['faculty_code']) ?> -
                                            <?= htmlspecialchars($dept['faculty_name']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($dept['email']) ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($dept['contact_number'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $dept['program_count'] ?> Programs</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $dept['department_status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($dept['department_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_department.php?id=<?= $dept['department_id'] ?>" 
                                               class="btn btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_department.php?id=<?= $dept['department_id'] ?>" 
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
$page_title = "Department Management - LMS";
require_once '../../../templates/layout/master_base.php';
?>