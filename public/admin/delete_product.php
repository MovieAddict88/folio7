<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/Product.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: products.php?error=No product selected.');
    exit;
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($id === false) {
    header('Location: products.php?error=Invalid product ID.');
    exit;
}

try {
    $pdo = getDBConnection();
    $product = new Product($pdo);

    if ($product->delete($id)) {
        header('Location: products.php?success=Product deleted successfully.');
    } else {
        header('Location: products.php?error=Failed to delete product.');
    }
} catch (PDOException $e) {
    // Handle foreign key constraint error
    if ($e->getCode() == '23000') {
        header('Location: products.php?error=Cannot delete product because it is associated with an existing invoice.');
    } else {
        header('Location: products.php?error=An unexpected database error occurred.');
    }
} catch (Exception $e) {
    header('Location: products.php?error=An unexpected error occurred.');
}
exit;
?>