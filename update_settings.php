<?php
session_start();

try {
    if (!$rbac->checkPermission('manage_settings')) {
        throw new Exception("Permission denied", ErrorCodes::PERMISSION_DENIED);
    }
    
    if (!filter_var($_POST['email_notifications'], FILTER_VALIDATE_BOOLEAN)) {
        throw new Exception("Invalid notification setting", ErrorCodes::INPUT_INVALID_FORMAT);
    }
    
    if (!in_array($_POST['theme'], ['light', 'dark', 'system'])) {
        throw new Exception("Invalid theme selection", ErrorCodes::INPUT_INVALID_FORMAT);
    }
    
    header('Content-Type: application/json');

    // Validate and sanitize input
    $settings = [
        'email_notifications' => isset($_POST['email_notifications']),
        'browser_notifications' => isset($_POST['browser_notifications']),
        'theme' => in_array($_POST['theme'], ['light', 'dark', 'system']) ? $_POST['theme'] : 'light',
        'compact_view' => isset($_POST['compact_view'])
    ];

    // Store settings in session
    $_SESSION['settings'] = $settings;

    // Store settings in database
    include 'dbconnect.php';
    $stmt = $conn->prepare("UPDATE tbl_users SET settings = ? WHERE email = ? AND active = 1");
    
    if (!$stmt->execute([json_encode($settings), $_SESSION['email']])) {
        throw new Exception("Failed to update settings");
    }

    echo json_encode([
        'success' => true,
        'theme' => $settings['theme'],
        'settings' => $settings // Include full settings object
    ]);
    
} catch (Exception $e) {
    echo ErrorHandler::getInstance()->handleError($e);
    exit;
}