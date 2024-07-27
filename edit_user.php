<?php
session_start();
if (!(isset($_SESSION['email']) && $_SESSION['user_type'] == 'admin')) {
    header('Location: index.php');
    exit;
}

// Include database connection
include 'dbconnect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $id = trim($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $user_type = trim($_POST['user_type']);

    // Only update the password if a new password is provided
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE tbl_users SET name = :name, email = :email, password = :password, user_type = :user_type WHERE id = :id";
    } else {
        $sql = "UPDATE tbl_users SET name = :name, email = :email, user_type = :user_type WHERE id = :id";
    }

    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
    if (!empty($_POST['password'])) {
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    }

    // Execute and redirect
    if ($stmt->execute()) {
        header("Location: admin_manage_account.php");
        exit();
    } else {
        echo "Error updating user.";
    }
}
?>
