<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/Invoice.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: invoices.php');
    exit;
}

$userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$dueDate = $_POST['due_date']; // Basic validation, can be improved
$items = isset($_POST['items']) ? $_POST['items'] : [];

// Basic validation
if ($userId === false || empty($dueDate) || empty($items)) {
    header('Location: create_invoice.php?error=Invalid input. Please fill out all fields.');
    exit;
}

$sanitizedItems = [];
foreach ($items as $item) {
    $productId = filter_var($item['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
    $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);

    if ($productId && $quantity && $price !== false) {
        $sanitizedItems[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price
        ];
    }
}

if (empty($sanitizedItems)) {
    header('Location: create_invoice.php?error=No valid items provided for the invoice.');
    exit;
}

try {
    $pdo = getDBConnection();
    $invoice = new Invoice($pdo);

    if ($invoice->create($userId, $sanitizedItems, $dueDate)) {
        header('Location: invoices.php?success=Invoice created successfully.');
    } else {
        header('Location: create_invoice.php?error=Failed to create invoice.');
    }
} catch (Exception $e) {
    // In a real app, log the error message: $e->getMessage()
    header('Location: create_invoice.php?error=An unexpected error occurred.');
}
exit;
?>