<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'teacher'])) {
     header('Location: ' . url('public/login.php'));
    exit;
}

if (!isset($_GET['offering_id'])) {
    die("Invalid course offering ID.");
}

$offering_id = intval($_GET['offering_id']);
$teacher_id = $_SESSION['user_id'];

// Verify teacher has access
$verify_sql = "
    SELECT c.course_code, c.course_name 
    FROM course_teacher ct
    JOIN course_offering co ON ct.offering_id = co.offering_id
    JOIN course c ON co.course_id = c.course_id
    WHERE ct.offering_id = ? AND ct.teacher_id = ?
";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param('ii', $offering_id, $teacher_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    die("You don't have access to this course.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_name = trim($_POST['module_name'] ?? '');
    $module_code = strtoupper(trim($_POST['module_code'] ?? ''));
    $description = trim($_POST['description'] ?? '');
    $sequence_number = intval($_POST['sequence_number'] ?? 1);
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? 'active';
    
    if (empty($module_name) || empty($module_code)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            // Check if module code already exists for this offering
            $check = $conn->prepare("SELECT module_id FROM module WHERE offering_id = ? AND module_code = ?");
            $check->bind_param('is', $offering_id, $module_code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Module code already exists for this course.");
            }
            
            $stmt = $conn->prepare("
                INSERT INTO module (offering_id, module_name, module_code, description, sequence_number, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('isssisss', $offering_id, $module_name, $module_code, $description, $sequence_number, $start_date, $end_date, $status);
            
            if ($stmt->execute()) {
                $success = "Module created successfully!";
                echo "<script>setTimeout(function(){ window.location.href = 'course_content.php?id=$offering_id'; }, 2000);</script>";
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$active_page = 'my_courses';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-folder-plus"></i> Create Module
                    </h4>
                    <a href="Courses/course_content.php?id=<?= $offering_id ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <strong>Course:</strong> <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                    </div>
                    
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
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Module Name <span class="text-danger">*</span></label>
                                <input type="text" name="module_name" class="form-control" required
                                       placeholder="e.g., Introduction to Programming"
                                       value="<?= isset($_POST['module_name']) ? htmlspecialchars($_POST['module_name']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Module Code <span class="text-danger">*</span></label>
                                <input type="text" name="module_code" class="form-control" required
                                       placeholder="e.g., M01" maxlength="20" style="text-transform: uppercase;"
                                       value="<?= isset($_POST['module_code']) ? htmlspecialchars($_POST['module_code']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Brief description of this module..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sequence Number <span class="text-danger">*</span></label>
                                <input type="number" name="sequence_number" class="form-control" required min="1"
                                       value="<?= isset($_POST['sequence_number']) ? $_POST['sequence_number'] : '1' ?>">
                                <small class="text-muted">Order in which this module appears</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="<?= isset($_POST['start_date']) ? $_POST['start_date'] : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="<?= isset($_POST['end_date']) ? $_POST['end_date'] : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Module
                            </button>
                            <a href="course_content.php?id=<?= $offering_id ?>" class="btn btn-secondary">
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
$page_title = "Create Module - LMS";
require_once include_file('templates/layout/master_base.php');
?>