<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['product_name'];
    $category = $_POST['product_category'];
    $price = $_POST['product_price'];
    $qty = $_POST['product_quantity'];

    // Prepare the INSERT statement
    $stmt = $conn->prepare("INSERT INTO products (product_name, product_category, product_price, product_quantity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $name, $category, $price, $qty);
    // "ssii" = string, string, integer, integer

    if ($stmt->execute()) {
        // Success
        header('Location: index.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    die("Invalid request");
}
