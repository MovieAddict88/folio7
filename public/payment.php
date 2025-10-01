<?php
session_start();
require_once '../config/db.php';
require_once '../src/Invoice.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Use 'id' from GET parameter, consistent with dashboard link
if (!isset($_GET['id'])) {
    header('Location: dashboard.php?error=No invoice selected.');
    exit;
}

$invoiceId = $_GET['id'];
$pdo = getDBConnection();
$invoice = new Invoice($pdo);
$invoiceData = $invoice->getById($invoiceId);

// Authorization check: ensure the invoice belongs to the logged-in user
if (!$invoiceData || $invoiceData['user_id'] != $_SESSION['user_id']) {
    header('Location: dashboard.php?error=Access denied or invoice not found.');
    exit;
}

// CRITICAL FIX: Allow payment if status is 'pending' OR 'rejected'
if (!in_array($invoiceData['status'], ['pending', 'rejected'])) {
    header('Location: dashboard.php?error=Payment cannot be processed for this invoice at its current status.');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment for Invoice #<?php echo htmlspecialchars($invoiceData['id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Payment for Invoice #<?php echo htmlspecialchars($invoiceData['id']); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title mb-0">Total Amount:</h5>
                            <h5 class="card-title mb-0">$<?php echo htmlspecialchars(number_format($invoiceData['total_amount'], 2)); ?></h5>
                        </div>
                        <div class="d-flex justify-content-between text-danger">
                            <p class="mb-0">Balance Due:</p>
                            <p class="mb-0">$<?php echo htmlspecialchars(number_format($invoiceData['balance'], 2)); ?></p>
                        </div>
                        <hr>
                        <form action="handle_payment.php" method="POST">
                            <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($invoiceData['id']); ?>">

                            <div class="mb-3">
                                <label for="amount" class="form-label"><strong>Payment Amount</strong></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="amount" name="amount"
                                           min="0.01" max="<?php echo htmlspecialchars($invoiceData['balance']); ?>"
                                           step="0.01" value="<?php echo htmlspecialchars($invoiceData['balance']); ?>" required>
                                </div>
                                <div class="form-text">
                                    You can pay the full balance or a partial amount.
                                </div>
                            </div>

                            <p class="card-text">Please select a payment method:</p>
                            <div class="list-group">
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="payment_method" value="GCASH" required>
                                    GCASH
                                </label>
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="payment_method" value="PAYMAYA">
                                    PAYMAYA
                                </label>
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="payment_method" value="PAYPAL">
                                    PAYPAL
                                </label>
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="payment_method" value="GOTYME">
                                    GOTYME
                                </label>
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="payment_method" value="BANK_TRANSFER">
                                    BANK TRANSFER
                                </label>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Proceed to Payment Gateway</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="dashboard.php">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>