<?php
session_start();
require_once '../../config/db.php';
require_once '../../src/Invoice.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

$pdo = getDBConnection();
$invoice = new Invoice($pdo);

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Invoices per page
$offset = ($page - 1) * $limit;

// Get total number of invoices for pagination
$totalInvoices = $invoice->countAll();
$totalPages = ceil($totalInvoices / $limit);

// Get invoices for the current page
$invoices = $invoice->getAllWithUsers($limit, $offset);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Invoices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_nav.php'; ?>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Manage Invoices</h2>
            <a href="create_invoice.php" class="btn btn-success">Create New Invoice</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Total Amount</th>
                    <th>Paid Amount</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Created On</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <?php
                    $status_class = 'secondary';
                    switch ($inv['status']) {
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
                        case 'rejected':
                            $status_class = 'danger';
                            break;
                    }
                ?>
                <tr class="<?php echo $inv['status'] === 'pending_verification' ? 'table-primary' : ''; ?>">
                    <td>#<?php echo htmlspecialchars($inv['id']); ?></td>
                    <td><?php echo htmlspecialchars($inv['username']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($inv['total_amount'], 2)); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($inv['amount_paid'], 2)); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($inv['balance'], 2)); ?></td>
                    <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($inv['status']))); ?></span></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($inv['created_at']))); ?></td>
                    <td><?php echo htmlspecialchars($inv['due_date']); ?></td>
                    <td>
                        <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info">View</a>
                        <a href="view_transaction_conversation.php?invoice_id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-warning">Discuss</a>
                        <?php if ($inv['status'] === 'pending_verification'): ?>
                            <form action="handle_verification.php" method="POST" class="d-inline">
                                <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
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
    </div>
</body>
</html>