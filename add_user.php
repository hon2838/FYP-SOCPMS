<?php
require_once 'telegram/telegram_handlers.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'admin')) {
    
    $email = $_SESSION['email'] ?? 'unknown';
    error_log("Unauthorized user addition attempt: " . $email);
    
    // Notify admin about unauthorized access
    notifySystemError(
        'Unauthorized Access',
        "Unauthorized attempt to add user by: $email",
        __FILE__,
        __LINE__
    );
    
    header('Location: index.php');
    exit;
}

// Include database connection
include 'dbconnect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_type = $_POST['user_type'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Prepare SQL statement
        $sql = "INSERT INTO tbl_users (name, email, password, user_type) 
                VALUES (:name, :email, :password, :user_type)";
        $stmt = $conn->prepare($sql);
        
        // Bind parameters and execute
        if ($stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password,
            ':user_type' => $user_type
        ])) {
            // Notify admin about successful user addition
            notifySystemError(
                'User Added',
                "New user added by admin {$_SESSION['email']}\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Type: $user_type",
                __FILE__,
                __LINE__
            );
            
            header('Location: admin_manage_account.php');
            exit;
        }

    } catch (Exception $e) {
        error_log("User addition error: " . $e->getMessage());
        
        // Notify admin about error
        notifySystemError(
            'Database Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        
        die("An error occurred while adding user. Please try again later.");
    }
}
?>
