<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = trim($_POST["login"]); // user_code or email
    $password = trim($_POST["password"]);

    // Fetch user and associated role from normalized tables
    $sql = "SELECT u.id, u.user_code, u.first_name, u.last_name, u.password_hash, r.role_name AS role 
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.role_id
            WHERE u.user_code = ? OR u.email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Check if password is hashed
        if (strlen($user['password_hash']) < 60) {
            $hashed = password_hash($user['password_hash'], PASSWORD_BCRYPT);
            $updateStmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $updateStmt->bind_param("si", $hashed, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            $user['password_hash'] = $hashed;
        }

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION["user_id"] = $user['id'];
            $_SESSION["user_code"] = $user['user_code'];
            $_SESSION["role"] = $user['role'];
            $_SESSION["first_name"] = $user['first_name'];
            $_SESSION["last_name"] = $user['last_name'];

            // Role-specific session data
            switch ($user['role']) {
                case 'student':
                    $stmt2 = $conn->prepare("
                        SELECT s.student_id, p.program_name, s.current_semester as semester, d.department_name as department 
                        FROM student s 
                        JOIN program p ON s.program_id = p.program_id 
                        JOIN department d ON p.department_id = d.department_id 
                        WHERE s.student_id=?
                    ");
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    $student = $result2->fetch_assoc();
                    $_SESSION["student_id"] = $student['student_id'] ?? '';
                    $_SESSION["program_name"] = $student['program_name'] ?? '';
                    $_SESSION["semester"] = $student['semester'] ?? '';
                    $_SESSION["department"] = $student['department'] ?? '';
                    $stmt2->close();
                    header("Location: ../../roles/Student/dashboard.php");
                    break;

                case 'faculty':
                    $stmt2 = $conn->prepare("
                        SELECT d.department_name as department, t.designation as title 
                        FROM teacher t 
                        JOIN department d ON t.department_id = d.department_id 
                        WHERE t.teacher_id=?
                    ");
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($teacher = $result2->fetch_assoc()) {
                         $_SESSION["department"] = $teacher['department'] ?? '';
                         $_SESSION["title"] = $teacher['title'] ?? '';
                    } else {
                         // Fallback if teacher profile incomplete
                         $_SESSION["department"] = 'Unknown';
                         $_SESSION["title"] = 'Faculty';
                    }
                    $stmt2->close();
                    header("Location: ../../roles/Faculty/dashboard.php");
                    break;

                case 'admin':
                    header("Location: ../../roles/admin/dashboard.php");
                    break;

                case 'superadmin':
                    header("Location: ../../roles/superadmin/dashboard.php");
                    break;

                default:
                    header("Location: login.html");
                    break;
            }
            exit;
        } else {
            echo "<script>alert('Invalid password'); window.location='login.html';</script>";
        }

    } else {
        echo "<script>alert('User not found'); window.location='login.html';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
