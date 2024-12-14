<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/ErrorCodes.php';
require_once 'includes/ErrorHandler.php';
require_once 'dbconnect.php';
require_once 'includes/RBAC.php';

$errorHandler = ErrorHandler::getInstance();
$rbac = new RBAC($conn);

try {
    if (!$rbac->checkPermission('manage_users')) {
        throw new Exception("Permission denied", ErrorCodes::PERMISSION_DENIED);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method", ErrorCodes::INPUT_INVALID_FORMAT);
    }

    $role_id = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);
    if (!$role_id) {
        throw new Exception("Invalid role ID", ErrorCodes::INPUT_INVALID_FORMAT);
    }

    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Start transaction
    $conn->beginTransaction();

    // Remove existing permissions
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);

    // Add new permissions
    if (!empty($permissions)) {
        $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permissions as $permission_id) {
            if (filter_var($permission_id, FILTER_VALIDATE_INT)) {
                $stmt->execute([$role_id, $permission_id]);
            }
        }
    }

    $conn->commit();
    
    // Clear RBAC cache for affected users
    $userStmt = $conn->prepare("SELECT id FROM tbl_users WHERE role_id = ?");
    $userStmt->execute([$role_id]);
    while ($user = $userStmt->fetch()) {
        $rbac->clearCache($user['id']);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo $errorHandler->handleError($e);
}