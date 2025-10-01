<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

require_once '../../config/db.php';
require_once '../../src/Conversation.php';

$pdo = getDBConnection();
$conversation = new Conversation($pdo);
$adminUserId = $_SESSION['user_id'];

$conversations = $conversation->getByUserId($adminUserId);
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>