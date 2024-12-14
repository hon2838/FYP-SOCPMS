<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation with timing attack prevention
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !$rbac->checkPermission('manage_users')) {
    $email = $_SESSION['email'] ?? 'unknown';
    error_log("Unauthorized edit user attempt: " . $email);
    
    // Notify admin about unauthorized access
    notifySystemError(
        'Unauthorized Access',
        "Unauthorized attempt to edit user by: $email",
        __FILE__,
        __LINE__
    );
    
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!$rbac->checkPermission('manage_users')) {
    handlePermissionError('manage_users', 'admin_manage_account.php');
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: strict-origin-when-cross-origin");

// Include database connection
include 'dbconnect.php';
require_once 'telegram/telegram_handlers.php';

// Helper function for string sanitization
function sanitizeString($string) {
    return htmlspecialchars(
        trim($string),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
}

// Rate limiting
if (!isset($_SESSION['edit_attempts'])) {
    $_SESSION['edit_attempts'] = 1;
    $_SESSION['edit_time'] = time();
} else {
    if (time() - $_SESSION['edit_time'] < 300) { // 5 minute window
        if ($_SESSION['edit_attempts'] > 10) { // Max 10 edit attempts per 5 minutes
            error_log("Rate limit exceeded for admin: " . $_SESSION['email']);
            die("Too many requests. Please try again later.");
        }
        $_SESSION['edit_attempts']++;
    } else {
        $_SESSION['edit_attempts'] = 1;
        $_SESSION['edit_time'] = time();
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Input validation and sanitization
    $id = filter_var(trim($_POST['id']), FILTER_VALIDATE_INT);
    $name = sanitizeString($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $user_type = sanitizeString($_POST['user_type']);
    
    // Additional validation
    if (!$id || $id <= 0) {
        die("Invalid user ID");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }
    
    if (!in_array($user_type, ['admin', 'user'])) {
        die("Invalid user type");
    }
    
    try {
        // Verify user exists and prevent timing attacks
        $checkStmt = $conn->prepare("SELECT id FROM tbl_users WHERE id = ? LIMIT 1");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->rowCount() === 0) {
            error_log("Attempt to edit non-existent user: {$id}");
            die("User not found");
        }
        
        // Prevent duplicate email
        $emailCheckStmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ? AND id != ? LIMIT 1");
        $emailCheckStmt->execute([$email, $id]);
        
        if ($emailCheckStmt->rowCount() > 0) {
            die("Email already exists");
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Update user
        $stmt = $conn->prepare("UPDATE tbl_users SET name = ?, email = ?, user_type = ? WHERE id = ?");
        
        if (!$stmt->execute([$name, $email, $user_type, $id])) {
            throw new Exception("Failed to update user");
        }

        // Notify admin about successful user modification
        notifySystemError(
            'User Modified',
            "User ID: $id modified by admin: {$_SESSION['email']}\nNew details: Name: $name, Email: $email, Type: $user_type",
            __FILE__,
            __LINE__
        );

        header('Location: admin_manage_account.php');
        exit();

    } catch (Exception $e) {
        error_log("User edit error: " . $e->getMessage());
        
        // Notify admin about error
        notifySystemError(
            'Database Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        
        die("An error occurred while updating user. Please try again later.");
    }
}
?>
