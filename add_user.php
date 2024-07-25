<?php
    session_start();
    if (!(isset($_SESSION['email']) && $_SESSION['user_type'] != 'admin')) {
      header('Location: index.php');
      exit;
    }
  
    // Include database connection
    include 'dbconnect.php';
  
    // Get user type based on email from database
    $email = $_SESSION['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $user_type = $_POST['user_type'];

    // Prepare SQL statement
    $sql = "INSERT INTO tbl_users (name, email, password, user_type) VALUES (:name, :email, :password, :user_type)";
    $stmt = $conn->prepare($sql); // Changed $pdo to $conn
    // Bind parameters
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR); // Corrected parameter name to match SQL statement
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