<?php
session_start();
require_once '../config/db.php';
require_once '../src/Invoice.php';
require_once '../src/Payment.php';
require_once '../src/Notification.php';

// User must be logged in to have a session context
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- Handle Callback from Payment Processor ---

// Determine the source of the data (GET for redirects, POST for forms)
$dataSource = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$invoiceId = $dataSource['invoice_id'] ?? null;
$transactionId = $dataSource['transaction_id'] ?? null;
$status = $dataSource['status'] ?? 'failed';
$paymentMethod = $dataSource['payment_method'] ?? 'Unknown';
$amount = $dataSource['amount'] ?? null;
$referenceNumber = $dataSource['reference_number'] ?? null; // For manual payments

// Handle failed or cancelled payments immediately
if ($status === 'cancelled' || $status === 'failed') {
    $errorMessage = $status === 'cancelled' ? 'Payment was cancelled.' : 'Payment failed.';
    // Ensure invoiceId is available for the redirect URL
    $redirectInvoiceId = $invoiceId ?: ($_GET['invoice_id'] ?? '');
    header('Location: payment.php?id=' . $redirectInvoiceId . '&error=' . urlencode($errorMessage));
    exit;
}

// Validate essential data
if (!$invoiceId || !$amount) {
    header('Location: dashboard.php?error=Invalid payment confirmation data.');
    exit;
}

$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$invoiceData = $invoice->getById($invoiceId);

// Authorization and validation: allow processing if status is 'pending'
if (!$invoiceData || $invoiceData['user_id'] != $_SESSION['user_id'] || $invoiceData['status'] !== 'pending') {
    header('Location: dashboard.php?error=Payment cannot be processed for this invoice.');
    exit;
}

// --- Finalize Payment Based on Status ---

$payment = new Payment($pdo);
$notification = new Notification($pdo);
$adminUserId = 1; // Assuming admin user ID is 1

try {
    $pdo->beginTransaction();

    if ($status === 'success') {
        // --- Handle auto-confirmed payments (GCash, PayMaya sim) ---
        if (!$transactionId) {
            throw new Exception("Missing transaction ID for successful payment.");
        }

        // 1. Record the payment
        $payment->create($invoiceId, $amount, $paymentMethod, $transactionId);

        // 2. Update the invoice payment details
        $invoice->updatePaymentDetails($invoiceId, $amount);

        // 3. Create a notification for the admin
        $message = "Payment of $" . number_format($amount, 2) . " for Invoice #$invoiceId was confirmed via $paymentMethod (Transaction ID: $transactionId).";
        $notification->create($adminUserId, $message);

        $pdo->commit();

        // Redirect to the final receipt page
        header('Location: receipt.php?invoice_id=' . $invoiceId);
        exit;

    } elseif ($status === 'pending_verification') {
        // --- Handle manually submitted payments (Bank Transfer) ---
        $finalTransactionId = 'MANUAL-' . ($referenceNumber ?: 'NA');

        // 1. Record the payment attempt
        // Note: The `create` method in Payment.php will need to be updated to handle a new column for the reference number.
        $payment->create($invoiceId, $amount, $paymentMethod, $finalTransactionId, $referenceNumber);

        // 2. Update the invoice status to 'pending_verification'
        $invoice->updateStatus($invoiceId, 'pending_verification');


        // 3. Create a notification for the admin to verify the payment
        $message = "A payment of $" . number_format($amount, 2) . " for Invoice #$invoiceId was submitted via $paymentMethod and requires verification. Reference: $referenceNumber.";
        $notification->create($adminUserId, $message);

        $pdo->commit();

        // Redirect to dashboard with a success message
        header('Location: dashboard.php?success=Your payment has been submitted for verification.');
        exit;
    } else {
        // Fallback for any other status
        throw new Exception("Invalid payment status received.");
    }

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Payment confirmation error: " . $e->getMessage()); // Log the actual error
    header('Location: payment.php?id=' . $invoiceId . '&error=A critical error occurred. Please contact support.');
    exit;
}
?>