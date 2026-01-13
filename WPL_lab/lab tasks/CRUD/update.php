<?php
require 'db.php';

$id = $_POST['product_id'];
$name = $_POST['product_name'];
$category = $_POST['product_category'];
$price = $_POST['product_price'];
$qty = $_POST['product_quantity'];

$query = "UPDATE products SET
product_name='$name',
product_category='$category',
product_price='$price',
product_quantity='$qty'
WHERE id=$id";

mysqli_query($conn, $query);

header('Location: index.php');
