<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check permissions
$can_create = in_array($user_role, ['admin', 'superadmin', 'faculty', 'teacher']);
if (!$can_create) {
    die('Access denied. You do not have permission to create notifications.');
}

$success = '';
$error = '';

// Fetch roles for targeting (admin only)
$roles = [];
if (in_array($user_role, ['admin', 'superadmin'])) {
    $roles_result = $conn->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Fetch programs for targeting (admin only)
$programs = [];
if (in_array($user_role, ['admin', 'superadmin'])) {
    $programs_result = $conn->query("
        SELECT p.program_id, p.program_name, p.program_code, d.department_name 
        FROM program p
        JOIN department d ON p.department_id = d.department_id
        WHERE p.program_status = 'active' AND p.is_deleted = FALSE
        ORDER BY p.program_name
    ");
    while ($row = $programs_result->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Fetch teacher's courses (faculty only)
$teacher_courses = [];
if (in_array($user_role, ['faculty', 'teacher'])) {
    $courses_sql = "
        SELECT DISTINCT
            co.offering_id,
            c.course_code,
            c.course_name,
            co.academic_year,
            co.semester,
            co.term
        FROM course_teacher ct
        JOIN course_offering co ON ct.offering_id = co.offering_id
        JOIN course c ON co.course_id = c.course_id
        WHERE ct.teacher_id = ?
        ORDER BY co.academic_year DESC, co.semester DESC
    ";
    $stmt = $conn->prepare($courses_sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teacher_courses[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $notification_type = $_POST['notification_type'] ?? 'info';
    $target_type = $_POST['target_type'] ?? '';
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
    
    if (empty($title) || empty($message)) {
        $error = "Please fill all required fields.";
    } else {
        try {
            $conn->begin_transaction();
            
            // Insert notification
            $is_general = ($target_type === 'all') ? 1 : 0;
            $stmt = $conn->prepare("
                INSERT INTO notification (title, message, notification_type, is_general, scheduled_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssisi', $title, $message, $notification_type, $is_general, $scheduled_at, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification");
            }
            
            $notification_id = $conn->insert_id;
            
            // Determine recipients based on user role and target type
            $recipient_ids = [];
            
            if (in_array($user_role, ['admin', 'superadmin'])) {
                // Admin logic
                if ($target_type === 'all') {
                    $result = $conn->query("SELECT id FROM users WHERE status = 'active' AND is_deleted = FALSE");
                    while ($row = $result->fetch_assoc()) {
                        $recipient_ids[] = $row['id'];
                    }
                    
                } elseif ($target_type === 'role') {
                    $target_roles = $_POST['target_roles'] ?? [];
                    if (!empty($target_roles)) {
                        $role_placeholders = implode(',', array_fill(0, count($target_roles), '?'));
                        $stmt = $conn->prepare("
                            SELECT DISTINCT u.id 
                            FROM users u
                            JOIN user_roles ur ON u.id = ur.user_id
                            WHERE ur.role_id IN ($role_placeholders)
                            AND u.status = 'active' AND u.is_deleted = FALSE
                        ");
                        $stmt->bind_param(str_repeat('i', count($target_roles)), ...$target_roles);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $recipient_ids[] = $row['id'];
                        }
                    }
                    
                } elseif ($target_type === 'program') {
                    $target_programs = $_POST['target_programs'] ?? [];
                    if (!empty($target_programs)) {
                        $program_placeholders = implode(',', array_fill(0, count($target_programs), '?'));
                        $stmt = $conn->prepare("
                            SELECT DISTINCT s.student_id 
                            FROM student s
                            JOIN users u ON s.student_id = u.id
                            WHERE s.program_id IN ($program_placeholders)
                            AND s.enrollment_status = 'active'
                            AND u.status = 'active' AND u.is_deleted = FALSE
                        ");
                        $stmt->bind_param(str_repeat('i', count($target_programs)), ...$target_programs);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $recipient_ids[] = $row['student_id'];
                        }
                    }
                }
                
            } elseif (in_array($user_role, ['faculty', 'teacher'])) {
                // Faculty logic - send to their students
                $target_offerings = $_POST['target_offerings'] ?? [];
                if (!empty($target_offerings)) {
                    $offering_placeholders = implode(',', array_fill(0, count($target_offerings), '?'));
                    $stmt = $conn->prepare("
                        SELECT DISTINCT e.student_id 
                        FROM enrollment e
                        WHERE e.offering_id IN ($offering_placeholders)
                        AND e.status = 'enrolled'
                    ");
                    $stmt->bind_param(str_repeat('i', count($target_offerings)), ...$target_offerings);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $recipient_ids[] = $row['student_id'];
                    }
                }
            }
            
            // Insert user notifications
            if (!empty($recipient_ids)) {
                $stmt = $conn->prepare("
                    INSERT INTO user_notification (user_id, notification_id) 
                    VALUES (?, ?)
                ");
                foreach ($recipient_ids as $recipient_id) {
                    $stmt->bind_param('ii', $recipient_id, $notification_id);
                    $stmt->execute();
                }
                
                // Insert into notification queue if scheduled
                if ($scheduled_at) {
                    $queue_stmt = $conn->prepare("
                        INSERT INTO notification_queue (notification_id, user_id, scheduled_for, delivery_method) 
                        VALUES (?, ?, ?, 'in_app')
                    ");
                    foreach ($recipient_ids as $recipient_id) {
                        $queue_stmt->bind_param('iis', $notification_id, $recipient_id, $scheduled_at);
                        $queue_stmt->execute();
                    }
                }
            }
            
            $conn->commit();
            $success = "Notification created successfully! Sent to " . count($recipient_ids) . " user(s).";
            echo "<script>setTimeout(function(){ window.location.href = 'notification_list.php'; }, 2000);</script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

$active_page = 'notifications';

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-bell-fill"></i> Create Notification</h4>
                    <a href="notification_list.php" class="btn btn-light btn-sm">
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
                        <!-- Notification Content -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-primary">
                                <i class="bi bi-pencil-square"></i> Notification Content
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       placeholder="e.g., Important Announcement"
                                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="5" required
                                          placeholder="Enter your notification message here..."><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Notification Type</label>
                                    <select name="notification_type" class="form-select">
                                        <option value="info" selected>Info</option>
                                        <option value="warning">Warning</option>
                                        <option value="alert">Alert</option>
                                        <option value="assignment">Assignment</option>
                                        <option value="exam">Exam</option>
                                        <option value="system">System</option>
                                    </select>
                                </div>
                                <?php if (in_array($user_role, ['admin', 'superadmin'])): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Schedule Delivery (Optional)</label>
                                    <input type="datetime-local" name="scheduled_at" class="form-control">
                                    <small class="text-muted">Leave empty to send immediately</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Target Audience -->
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h5 class="mb-3 text-primary">
                                <i class="bi bi-people-fill"></i> Target Audience
                            </h5>
                            
                            <?php if (in_array($user_role, ['admin', 'superadmin'])): ?>
                                <!-- Admin targeting options -->
                                <div class="mb-3">
                                    <label class="form-label">Send To</label>
                                    <select name="target_type" id="targetType" class="form-select" onchange="showTargetOptions()">
                                        <option value="all" selected>All Users</option>
                                        <option value="role">Specific Roles</option>
                                        <option value="program">Specific Programs</option>
                                    </select>
                                </div>
                                
                                <div id="roleSelection" class="target-option" style="display:none;">
                                    <label class="form-label">Select Roles</label>
                                    <div class="border rounded p-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($roles as $role): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="target_roles[]" value="<?= $role['role_id'] ?>"
                                                       id="role_<?= $role['role_id'] ?>">
                                                <label class="form-check-label" for="role_<?= $role['role_id'] ?>">
                                                    <?= ucfirst(htmlspecialchars($role['role_name'])) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div id="programSelection" class="target-option" style="display:none;">
                                    <label class="form-label">Select Programs</label>
                                    <div class="border rounded p-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($programs as $program): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="target_programs[]" value="<?= $program['program_id'] ?>"
                                                       id="program_<?= $program['program_id'] ?>">
                                                <label class="form-check-label" for="program_<?= $program['program_id'] ?>">
                                                    <?= htmlspecialchars($program['program_code']) ?> - 
                                                    <?= htmlspecialchars($program['program_name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- Faculty targeting - only their courses -->
                                <?php if (empty($teacher_courses)): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        You don't have any assigned courses. Please contact the administrator.
                                    </div>
                                <?php else: ?>
                                    <label class="form-label">Send to Students in <span class="text-danger">*</span></label>
                                    <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                                        <?php foreach ($teacher_courses as $course): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="target_offerings[]" value="<?= $course['offering_id'] ?>"
                                                       id="course_<?= $course['offering_id'] ?>">
                                                <label class="form-check-label" for="course_<?= $course['offering_id'] ?>">
                                                    <strong><?= htmlspecialchars($course['course_code']) ?></strong> - 
                                                    <?= htmlspecialchars($course['course_name']) ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($course['academic_year']) ?> | 
                                                        Semester <?= $course['semester'] ?> | 
                                                        <?= $course['term'] ?>
                                                    </small>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> Only active users will receive the notification.
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Send Notification
                            </button>
                            <a href="notification_list.php" class="btn btn-secondary">
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
function showTargetOptions() {
    document.querySelectorAll('.target-option').forEach(el => el.style.display = 'none');
    const targetType = document.getElementById('targetType').value;
    if (targetType === 'role') {
        document.getElementById('roleSelection').style.display = 'block';
    } else if (targetType === 'program') {
        document.getElementById('programSelection').style.display = 'block';
    }
}
</script>

<?php
$content = ob_get_clean();
$page_title = "Create Notification - LMS";
require_once '../../templates/layout/master_base.php';
?>