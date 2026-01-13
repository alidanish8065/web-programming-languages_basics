<?php
$id = $_GET['product_id'];

$conn = mysqli_connect('localhost', 'root', '', 'SHOP');

$query = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
