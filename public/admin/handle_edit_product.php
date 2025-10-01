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

$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);

if ($id === false || empty($name) || $price === false || $price < 0) {
    header('Location: edit_product.php?id=' . $_POST['id'] . '&error=Invalid input.');
    exit;
}

try {
    $pdo = getDBConnection();
    $product = new Product($pdo);

    if ($product->update($id, $name, $description, $price)) {
        header('Location: products.php?success=Product updated successfully.');
    } else {
        header('Location: edit_product.php?id=' . $id . '&error=Failed to update product.');
    }
} catch (Exception $e) {
    header('Location: edit_product.php?id=' . $id . '&error=An unexpected error occurred.');
}
exit;
?>