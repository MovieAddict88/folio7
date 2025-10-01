<?php
session_start();
require_once '../config/db.php';
require_once '../src/Conversation.php';
require_once '../src/Message.php';
require_once '../src/Invoice.php';
require_once '../src/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['invoice_id'])) {
    header('Location: dashboard.php?error=No invoice specified.');
    exit;
}

$pdo = getDBConnection();
$invoiceId = $_GET['invoice_id'];
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$invoiceRepo = new Invoice($pdo);
$invoice = $invoiceRepo->getDetailsById($invoiceId);

if (!$invoice || ($invoice['invoice']['user_id'] != $userId && $userRole !== 'admin')) {
    header('Location: dashboard.php?error=Access denied to this invoice.');
    exit;
}

$conversationRepo = new Conversation($pdo);
$conversation = $conversationRepo->findByInvoiceId($invoiceId);

if (!$conversation) {
    $userRepo = new User($pdo);
    $admins = $userRepo->getAdmins();
    if (empty($admins)) {
        // Fallback or error handling if no admins are found
        die("No admin users found. Cannot create a conversation.");
    }
    // Assign the first available admin
    $adminId = $admins[0]['id'];
    $subject = "Discussion for Invoice #" . $invoiceId;
    $conversationId = $conversationRepo->create($invoice['invoice']['user_id'], $adminId, $subject, $invoiceId);
    $conversation = $conversationRepo->getById($conversationId);
} else {
    $conversationId = $conversation['id'];
}

$messageRepo = new Message($pdo);
$messages = $messageRepo->getByConversationId($conversationId);

// Mark messages as read
$messageRepo->markAsRead($conversationId, $userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Conversation for Invoice #<?php echo htmlspecialchars($invoiceId); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-box {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 15px;
        }
        .message .sender {
            font-weight: bold;
        }
        .message .timestamp {
            font-size: 0.8em;
            color: #888;
        }
        .message-form {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h3>Conversation for Invoice #<?php echo htmlspecialchars($invoiceId); ?></h3>
    <div class="chat-box bg-light">
        <?php if (empty($messages)): ?>
            <p class="text-center">No messages yet. Start the conversation!</p>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <div class="message">
                    <p>
                        <span class="sender"><?php echo htmlspecialchars($message['sender_name']); ?>:</span>
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        <?php if ($message['file_path']): ?>
                            <br><a href="<?php echo htmlspecialchars($message['file_path']); ?>" target="_blank">View Attachment</a>
                        <?php endif; ?>
                    </p>
                    <p class="timestamp"><?php echo date('M j, Y, g:i a', strtotime($message['created_at'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="message-form">
        <form action="handle_message.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="conversation_id" value="<?php echo $conversationId; ?>">
            <input type="hidden" name="invoice_id" value="<?php echo $invoiceId; ?>">
            <div class="mb-3">
                <label for="message" class="form-label">Your Message</label>
                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="attachment" class="form-label">Attach File (Optional)</label>
                <input class="form-control" type="file" id="attachment" name="attachment">
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
            <a href="view_invoice.php?id=<?php echo htmlspecialchars($invoiceId); ?>" class="btn btn-secondary">Back to Invoice</a>
        </form>
    </div>
</div>
</body>
</html>