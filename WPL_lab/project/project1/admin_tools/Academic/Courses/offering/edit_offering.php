<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$offering_id = (int)$_GET['id'];
$active_page = 'offerings';
$success = $error = '';

// Fetch offering
$stmt = $conn->prepare("
    SELECT co.*, c.course_code, c.course_name 
    FROM course_offering co
    JOIN course c ON co.course_id = c.course_id
    WHERE co.offering_id = ?
");
$stmt->bind_param('i', $offering_id);
$stmt->execute();
$offering = $stmt->get_result()->fetch_assoc();

if (!$offering) die('Offering not found.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year = trim($_POST['academic_year']);
    $semester = (int)$_POST['semester'];
    $term = $_POST['term'];
    $max_enrollment = !empty($_POST['max_enrollment']) ? (int)$_POST['max_enrollment'] : null;
    $location = trim($_POST['location']);
    
    try {
        $stmt = $conn->prepare("UPDATE course_offering SET academic_year=?, semester=?, term=?, max_enrollment=?, location=? WHERE offering_id=?");
        $stmt->bind_param('sisisi', $academic_year, $semester, $term, $max_enrollment, $location, $offering_id);
        
        if ($stmt->execute()) {
            $success = "Offering updated successfully!";
            echo "<script>setTimeout(() => window.location.href='offering_list.php', 2000);</script>";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
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
                    <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Offering: <?= htmlspecialchars($offering['course_code']) ?></h5>
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
                            <label class="form-label">Course</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($offering['course_code'] . ' - ' . $offering['course_name']) ?>" 
                                   readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <select name="academic_year" class="form-select" required>
                                    <option value="<?= ($current_year-1) . '-' . $current_year ?>" 
                                            <?= $offering['academic_year'] == ($current_year-1) . '-' . $current_year ? 'selected' : '' ?>>
                                        <?= ($current_year-1) . '-' . $current_year ?>
                                    </option>
                                    <option value="<?= $current_year . '-' . ($current_year+1) ?>" 
                                            <?= $offering['academic_year'] == $current_year . '-' . ($current_year+1) ? 'selected' : '' ?>>
                                        <?= $current_year . '-' . ($current_year+1) ?>
                                    </option>
                                    <option value="<?= ($current_year+1) . '-' . ($current_year+2) ?>" 
                                            <?= $offering['academic_year'] == ($current_year+1) . '-' . ($current_year+2) ? 'selected' : '' ?>>
                                        <?= ($current_year+1) . '-' . ($current_year+2) ?>
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <?php for($i=1; $i<=10; $i++): ?>
                                        <option value="<?= $i ?>" <?= $offering['semester'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Term <span class="text-danger">*</span></label>
                                <select name="term" class="form-select" required>
                                    <option value="Fall" <?= $offering['term'] == 'Fall' ? 'selected' : '' ?>>Fall</option>
                                    <option value="Spring" <?= $offering['term'] == 'Spring' ? 'selected' : '' ?>>Spring</option>
                                    <option value="Summer" <?= $offering['term'] == 'Summer' ? 'selected' : '' ?>>Summer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maximum Enrollment</label>
                                <input type="number" name="max_enrollment" class="form-control" 
                                       value="<?= $offering['max_enrollment'] ?>" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location/Room</label>
                                <input type="text" name="location" class="form-control" 
                                       value="<?= htmlspecialchars($offering['location'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Offering
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
$page_title = "Edit Offering - LMS";
require_once '../../../../templates/layout/master_base.php';
?>