<?php
include 'dbconnect.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; 
    script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net/; 
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net/;
    font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com;
    img-src 'self' data: https:;
    connect-src 'self';");
header("Referrer-Policy: strict-origin-only");

// Rate limiting for login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 1;
    $_SESSION['first_attempt'] = time();
} else {
    if (time() - $_SESSION['first_attempt'] < 300) { // 5 minute window
        if ($_SESSION['login_attempts'] > 5) { // Max 5 attempts per 5 minutes
            error_log("Login rate limit exceeded for IP: " . $_SERVER['REMOTE_ADDR']);
            die("Too many login attempts. Please try again later.");
        }
        $_SESSION['login_attempts']++;
    } else {
        $_SESSION['login_attempts'] = 1;
        $_SESSION['first_attempt'] = time();
    }
}

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
        // Prepare a select statement with enhanced security
        $sql = "SELECT id, email, password, name, user_type, active FROM tbl_users WHERE email = ? AND active = 1";
        if ($stmt = $conn->prepare($sql)) {
            // Validate and sanitize email
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email format attempt: " . $email);
                die("Invalid email format");
            }

            // Bind parameters using proper type
            $stmt->bindParam(1, $email, PDO::PARAM_STR);

            try {
                // Execute with timing attack protection
                if ($stmt->execute()) {
                    if ($stmt->rowCount() === 1) {
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);

                        // Verify password with timing attack protection
                        if (hash_equals(hash('sha256', $row['password']), hash('sha256', $_POST['password']))) {
                            if ($row['active'] == 1) {
                                // Start new session and regenerate ID
                                session_regenerate_id(true);

                                // Set session variables
                                $_SESSION['loggedin'] = true;
                                $_SESSION['id'] = $row['id'];
                                $_SESSION['email'] = $row['email'];
                                $_SESSION['user_type'] = $row['user_type'];
                                $_SESSION['name'] = $row['name'];
                                $_SESSION['last_activity'] = time();

                                // Log successful login
                                error_log("Successful login: " . $row['email']);

                                // Clear login attempts on success
                                unset($_SESSION['login_attempts']);
                                unset($_SESSION['first_attempt']);

                                // Redirect based on user type
                                header('Location: ' . ($row['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
                                exit;
                            } else {
                                error_log("Login attempt on inactive account: " . $row['email']);
                                die("Account is inactive");
                            }
                        } else {
                            // Log failed attempt
                            error_log("Failed login attempt for email: " . $email);
                            sleep(1); // Prevent brute force
                            $password_err = "Invalid password";
                        }
                    } else {
                        error_log("Login attempt with non-existent email: " . $email);
                        sleep(1); // Prevent brute force
                        $email_err = "Email not found";
                    }
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                die("An error occurred. Please try again later.");
            }

            // Close statement
            $stmt = null;
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
    <?php include 'includes/footer.php'; ?>
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