<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$active_page = 'academic';

// Fetch all faculties
$sql = "
    SELECT 
        f.*,
        COUNT(DISTINCT d.department_id) as department_count
    FROM faculty f
    LEFT JOIN department d ON f.faculty_id = d.faculty_id AND d.is_deleted = FALSE
    WHERE f.is_deleted = FALSE
    GROUP BY f.faculty_id
    ORDER BY f.faculty_name
";
$result = $conn->query($sql);
$faculties = [];
while ($row = $result->fetch_assoc()) {
    $faculties[] = $row;
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-building"></i> Faculty Management</h4>
        <a href="create_faculty.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Faculty
        </a>
    </div>

    <?php if (empty($faculties)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No faculties found. Create your first faculty to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Faculty Code</th>
                                <th>Faculty Name</th>
                                <th>Departments</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; foreach ($faculties as $faculty): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td><code><?= htmlspecialchars($faculty['faculty_code']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($faculty['faculty_name']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?= $faculty['department_count'] ?> Departments</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $faculty['faculty_status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($faculty['faculty_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= isset($faculty['created_at']) ? date('M d, Y', strtotime($faculty['created_at'])) : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_faculty.php?id=<?= $faculty['faculty_id'] ?>" 
                                               class="btn btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_faculty.php?id=<?= $faculty['faculty_id'] ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this faculty?')">
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
$page_title = "Faculty Management - LMS";
require_once '../../../templates/layout/master_base.php';
?>