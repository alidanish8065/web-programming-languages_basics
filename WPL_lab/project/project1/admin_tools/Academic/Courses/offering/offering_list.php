<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$active_page = 'offerings';

// Fetch all offerings
$sql = "
    SELECT 
        co.*,
        c.course_code,
        c.course_name,
        c.credit_hrs,
        d.department_code,
        GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as teachers,
        COUNT(DISTINCT e.student_id) as enrolled_count
    FROM course_offering co
    JOIN course c ON co.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    LEFT JOIN course_teacher ct ON co.offering_id = ct.offering_id
    LEFT JOIN users u ON ct.teacher_id = u.id
    LEFT JOIN enrollment e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
    WHERE c.is_deleted = FALSE
    GROUP BY co.offering_id
    ORDER BY co.academic_year DESC, co.semester DESC, c.course_code
";
$offerings = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-calendar-event"></i> Course Offerings</h4>
        <div>
            <a href="../course_list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Courses
            </a>
            <a href="create_offering.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Schedule Course
            </a>
        </div>
    </div>

    <?php if (empty($offerings)): ?>
        <div class="alert alert-info">No course offerings scheduled.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Teachers</th>
                            <th>Enrolled</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offerings as $i => $o): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($o['course_code']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($o['course_name']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($o['academic_year']) ?></td>
                                <td><span class="badge bg-info">Sem <?= $o['semester'] ?></span></td>
                                <td><span class="badge bg-secondary"><?= $o['term'] ?></span></td>
                                <td>
                                    <?php if ($o['teachers']): ?>
                                        <small><?= htmlspecialchars($o['teachers']) ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <?= $o['enrolled_count'] ?><?= $o['max_enrollment'] ? '/' . $o['max_enrollment'] : '' ?>
                                    </span>
                                </td>
                                <td><small><?= htmlspecialchars($o['location'] ?? 'TBA') ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="assign_teacher.php?id=<?= $o['offering_id'] ?>" class="btn btn-info" title="Assign Teacher">
                                            <i class="bi bi-person-plus"></i>
                                        </a>
                                        <a href="edit_offering.php?id=<?= $o['offering_id'] ?>" class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete_offering.php?id=<?= $o['offering_id'] ?>" class="btn btn-danger" 
                                           onclick="return confirm('Delete this offering?')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = "Course Offerings - LMS";
require_once '../../../../templates/layout/master_base.php';
?>