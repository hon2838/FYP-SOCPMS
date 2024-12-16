<?php
require_once 'telegram/telegram_handlers.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: strict-origin-when-cross-origin");

// Rate limiting with notifications
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 1;
    $_SESSION['register_time'] = time();
} else {
    if (time() - $_SESSION['register_time'] < 300) { // 5 minute window
        if ($_SESSION['register_attempts'] > 3) {
            // Log and notify about rate limit breach
            error_log("Registration rate limit exceeded from IP: " . $_SERVER['REMOTE_ADDR']);
            notifySystemError(
                'Rate Limit Exceeded',
                "Multiple registration attempts from IP: {$_SERVER['REMOTE_ADDR']}",
                __FILE__,
                __LINE__
            );
            die("Too many registration attempts. Please try again later.");
        }
        $_SESSION['register_attempts']++;
    } else {
        $_SESSION['register_attempts'] = 1;
        $_SESSION['register_time'] = time();
    }
}

include 'dbconnect.php'; // Adjust the path if necessary

// Initialize variables for form validation
$name = $password = $confirmPassword = $email = '';
$name_err = $password_err = $confirmPassword_err = '';

// Input validation function
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Password strength validation
function validatePassword($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!preg_match("/[a-z]/", $password)) {
        return "Password must contain at least one lowercase letter";
    }
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least one number";
    }
    if (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $password)) {
        return "Password must contain at least one special character";
    }
    return "";
}

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            // Notify about duplicate registration attempt
            notifySystemError(
                'Registration Attempt',
                "Duplicate registration attempt for email: $email\nIP: {$_SERVER['REMOTE_ADDR']}",
                __FILE__,
                __LINE__
            );
            throw new Exception("Email already exists");
        }
        
        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO tbl_users (name, email, password, user_type) VALUES (?, ?, ?, 'user')");
        
        if ($stmt->execute([$name, $email, $hashedPassword])) {
            // Notify admin about successful registration
            notifySystemError(
                'New User Registration',
                "New user registered:\nName: $name\nEmail: $email\nIP: {$_SERVER['REMOTE_ADDR']}\nTime: " . date('Y-m-d H:i:s'),
                __FILE__,
                __LINE__
            );
            
            header("Location: index.php");
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        // Notify admin about registration error
        notifySystemError(
            'Registration Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        die("An error occurred during registration. Please try again later.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOCPMS - Register</title>
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

    <!-- Registration Section -->
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
                                <h3 class="fw-bold mb-2">Create Account</h3>
                                <p class="text-muted mb-4">Join our paperwork management system</p>

                                <form action="register.php" method="post" class="needs-validation" novalidate>
                                    <!-- Name Field -->
                                    <div class="mb-4">
                                        <label for="name" class="form-label fw-medium">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-muted"></i>
                                            </span>
                                            <input type="text" 
                                                class="form-control form-control-lg border-start-0 ps-0 <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" 
                                                id="name" 
                                                name="name" 
                                                value="<?php echo $name; ?>"
                                                placeholder="Enter your full name"
                                                required>
                                            <div class="invalid-feedback"><?php echo $name_err; ?></div>
                                        </div>
                                    </div>

                                    <!-- Email Field -->
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
                                                value="<?php echo $email; ?>"
                                                placeholder="Enter your email"
                                                required>
                                        </div>
                                    </div>

                                    <!-- Password Field -->
                                    <div class="mb-4">
                                        <label for="password" class="form-label fw-medium">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" 
                                                class="form-control form-control-lg border-start-0 ps-0 <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                                id="password" 
                                                name="password"
                                                placeholder="Create a password"
                                                required>
                                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                                        </div>
                                    </div>

                                    <!-- Confirm Password Field -->
                                    <div class="mb-4">
                                        <label for="confirmPassword" class="form-label fw-medium">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" 
                                                class="form-control form-control-lg border-start-0 ps-0 <?php echo (!empty($confirmPassword_err)) ? 'is-invalid' : ''; ?>" 
                                                id="confirmPassword" 
                                                name="confirmPassword"
                                                placeholder="Confirm your password"
                                                required>
                                            <div class="invalid-feedback"><?php echo $confirmPassword_err; ?></div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Register Account
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-4">
                                    <p class="text-muted mb-0">
                                        Already have an account? 
                                        <a href="index.php" class="text-primary fw-medium">Sign In</a>
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
</body>
</html>
