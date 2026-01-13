<?php
require 'db.php';

if (isset($_GET['product_id'])) {
    $id = $_GET['product_id'];

    // Prepare the DELETE statement
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id); // "i" = integer

    // Execute the statement
    if ($stmt->execute()) {
        // Success, redirect back
        header('Location: index.php');
        exit;
    } else {
        echo "Error deleting product: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "No product ID provided.";
}
?>
