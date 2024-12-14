<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Log the logout attempt
if (isset($_SESSION['email'])) {
    error_log("User logout: " . $_SESSION['email']);
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        true,
        true
    );
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Destroy the session
session_destroy();

// Clear any sensitive cookies
setcookie('user_id', '', time() - 3600, '/');
setcookie('remember_me', '', time() - 3600, '/');

// Prevent caching of this page
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");

// Redirect to login page
header("Location: index.php");
exit;
?>
