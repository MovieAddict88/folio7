<?php
require_once '../config/db.php';
require_once '../src/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Only allow POST requests
    header('Location: register.php');
    exit;
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];

if (empty($username) || empty($email) || empty($password)) {
    header('Location: register.php?error=All fields are required.');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=Invalid email format.');
    exit;
}

try {
    $pdo = getDBConnection();
    $user = new User($pdo);

    if ($user->create($username, $email, $password)) {
        header('Location: login.php?success=Registration successful. Please log in.');
        exit;
    } else {
        header('Location: register.php?error=Username or email already exists.');
        exit;
    }
} catch (Exception $e) {
    // In a real app, log the error
    header('Location: register.php?error=An unexpected error occurred.');
    exit;
}
?>