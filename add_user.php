<?php
session_start();
if (!(isset($_SESSION['email']) && $_SESSION['user_type'] == 'admin')) {
    header('Location: index.php');
    exit;
}

// Add after session validation
if (!$rbac->checkPermission('manage_users')) {
    error_log("Unauthorized user creation attempt: " . $_SESSION['email']);
    header('Location: index.php');
    exit;
}

// Include database connection
include 'dbconnect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $user_type = $_POST['user_type'];

    // Validate role_id
    $role_id = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);
    if (!$role_id) {
        throw new Exception("Invalid role selected");
    }

    // Prepare SQL statement
    $sql = "INSERT INTO tbl_users (name, email, password, role_id) VALUES (:name, :email, :password, :role_id)";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);

    // Execute and redirect
    if ($stmt->execute()) {
        header("Location: admin_manage_account.php");
        exit();
    } else {
        echo "Error adding user.";
    }
}
?>
