<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

$message = "";

/* =========================
   Utility: Luhn checksum
   ========================= */
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

/* =========================
   Generate admin code
   ========================= */
function generateAdminCode($role, $conn) {

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


/* =========================
   Handle POST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($first_name === '' || $last_name === '' || $password === '') {
        $message = "All fields are required.";
    } else {

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $role_name = 'admin';

        try {
            $conn->begin_transaction();

            // Generate user_code
            $user_code = generateAdminCode($role_name,$conn);

            // Insert user
            $userStmt = $conn->prepare("
                INSERT INTO users
                    (first_name, last_name, password_hash, user_code)
                VALUES (?, ?, ?, ?)
            ");
            $userStmt->bind_param(
                "ssss",
                $first_name,
                $last_name,
                $password_hash,
                $user_code
            );
            if (!$userStmt->execute()) {
                throw new Exception("User insert failed");
            }

            $user_id = $userStmt->insert_id;
            $userStmt->close();

            // Resolve role_id
            $roleStmt = $conn->prepare("
                SELECT role_id
                FROM roles
                WHERE role_name = ?
                LIMIT 1
            ");
            $roleStmt->bind_param("s", $role_name);
            $roleStmt->execute();
            $roleStmt->bind_result($role_id);
            $roleStmt->fetch();
            $roleStmt->close();

            if (!$role_id) {
                throw new Exception("Admin role not found in roles table");
            }

            // Assign role
            $assignStmt = $conn->prepare("
                INSERT INTO user_roles (user_id, role_id)
                VALUES (?, ?)
            ");
            $assignStmt->bind_param("ii", $user_id, $role_id);

            if (!$assignStmt->execute()) {
                throw new Exception("Role assignment failed");
            }
            $assignStmt->close();

            $conn->commit();
            $message = "Admin account created successfully.<br>User Code: <strong>$user_code</strong>";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin Account</title>
    <link href="../node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <h2>Create Admin Account</h2>

    <?php if ($message): ?>
        <div class="alert alert-info mt-3">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label class="form-label">First Name</label>
            <input name="first_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input name="last_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Create Admin
        </button>
    </form>
</div>

</body>
</html>
