<?php
session_start();
require '../../public/dbconfig.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    die("Access denied.");
}

if (!isset($_GET['user_code']) || empty($_GET['user_code'])) {
    die("User ID is required.");
}

$user_code = intval($_GET['user_code']);

$sql = "DELETE FROM users WHERE user_code = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_code);

if ($stmt->execute()) {
    echo "User deleted successfully.";
} else {
    echo "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();
header("Location: user_list.php");
?>
