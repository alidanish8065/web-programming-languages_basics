<?php
$id = $_GET['product_id'];

$conn = mysqli_connect('localhost', 'root', '', 'wpl');

$query = "DELETE FROM products WHERE id = '$id'";
mysqli_query($conn, $query);

header('Location: index.php');
