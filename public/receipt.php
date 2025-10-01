<?php
session_start();
require_once '../config/db.php';
require_once '../src/Invoice.php';
require_once '../src/Payment.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$invoiceId = $_GET['invoice_id'] ?? null;

if (!$invoiceId) {
    header('Location: dashboard.php?error=No invoice specified.');
    exit;
}

$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$payment = new Payment($pdo);

$details = $invoice->getDetailsById($invoiceId);
$payments = $payment->findByInvoiceId($invoiceId);

// Authorization check
if (!$details || $details['invoice']['user_id'] != $_SESSION['user_id']) {
    header('Location: dashboard.php?error=Access denied.');
    exit;
}

if (empty($payments)) {
     header('Location: view_invoice.php?id=' . $invoiceId . '&error=Payment details not found.');
    exit;
}

$invoiceData = $details['invoice'];
$latestPayment = $payments[0]; // The latest payment for the main receipt details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-box {
            max-width: 800px; /* Increased width for more details */
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }
        .summary-table th, .summary-table td {
            border: none !important;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="receipt-box">
            <div class="text-center">
                <h2 class="mb-4">Payment Confirmation</h2>
                <p class="lead">Thank you for your payment for Invoice #<?php echo htmlspecialchars($invoiceData['id']); ?>.</p>
            </div>
            <hr>

            <h4 class="mb-3">Latest Payment Details</h4>
            <ul class="list-unstyled">
                <li><strong>Payment Date:</strong> <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($latestPayment['payment_date']))); ?></li>
                <li><strong>Payment Method:</strong> <?php echo htmlspecialchars($latestPayment['payment_method']); ?></li>
                <li><strong>Transaction ID:</strong> <?php echo htmlspecialchars($latestPayment['transaction_id']); ?></li>
                <li class="mt-2"><strong>Amount Paid (This Transaction):</strong> <span class="h5">$<?php echo htmlspecialchars(number_format($latestPayment['amount'], 2)); ?></span></li>
            </ul>
            <hr>

            <h4 class="mb-3">Invoice Summary</h4>
            <table class="table summary-table">
                <tbody>
                    <tr>
                        <th class="text-end" style="width: 75%;">Total Invoice Amount:</th>
                        <td class="text-end h5">$<?php echo htmlspecialchars(number_format($invoiceData['total_amount'], 2)); ?></td>
                    </tr>
                    <tr>
                        <th class="text-end">Total Amount Paid:</th>
                        <td class="text-end h5 text-success">$<?php echo htmlspecialchars(number_format($invoiceData['amount_paid'], 2)); ?></td>
                    </tr>
                    <tr>
                        <th class="text-end text-danger">Remaining Balance:</th>
                        <td class="text-end h5 text-danger">$<?php echo htmlspecialchars(number_format($invoiceData['balance'], 2)); ?></td>
                    </tr>
                </tbody>
            </table>

            <hr>

            <h4 class="mb-3">Full Payment History</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($p['payment_date']))); ?></td>
                        <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($p['transaction_id']); ?></td>
                        <td class="text-end">$<?php echo htmlspecialchars(number_format($p['amount'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr>
            <div class="text-center mt-4">
                <a href="view_invoice.php?id=<?php echo htmlspecialchars($invoiceData['id']); ?>" class="btn btn-primary">View Current Invoice Status</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>