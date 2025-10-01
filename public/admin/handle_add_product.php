<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/Product.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$name = trim($_POST['name']);
$description = trim($_POST['description']);
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

if (empty($name) || $price === false || $price < 0) {
    header('Location: add_product.php?error=Invalid input.');
    exit;
}

try {
    $pdo = getDBConnection();
    $product = new Product($pdo);

    if ($product->create($name, $description, $price)) {
        header('Location: products.php?success=Product added successfully.');
    } else {
        header('Location: add_product.php?error=Failed to add product.');
    }
} catch (Exception $e) {
    header('Location: add_product.php?error=An unexpected error occurred.');
}
exit;
?>