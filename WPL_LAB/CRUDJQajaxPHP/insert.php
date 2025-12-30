<?php
include('dbConnection.php');

// Get raw JSON data
$data = file_get_contents("php://input");

// Decode JSON
$mydata = json_decode($data, true);

// Check if JSON is valid
if (!$mydata) {
    echo "Invalid data received";
    exit;
}

// Assign values safely
$name     = $mydata['name'] ?? '';
$email    = $mydata['email'] ?? '';
$password = $mydata['password'] ?? '';

// Check empty fields
if (!empty($name) && !empty($email) && !empty($password)) {

    $sql = "INSERT INTO student (name, email, password) 
            VALUES ('$name', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "Student Saved Successfully";
    } else {
        echo "Database Error: " . $conn->error;
    }

} else {
    echo "Fill all fields";
}
?>
