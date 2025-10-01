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

if ($notification->delete($notificationId)) {
    header('Location: index.php?success=Notification deleted.');
} else {
    header('Location: index.php?error=Failed to delete notification.');
}
exit;