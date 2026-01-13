<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

if (!isset($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = intval($_GET['id']);

// Fetch user data with roles
$sql = "
    SELECT 
        u.*,
        GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles,
        GROUP_CONCAT(r.role_id SEPARATOR ',') as role_ids
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

// Fetch student data if user is a student
$student_data = null;
if (strpos($user['roles'], 'student') !== false) {
    $stmt = $conn->prepare("SELECT s.*, p.program_name, p.program_code, p.degree_level, p.program_id FROM student s JOIN program p ON s.program_id = p.program_id WHERE s.student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();
}

// Fetch teacher data if user is faculty
$teacher_data = null;
if (strpos($user['roles'], 'faculty') !== false) {
    $stmt = $conn->prepare("SELECT t.*, d.department_name, d.department_code, d.department_id FROM teacher t JOIN department d ON t.department_id = d.department_id WHERE t.teacher_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_data = $result->fetch_assoc();
    $stmt->close();
}

// Fetch dropdowns
$departments = $programs = $enrollment_statuses = [];
$departments_result = $conn->query("SELECT department_id, department_name, department_code FROM department WHERE department_status='active' AND is_deleted=FALSE ORDER BY department_name");
while($row=$departments_result->fetch_assoc()) $departments[]=$row;
$programs_result = $conn->query("SELECT program_id, program_name, program_code, degree_level FROM program WHERE program_status='active' AND is_deleted=FALSE ORDER BY program_name");
while($row=$programs_result->fetch_assoc()) $programs[]=$row;
$enrollment_statuses_result = $conn->query("SELECT DISTINCT status FROM enrollment ORDER BY status");
while($row=$enrollment_statuses_result->fetch_assoc()) $enrollment_statuses[]=$row['status'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $error = "Email is required.";
    } else {
        try {
            $conn->begin_transaction();

            // Update user table
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET email=?, contact_number=?, password_hash=?, status=? WHERE id=?");
                $stmt->bind_param('ssssi',$email,$contact_number,$password_hash,$status,$user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET email=?, contact_number=?, status=? WHERE id=?");
                $stmt->bind_param('sssi',$email,$contact_number,$status,$user_id);
            }
            $stmt->execute();

            // Student program / status request handling
            if ($student_data) {
                $current_semester = intval($_POST['current_semester']);
                $stmt = $conn->prepare("UPDATE student SET current_semester=? WHERE student_id=?");
                $stmt->bind_param('ii', $current_semester, $user_id);
                $stmt->execute();

                // Optional: Program/status change requests
                $new_program_id = intval($_POST['new_program_id'] ?? 0);
                $new_status = $_POST['new_enrollment_status'] ?? null;
                if($new_program_id || $new_status) {
                    $stmt = $conn->prepare("INSERT INTO student_program_change_requests (student_id,new_program_id,new_status,requested_by) VALUES (?,?,?,?)");
                    $stmt->bind_param('iisi',$user_id,$new_program_id,$new_status,$_SESSION['user_id']);
                    $stmt->execute();
                }
            }

            // Teacher update with designation history
            if ($teacher_data) {
                $department_id = $_POST['department_id'] ?? $teacher_data['department_id'];
                $designation = $_POST['designation'] ?? $teacher_data['designation'];
                $employment_status = $_POST['employment_status'] ?? $teacher_data['employment_status'];

                if($designation !== $teacher_data['designation']) {
                    $stmt = $conn->prepare("INSERT INTO teacher_designation_history (teacher_id,old_designation,new_designation,changed_by) VALUES (?,?,?,?)");
                    $stmt->bind_param('sssi',$user_id,$teacher_data['designation'],$designation,$_SESSION['user_id']);
                    $stmt->execute();
                }

                $stmt = $conn->prepare("UPDATE teacher SET department_id=?, designation=?, employment_status=? WHERE teacher_id=?");
                $stmt->bind_param('issi',$department_id,$designation,$employment_status,$user_id);
                $stmt->execute();
            }

            $conn->commit();
            $success = "User updated successfully!";

            echo "<script>setTimeout(function(){window.location.href='user_list.php';},2000);</script>";

        } catch(Exception $e) {
            $conn->rollback();
            $error = "Error updating user: ".$e->getMessage();
        }
    }
}

// Include existing HTML form here, add dropdowns for new_program_id and new_enrollment_status in student section, designation handled as select with current value selected
$active_page = 'users';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Edit User</h4>
                    <a href="user_list.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Users
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
                    
                    <form method="POST" id="editUserForm">
                        <!-- Read-Only Information -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-secondary">
                                <i class="bi bi-lock-fill"></i> Non-Editable Information
                            </h5>
                            
                            <!-- In the "Non-Editable Information" section, replace the role-specific blocks: -->

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">User Code</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($user['user_code']) ?>" readonly>
        <small class="text-muted">User code cannot be changed</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
        <small class="text-muted">Name cannot be changed</small>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Roles</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars(ucwords(str_replace(',', ', ', $user['roles']))) ?>" readonly>
        <small class="text-muted">Roles cannot be changed</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Account Created</label>
        <input type="text" class="form-control" value="<?= date('M d, Y', strtotime($user['created_at'])) ?>" readonly>
    </div>
</div>

<?php if ($student_data): ?>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Student Number</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($student_data['student_number']) ?>" readonly>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Admission Year</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($student_data['admission_year']) ?>" readonly>
    </div>
</div>
<?php endif; ?>

<?php if ($teacher_data): ?>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Employee Number</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($teacher_data['employee_number']) ?>" readonly>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Hire Date</label>
        <input type="text" class="form-control" value="<?= date('M d, Y', strtotime($teacher_data['hire_date'])) ?>" readonly>
    </div>
</div>
<?php endif; ?>
                           
                        </div>
                        
                        <!-- Editable Contact Information -->
                        <div class="border rounded p-3 mb-4">
                            <h5 class="mb-3 text-primary">
                                <i class="bi bi-envelope-fill"></i> Contact Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?= htmlspecialchars($user['email']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control"
                                           value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Settings -->
                        <div class="border rounded p-3 mb-4">
                            <h5 class="mb-3 text-primary">
                                <i class="bi bi-gear-fill"></i> Account Settings
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control" minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                            <i class="bi bi-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Leave empty to keep current password</small>
                                </div>
                            </div>
                        </div>

                        <!-- Student-specific editable fields -->
                   <!-- In the Student-specific editable fields section, replace with: -->

<?php if ($student_data): ?>
<div class="border rounded p-3 mb-4">
    <h5 class="mb-3 text-success">
        <i class="bi bi-mortarboard-fill"></i> Student Information
    </h5>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Current Program</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student_data['program_code']) ?> - <?= htmlspecialchars($student_data['program_name']) ?>" readonly>
            <small class="text-muted">Current program (read-only)</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Request Program Change</label>
            <select name="new_program_id" class="form-select">
                <option value="0">No Change</option>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= $program['program_id'] ?>">
                        <?= htmlspecialchars($program['program_code']) ?> - 
                        <?= htmlspecialchars($program['program_name']) ?> 
                        (<?= htmlspecialchars($program['degree_level']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Select new program for change request</small>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Current Semester</label>
            <select name="current_semester" class="form-select">
                <?php for($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?>" <?= $student_data['current_semester'] == $i ? 'selected' : '' ?>>
                        Semester <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Current Enrollment Status</label>
            <input type="text" class="form-control" value="<?= ucfirst(htmlspecialchars($student_data['enrollment_status'])) ?>" readonly>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Request Status Change</label>
            <select name="new_enrollment_status" class="form-select">
                <option value="">No Change</option>
                <?php foreach ($enrollment_statuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>">
                        <?= ucfirst(htmlspecialchars($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Select new status for change request</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Academic Standing</label>
            <input type="text" class="form-control" value="<?= ucfirst(htmlspecialchars($student_data['academic_standing'])) ?>" readonly>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">CGPA</label>
            <input type="text" class="form-control" value="<?= $student_data['cgpa'] ? number_format($student_data['cgpa'], 2) : 'N/A' ?>" readonly>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Completed Credits</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student_data['completed_credit_hrs']) ?>" readonly>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Remaining Credits</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($student_data['remaining_credit_hrs']) ?>" readonly>
        </div>
    </div>
</div>
<?php endif; ?>
                        <!-- Teacher-specific editable fields -->
                        <?php if ($teacher_data): ?>
                        <div class="border rounded p-3 mb-4">
                            <h5 class="mb-3 text-info">
                                <i class="bi bi-person-workspace"></i> Faculty Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <select name="department_id" class="form-select">
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['department_id'] ?>"
                                                    <?= $teacher_data['department_id'] == $dept['department_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['department_code']) ?> - 
                                                <?= htmlspecialchars($dept['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Designation</label>
                                    <select name="designation" class="form-select">
                                        <option value="lecturer" <?= $teacher_data['designation'] === 'lecturer' ? 'selected' : '' ?>>Lecturer</option>
                                        <option value="assistant_professor" <?= $teacher_data['designation'] === 'assistant_professor' ? 'selected' : '' ?>>Assistant Professor</option>
                                        <option value="associate_professor" <?= $teacher_data['designation'] === 'associate_professor' ? 'selected' : '' ?>>Associate Professor</option>
                                        <option value="professor" <?= $teacher_data['designation'] === 'professor' ? 'selected' : '' ?>>Professor</option>
                                        <option value="lab_assistant" <?= $teacher_data['designation'] === 'lab_assistant' ? 'selected' : '' ?>>Lab Assistant</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Employment Status</label>
                                <select name="employment_status" class="form-select">
                                    <option value="active" <?= $teacher_data['employment_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="on_leave" <?= $teacher_data['employment_status'] === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                                    <option value="resigned" <?= $teacher_data['employment_status'] === 'resigned' ? 'selected' : '' ?>>Resigned</option>
                                    <option value="retired" <?= $teacher_data['employment_status'] === 'retired' ? 'selected' : '' ?>>Retired</option>
                                    <option value="terminated" <?= $teacher_data['employment_status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to update this user?')">
                                <i class="bi bi-check-circle"></i> Update User
                            </button>
                            <a href="user_list.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
}
</script>

<?php
$content = ob_get_clean();
$page_title = "Edit User - LMS";
require_once include_file('templates/layout/master_base.php');
?>