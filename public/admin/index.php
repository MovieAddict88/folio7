<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

require_once '../../config/db.php';
require_once '../../src/Notification.php';

$pdo = getDBConnection();
$notification = new Notification($pdo);
$adminUserId = 1; // Assuming the admin user who receives notifications has user_id = 1

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get notifications for the current page
$unreadNotifications = $notification->getUnreadByUserIdWithPagination($adminUserId, $limit, $offset);
$totalNotifications = $notification->countUnreadByUserId($adminUserId);
$totalPages = ceil($totalNotifications / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_nav.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Welcome, Admin!</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Payment Notifications</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($unreadNotifications)): ?>
                            <div class="list-group-item">No new notifications.</div>
                        <?php else: ?>
                            <?php foreach ($unreadNotifications as $notif): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small><?php echo htmlspecialchars(date('M j, Y, g:i a', strtotime($notif['created_at']))); ?></small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="view_notification.php?id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="delete_notification.php?id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this notification?');">Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>