<?php
session_start();
require_once '../config/db.php';
require_once '../src/Invoice.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php?error=No invoice selected.');
    exit;
}

$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$details = $invoice->getDetailsById($_GET['id']);

// Authorization check: ensure the invoice belongs to the logged-in user OR the user is an admin
if (!$details || ($details['invoice']['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    header('Location: dashboard.php?error=Access denied or invoice not found.');
    exit;
}

$invoiceData = $details['invoice'];
$items = $details['items'];
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
                    <strong>Status:</strong> <span class="badge bg-<?php
                        $status = $invoiceData['status'];
                        if ($status === 'paid') {
                            echo 'success';
                        } elseif ($status === 'pending' || $status === 'pending_verification') {
                            echo 'warning';
                        } elseif ($status === 'rejected' || $status === 'cancelled') {
                            echo 'danger';
                        } else {
                            echo 'secondary';
                        }
                    ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span><br>
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
                        <th>$<?php echo htmlspecialchars(number_format($invoiceData['amount_paid'], 2)); ?></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end text-danger">Balance Due:</th>
                        <th class="text-danger">$<?php echo htmlspecialchars(number_format($invoiceData['balance'], 2)); ?></th>
                    </tr>
                </tfoot>
            </table>
             <div class="text-center mt-4">
                 <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                 <?php if ($invoiceData['balance'] > 0 && in_array($invoiceData['status'], ['pending', 'rejected', 'cancelled'])): ?>
                    <a href="payment.php?id=<?php echo htmlspecialchars($invoiceData['id']); ?>" class="btn btn-success">Pay Now</a>
                 <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>