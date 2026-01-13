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
        GROUP_CONCAT(DISTINCT r.role_name SEPARATOR ', ') as roles
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
    die("User not found or already deleted.");
}

$user = $result->fetch_assoc();

// Prevent self-deletion
if ($user_id == $_SESSION['user_id']) {
    die("You cannot delete your own account.");
}

// Check if user has any critical data
$has_enrollments = false;
$has_courses = false;
$has_submissions = false;

// Check for student data
if (strpos($user['roles'], 'student') !== false) {
    $check = $conn->query("SELECT COUNT(*) as count FROM enrollment WHERE student_id = $user_id");
    $row = $check->fetch_assoc();
    $has_enrollments = $row['count'] > 0;
    
    $check = $conn->query("SELECT COUNT(*) as count FROM assignment_submission WHERE student_id = $user_id");
    $row = $check->fetch_assoc();
    $has_submissions = $row['count'] > 0;
}

// Check for faculty data
if (strpos($user['roles'], 'faculty') !== false) {
    $check = $conn->query("SELECT COUNT(*) as count FROM course_teacher WHERE teacher_id = $user_id");
    $row = $check->fetch_assoc();
    $has_courses = $row['count'] > 0;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete_type = $_POST['delete_type'] ?? 'soft';
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm !== 'DELETE') {
        $error = "Please type 'DELETE' to confirm deletion.";
    } else {
        try {
            $conn->begin_transaction();
            
            if ($delete_type === 'soft') {
                // Soft delete - just mark as deleted
                $stmt = $conn->prepare("UPDATE users SET is_deleted = TRUE, status = 'inactive' WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete user: " . $stmt->error);
                }
                
                $conn->commit();
                $success = "User has been soft deleted successfully. The user account is now inactive but data is preserved.";
                
            } else {
                // Hard delete - permanently remove user and all related data
                
                // Delete in order to respect foreign key constraints
                
                // 1. Delete student-related data
                if (strpos($user['roles'], 'student') !== false) {
                    $conn->query("DELETE FROM grade_appeal WHERE student_id = $user_id");
                    $conn->query("DELETE FROM waiting_list WHERE student_id = $user_id");
                    $conn->query("DELETE FROM assignment_submission WHERE student_id = $user_id");
                    $conn->query("DELETE FROM exam_attempt WHERE student_id = $user_id");
                    $conn->query("DELETE FROM attendance_record WHERE student_id = $user_id");
                    $conn->query("DELETE FROM enrollment WHERE student_id = $user_id");
                    $conn->query("DELETE FROM course_evaluation WHERE student_id = $user_id");
                    $conn->query("DELETE FROM certificate WHERE student_id = $user_id");
                    $conn->query("DELETE FROM invoice WHERE student_id = $user_id");
                    $conn->query("DELETE FROM student WHERE student_id = $user_id");
                }
                
                // 2. Delete teacher-related data
                if (strpos($user['roles'], 'faculty') !== false) {
                    $conn->query("DELETE FROM course_teacher WHERE teacher_id = $user_id");
                    $conn->query("DELETE FROM teacher_availability WHERE teacher_id = $user_id");
                    $conn->query("DELETE FROM timetable WHERE teacher_id = $user_id");
                    $conn->query("DELETE FROM teacher WHERE teacher_id = $user_id");
                }
                
                // 3. Delete admission data
                $conn->query("DELETE FROM admission WHERE user_id = $user_id");
                
                // 4. Delete user roles
                $conn->query("DELETE FROM user_roles WHERE user_id = $user_id");
                
                // 5. Delete user notifications
                $conn->query("DELETE FROM user_notification WHERE user_id = $user_id");
                
                // 6. Delete forum posts
                $conn->query("DELETE FROM forum_post WHERE posted_by = $user_id");
                $conn->query("DELETE FROM forum_thread WHERE created_by = $user_id");
                $conn->query("DELETE FROM forum WHERE created_by = $user_id");
                
                // 7. Finally delete the user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete user: " . $stmt->error);
                }
                
                $conn->commit();
                $success = "User has been permanently deleted. All associated data has been removed.";
            }
            
            // Redirect after 2 seconds
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'user_list.php';
                }, 2000);
            </script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
}

