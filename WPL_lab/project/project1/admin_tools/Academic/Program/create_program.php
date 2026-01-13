<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$success = '';
$error = '';

// Fetch departments for dropdown
$departments_result = $conn->query("
    SELECT d.*, f.faculty_name 
    FROM department d
    JOIN faculty f ON d.faculty_id = f.faculty_id
    WHERE d.department_status = 'active' AND d.is_deleted = FALSE 
    ORDER BY f.faculty_name, d.department_name
");
$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_name = trim($_POST['program_name'] ?? '');
    $program_code = strtoupper(trim($_POST['program_code'] ?? ''));
    $department_id = intval($_POST['department_id'] ?? 0);
    $degree_level = $_POST['degree_level'] ?? '';
    $duration = intval($_POST['duration'] ?? 0);
    $minimum_semesters = intval($_POST['minimum_semesters'] ?? 0);
    $minimum_credit_hrs = intval($_POST['minimum_credit_hrs'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $program_status = $_POST['program_status'] ?? 'active';
    
    if (empty($program_name) || empty($program_code) || empty($department_id) || empty($degree_level) || $duration <= 0 || $minimum_semesters <= 0 || $minimum_credit_hrs <= 0) {
        $error = "Please fill all required fields with valid values.";
    } else {
        try {
            // Check if program code already exists
            $check = $conn->prepare("SELECT program_id FROM program WHERE program_code = ? AND is_deleted = FALSE");
            $check->bind_param('s', $program_code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Program code already exists.");
            }
            
            $stmt = $conn->prepare("
                INSERT INTO program (program_name, program_code, department_id, degree_level, duration, minimum_semesters, minimum_credit_hrs, description, program_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ssiisiiss', $program_name, $program_code, $department_id, $degree_level, $duration, $minimum_semesters, $minimum_credit_hrs, $description, $program_status);
            
            if ($stmt->execute()) {
                $success = "Program created successfully!";
                echo "<script>setTimeout(function(){ window.location.href = 'program_list.php'; }, 2000);</script>";
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
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-book"></i> Create New Program</h4>
                    <a href="program_list.php" class="btn btn-light btn-sm">
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
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-primary">Basic Information</h5>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Program Name <span class="text-danger">*</span></label>
                                    <input type="text" name="program_name" class="form-control" required
                                           placeholder="e.g., Bachelor of Science in Computer Science"
                                           value="<?= isset($_POST['program_name']) ? htmlspecialchars($_POST['program_name']) : '' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Program Code <span class="text-danger">*</span></label>
                                    <input type="text" name="program_code" class="form-control" required
                                           placeholder="e.g., BSCS" maxlength="10" style="text-transform: uppercase;"
                                           value="<?= isset($_POST['program_code']) ? htmlspecialchars($_POST['program_code']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department <span class="text-danger">*</span></label>
                                    <select name="department_id" class="form-select" required>
                                        <option value="">-- Select Department --</option>
                                        <?php 
                                        $current_faculty = '';
                                        foreach ($departments as $dept): 
                                            if ($current_faculty !== $dept['faculty_name']) {
                                                if ($current_faculty !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . htmlspecialchars($dept['faculty_name']) . '">';
                                                $current_faculty = $dept['faculty_name'];
                                            }
                                        ?>
                                            <option value="<?= $dept['department_id'] ?>"
                                                    <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['department_code']) ?> - 
                                                <?= htmlspecialchars($dept['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if ($current_faculty !== '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Degree Level <span class="text-danger">*</span></label>
                                    <select name="degree_level" class="form-select" required>
                                        <option value="">-- Select Degree Level --</option>
                                        <option value="diploma" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'diploma') ? 'selected' : '' ?>>Diploma</option>
                                        <option value="bachelors" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'bachelors') ? 'selected' : '' ?>>Bachelor's</option>
                                        <option value="masters" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'masters') ? 'selected' : '' ?>>Master's</option>
                                        <option value="phd" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'phd') ? 'selected' : '' ?>>PhD</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-primary">Program Requirements</h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Duration (Years) <span class="text-danger">*</span></label>
                                    <input type="number" name="duration" class="form-control" required min="1" max="10"
                                           value="<?= isset($_POST['duration']) ? $_POST['duration'] : '4' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Minimum Semesters <span class="text-danger">*</span></label>
                                    <input type="number" name="minimum_semesters" class="form-control" required min="1" max="20"
                                           value="<?= isset($_POST['minimum_semesters']) ? $_POST['minimum_semesters'] : '8' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Minimum Credit Hours <span class="text-danger">*</span></label>
                                    <input type="number" name="minimum_credit_hrs" class="form-control" required min="1" max="300"
                                           value="<?= isset($_POST['minimum_credit_hrs']) ? $_POST['minimum_credit_hrs'] : '120' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Brief description of the program..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="program_status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="phased_out">Phased Out</option>
                            </select>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Program
                            </button>
                            <a href="program_list.php" class="btn btn-secondary">
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
$page_title = "Create Program - LMS";
require_once '../../../templates/layout/master_base.php';
?>