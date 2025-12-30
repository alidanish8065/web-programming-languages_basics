<?php
session_start();
require "dbconfig.php"; // Make sure dbconfig.php creates $conn

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = trim($_POST["login"]); // Can be user_code or email
    $password = trim($_POST["password"]);

    // Prepare statement to avoid SQL injection
    $sql = "SELECT * FROM users WHERE user_code = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        // If password is plain text (first login), hash it and update DB
        if (strlen($user['password']) < 60) { // bcrypt hashes are 60 chars
            $hashed = password_hash($user['password'], PASSWORD_BCRYPT);
            $updateStmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($updateStmt, "si", $hashed, $user['id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            $user['password'] = $hashed;
        }

        if (password_verify($password, $user["password"])) {
            // Store session variables using user_id
            $_SESSION["user_id"]   = $user["id"];           // users.id primary key
            $_SESSION["user_code"] = $user["user_code"];    // generated roll/identifier
            $_SESSION["role"]      = $user["role"];
            $_SESSION["name"]      = $user["full_name"];

            // Fetch additional info based on role
            if ($user["role"] === "student") {
                $stmt2 = $conn->prepare("SELECT student_id, program_name, semester, department FROM students WHERE user_id = ?");
                $stmt2->bind_param("i", $user['id']);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $student = $result2->fetch_assoc();
                $_SESSION["student_id"] = $student['student_id'] ?? '';
                $_SESSION["program_name"] = $student['program_name'] ?? '';
                $_SESSION["semester"] = $student['semester'] ?? '';
                $_SESSION["department"] = $student['department'] ?? '';
                $stmt2->close();
            } elseif ($user["role"] === "teacher") {
                $stmt2 = $conn->prepare("SELECT department, title FROM instructors WHERE user_id = ?");
                $stmt2->bind_param("i", $user['id']);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $teacher = $result2->fetch_assoc();
                $_SESSION["department"] = $teacher['department'] ?? '';
                $_SESSION["title"] = $teacher['title'] ?? '';
                $stmt2->close();
            }

            // Role-based redirection
            switch ($user["role"]) {
                case "student":
                    header("Location: ../roles/Student/dashboard.php");
                    break;
                case "teacher":
                    header("Location: ../roles/Teacher/dashboard.php");
                    break;
                case "admin":
                    header("Location: ../roles/admin/dashboard.php");
                    break;
                case "superadmin":
                    header("Location: ../roles/superadmin/dashboard.php");
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

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
