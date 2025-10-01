<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';
require_once '../src/Conversation.php';
require_once '../src/Message.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: conversations.php?error=Invalid conversation ID.');
    exit;
}

$conversationId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

$pdo = getDBConnection();
$conversationRepo = new Conversation($pdo);
$messageRepo = new Message($pdo);

$conversation = $conversationRepo->getById($conversationId);

// Ensure the user is part of this conversation
if (!$conversation || ($conversation['user_id'] != $userId && $conversation['admin_id'] != $userId)) {
    header('Location: conversations.php?error=Access denied.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $messageRepo->create($conversationId, $userId, $message);
        header('Location: view_conversation.php?id=' . $conversationId);
        exit;
    }
}

$messages = $messageRepo->getByConversationId($conversationId);
$messageRepo->markAsRead($conversationId, $userId);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Conversation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .message {
            margin-bottom: 15px;
        }
        .message .sender {
            font-weight: bold;
        }
        .message .content {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
        }
        .my-message .content {
            background-color: #dcf8c6;
        }
        .my-message {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="chat-container">
            <h3><?php echo htmlspecialchars($conversation['subject']); ?></h3>
            <hr>
            <div class="messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $userId ? 'my-message' : ''; ?>">
                        <div class="sender"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                        <div class="content">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        <small class="text-muted"><?php echo $msg['created_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <hr>
            <form action="view_conversation.php?id=<?php echo $conversationId; ?>" method="post">
                <div class="form-group">
                    <label for="message">Your Reply</label>
                    <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
        <div class="text-center mt-3">
            <a href="conversations.php" class="btn btn-secondary">Back to Conversations</a>
        </div>
    </div>
</body>
</html>