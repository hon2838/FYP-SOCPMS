<?php
include 'dbconnect.php';
// Initialize variables
$email = $password = '';
$email_err = $password_err = '';

// Process login form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Validate username
  if (empty(trim($_POST['email']))) {
      $email_err = 'Please enter email.';
  } else {
      $email = trim($_POST['email']);
  }

  // Validate password
  if (empty(trim($_POST['password']))) {
      $password_err = 'Please enter your password.';
  } else {
      $password = trim($_POST['password']);
  }

  // Check input errors before processing the database query
  if (empty($email_err) && empty($password_err)) {
    // Prepare a select statement
    $sql = "SELECT id, email, password, user_type FROM tbl_users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(1, $param_email); // Use bindParam for PDO and change to $param_email
        // Set parameters
        $param_email = $email; // Use $email, not $username
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
          // Check if email exists, if yes then verify password
          if ($stmt->rowCount() > 0) {
              // Fetch result
              $row = $stmt->fetch(PDO::FETCH_ASSOC);
              $id = $row['id'];
              $fetched_email = $row['email'];
              $hashed_password = $row['password'];
              $usertype = $row['user_type'];
              
              if (password_verify($password, $hashed_password)) {
                  // Password is correct, start a new session
                  session_start();
                  // Store data in session variables
                  $_SESSION['loggedin'] = true;
                  $_SESSION['id'] = $id;
                  $_SESSION['email'] = $fetched_email;
                  $_SESSION['user_type'] = $user_type;
                  // Redirect user based on usertype
                  if ($user_type == 'admin') {
                      header('location: admin_dashboard.php');
                  } else 
                      header('location: user_dashboard.php');
                  
              } else {
                  // Display an error message if password is not valid
                  $password_err = 'Invalid password.';
                  echo '<script>alert("Invalid password.")</script>';
              }
          } else {
              // Display an error message if email doesn't exist
              $email_err = 'No account found with that email.';
              echo '<script>alert("No account found with that email.")</script>';
          }
      } else {
          echo 'Oops! Something went wrong. Please try again later.';
      }
          // Close statement
          $conn = null;
      }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOCPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
  </head>

  <body>
    
  <body>
    <div class="d-lg-flex half">
      <div class="bg order-1 order-md-2" style="background-image: url('login-bg.png');"></div>
      <div class="contents order-2 order-md-1">
        <div class="container">
          <div class="row align-items-center justify-content-center">
            <div class="col-md-7">
              <h3>Login to <strong>SOC-PMS</strong></h3>
              <p class="mb-4">Login with your email and password into School of Computing Paperwork Management System.</p>
              <form action="index.php" method="post">
                <div class="form-group first">
                  <label for="email">Email</label>
                  <input type="text" class="form-control" placeholder="your-email@gmail.com" id="email" name="email">
                </div>
                <div class="form-group last mb-3">
                  <label for="password">Password</label>
                  <input type="password" class="form-control" placeholder="Your Password" id="password" name="password">
                </div>
                <div class="d-flex mb-5 align-items-center">
                  <label class="control control--checkbox mb-0">
                    <span class="caption">Remember me</span>
                    <input type="checkbox" checked="checked" />
                    <div class="control__indicator"></div>
                  </label>
                  <span class="ml-auto"><a href="#" class="forgot-pass">Forgot Password</a></span>
                </div>
                <input type="submit" value="Log In" class="btn btn-block btn-primary">
              </form>
              <span class="d-block text-left my-4 text-muted">Not registered? <a href="register.php">Create an account</a></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>
