<?php
function hasPermission($userId, $permissionName) {
    global $conn;
    
    $sql = "SELECT COUNT(*) FROM tbl_users u
            JOIN tbl_role_permissions rp ON u.role_id = rp.role_id
            JOIN tbl_permissions p ON rp.permission_id = p.permission_id
            WHERE u.id = ? AND p.permission_name = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $permissionName]);
    return $stmt->fetchColumn() > 0;
}

function getUserPermissions($userId) {
    global $conn;
    
    $sql = "SELECT p.permission_name FROM tbl_users u
            JOIN tbl_role_permissions rp ON u.role_id = rp.role_id
            JOIN tbl_permissions p ON rp.permission_id = p.permission_id
            WHERE u.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}