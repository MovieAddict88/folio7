<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/Invoice.php';
require_once '../../src/Payment.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: invoices.php?error=No invoice selected.');
    exit;
}

$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$details = $invoice->getDetailsById($_GET['id']);

if (!$details) {
    header('Location: invoices.php?error=Invoice not found.');
    exit;
}

$invoiceData = $details['invoice'];
$items = $details['items'];
$paymentData = null;

// Fetch payment details for paid or pending verification invoices
if ($invoiceData['status'] === 'paid' || $invoiceData['status'] === 'pending_verification') {
    $payment = new Payment($pdo);
    $paymentData = $payment->findByInvoiceId($_GET['id']);
}

// Determine badge class for status
$status_class = 'secondary';
switch ($invoiceData['status']) {
    case 'paid':
        $status_class = 'success';
        break;
    case 'pending':
        $status_class = 'warning';
        break;
    case 'pending_verification':
        $status_class = 'info';
        break;
    case 'cancelled':
        $status_class = 'danger';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoiceData['id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }
    </style>
</head>
<body>
    <?php include '_nav.php'; ?>
    <div class="container mt-5">
        <div class="invoice-box">
            <div class="row">
                <div class="col-sm-6">
                    <h2 class="mb-4">Invoice #<?php echo htmlspecialchars($invoiceData['id']); ?></h2>
                    <strong>Billed To:</strong><br>
                    <?php echo htmlspecialchars($invoiceData['username']); ?><br>
                    <?php echo htmlspecialchars($invoiceData['email']); ?>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <strong>Status:</strong> <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($invoiceData['status']))); ?></span><br>
                    <strong>Date Created:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($invoiceData['created_at']))); ?><br>
                    <strong>Date Due:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($invoiceData['due_date']))); ?>
                </div>
            </div>

            <hr>

            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($item['quantity'] * $item['price'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Grand Total:</th>
                        <th>$<?php echo htmlspecialchars(number_format($invoiceData['total_amount'], 2)); ?></th>
                    </tr>
                     <tr>
                        <th colspan="3" class="text-end">Amount Paid:</th>
                        <th class="text-success">$<?php echo htmlspecialchars(number_format($invoiceData['amount_paid'], 2)); ?></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end text-danger">Balance Due:</th>
                        <th class="text-danger">$<?php echo htmlspecialchars(number_format($invoiceData['balance'], 2)); ?></th>
                    </tr>
                </tfoot>
            </table>

            <?php if (!empty($paymentData)): ?>
            <hr>
            <h4>Payment History</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                        <th>Reference #</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paymentData as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($payment['payment_date']))); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($payment['amount'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($payment['reference_number'] ?: 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if ($invoiceData['status'] === 'pending_verification'): ?>
            <hr>
            <div class="card mt-4 bg-light border-primary">
                <div class="card-header">
                    <h5 class="mb-0">Admin Verification Action</h5>
                </div>
                <div class="card-body text-center">
                    <p class="card-text">This payment requires manual verification. Please use the reference number to confirm the payment was received.</p>
                    <div class="d-flex justify-content-center">
                        <form action="handle_verification.php" method="POST" class="d-inline me-2">
                            <input type="hidden" name="invoice_id" value="<?php echo $invoiceData['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-lg">Approve Payment</button>
                        </form>
                        <form action="handle_verification.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this payment? This action cannot be undone.');">
                            <input type="hidden" name="invoice_id" value="<?php echo $invoiceData['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger btn-lg">Reject Payment</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                 <a href="invoices.php" class="btn btn-secondary">Back to Invoices</a>
                 <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
            </div>
        </div>
    </div>
</body>
</html>