<?php
// Database configuration settings
define('DB_HOST', 'sql311.infinityfree.com'); // e.g., sqlXXX.infinityfree.com
define('DB_USER', 'if0_40043611');
define('DB_PASS', '4VVob4pFy2oKqTx');
define('DB_NAME', 'if0_40043611_billing2');

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