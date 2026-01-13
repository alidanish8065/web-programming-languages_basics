<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

function luhnChecksum($number) {
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = intval($number[$i]);
        if ($alt) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt = !$alt;
    }
    return (10 - ($sum % 10)) % 10;
}
function generateUserCode($role, $conn) {

    // Role → numeric code mapping
    $roleCodes = [
        'student'      => '01',
        'faculty'      => '02',
        'admin'        => '07',
        'superadmin'   => '09',
        'accounts'     => '08',
        'student_affairs' => '03',
        'examination' => '04',
        'admission' => '05',
        'accounts' => '06',
    ];

    $roleCode = $roleCodes[$role] ?? '99';
    $year = date('y');

    // Atomic sequence per role per year
    $seqStmt = $conn->prepare("
        INSERT INTO user_code_sequence (role, year, last_number)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE last_number = last_number + 1
    ");
    $seqStmt->bind_param("ss", $role, $year);
    $seqStmt->execute();
    $seqStmt->close();

    $getStmt = $conn->prepare("
        SELECT last_number FROM user_code_sequence
        WHERE role = ? AND year = ?
    ");
    $getStmt->bind_param("ss", $role, $year);
    $getStmt->execute();
    $getStmt->bind_result($lastNumber);
    $getStmt->fetch();
    $getStmt->close();

    $serial = str_pad($lastNumber, 5, '0', STR_PAD_LEFT);

    // Core for checksum
    $core = $roleCode . $year . $serial;
    $checksum = luhnChecksum($core);

    return "UI{$roleCode}-{$year}{$serial}-{$checksum}";
}


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

// Fetch departments for dropdown
$departments_result = $conn->query("SELECT department_id, department_name, department_code, faculty_id FROM department WHERE department_status = 'active' AND is_deleted = FALSE ORDER BY department_name");
$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch programs for dropdown
$programs_result = $conn->query("SELECT program_id, program_name, program_code, degree_level, department_id FROM program WHERE program_status = 'active' AND is_deleted = FALSE ORDER BY program_name");
$programs = [];
while ($row = $programs_result->fetch_assoc()) {
    $programs[] = $row;
}

// Fetch roles for dropdown
$roles_result = $conn->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
$roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $roles[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $cnic = trim($_POST['cnic'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role_id)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            $conn->begin_transaction();
            
            // Get the selected role name
            $role_stmt = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ?");
            $role_stmt->bind_param('i', $role_id);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $role_row = $role_result->fetch_assoc();
            $role_name = $role_row['role_name'] ?? '';
            
            if (empty($role_name)) {
                throw new Exception("Invalid role selected.");
            }
            
            // Generate unique user code
            $user_code = generateUserCode($role_name, $conn);
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert into users table
            $stmt = $conn->prepare("
                INSERT INTO users (first_name, last_name, email, contact_number, password_hash, user_code, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param('ssssss', $first_name, $last_name, $email, $contact_number, $password_hash, $user_code);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user: " . $stmt->error);
            }
            
            $user_id = $conn->insert_id;
            
            // Insert user role
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->bind_param('ii', $user_id, $role_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to assign role: " . $stmt->error);
            }
            
            // Initialize variables for success message
            $student_number = null;
            $employee_number = null;
            
            // Handle role-specific inserts
            if ($role_name === 'student') {
                $program_id = $_POST['program_id'] ?? null;
                $admission_year = $_POST['admission_year'] ?? date('Y');
                $current_semester = $_POST['current_semester'] ?? 1;
                
                if (empty($program_id)) {
                    throw new Exception("Program selection is required for student role.");
                }
                
                // Generate student number
                $program_result = $conn->query("SELECT program_code FROM program WHERE program_id = $program_id");
                if (!$program_result) {
                    throw new Exception("Failed to fetch program details.");
                }
                $program_row = $program_result->fetch_assoc();
                $program_code = $program_row['program_code'];
                $year = date('Y');
                
                // Get or create sequence
                $seq_stmt = $conn->prepare("
                    INSERT INTO student_number_sequence (year, program_code, last_number) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE last_number = last_number + 1
                ");
                $seq_stmt->bind_param('is', $year, $program_code);
                $seq_stmt->execute();
                
                $last_num_result = $conn->query("
                    SELECT last_number FROM student_number_sequence 
                    WHERE year = $year AND program_code = '$program_code'
                ");
                $last_num_row = $last_num_result->fetch_assoc();
                $last_num = $last_num_row['last_number'];
                
                $student_number = $program_code . '-' . $year . '-' . str_pad($last_num, 4, '0', STR_PAD_LEFT);
                
                // Get program credit hours
                $credit_result = $conn->query("SELECT minimum_credit_hrs FROM program WHERE program_id = $program_id");
                $credit_row = $credit_result->fetch_assoc();
                $min_credit_hrs = $credit_row['minimum_credit_hrs'];
                
                // Insert student - FIX: Use 'active' instead of 'enrolled'
                $stmt = $conn->prepare("
                    INSERT INTO student (student_id, program_id, student_number, admission_year, current_semester, enrollment_status, academic_standing, remaining_credit_hrs) 
                    VALUES (?, ?, ?, ?, ?, 'active', 'good', ?)
                ");
                $stmt->bind_param('iisiii', $user_id, $program_id, $student_number, $admission_year, $current_semester, $min_credit_hrs);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create student record: " . $stmt->error);
                }
                
            } elseif ($role_name === 'faculty') {
                $department_id = $_POST['department_id'] ?? null;
                $designation = $_POST['designation'] ?? 'lecturer';
                $hire_date = $_POST['hire_date'] ?? date('Y-m-d');
                
                if (empty($department_id)) {
                    throw new Exception("Department selection is required for faculty role.");
                }
                
                // Generate employee number
                $dept_result = $conn->query("SELECT department_code FROM department WHERE department_id = $department_id");
                if (!$dept_result) {
                    throw new Exception("Failed to fetch department details.");
                }
                $dept_row = $dept_result->fetch_assoc();
                $dept_code = $dept_row['department_code'];
                
                $emp_result = $conn->query("SELECT COUNT(*) as count FROM teacher");
                $emp_row = $emp_result->fetch_assoc();
                $emp_count = $emp_row['count'];
                
                $employee_number = $dept_code . '-EMP-' . str_pad($emp_count + 1, 5, '0', STR_PAD_LEFT);
                
                // Insert teacher
                $stmt = $conn->prepare("
                    INSERT INTO teacher (teacher_id, department_id, employee_number, designation, hire_date, employment_status) 
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmt->bind_param('iisss', $user_id, $department_id, $employee_number, $designation, $hire_date);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create teacher record: " . $stmt->error);
                }
            }
            // For other roles (admin, accounts, etc.), no additional tables needed
            
            $conn->commit();
            
            // Build success message
            $success = "User created successfully!<br>";
            $success .= "User Code: <strong>$user_code</strong><br>";
            $success .= "Role: <strong>" . ucfirst($role_name) . "</strong>";
            
            if ($student_number) {
                $success .= "<br>Student Number: <strong>$student_number</strong>";
            }
            if ($employee_number) {
                $success .= "<br>Employee Number: <strong>$employee_number</strong>";
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error creating user: " . $e->getMessage();
        }
    }
}

$active_page = 'users';

ob_start();
?>

<!-- Rest of your HTML code remains the same -->
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Create New User</h4>
                    <a href="user_list.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Users
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class='alert alert-success alert-dismissible fade show'>
                            <i class="bi bi-check-circle-fill"></i>
                            <?= $success ?>
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
                    
                    <form method="POST" id="createUserForm">
                        <!-- Basic Information -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-primary">
                                <i class="bi bi-person-badge"></i> Basic Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" required 
                                           value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required
                                           value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" placeholder="+92-XXX-XXXXXXX"
                                           value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CNIC Number</label>
                                    <input type="text" name="cnic" class="form-control" placeholder="XXXXX-XXXXXXX-X" maxlength="15"
                                           value="<?= isset($_POST['cnic']) ? htmlspecialchars($_POST['cnic']) : '' ?>">
                                    <small class="text-muted">Format: 12345-1234567-1</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                        </div>
                        
                        <!-- Role Selection -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-primary">
                                <i class="bi bi-shield-check"></i> Role Assignment
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Select Role <span class="text-danger">*</span></label>
                                <select name="role_id" id="roleSelect" class="form-select" required onchange="showRoleFields()">
                                    <option value="">-- Select a Role --</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>" 
                                                data-role="<?= $role['role_name'] ?>"
                                                <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                            <?= ucfirst(htmlspecialchars($role['role_name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Student-specific fields -->
                        <div id="student-fields" class="role-specific border rounded p-3 mb-4 bg-light" style="display:none">
                            <h5 class="mb-3 text-success">
                                <i class="bi bi-mortarboard-fill"></i> Student Information
                            </h5>
                            
                            <!-- Program Selection -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Degree Level</label>
                                    <select name="degree_level" class="form-select" id="degreeLevel" onchange="filterPrograms()">
                                        <option value="">-- Select Degree Level --</option>
                                        <option value="diploma" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'diploma') ? 'selected' : '' ?>>Diploma</option>
                                        <option value="bachelors" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'bachelors') ? 'selected' : '' ?>>Bachelor's</option>
                                        <option value="masters" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'masters') ? 'selected' : '' ?>>Master's</option>
                                        <option value="phd" <?= (isset($_POST['degree_level']) && $_POST['degree_level'] == 'phd') ? 'selected' : '' ?>>PhD</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program/Subject <span class="text-danger">*</span></label>
                                    <select name="program_id" id="programSelect" class="form-select">
                                        <option value="">-- Select Program --</option>
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?= $program['program_id'] ?>" 
                                                    data-degree="<?= $program['degree_level'] ?>"
                                                    <?= (isset($_POST['program_id']) && $_POST['program_id'] == $program['program_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($program['program_code']) ?> - 
                                                <?= htmlspecialchars($program['program_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">This includes the subject/specialization</small>
                                </div>
                            </div>

                            <!-- Semester and Academic Year -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Semester <span class="text-danger">*</span></label>
                                    <select name="current_semester" class="form-select">
                                        <option value="1" selected>Semester 1</option>
                                        <?php for($i = 2; $i <= 8; $i++): ?>
                                            <option value="<?= $i ?>" <?= (isset($_POST['current_semester']) && $_POST['current_semester'] == $i) ? 'selected' : '' ?>>
                                                Semester <?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Admission Year <span class="text-danger">*</span></label>
                                    <input type="number" name="admission_year" class="form-control" 
                                           value="<?= isset($_POST['admission_year']) ? $_POST['admission_year'] : date('Y') ?>" 
                                           min="2000" max="<?= date('Y') + 1 ?>">
                                </div>
                            </div>

                            <!-- Term -->
                            <div class="mb-3">
                                <label class="form-label">Enrollment Term</label>
                                <select name="enrollment_term" class="form-select">
                                    <option value="Fall" selected>Fall</option>
                                    <option value="Spring" <?= (isset($_POST['enrollment_term']) && $_POST['enrollment_term'] == 'Spring') ? 'selected' : '' ?>>Spring</option>
                                    <option value="Summer" <?= (isset($_POST['enrollment_term']) && $_POST['enrollment_term'] == 'Summer') ? 'selected' : '' ?>>Summer</option>
                                </select>
                            </div>
                        </div>

                        <!-- Faculty-specific fields -->
                        <div id="faculty-fields" class="role-specific border rounded p-3 mb-4 bg-light" style="display:none">
                            <h5 class="mb-3 text-info">
                                <i class="bi bi-person-workspace"></i> Faculty Information
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select">
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['department_id'] ?>"
                                                <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['department_code']) ?> - 
                                            <?= htmlspecialchars($dept['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Designation</label>
                                    <select name="designation" class="form-select">
                                        <option value="lecturer" selected>Lecturer</option>
                                        <option value="assistant_professor" <?= (isset($_POST['designation']) && $_POST['designation'] == 'assistant_professor') ? 'selected' : '' ?>>Assistant Professor</option>
                                        <option value="associate_professor" <?= (isset($_POST['designation']) && $_POST['designation'] == 'associate_professor') ? 'selected' : '' ?>>Associate Professor</option>
                                        <option value="professor" <?= (isset($_POST['designation']) && $_POST['designation'] == 'professor') ? 'selected' : '' ?>>Professor</option>
                                        <option value="lab_assistant" <?= (isset($_POST['designation']) && $_POST['designation'] == 'lab_assistant') ? 'selected' : '' ?>>Lab Assistant</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hire Date</label>
                                    <input type="date" name="hire_date" class="form-control" 
                                           value="<?= isset($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Admin/Other roles fields -->
                        <div id="other-fields" class="role-specific border rounded p-3 mb-4 bg-light" style="display:none">
                            <h5 class="mb-3 text-warning">
                                <i class="bi bi-shield-check"></i> Role Information
                            </h5>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No additional information required for this role. Basic user account will be created.
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Create User
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
function showRoleFields() {
    // Hide all role-specific fields first
    document.querySelectorAll('.role-specific').forEach(el => el.style.display = 'none');
    
    // Disable all inputs in role-specific fields to prevent validation errors
    const roleInputs = document.querySelectorAll('.role-specific input, .role-specific select');
    roleInputs.forEach(input => {
        input.disabled = true;
        input.required = false;
    });

    // Get selected role
    const select = document.getElementById('roleSelect');
    if (!select.value) return;

    const selectedOption = select.options[select.selectedIndex];
    const roleName = selectedOption.getAttribute('data-role');
    
    let activeSection = null;

    // Show relevant fields
    if (roleName === 'student') {
        activeSection = document.getElementById('student-fields');
    } else if (roleName === 'faculty') {
        activeSection = document.getElementById('faculty-fields');
    } else {
        // Generic roles (admin, accounts, etc.)
        activeSection = document.getElementById('other-fields');
    }

    if (activeSection) {
        activeSection.style.display = 'block';
        // Enable inputs in the active section
        activeSection.querySelectorAll('input, select').forEach(input => {
            input.disabled = false;
        });
    }
}

function filterPrograms() {
    const degreeLevel = document.getElementById('degreeLevel').value;
    const programSelect = document.getElementById('programSelect');
    const options = programSelect.getElementsByTagName('option');
    
    for (let i = 1; i < options.length; i++) {
        const option = options[i];
        const optionDegree = option.getAttribute('data-degree');
        
        if (degreeLevel === '' || optionDegree === degreeLevel) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    }
    
    // Reset selection if current selection is hidden
    if (programSelect.selectedIndex > 0 && 
        options[programSelect.selectedIndex].style.display === 'none') {
        programSelect.selectedIndex = 0;
    }
}

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

// Show role fields on page load if role was previously selected
document.addEventListener('DOMContentLoaded', function() {
    showRoleFields();
    filterPrograms();
});
</script>

<?php
$content = ob_get_clean();
$page_title = "Create User - LMS";
require_once include_file('templates/layout/master_base.php');
?>