<?php
require 'db.php';

$id = $_GET['product_id'];
mysqli_query($conn, "DELETE FROM products WHERE id = $id");

header('Location: index.php');
