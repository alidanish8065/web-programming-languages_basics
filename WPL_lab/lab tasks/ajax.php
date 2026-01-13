<!DOCTYPE html>
<html>
<head>
    <title>Single File CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container my-5">
    <h2>Product Form</h2>
    <div class="card p-4 mb-5 shadow-sm">
        <form id="productForm">
            <input type="hidden" name="id" id="id">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" class="form-control" name="product_name" id="product_name">
            </div>
            <div class="mb-3">
                <label>Category</label>
                <input type="text" class="form-control" name="product_category" id="product_category">
            </div>
            <div class="mb-3">
                <label>Price</label>
                <input type="number" class="form-control" name="product_price" id="product_price">
            </div>
            <div class="mb-3">
                <label>Quantity</label>
                <input type="number" class="form-control" name="product_quantity" id="product_quantity">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>

    <h2>Products List</h2>
    <table class="table table-bordered table-hover" id="productTable">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
$(document).ready(function(){
    function loadProducts(){
        $.post('products.php', {action:'fetch'}, function(data){
            $('#productTable tbody').html(data);
        });
    }
    loadProducts();

    $('#productForm').on('submit', function(e){
        e.preventDefault();
        let id = $('#id').val();
        let action = id ? 'update' : 'add';
        $.post('products.php', $(this).serialize() + '&action=' + action, function(response){
            alert(response);
            $('#productForm')[0].reset();
            $('#id').val('');
            loadProducts();
        });
    });

    $(document).on('click', '.editBtn', function(){
        let id = $(this).data('id');
        $.post('products.php', {action:'get', id:id}, function(data){
            let d = JSON.parse(data);
            $('#id').val(d.id);
            $('#product_name').val(d.product_name);
            $('#product_category').val(d.product_category);
            $('#product_price').val(d.product_price);
            $('#product_quantity').val(d.product_quantity);
        });
    });

    $(document).on('click', '.deleteBtn', function(){
        if(confirm('Are you sure?')){
            let id = $(this).data('id');
            $.post('products.php', {action:'delete', id:id}, function(response){
                alert(response);
                loadProducts();
            });
        }
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
