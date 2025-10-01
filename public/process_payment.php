<?php
session_start();

// --- Payment Processor ---

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Retrieve payment details from the query string
$invoiceId = $_GET['invoice_id'] ?? null;
$amount = $_GET['amount'] ?? null;
$paymentMethod = strtolower($_GET['payment_method'] ?? '');
$callbackUrl = $_GET['callback_url'] ?? null;

// Basic validation
if (!$invoiceId || !$amount || !$paymentMethod || !$callbackUrl) {
    die("<h1>Error: Invalid Payment Request</h1><p>Missing required payment details.</p>");
}

// --- Payment Method Routing ---

// In a real application, you would integrate with different payment gateway SDKs here.
// This is a simulation.

$paymentPageTitle = "Process Payment";
$paymentPageContent = "";

// Simulate a transaction ID now, as it might be needed by the gateway before redirection.
$transactionId = 'SIM_TXN_' . strtoupper(uniqid());

// Prepare the callback parameters for success
$successCallbackParams = [
    'invoice_id' => $invoiceId,
    'transaction_id' => $transactionId,
    'status' => 'success', // Simulate a successful payment
    'payment_method' => $paymentMethod,
    'amount' => $amount
];
$successCallbackUrl = $callbackUrl . '?' . http_build_query($successCallbackParams);

// Prepare the callback for manual verification
$manualCallbackParams = [
    'invoice_id' => $invoiceId,
    'transaction_id' => 'N/A', // No external transaction ID yet
    'status' => 'pending_verification',
    'payment_method' => $paymentMethod,
    'amount' => $amount
];
$manualCallbackUrl = $callbackUrl . '?' . http_build_query($manualCallbackParams);

$cancelUrl = $callbackUrl . '?status=cancelled&invoice_id=' . $invoiceId;

// --- UI for Different Payment Methods ---

if ($paymentMethod === 'gcash' || $paymentMethod === 'paymaya') {
    // Simulate a redirect to a digital wallet payment page
    $paymentPageTitle = "Pay with " . ucfirst($paymentMethod);
    $logoUrl = $paymentMethod === 'gcash' ? 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ee/GCash_logo.svg/1200px-GCash_logo.svg.png' : 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/PayMaya_logo.svg/1200px-PayMaya_logo.svg.png';

    $paymentPageContent = <<<HTML
<div class="card gateway-card">
    <div class="card-header gateway-header text-center">
        <img src="{$logoUrl}" alt="{$paymentMethod} logo" style="height: 40px;">
    </div>
    <div class="card-body p-4">
        <h5 class="card-title text-center">Scan to Pay</h5>
        <p class="text-center text-muted">You are paying for Invoice #{$invoiceId}</p>
        <hr>
        <div class="text-center">
            <p class="mb-1"><strong>Merchant:</strong> Billing System Inc.</p>
            <p class="mb-1"><strong>Account Name:</strong> Jules V.</p>
            <p class="mb-1"><strong>Account Number:</strong> 09663016917</p>
        </div>
        <hr>
        <div class="d-flex justify-content-between">
            <p class="mb-0"><strong>Amount to Pay:</strong></p>
            <p class="mb-0 fs-5"><strong>₱{$amount}</strong></p>
        </div>
        <div class="alert alert-info mt-4">
            This is a simulated payment screen. After "paying," click the button below to confirm your transaction.
        </div>
        <div class="d-grid mt-4">
            <a href="{$successCallbackUrl}" class="btn btn-success btn-lg">I Have Paid</a>
        </div>
    </div>
    <div class="card-footer text-center bg-transparent">
        <a href="{$cancelUrl}">Cancel Payment</a>
    </div>
</div>
HTML;

} else {
    // Simulate a manual payment method like a bank transfer or over-the-counter payment
    $paymentPageTitle = "Pay with " . ucfirst(str_replace('_', ' ', $paymentMethod));
    $paymentPageContent = <<<HTML
<div class="card gateway-card">
    <div class="card-header gateway-header">
        <h3 class="text-center mb-0">{$paymentPageTitle}</h3>
    </div>
    <div class="card-body p-4">
        <h5 class="card-title">Payment Instructions</h5>
        <p>Please complete the payment using the details below and then enter the transaction reference number to proceed.</p>
        <div class="alert alert-secondary">
            <p class="mb-1"><strong>Bank Name:</strong> BDO Unibank, Inc.</p>
            <p class="mb-1"><strong>Account Name:</strong> Billing System Inc.</p>
            <p class="mb-1"><strong>Account Number:</strong> 123-456-7890</p>
            <p class="mb-0"><strong>Amount:</strong> ₱{$amount}</p>
        </div>
        <hr>
        <form action="{$manualCallbackUrl}" method="POST">
            <input type="hidden" name="invoice_id" value="{$invoiceId}">
            <input type="hidden" name="amount" value="{$amount}">
            <input type="hidden" name="payment_method" value="{$paymentMethod}">
            <input type="hidden" name="status" value="pending_verification">

            <div class="mb-3">
                <label for="reference_number" class="form-label"><strong>Reference Number</strong></label>
                <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Enter the transaction code" required>
                <div class="form-text">Enter the reference number from your deposit slip or online transfer.</div>
            </div>
            <div class="alert alert-warning">
                This is a simulation. Your payment will be marked as "Pending Verification" and will need to be manually approved by an admin.
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Submit for Verification</button>
            </div>
        </form>
    </div>
    <div class="card-footer text-center bg-transparent">
        <a href="{$cancelUrl}">Cancel Payment</a>
    </div>
</div>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($paymentPageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .gateway-card {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .gateway-header {
            background-color: #0d6efd;
            color: white;
            border-bottom: none;
            padding: 1rem;
        }
        .form-text { font-size: 0.875em; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php echo $paymentPageContent; ?>
            </div>
        </div>
    </div>
</body>
</html>