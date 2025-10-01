<?php
session_start();

// Unset all of the session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page with a success message
header('Location: login.php?success=You have been logged out.');
exit;
?>