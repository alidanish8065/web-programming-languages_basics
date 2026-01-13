<?php
// profile.php - User Profile Page
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'guest';
$upload_dir = __DIR__ . '/uploads/profiles/';
$message = $error = '';

// Ensure upload directory exists
if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

// --- Handle profile image upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;

        // Delete old image
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc()['profile_image'] ?? null;
        if ($old) @unlink($upload_dir . $old);
        $stmt->close();

        // Move new file
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $filename, $user_id);
            if ($stmt->execute()) {
                $_SESSION['profile_image'] = $filename; // Update session
                $message = 'Profile image updated!';
            } else {
                $error = 'Database error.';
            }
            $stmt->close();
        } else {
            $error = 'Failed to upload file.';
        }
    } else {
        $error = 'Invalid file. Use JPG/PNG/GIF under 5MB.';
    }
}

// --- Handle image deletion ---
if (isset($_POST['delete_image'])) {
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc()['profile_image'] ?? null;
    if ($old) @unlink($upload_dir . $old);
    $stmt->close();

    $conn->query("UPDATE users SET profile_image = NULL WHERE id = $user_id");
    $_SESSION['profile_image'] = null;
    $message = 'Image removed.';
}

// --- Fetch user info ---
if ($role === 'student') {
    $query = "SELECT u.*, s.student_number, s.current_semester, s.academic_standing, 
              s.completed_credit_hrs, s.remaining_credit_hrs, p.program_name, d.department_name
              FROM users u 
              JOIN student s ON u.id = s.student_id 
              JOIN program p ON s.program_id = p.program_id
              JOIN department d ON p.department_id = d.department_id
              WHERE u.id = $user_id";
} elseif ($role === 'faculty') {
    $query = "SELECT u.*, t.employee_number, t.designation, d.department_name
              FROM users u 
              JOIN teacher t ON u.id = t.teacher_id 
              JOIN department d ON t.department_id = d.department_id
              WHERE u.id = $user_id";
} else {
    $query = "SELECT * FROM users WHERE id = $user_id";
}

$user = $conn->query($query)->fetch_assoc();

// --- Determine profile image URL ---
$image_url = (!empty($_SESSION['profile_image']) && file_exists($upload_dir . $_SESSION['profile_image']))
    ? 'uploads/profiles/' . $_SESSION['profile_image']
    : 'male_avatar.jpeg';

// --- Dashboard URL map ---
$dashboard_map = [
    'student' => '../roles/student/dashboard.php',
    'faculty' => '../roles/faculty/dashboard.php',
    'admin' => '../roles/admin/dashboard.php',
];
$dashboard_url = $dashboard_map[$role] ?? 'dashboard.php';

ob_start();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <img src="<?= $image_url ?>" class="img-thumbnail rounded-circle mb-3" 
                     style="width:180px;height:180px;object-fit:cover;">
                <form method="POST" enctype="multipart/form-data" class="mb-2">
                    <input type="file" name="profile_image" class="form-control form-control-sm mb-2" accept="image/*" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Upload</button>
                </form>
                <?php if ($user['profile_image']): ?>
                <form method="POST">
                    <button name="delete_image" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($role === 'student'): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Academic Summary</h6>
                <p class="mb-1"><strong>Standing:</strong> 
                    <span class="badge bg-<?= $user['academic_standing'] === 'honors' ? 'success' : 'primary' ?>">
                        <?= ucfirst($user['academic_standing']) ?>
                    </span>
                </p>
                <p class="mb-1"><strong>Semester:</strong> <?= $user['current_semester'] ?></p>
                <p class="mb-1"><strong>Completed:</strong> <?= $user['completed_credit_hrs'] ?> credits</p>
                <p class="mb-0"><strong>Remaining:</strong> <?= $user['remaining_credit_hrs'] ?> credits</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6"><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="col-6"><strong>Code:</strong> <?= htmlspecialchars($user['user_code']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
                    <div class="col-6"><strong>Contact:</strong> <?= htmlspecialchars($user['contact_number'] ?? 'N/A') ?></div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Status:</strong> 
                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </div>
                    <div class="col-6"><strong>Joined:</strong> <?= date('M Y', strtotime($user['created_at'])) ?></div>
                </div>
            </div>
        </div>

        <?php if ($role === 'student'): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Academic Details</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Student ID:</strong> <?= $user['student_number'] ?></p>
                <p class="mb-1"><strong>Program:</strong> <?= htmlspecialchars($user['program_name']) ?></p>
                <p class="mb-0"><strong>Department:</strong> <?= htmlspecialchars($user['department_name']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === 'faculty'): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-briefcase"></i> Employment Details</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Employee ID:</strong> <?= $user['employee_number'] ?></p>
                <p class="mb-1"><strong>Designation:</strong> <?= ucfirst(str_replace('_', ' ', $user['designation'])) ?></p>
                <p class="mb-0"><strong>Department:</strong> <?= htmlspecialchars($user['department_name']) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'My Profile';
require_once '../templates/layout/master_base.php';
$conn->close();
?>
