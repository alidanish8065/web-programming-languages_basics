<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "LMS";

// Create a connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Function to execute queries safely
function dbquery($sql, $params = [], $types = "") {
    global $conn;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        // Bind parameters dynamically
        if ($types === "") {
            $types = str_repeat("s", count($params)); // default all string
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        die("Execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}
?>
