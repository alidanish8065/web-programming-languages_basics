<?php
session_start();
require '../../public/dbconfig.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin','admin'])) {
    die('Access denied.');
}

$allowedRoles = ($_SESSION['role'] === 'superadmin') 
                ? ['student','teacher','admin','superadmin','staff']
                : ['student','teacher','staff'];

// Generate unique user code

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

function generateUserCode($role, $subRole, $conn) {
    // Role mapping
    $roleCodes = [
        'student' => '01',
        'teacher' => '02',
        'admin' => '07',
        'staff' => '05',
        'superadmin' => '09'
    ];

    // Sub-role mapping (example)
    $subRoleCodes = [
        'bscs' => '11',
        'bsit' => '12',
        'bsse' => '13',
        'bsai' => '14',
        'permanent' => '21',
        'visiting' => '22',
        'network' => '61',
        'systems' => '62',
        'software' => '63'
    ];

    $RR = $roleCodes[$role] ?? '99';
    $SS = $subRoleCodes[$subRole] ?? '99';

    // Fetch count for that role + sub-role
    $sql = "SELECT COUNT(*) AS count FROM users WHERE role='$role' AND sub_role='$subRole'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    $serial = str_pad($row['count'] + 1, 5, '0', STR_PAD_LEFT);
    $year = date('y');

    // Core number for checksum
    $core = $RR . $SS . $year . $serial;

    $checksum = luhnChecksum($core);

    return "UI".$RR."-".$year.$serial."-".$SS.$checksum;
}


$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $age = $_POST['age'] ?? 0;
    $gender = $_POST['gender'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $department = $_POST['department'] ?? '';
    $campus = $_POST['campus'] ?? '';
    $teachertitle = $_POST['title'] ?? '';


    if (!in_array($role, $allowedRoles)) die("Unauthorized role.");

    $user_code = generateUserCode($role,$subRole, $conn);
    $role_prefix = substr($user_code,0,5);
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users 
(user_code, full_name, age, gender, phone, email, address, password, role, role_prefix,) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
    "ssisissssss",
    $user_code,
    $full_name,
    $age,
    $gender,
    $phone,
    $email,
    $address,
    $hashedPassword,
    $role,
    $role_prefix,
);


    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        // Role-specific insertion here if needed
        $success = "User created successfully with code $user_code";
    } else {
        $error = $stmt->error;
    }
    $stmt->close();
}
$conn->close();
ob_start();
?>


<h2>Create User</h2>
<?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
<?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="POST">

<div class="mb-3">
<label>Role</label>
<select id="role" name="role" class="form-select" required onchange="showFields()">
<option value="">Select Role</option>
<?php foreach ($allowedRoles as $r) echo "<option value='$r'>".ucfirst($r)."</option>"; ?>
</select>
</div>

<div class="mb-3">
<label>Password</label>
<input type="text" name="password" class="form-control" required>
</div>
<div class="mb-3">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control" required>
</div>
<div class="mb-3">
<label>Age</label>
<input type="number" name="age" class="form-control" required>
</div>
<div class="mb-3">
<label>Gender</label>
<select name="gender" class="form-select" required>
<option value="">Select Gender</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
</select>
</div>
<div class="mb-3">
<label>Phone</label>
<input type="text" name="phone" class="form-control">
</div>
<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control">
</div>
<div class="mb-3">
<label>Address</label>
<input type="text" name="address" class="form-control">
</div>

<!-- Role-specific fields -->
<!-- Role-specific fields -->
<div id="student-fields" class="role-specific" style="display:none">

<div class="mb-3">
    <label>Program Name</label>
    <select name="program_name" class="form-select">
        <option value="">Select Program</option>
        <option value="B.Tech Computer Science">B.Tech Computer Science</option>
        <option value="B.Tech Electrical Engineering">B.Tech Electrical Engineering</option>
        <option value="BBA">BBA</option>
        <option value="MBA">MBA</option>
        <!-- Add more programs as needed -->
    </select>
</div>

<div class="mb-3">
    <label>Semester</label>
    <input type="number" name="semester" class="form-control">
</div>

<div class="mb-3">
    <label>Degree</label>
    <select name="degree" class="form-select">
        <option value="">Select Degree</option>
        <option value="Bachelor">Bachelor</option>
        <option value="Master">Master</option>
        <option value="PhD">PhD</option>
        <!-- Add more degrees as needed -->
    </select>
</div>

<div class="mb-3">
    <label>Department</label>
    <select name="department" class="form-select">
        <option value="">Select Department</option>
        <option value="Computer Science">Computer Science</option>
        <option value="Electrical Engineering">Electrical Engineering</option>
        <option value="Business Administration">Business Administration</option>
        <option value="Mechanical Engineering">Mechanical Engineering</option>
        <!-- Add more departments as needed -->
    </select>
</div>

</div>

<div id="teacher-fields" class="role-specific" style="display:none">
<div class="mb-3"><label>Department</label><input type="text" name="department" class="form-control"></div>
<div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control"></div>
</div>

<button type="submit" class="btn btn-primary">Create User</button>
</form>

<?php
$content = ob_get_clean();
require_once '../../templates/layout/master_base.php';
$conn->close();
?>
