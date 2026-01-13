<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$active_page = 'offerings';
$success = $error = '';

// Fetch courses
$courses = $conn->query("
    SELECT c.*, d.department_name 
    FROM course c
    JOIN department d ON c.department_id = d.department_id
    WHERE c.is_deleted = FALSE 
    ORDER BY c.course_code
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $academic_year = trim($_POST['academic_year']);
    $semester = (int)$_POST['semester'];
    $term = $_POST['term'];
    $max_enrollment = !empty($_POST['max_enrollment']) ? (int)$_POST['max_enrollment'] : null;
    $location = trim($_POST['location']);
    
    if (empty($course_id) || empty($academic_year) || empty($semester)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            // Check duplicate
            $check = $conn->prepare("SELECT offering_id FROM course_offering WHERE course_id=? AND academic_year=? AND semester=? AND term=?");
            $check->bind_param('isis', $course_id, $academic_year, $semester, $term);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("This offering already exists.");
            }
            
            // Insert
            $stmt = $conn->prepare("INSERT INTO course_offering (course_id, academic_year, semester, term, max_enrollment, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isisis', $course_id, $academic_year, $semester, $term, $max_enrollment, $location);
            
            if ($stmt->execute()) {
                $success = "Course offering created successfully!";
                echo "<script>setTimeout(() => window.location.href='offering_list.php', 2000);</script>";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$current_year = date('Y');
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Schedule Course Offering</h5>
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
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Course <span class="text-danger">*</span></label>
                            <select name="course_id" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['course_id'] ?>">
                                        <?= htmlspecialchars($c['course_code']) ?> - 
                                        <?= htmlspecialchars($c['course_name']) ?>
                                        (<?= $c['credit_hrs'] ?> credits)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <select name="academic_year" class="form-select" required>
                                    <option value="<?= ($current_year-1) . '-' . $current_year ?>">
                                        <?= ($current_year-1) . '-' . $current_year ?>
                                    </option>
                                    <option value="<?= $current_year . '-' . ($current_year+1) ?>" selected>
                                        <?= $current_year . '-' . ($current_year+1) ?>
                                    </option>
                                    <option value="<?= ($current_year+1) . '-' . ($current_year+2) ?>">
                                        <?= ($current_year+1) . '-' . ($current_year+2) ?>
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <?php for($i=1; $i<=10; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Term <span class="text-danger">*</span></label>
                                <select name="term" class="form-select" required>
                                    <option value="Fall" selected>Fall</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximum Enrollment</label>
                                <input type="number" name="max_enrollment" class="form-control" 
                                       placeholder="Leave empty for unlimited" min="1">
                                <small class="text-muted">Maximum students allowed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location/Room</label>
                                <input type="text" name="location" class="form-control" 
                                       placeholder="e.g., Room 301, Lab A">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            After creating, you can assign faculty to this offering.
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Offering
                        </button>
                        <a href="offering_list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = "Schedule Course Offering - LMS";
require_once '../../../../templates/layout/master_base.php';
?>