<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

if (!isset($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = intval($_GET['id']);

// Fetch user data
$sql = "
    SELECT 
        u.*,
        GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.role_id
    WHERE u.id = ? AND u.is_deleted = FALSE
    GROUP BY u.id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();

// Student data
$student_data = null;
if (strpos($user['roles'], 'student') !== false) {
    $stmt = $conn->prepare("
        SELECT s.*, p.program_name, p.program_code 
        FROM student s 
        JOIN program p ON s.program_id = p.program_id 
        WHERE s.student_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $student_data = $stmt->get_result()->fetch_assoc();
}

// Teacher data
$teacher_data = null;
if (strpos($user['roles'], 'faculty') !== false) {
    $stmt = $conn->prepare("
        SELECT t.*, d.department_name, d.department_code 
        FROM teacher t 
        JOIN department d ON t.department_id = d.department_id 
        WHERE t.teacher_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $teacher_data = $stmt->get_result()->fetch_assoc();
}

$page_title = "View User - LMS";
require_once include_file('templates/layout/master_base.php');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-person-lines-fill"></i> User Details</h4>
                    <div>
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-light btn-sm me-1">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="user_list.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if (file_exists("../../public/uploads/profile_" . $user['id'] . ".jpg")): ?>
                            <img src="../../public/uploads/profile_<?= $user['id'] ?>.jpg" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px; font-size: 3rem;">
                                <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="mt-3"><?= htmlspecialchars($user['full_name']) ?></h3>
                        <span class="badge bg-primary fs-6"><?= htmlspecialchars(ucwords($user['roles'])) ?></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">User Code</label>
                            <p class="fs-5 border-bottom pb-2"><?= htmlspecialchars($user['user_code']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Email</label>
                            <p class="fs-5 border-bottom pb-2"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Contact Number</label>
                            <p class="fs-5 border-bottom pb-2"><?= htmlspecialchars($user['contact_number'] ?: 'N/A') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Status</label>
                            <p class="fs-5 border-bottom pb-2">
                                <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <?php if ($student_data): ?>
                    <h5 class="text-success mt-4 border-bottom pb-2"><i class="bi bi-mortarboard"></i> Student Info</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Student Number</label>
                            <p><?= htmlspecialchars($student_data['student_number']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Program</label>
                            <p><?= htmlspecialchars($student_data['program_code'] . ' - ' . $student_data['program_name']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Semester</label>
                            <p><?= htmlspecialchars($student_data['current_semester']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">CGPA</label>
                            <p><?= htmlspecialchars($student_data['cgpa'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($teacher_data): ?>
                    <h5 class="text-info mt-4 border-bottom pb-2"><i class="bi bi-person-workspace"></i> Faculty Info</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Employee Number</label>
                            <p><?= htmlspecialchars($teacher_data['employee_number']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Designation</label>
                            <p><?= ucfirst(str_replace('_', ' ', $teacher_data['designation'])) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Department</label>
                            <p><?= htmlspecialchars($teacher_data['department_code'] . ' - ' . $teacher_data['department_name']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted">Hire Date</label>
                            <p><?= date('M d, Y', strtotime($teacher_data['hire_date'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
