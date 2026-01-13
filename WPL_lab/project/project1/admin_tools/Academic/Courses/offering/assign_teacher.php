<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$offering_id = (int)$_GET['id'];
$active_page = 'offerings';
$success = $error = '';

// Fetch offering details
$stmt = $conn->prepare("
    SELECT co.*, c.course_code, c.course_name, d.department_id
    FROM course_offering co
    JOIN course c ON co.course_id = c.course_id
    JOIN department d ON c.department_id = d.department_id
    WHERE co.offering_id = ?
");
$stmt->bind_param('i', $offering_id);
$stmt->execute();
$offering = $stmt->get_result()->fetch_assoc();

if (!$offering) die('Offering not found.');

// Fetch available teachers from same department
$teachers = $conn->query("
    SELECT t.teacher_id, u.first_name, u.last_name, t.designation
    FROM teacher t
    JOIN users u ON t.teacher_id = u.id
    WHERE t.department_id = {$offering['department_id']} AND t.employment_status = 'active'
    ORDER BY u.first_name
")->fetch_all(MYSQLI_ASSOC);

// Fetch current assignments
$assigned = $conn->query("
    SELECT ct.*, u.first_name, u.last_name, t.designation
    FROM course_teacher ct
    JOIN users u ON ct.teacher_id = u.id
    JOIN teacher t ON ct.teacher_id = t.teacher_id
    WHERE ct.offering_id = $offering_id
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)$_POST['teacher_id'];
    $role = $_POST['role'];
    
    try {
        // Check duplicate
        $check = $conn->prepare("SELECT 1 FROM course_teacher WHERE offering_id=? AND teacher_id=? AND role=?");
        $check->bind_param('iis', $offering_id, $teacher_id, $role);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("This teacher is already assigned with this role.");
        }
        
        $stmt = $conn->prepare("INSERT INTO course_teacher (offering_id, teacher_id, role) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $offering_id, $teacher_id, $role);
        
        if ($stmt->execute()) {
            $success = "Teacher assigned successfully!";
            header("Refresh:0");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle removal
if (isset($_GET['remove'])) {
    $ct_id = (int)$_GET['remove'];
    $conn->query("DELETE FROM course_teacher WHERE course_teacher_id = $ct_id");
    header("Location: assign_teacher.php?id=$offering_id");
    exit;
}

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h5 class="mb-0">
                        <i class="bi bi-person-plus"></i> Assign Teachers: 
                        <?= htmlspecialchars($offering['course_code']) ?>
                    </h5>
                    <a href="offering_list.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <!-- Assign Form -->
                    <form method="POST" class="mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teacher <span class="text-danger">*</span></label>
                                <select name="teacher_id" class="form-select" required>
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?= $t['teacher_id'] ?>">
                                            <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
                                            (<?= ucfirst(str_replace('_', ' ', $t['designation'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="instructor">Instructor</option>
                                    <option value="co_instructor">Co-Instructor</option>
                                    <option value="lab_instructor">Lab Instructor</option>
                                    <option value="teaching_assistant">Teaching Assistant</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus"></i> Assign
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Assigned Teachers -->
                    <h6>Currently Assigned Teachers</h6>
                    <?php if (empty($assigned)): ?>
                        <div class="alert alert-info">No teachers assigned yet.</div>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Teacher</th>
                                    <th>Designation</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                        <td><?= ucfirst(str_replace('_', ' ', $a['designation'])) ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= ucfirst(str_replace('_', ' ', $a['role'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?id=<?= $offering_id ?>&remove=<?= $a['course_teacher_id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Remove this teacher?')">
                                                <i class="bi bi-trash"></i> Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = "Assign Teachers - LMS";
require_once '../../../../templates/layout/master_base.php';
?>