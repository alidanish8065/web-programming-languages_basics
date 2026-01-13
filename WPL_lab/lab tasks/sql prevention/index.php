<?php
require 'db.php';

// Prepare the query
$stmt = $conn->prepare("SELECT id, product_category, product_name, product_price, product_quantity FROM products");
$stmt->execute();

// Get the result
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between mb-3">
        <h3>Product List</h3>
        <a href="create.php" class="btn btn-success">+ Add Product</a>
    </div>

   <table class="table table-bordered border-dark table-hover shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Name</th>
                <th>Price</th>
                <th>Qty</th>
                <th width="150">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['product_category']) ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['product_price']) ?></td>
                <td><?= htmlspecialchars($row['product_quantity']) ?></td>
                <td>
                    <a href="edit.php?product_id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?product_id=<?= htmlspecialchars($row['id']) ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

</body>
</html>
