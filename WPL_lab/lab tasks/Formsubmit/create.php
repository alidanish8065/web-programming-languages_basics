<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-4">Add Product</h4>

            <form method="post" action="store.php">
                <input class="form-control mb-3" name="product_name" placeholder="Product Name" required>
                <input class="form-control mb-3" name="product_category" placeholder="Category" required>
                <input class="form-control mb-3" type="number" name="product_price" placeholder="Price" required>
                <input class="form-control mb-3" type="number" name="product_quantity" placeholder="Quantity" required>

                <button class="btn btn-primary">Save</button>
                <a href="index.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
