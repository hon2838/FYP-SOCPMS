<?php
// add_user.php
require 'dbconnect.php'; // Make sure you have this file for database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $user_type = $_POST['usertype'];

    // Prepare SQL statement
    $sql = "INSERT INTO tbl_users (name, email, password, usertype) VALUES (:name, :email, :password, :usertype)";
    $stmt = $conn->prepare($sql); // Changed $pdo to $conn
    // Bind parameters
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':usertype', $user_type, PDO::PARAM_STR); // Corrected parameter name to match SQL statement
    // Execute and redirect
    if ($stmt->execute()) {
        // Redirect back to the admin_manage_account.php or to a success page
        header("Location: admin_manage_account.php");
        exit();
    } else {
        echo "Error adding user.";
    }
}
?>