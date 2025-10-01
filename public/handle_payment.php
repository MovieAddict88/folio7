<?php
session_start();
require_once '../config/db.php';
require_once '../src/Invoice.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$invoiceId = $_POST['invoice_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$paymentMethod = $_POST['payment_method'] ?? null;

if (!$invoiceId || !$amount || !$paymentMethod) {
    header('Location: dashboard.php?error=Invalid payment data.');
    exit;
}

$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$invoiceData = $invoice->getById($invoiceId);

// Authorization and validation
if (!$invoiceData || $invoiceData['user_id'] != $_SESSION['user_id'] || !in_array($invoiceData['status'], ['pending', 'rejected'])) {
    header('Location: dashboard.php?error=Payment cannot be processed.');
    exit;
}

// If the invoice was rejected, reset its status to 'pending' before trying again.
if ($invoiceData['status'] === 'rejected') {
    $invoice->updateStatus($invoiceId, 'pending');
}

// --- Redirect to External Payment Gateway ---

// In a real application, the base URL should be stored in a configuration file.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Get the base path of the application by finding the directory of the current script.
$basePath = dirname($_SERVER['SCRIPT_NAME']);
// Construct a clean callback URL pointing to confirm_payment.php
$callbackUrl = $protocol . $host . $basePath . '/confirm_payment.php';

// Prepare query parameters for the gateway URL
$gatewayParams = [
    'invoice_id' => $invoiceId,
    'amount' => $amount,
    'payment_method' => $paymentMethod,
    'callback_url' => $callbackUrl,
    // In a real scenario, you'd also include a signature/hash to verify the request's integrity
    // 'hash' => hash_hmac('sha256', $invoiceId . $amount, 'YOUR_SECRET_KEY')
];

// Build the final redirect URL for the new payment processor
$redirectUrl = 'process_payment.php?' . http_build_query($gatewayParams);

// Redirect the user to the simulated external gateway
header('Location: ' . $redirectUrl);
exit;
?>