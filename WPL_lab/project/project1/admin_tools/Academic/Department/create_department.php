<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$success = '';
$error = '';

// Fetch faculties for dropdown
$faculties_result = $conn->query("SELECT faculty_id, faculty_name, faculty_code FROM faculty WHERE faculty_status = 'active' AND is_deleted = FALSE ORDER BY faculty_name");
$faculties = [];
while ($row = $faculties_result->fetch_assoc()) {
    $faculties[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_name = trim($_POST['department_name'] ?? '');
    $department_code = strtoupper(trim($_POST['department_code'] ?? ''));
    $faculty_id = intval($_POST['faculty_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $department_status = $_POST['department_status'] ?? 'active';
    
    if (empty($department_name) || empty($department_code) || empty($faculty_id) || empty($email)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            // Check if department code already exists
            $check = $conn->prepare("SELECT department_id FROM department WHERE department_code = ? AND is_deleted = FALSE");
            $check->bind_param('s', $department_code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Department code already exists.");
            }
            
            $stmt = $conn->prepare("
                INSERT INTO department (department_name, department_code, faculty_id, email, contact_number, department_status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ssisss', $department_name, $department_code, $faculty_id, $email, $contact_number, $department_status);
            
            if ($stmt->execute()) {
                $success = "Department created successfully!";
                echo "<script>setTimeout(function(){ window.location.href = 'department_list.php'; }, 2000);</script>";
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$active_page = 'academic';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Create New Department</h4>
                    <a href="department_list.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class='alert alert-success alert-dismissible fade show'>
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class='alert alert-danger alert-dismissible fade show'>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Faculty <span class="text-danger">*</span></label>
                            <select name="faculty_id" class="form-select" required>
                                <option value="">-- Select Faculty --</option>
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?= $faculty['faculty_id'] ?>"
                                            <?= (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $faculty['faculty_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($faculty['faculty_code']) ?> - 
                                        <?= htmlspecialchars($faculty['faculty_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department Name <span class="text-danger">*</span></label>
                                <input type="text" name="department_name" class="form-control" required
                                       placeholder="e.g., Computer Science"
                                       value="<?= isset($_POST['department_name']) ? htmlspecialchars($_POST['department_name']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department Code <span class="text-danger">*</span></label>
                                <input type="text" name="department_code" class="form-control" required
                                       placeholder="e.g., CS" maxlength="10" style="text-transform: uppercase;"
                                       value="<?= isset($_POST['department_code']) ? htmlspecialchars($_POST['department_code']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       placeholder="dept@university.edu"
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                       placeholder="+92-XXX-XXXXXXX"
                                       value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="department_status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Department
                            </button>
                            <a href="department_list.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = "Create Department - LMS";
require_once '../../../templates/layout/master_base.php';
?>