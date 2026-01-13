<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_name = trim($_POST['faculty_name'] ?? '');
    $faculty_code = strtoupper(trim($_POST['faculty_code'] ?? ''));
    $faculty_status = $_POST['faculty_status'] ?? 'active';
    
    if (empty($faculty_name) || empty($faculty_code)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            // Check if faculty code already exists
            $check = $conn->prepare("SELECT faculty_id FROM faculty WHERE faculty_code = ? AND is_deleted = FALSE");
            $check->bind_param('s', $faculty_code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Faculty code already exists.");
            }
            
            $stmt = $conn->prepare("
                INSERT INTO faculty (faculty_name, faculty_code, faculty_status) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param('sss', $faculty_name, $faculty_code, $faculty_status);
            
            if ($stmt->execute()) {
                $success = "Faculty created successfully!";
                echo "<script>setTimeout(function(){ window.location.href = 'faculty_list.php'; }, 2000);</script>";
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
                    <h4 class="mb-0"><i class="bi bi-building"></i> Create New Faculty</h4>
                    <a href="faculty_list.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class='alert alert-success alert-dismissible fade show'>
                            <i class="bi bi-check-circle-fill"></i>
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class='alert alert-danger alert-dismissible fade show'>
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Faculty Name <span class="text-danger">*</span></label>
                            <input type="text" name="faculty_name" class="form-control" required
                                   placeholder="e.g., Faculty of Engineering"
                                   value="<?= isset($_POST['faculty_name']) ? htmlspecialchars($_POST['faculty_name']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Faculty Code <span class="text-danger">*</span></label>
                            <input type="text" name="faculty_code" class="form-control" required
                                   placeholder="e.g., FOE" maxlength="10" style="text-transform: uppercase;"
                                   value="<?= isset($_POST['faculty_code']) ? htmlspecialchars($_POST['faculty_code']) : '' ?>">
                            <small class="text-muted">Short code for the faculty (will be converted to uppercase)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="faculty_status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Faculty
                            </button>
                            <a href="faculty_list.php" class="btn btn-secondary">
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
$page_title = "Create Faculty - LMS";
require_once '../../../templates/layout/master_base.php';
?>