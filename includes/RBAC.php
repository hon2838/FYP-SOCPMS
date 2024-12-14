<?php
class RBAC {
    private $conn;
    private static $permissionCache = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    private function cachePermissions($userId) {
        if (!isset(self::$permissionCache[$userId])) {
            $stmt = $this->conn->prepare("
                SELECT p.permission_name
                FROM tbl_users u
                JOIN role_permissions rp ON u.role_id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.permission_id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            self::$permissionCache[$userId] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return self::$permissionCache[$userId];
    }
    
    public function hasPermission($userId, $permissionName) {
        $permissions = $this->cachePermissions($userId);
        return in_array($permissionName, $permissions);
    }
    
    public function getUserPermissions($userId) {
        $stmt = $this->conn->prepare("
            SELECT p.permission_name
            FROM tbl_users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function checkPermission($permissionName) {
        // Skip permission check for superadmin
        $stmt = $this->conn->prepare("
            SELECT r.role_name 
            FROM tbl_users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['id']]);
        $roleName = $stmt->fetchColumn();

        if ($roleName === 'super_admin') {
            return true;
        }

        return $this->hasPermission($_SESSION['id'], $permissionName);
    }
    
    public function clearCache($userId = null) {
        if ($userId === null) {
            self::$permissionCache = [];
        } else {
            unset(self::$permissionCache[$userId]);
        }
    }
}