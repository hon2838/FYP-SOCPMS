<?php
include 'dbconnect.php';
// Initialize variables
$email = $password = '';
$email_err = $password_err = '';
// Process login form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Validate email
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
        $stmt->bindParam(1, $param_email);
        // Set parameters
        $param_email = $email;
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
          // Check if email exists, if yes then verify password
          if ($stmt->rowCount() > 0) {
              // Fetch result
              $row = $stmt->fetch(PDO::FETCH_ASSOC);
              $id = $row['id'];
              $fetched_email = $row['email'];
              $hashed_password = $row['password'];
              $user_type = $row['user_type'];
              
              // Debugging: Print fetched data
              var_dump($row);

              if (password_verify($password, $hashed_password)) {
                  // Password is correct, start a new session
                  session_start();
                  // Store data in session variables
                  $_SESSION['loggedin'] = true;
                  $_SESSION['id'] = $id;
                  $_SESSION['email'] = $fetched_email;
                  $_SESSION['user_type'] = $user['user_type'];
                  $_SESSION['user_type'] = $user_type;
                  // Redirect user based on usertype
                  if ($user_type == 'admin') {
                      header('location: admin_dashboard.php');
                  } else if ($user_type == 'user') {
                      header('location: user_dashboard.php');
                  } else {
                    echo '<script>alert("Invalid User Type: ' . htmlspecialchars($user_type) . '")</script>';
                  }
                  
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
          $stmt = null;
      } else {
          echo 'Oops! Something went wrong with the SQL statement. Please try again later.';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-file-alt text-primary me-2"></i>
                <span class="fw-bold">SOC Paperwork System</span>
            </a>
        </div>
    </nav>

    <!-- Login Section -->
    <div class="d-lg-flex min-vh-100">
        <div class="bg order-1 order-md-2 d-none d-md-block w-50" 
             style="background-image: url('login-bg.png'); background-size: cover; background-position: center;">
        </div>
        <div class="contents order-2 order-md-1 w-50">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4 p-md-5">
                                <h3 class="fw-bold mb-2">Welcome Back</h3>
                                <p class="text-muted mb-4">Please log in to your account</p>
                                
                                <form action="index.php" method="post" class="needs-validation" novalidate>
                                    <div class="mb-4">
                                        <label for="email" class="form-label fw-medium">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-envelope text-muted"></i>
                                            </span>
                                            <input type="email" 
                                                   class="form-control form-control-lg border-start-0 ps-0" 
                                                   id="email" 
                                                   name="email" 
                                                   placeholder="Enter your email"
                                                   required>
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid email address.</div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label fw-medium">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control form-control-lg border-start-0 ps-0" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Enter your password"
                                                   required>
                                        </div>
                                        <div class="invalid-feedback">Please enter your password.</div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Sign In
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-4">
                                    <p class="text-muted mb-0">
                                        Don't have an account? 
                                        <a href="register.php" class="text-primary fw-medium">Register</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Form Validation Script -->
    <script>
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>