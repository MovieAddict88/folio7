<?php
session_start();
require_once '../config/db.php';
require_once '../src/Message.php';
require_once '../src/Conversation.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDBConnection();
$messageRepo = new Message($pdo);
$conversationRepo = new Conversation($pdo);

$conversationId = $_POST['conversation_id'];
$message = trim($_POST['message']);
$senderId = $_SESSION['user_id'];

// Authorization check
$conversation = $conversationRepo->getById($conversationId);
if (!$conversation || ($conversation['user_id'] != $senderId && $_SESSION['role'] !== 'admin')) {
    // Redirect to dashboard with a generic error if unauthorized
    header("Location: dashboard.php?error=Unauthorized");
    exit;
}

// Get the invoice ID from the trusted conversation object
$invoiceId = $conversation['invoice_id'];

$filePath = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $fileInfo = pathinfo(basename($_FILES["attachment"]["name"]));
    $fileExtension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
    // Generate a unique filename to prevent collisions and improve security
    $uniqueFileName = uniqid('attachment_', true) . bin2hex(random_bytes(4)) . $fileExtension;
    $targetFilePath = $targetDir . $uniqueFileName;

    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $targetFilePath)) {
        $filePath = $targetFilePath;
    } else {
        // Handle file upload error
        $redirectUrl = isset($_POST['is_admin']) && $_POST['is_admin'] == 'true' ? "admin/view_transaction_conversation.php" : "view_transaction_conversation.php";
        header("Location: $redirectUrl?invoice_id=$invoiceId&error=File upload failed");
        exit;
    }
}

if (!empty($message) || $filePath) {
    $messageRepo->create($conversationId, $senderId, $message, $filePath);
}

// Redirect back to the correct conversation page
$redirectUrl = isset($_POST['is_admin']) && $_POST['is_admin'] == 'true' ? "admin/view_transaction_conversation.php" : "view_transaction_conversation.php";
header("Location: $redirectUrl?invoice_id=$invoiceId");
exit;
?>