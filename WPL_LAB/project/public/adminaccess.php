<?php
// admin_access.php
session_start();
require "dbconfig.php"; // make sure this file creates $conn

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $password = trim($_POST['password']);


    if ($full_name && $password) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Generate user_code for admin
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
$role = 'admin';
$subRole = 'permanent'; // example sub-role

function generateAdminCode($role, $subRole, $conn) {
    // Role mapping
    $roleCodes = [
        
        'admin' => '07',
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
        $user_code = generateAdminCode($role,$subRole,$conn);
        $role_prefix = substr($user_code,0,5);
        $role = 'admin';

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (user_code, full_name, password, role, role_prefix, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $user_code, $full_name, $hashedPassword, $role, $role_prefix);

        if ($stmt->execute()) {
            $message = "Admin user created successfully with code $user_code!";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "Please fill in all fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin User</title>
    <link href="../node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2>Create Admin User</h2>
    <?php if ($message) echo "<div class='alert alert-info'>$message</div>"; ?>

    <form method="POST" class="mt-3">
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Admin</button>
    </form>
</div>
</body>
</html>
