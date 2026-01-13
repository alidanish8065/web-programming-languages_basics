<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['product_id'];
    $name = $_POST['product_name'];
    $category = $_POST['product_category'];
    $price = $_POST['product_price'];
    $qty = $_POST['product_quantity'];

    // Prepare the UPDATE statement
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_category = ?, product_price = ?, product_quantity = ? WHERE id = ?");
    $stmt->bind_param("ssiii", $name, $category, $price, $qty, $id);
    // "ssiii" = string, string, integer, integer, integer

    if ($stmt->execute()) {
        header('Location: index.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    die("Invalid request");
}
