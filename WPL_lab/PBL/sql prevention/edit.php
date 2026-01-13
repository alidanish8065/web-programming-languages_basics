<?php
require 'db.php';

if (isset($_GET['product_id'])) {
    $id = $_GET['product_id'];

    // Prepare the SELECT statement
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id); // "i" = integer

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    if (!$data) {
        die("Product not found.");
    }
} else {
    die("No product ID provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-4">Edit Product</h4>

            <form method="post" action="update.php">
                <input type="hidden" name="product_id" value="<?= $data['id'] ?>">

                <input class="form-control mb-3" name="product_name"
                       value="<?= htmlspecialchars($data['product_name']) ?>" required>

                <input class="form-control mb-3" name="product_category"
                       value="<?= htmlspecialchars($data['product_category']) ?>" required>

                <input class="form-control mb-3" type="number" name="product_price"
                       value="<?= $data['product_price'] ?>" required>

                <input class="form-control mb-3" type="number" name="product_quantity"
                       value="<?= $data['product_quantity'] ?>" required>

                <button class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
