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
// Assuming the admin user who receives notifications has user_id = 1
$unreadNotifications = $notification->getUnreadByUserId(1);
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
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small><?php echo htmlspecialchars(date('M j, Y, g:i a', strtotime($notif['created_at']))); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>