<?php
session_start();
require '../../public/dbconfig.php';


// Redirect if not logged in
if (!isset($_SESSION['user_code'])) {
    header('Location: ../../public/login.php');
    exit;
}

$user_code = $_SESSION['user_code'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];
$student_id = $_SESSION['student_id'] ?? '';

$active_page = 'home'; // Highlight dashboard in sidebar

// Fetch courses for students/teachers
$sql = "SELECT id, user_code, full_name, role FROM users";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Page content injection
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Users</h4>
        <div>
            <a href="create_user.php" class="btn btn-primary btn-sm">+ Create User</a>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>User Code</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $serial = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $serial++ ?></td>
                    <td><?= htmlspecialchars($row['user_code']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= ucfirst($row['role']) ?></td>
                    <td>
                        <a href="edit_user.php?user_code=<?= $row['user_code'] ?>" 
                           class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_user.php?user_code=<?= $row['user_code'] ?>" onclick="return confirm('Are you sure you want to delete this record? It can\'t be recovered after deleting.')" class="btn btn-danger btn-sm">Delete</a>   
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

<script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<?php
$content = ob_get_clean();
$page_title = "Dashboard - LMS";
include '../../templates/layout/master_base.php';