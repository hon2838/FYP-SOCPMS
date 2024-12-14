<?php
// Include functions first
require_once 'includes/functions.php';

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
        PDO::ATTR_TIMEOUT => 5
    ];

    // Create PDO instance
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Additional security checks
    $conn->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    $conn->exec("SET SESSION time_zone = '+00:00'");
    
    // After PDO connection
    require_once 'includes/RBAC.php';
    $rbac = new RBAC($conn);

    // Make RBAC available globally if needed
    global $rbac;

    // Add after database connection
    require_once 'telegram_bot.php';

    $telegram = new TelegramBot(
        getenv('TELEGRAM_BOT_TOKEN') ?: 'your_bot_token_here',
        getenv('TELEGRAM_CHAT_ID') ?: 'your_chat_id_here'
    );

} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Register shutdown function
register_shutdown_function(function() use (&$conn) {
    closeConnection($conn);
});
?>
