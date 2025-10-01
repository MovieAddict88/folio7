<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=Access denied.');
    exit;
}

require_once '../../config/db.php';
require_once '../../src/Notification.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=Invalid notification ID.');
    exit;
}

$notificationId = (int)$_GET['id'];

$pdo = getDBConnection();
$notification = new Notification($pdo);

// Mark the notification as read
$notification->markAsRead($notificationId);

// Get the notification to find out where to redirect
$notif = $notification->getById($notificationId);

if ($notif) {
    // TODO: This implementation relies on parsing the invoice ID from the notification message.
    // A more robust solution would be to store the related entity (e.g., 'invoice') and its ID
    // in separate columns in the `notifications` table. This would make the redirection logic
    // more reliable and easier to maintain.

    // Example of a message: "New payment received for invoice #123"
    // We need to parse the invoice ID from the message
    if (preg_match('/invoice #(\d+)/', $notif['message'], $matches)) {
        $invoiceId = $matches[1];
        header('Location: view_invoice.php?id=' . $invoiceId);
        exit;
    }
}

// Fallback if the message format is not recognized or notification not found
header('Location: index.php?error=Could not determine the destination for this notification.');
exit;