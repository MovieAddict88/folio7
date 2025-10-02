<?php
// Database configuration settings
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'billing_system');

/**
 * Creates a new database connection using PDO.
 * @return PDO|null
 */
function getDBConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // In a real application, you would log this error and show a generic message
        die('Database connection failed: ' . $e->getMessage());
    }
}
?>