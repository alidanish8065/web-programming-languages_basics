<?php
require 'db.php';

$name = $_POST['product_name'];
$category = $_POST['product_category'];
$price = $_POST['product_price'];
$qty = $_POST['product_quantity'];

$query = "INSERT INTO products 
(product_name, product_category, product_price, product_quantity)
VALUES ('$name', '$category', '$price', '$qty')";

mysqli_query($conn, $query);

header('Location: index.php');
