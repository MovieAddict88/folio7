<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';
require_once '../src/Conversation.php';

$pdo = getDBConnection();
$conversation = new Conversation($pdo);
$conversations = $conversation->getByUserId($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversations</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Conversations</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conversations as $conv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($conv['subject']); ?></td>
                        <td><?php echo htmlspecialchars($conv['created_at']); ?></td>
                        <td>
                            <a href="view_conversation.php?id=<?php echo $conv['id']; ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>