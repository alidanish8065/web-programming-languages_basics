<?php
// $db_host = "127.0.0.1";   // localhost hata diya -->
$db_user = "root";
$db_password = "";
$db_name = "jqajax";
// $db_port = 3307;          // 👈 MOST IMPORTANT

$conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}
?>
