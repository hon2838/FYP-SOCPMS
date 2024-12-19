<?php
class PermissionManager {
    private $conn;
    private $user_id;
    private $permissions = [];
    private $roles = [];

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loadUserPermissions();
        $this->loadUserRoles();
    }

    private function loadUserPermissions() {
        try {
            $sql = "SELECT DISTINCT p.permission_name 
                    FROM tbl_permissions p 
                    JOIN tbl_role_permissions rp ON p.permission_id = rp.permission_id 
                    JOIN tbl_user_roles ur ON rp.role_id = ur.role_id 
                    WHERE ur.user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->user_id]);
            $this->permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Loaded permissions for user {$this->user_id}: " . implode(', ', $this->permissions));
        } catch (Exception $e) {
            error_log("Error loading permissions: " . $e->getMessage());
            throw $e;
        }
    }

    private function loadUserRoles() {
        $sql = "SELECT r.role_name 
                FROM tbl_roles r 
                JOIN tbl_user_roles ur ON r.role_id = ur.role_id 
                WHERE ur.user_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$this->user_id]);
        $this->roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function hasPermission($permission) {
        return in_array($permission, $this->permissions);
    }

    public function hasRole($role) {
        return in_array($role, $this->roles);
    }

    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            error_log("Permission denied: User {$this->user_id} attempted to access {$permission}");
            die("Access denied: Insufficient permissions");
        }
    }

    public function logPermissionCheck($permission, $granted) {
        error_log(
            sprintf(
                "Permission check: User %d, Permission %s, Granted %s",
                $this->user_id,
                $permission,
                $granted ? 'Yes' : 'No'
            )
        );
    }
}