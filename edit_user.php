<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation with timing attack prevention
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'admin')) {
    error_log("Unauthorized edit user attempt: " . ($_SESSION['email'] ?? 'unknown'));
    header('Location: index.php');
    exit;
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; 
    script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net/; 
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net/;
    font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com;
    img-src 'self' data: https:;
    connect-src 'self';");
header("Referrer-Policy: strict-origin-only");

// Include database connection
include 'dbconnect.php';

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
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $user_type = filter_var(trim($_POST['user_type']), FILTER_SANITIZE_STRING);
    
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
        
        // Update user data with prepared statement
        $updateSql = "UPDATE tbl_users SET name = ?, email = ?, user_type = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$name, $email, $user_type, $id]);
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $passwordSql = "UPDATE tbl_users SET password = ? WHERE id = ?";
            $passwordStmt = $conn->prepare($passwordSql);
            $passwordStmt->execute([$password, $id]);
        }
        
        $conn->commit();
        
        // Log successful update
        error_log("User {$id} updated successfully by admin: " . $_SESSION['email']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error updating user: " . $e->getMessage());
        die("An error occurred. Please try again later.");
    }
}
?>
