<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

require_once '../../config/db.php';
require_once '../../src/User.php';
require_once '../../src/Invoice.php';
require_once '../../src/Product.php';
require_once '../../src/Conversation.php';

$pdo = getDBConnection();
$user = new User($pdo);
$invoice = new Invoice($pdo);
$product = new Product($pdo);
$conversation = new Conversation($pdo);
$adminUserId = $_SESSION['user_id'];

// Fetch data for dashboard
$totalUsers = $user->getTotalStandardUsersCount();
$usersWithUpcomingInvoices = $invoice->countUsersWithUpcomingDueInvoices();
$totalProducts = $product->getTotalCount();
$usersWithProducts = $invoice->countUsersWithInvoices();

$dashboardData = [
    'totalUsers' => $totalUsers,
    'usersWithUpcomingInvoices' => $usersWithUpcomingInvoices,
    'totalProducts' => $totalProducts,
    'usersWithProducts' => $usersWithProducts,
];

$conversations = $conversation->getByUserId($adminUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '_nav.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Admin Dashboard</h1>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        User Statistics
                    </div>
                    <div class="card-body">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        Product Statistics
                    </div>
                    <div class="card-body">
                        <canvas id="productChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Conversations</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($conversations)): ?>
                            <div class="list-group-item">No new conversations.</div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): ?>
                                <a href="../view_conversation.php?id=<?php echo $conv['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($conv['subject']); ?></h5>
                                        <small><?php echo htmlspecialchars(date('M j, Y, g:i a', strtotime($conv['created_at']))); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const dashboardData = <?php echo json_encode($dashboardData); ?>;

    // User Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: ['Total Users', 'Users with Upcoming Dues'],
            datasets: [{
                label: 'User Counts',
                data: [dashboardData.totalUsers, dashboardData.usersWithUpcomingInvoices],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 99, 132, 0.6)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Product Chart
    const productCtx = document.getElementById('productChart').getContext('2d');
    new Chart(productCtx, {
        type: 'bar',
        data: {
            labels: ['Total Products', 'Users with Products'],
            datasets: [{
                label: 'Product Counts',
                data: [dashboardData.totalProducts, dashboardData.usersWithProducts],
                 backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>