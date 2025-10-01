<?php
session_start();
require_once '../config/db.php';
require_once '../src/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    header('Location: login.php?error=Username and password are required.');
    exit;
}

try {
    $pdo = getDBConnection();
    $user = new User($pdo);

    $userData = $user->login($username, $password);

    if ($userData) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['role'] = $userData['role'];

        header('Location: dashboard.php');
        exit;
    } else {
        header('Location: login.php?error=Invalid username or password.');
        exit;
    }
} catch (Exception $e) {
    // In a real app, log the error
    header('Location: login.php?error=An unexpected error occurred.');
    exit;
}
?>