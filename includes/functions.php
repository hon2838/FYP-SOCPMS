<?php
// User permission error handler
function handlePermissionError($permissionName, $redirectUrl = 'index.php') {
    $_SESSION['error_message'] = "Access denied. You don't have permission to perform this action.";
    error_log("Permission denied: {$permissionName} for user {$_SESSION['email']}");
    header('Location: ' . $redirectUrl);
    exit;
}

// Database related functions
if (!function_exists('validateConnection')) {
    function validateConnection($conn) {
        try {
            $conn->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("Connection validation failed: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('closeConnection')) {
    function closeConnection(&$conn) {
        $conn = null;
    }
}
?>