$active_page = 'users';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Delete User</h4>
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
                    
                    <?php if (!$success): ?>
                    
                    <!-- User Information -->
                    <div class="alert alert-warning">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-circle"></i> Warning</h5>
                        <p>You are about to delete the following user:</p>
                    </div>
                    
                    <div class="border rounded p-3 mb-4 bg-light">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <strong>User Code:</strong> <?= htmlspecialchars($user['user_code']) ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Roles:</strong> <?= htmlspecialchars(ucwords(str_replace(',', ', ', $user['roles']))) ?>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Status:</strong> 
                                <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Created:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data Impact Assessment -->
                    <?php if ($has_enrollments || $has_courses || $has_submissions): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading"><i class="bi bi-database-exclamation"></i> Data Impact Warning</h6>
                        <p class="mb-2">This user has associated data in the system:</p>
                        <ul class="mb-0">
                            <?php if ($has_enrollments): ?>
                                <li>Has course enrollments</li>
                            <?php endif; ?>
                            <?php if ($has_submissions): ?>
                                <li>Has assignment submissions</li>
                            <?php endif; ?>
                            <?php if ($has_courses): ?>
                                <li>Is assigned to courses as instructor</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="deleteForm">
                        <!-- Deletion Type Selection -->
                        <div class="border rounded p-3 mb-4">
                            <h5 class="mb-3">Choose Deletion Type</h5>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="delete_type" id="softDelete" value="soft" checked>
                                <label class="form-check-label" for="softDelete">
                                    <strong>Soft Delete (Recommended)</strong>
                                    <p class="text-muted mb-0 ms-4">
                                        <small>
                                            <i class="bi bi-info-circle"></i>
                                            Marks the user as deleted but preserves all data for records and audit purposes. 
                                            The user cannot log in but their history remains intact. This is reversible.
                                        </small>
                                    </p>
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delete_type" id="hardDelete" value="hard">
                                <label class="form-check-label" for="hardDelete">
                                    <strong class="text-danger">Hard Delete (Permanent)</strong>
                                    <p class="text-muted mb-0 ms-4">
                                        <small>
                                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                            Permanently removes the user and ALL associated data from the database. 
                                            This includes enrollments, submissions, grades, and all history. 
                                            <strong>This action CANNOT be undone!</strong>
                                        </small>
                                    </p>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Confirmation Input -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3">Confirm Deletion</h5>
                            <div class="mb-3">
                                <label class="form-label">
                                    Type <code>DELETE</code> to confirm 
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="confirm" id="confirmInput" class="form-control" 
                                       placeholder="Type DELETE here" required autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="bi bi-trash"></i> Delete User
                            </button>
                            <a href="user_list.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enable delete button only when "DELETE" is typed correctly
document.getElementById('confirmInput').addEventListener('input', function() {
    const deleteBtn = document.getElementById('deleteBtn');
    if (this.value === 'DELETE') {
        deleteBtn.disabled = false;
        deleteBtn.classList.remove('btn-danger');
        deleteBtn.classList.add('btn-danger');
    } else {
        deleteBtn.disabled = true;
    }
});

// Add additional confirmation for hard delete
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    const deleteType = document.querySelector('input[name="delete_type"]:checked').value;
    
    if (deleteType === 'hard') {
        if (!confirm('⚠️ FINAL WARNING ⚠️\n\nYou are about to PERMANENTLY delete this user and ALL their data.\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php
$content = ob_get_clean();
$page_title = "Delete User - LMS";
require_once include_file('templates/layout/master_base.php');
?>