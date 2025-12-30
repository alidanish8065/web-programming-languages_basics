<?php
session_start();
require '../../public/dbconfig.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin','admin'])) {
    die('Access denied.');
}

$allowedRoles = ($_SESSION['role'] === 'superadmin')
    ? ['student','teacher','admin','superadmin','staff']
    : ['student','teacher','staff'];

if (!isset($_GET['user_code'])) {
    die("Invalid user code.");
}

$user_code = $_GET['user_code'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_code = ?");
$stmt->bind_param("s", $user_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) die("User not found.");
$user = $result->fetch_assoc();

// Fetch role-specific data
$roleData = [];
if($user['role'] === 'student'){
    $stmt2 = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt2->bind_param("i", $user['id']);
    $stmt2->execute();
    $roleData = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
} elseif($user['role'] === 'teacher'){
    $stmt2 = $conn->prepare("SELECT * FROM instructors WHERE user_id = ?");
    $stmt2->bind_param("i", $user['id']);
    $stmt2->execute();
    $roleData = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $password = $_POST['password'];

    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $user['password'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, age=?, gender=?, phone=?, email=?, address=?, password=? WHERE user_code=?");
    $stmt->bind_param("sissssss", $full_name, $age, $gender, $phone, $email, $address, $hashedPassword, $user_code);

    if($stmt->execute()){
        $success = "User updated successfully.";
        echo "<script>alert('User updated successfully'); window.location='user_list.php';</script>";
        exit;
    } else {
        $error = $stmt->error;
    }
    $stmt->close();
}
ob_start();
?>

<div class="container p-4">
<h2>Edit User</h2>
<?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
<?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="POST">

<div class="mb-3">
<label>User Code</label>
<input type="text" class="form-control" value="<?= $user['user_code'] ?>" readonly onclick="alertUserCode()">
</div>

<div class="mb-3">
<label>Role</label>
<input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
</div>

<div class="mb-3">
<label>Password (leave empty to keep old)</label>
<input type="password" name="password" class="form-control">
</div>
<div class="mb-3">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control" value="<?= $user['full_name'] ?>" required>
</div>
<div class="mb-3">
<label>Age</label>
<input type="number" name="age" class="form-control" value="<?= $user['age'] ?>" required>
</div>
<div class="mb-3">
<label>Gender</label>
<select name="gender" class="form-select">
<option value="Male" <?= $user['gender']=='Male'?'selected':'' ?>>Male</option>
<option value="Female" <?= $user['gender']=='Female'?'selected':'' ?>>Female</option>
<option value="Other" <?= $user['gender']=='Other'?'selected':'' ?>>Other</option>
</select>
</div>
<div class="mb-3">
<label>Phone</label>
<input type="text" name="phone" class="form-control" value="<?= $user['phone'] ?>">
</div>
<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" value="<?= $user['email'] ?>">
</div>
<div class="mb-3">
<label>Address</label>
<input type="text" name="address" class="form-control" value="<?= $user['address'] ?>">
</div>

<!-- Role-specific -->
<div id="student-fields" class="role-specific" style="display:none">
<div class="mb-3"><label>Program Name</label><input type="text" name="program_name" class="form-control" value="<?= $roleData['program_name'] ?? '' ?>"></div>
<div class="mb-3"><label>Semester</label><input type="number" name="semester" class="form-control" value="<?= $roleData['semester'] ?? '' ?>"></div>
<div class="mb-3"><label>Degree</label><input type="text" name="degree" class="form-control" value="<?= $roleData['degree'] ?? '' ?>"></div>
<div class="mb-3"><label>Department</label><input type="text" name="department" class="form-control" value="<?= $roleData['department'] ?? '' ?>"></div>
</div>

<div id="teacher-fields" class="role-specific" style="display:none">
<div class="mb-3"><label>Department</label><input type="text" name="department" class="form-control" value="<?= $roleData['department'] ?? '' ?>"></div>
<div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" value="<?= $roleData['title'] ?? '' ?>"></div>
</div>

<button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to update this user?')">Confirm Edit</button>
</form>
</div>

<script>
function showFields() {
    var role = '<?= $user['role'] ?>';
    document.querySelectorAll('.role-specific').forEach(el => el.style.display='none');
    var target = document.getElementById(role+'-fields');
    if(target) target.style.display='block';
}
function alertUserCode(){
    alert('User code cannot be edited');
}
</script>
<?php
$content = ob_get_clean();
require_once '../../templates/layout/master_base.php';
require_once '../../templates/layout/navbar.php';
$conn->close();
?>
