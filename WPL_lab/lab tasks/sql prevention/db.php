<?php
$conn = mysqli_connect('localhost', 'root', '', 'SHOP');

if (!$conn) {
    die('Database connection failed');
}
