<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Billing System</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="conversations.php">Messages</a>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text">
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Your Dashboard</h2>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin/" class="btn btn-primary">Go to Admin Panel</a>
            <?php endif; ?>
        </div>

        <h4>Your Invoices</h4>
        <?php
        require_once '../config/db.php';
        require_once '../src/Invoice.php';

        $pdo = getDBConnection();
        $invoice = new Invoice($pdo);
        $userId = $_SESSION['user_id'];

        // Pagination settings
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // Invoices per page
        $offset = ($page - 1) * $limit;

        // Get total number of invoices for this user
        $totalInvoices = $invoice->countByUserId($userId);
        $totalPages = ceil($totalInvoices / $limit);

        // Get invoices for the current page
        $userInvoices = $invoice->getByUserId($userId, $limit, $offset);
        ?>

        <?php if (empty($userInvoices)): ?>
            <div class="alert alert-info">You have no invoices yet.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Total Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userInvoices as $inv): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($inv['id']); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($inv['total_amount'], 2)); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($inv['balance'], 2)); ?></td>
                        <td><span class="badge bg-<?php
                            $status = $inv['status'];
                            if ($status === 'paid' || $status === 'approved') {
                                echo 'success';
                            } elseif ($status === 'pending' || $status === 'pending_verification') {
                                echo 'warning';
                            } else {
                                echo 'danger'; // covers 'rejected', 'cancelled' etc.
                            }
                        ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($inv['status']))); ?></span></td>
                        <td><?php echo htmlspecialchars($inv['due_date']); ?></td>
                        <td>
                            <?php
                                // Determine button properties based on invoice status and balance
                                $isPayable = $inv['balance'] > 0 && ($inv['status'] === 'pending' || $inv['status'] === 'rejected');
                                
                                if ($isPayable) {
                                    $buttonText = 'Pay Balance';
                                    $buttonLink = 'payment.php?id=' . $inv['id']; // FIX: Changed 'invoice_id' to 'id'
                                    $buttonClass = 'btn-success';
                                } else {
                                    $buttonText = 'View Details';
                                    $buttonLink = 'view_invoice.php?id=' . $inv['id'];
                                    $buttonClass = 'btn-info';
                                }
                            ?>
                            <a href="<?php echo $buttonLink; ?>" class="btn btn-sm <?php echo $buttonClass; ?>"><?php echo $buttonText; ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>