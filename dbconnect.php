<?php
// dbconnect.php

// Define database credentials as constants
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'soc_pms');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'db_errors.log');

try {
    // Construct DSN with charset
    $dsn = "mysql:host=" . DB_HOST . 
           ";dbname=" . DB_NAME . 
           ";charset=" . DB_CHARSET;
    
    // Array of PDO options for security
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_TIMEOUT => 5, // Connection timeout in seconds
    ];

    // Create PDO instance
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Additional security checks
    $conn->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    $conn->exec("SET SESSION time_zone = '+00:00'");
    
    // Verify connection is alive
    if (!$conn->query('SELECT 1')) {
        throw new PDOException('Database connection test failed');
    }

} catch(PDOException $e) {
    // Log error securely
    error_log("Database connection error: " . $e->getMessage());
    
    // Generic error message for users
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Temporarily Unavailable');
    die('Database connection error. Please try again later.');
}

// Function to sanitize database inputs
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Function to validate database connection
function validateConnection($conn) {
    try {
        $conn->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        error_log("Connection validation failed: " . $e->getMessage());
        return false;
    }
}

// Function to close database connection
function closeConnection(&$conn) {
    $conn = null;
}

// Register shutdown function
register_shutdown_function(function() use (&$conn) {
    closeConnection($conn);
});
?>
