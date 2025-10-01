<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/Invoice.php';
require_once '../../src/Payment.php';
require_once '../../src/Notification.php';
require_once '../../src/User.php';

// Authenticate admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

// Check for POST request and required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['invoice_id']) || !isset($_POST['action'])) {
    header('Location: invoices.php?error=Invalid request.');
    exit;
}

$invoiceId = $_POST['invoice_id'];
$action = $_POST['action'];

$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$payment = new Payment($pdo);
$notification = new Notification($pdo);

$invoiceData = $invoice->getById($invoiceId);

// Validate invoice exists and is pending verification
if (!$invoiceData || $invoiceData['status'] !== 'pending_verification') {
    header('Location: invoices.php?error=Invalid invoice or action cannot be performed.');
    exit;
}

$userId = $invoiceData['user_id'];

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // When approving, we need to check if the invoice is fully paid or partially paid.
        // We'll get the latest payment to know the amount.
        $latestPayment = $payment->getLatestPaymentForInvoice($invoiceId);

        if (!$latestPayment) {
            throw new Exception("No payment record found to approve for invoice #{$invoiceId}.");
        }

        // The amount of the payment being approved
        $paymentAmount = $latestPayment['amount'];

        // The invoice class will now handle the logic of updating the status
        $invoice->updateStatusBasedOnBalance($invoiceId);

        // Update notification message to be more accurate
        $newInvoiceState = $invoice->getById($invoiceId);
        $status = $newInvoiceState['status'];

        $message = "Your payment of $" . number_format($paymentAmount, 2) . " for Invoice #{$invoiceId} has been approved. The invoice status is now '{$status}'.";
        $notification->create($userId, $message);

        $successMessage = "Payment for Invoice #{$invoiceId} has been approved. Invoice status is now '{$status}'.";

    } elseif ($action === 'reject') {
        // Find the latest payment to know how much to revert
        $latestPayment = $payment->getLatestPaymentForInvoice($invoiceId);

        if (!$latestPayment) {
            throw new Exception("No payment record found to reject for invoice #{$invoiceId}.");
        }

        $amountToRevert = $latestPayment['amount'];

        // 1. Revert the invoice's balance and amount_paid
        $invoice->revertPayment($invoiceId, $amountToRevert);

        // 2. Update the invoice status to 'rejected'
        $invoice->updateStatus($invoiceId, 'rejected');

        // 3. Delete the specific payment record that was rejected
        $payment->deleteById($latestPayment['id']);

        // 4. Create a notification for the user
        $message = "Your submitted payment of $" . number_format($amountToRevert, 2) . " for Invoice #{$invoiceId} was rejected. Please check your payment details and try again, or contact support.";
        $notification->create($userId, $message);

        $successMessage = "The payment for Invoice #{$invoiceId} has been rejected. The user has been notified.";

    } else {
        throw new Exception("Invalid action specified.");
    }

    $pdo->commit();
    header('Location: invoices.php?success=' . urlencode($successMessage));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Verification handling error: ' . $e->getMessage());
    header('Location: view_invoice.php?id=' . $invoiceId . '&error=An error occurred while processing the action.');
    exit;
}
?>