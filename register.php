<?php
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

// Rate limiting for registration attempts
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 1;
    $_SESSION['register_time'] = time();
} else {
    if (time() - $_SESSION['register_time'] < 300) { // 5 minute window
        if ($_SESSION['register_attempts'] > 3) { // Max 3 registration attempts per 5 minutes
            error_log("Registration rate limit exceeded from IP: " . $_SERVER['REMOTE_ADDR']);
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

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a name.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM tbl_users WHERE name = :name";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters to the prepared statement
            $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);

            // Set parameters
            $param_name = trim($_POST["name"]);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if name already exists
                if ($stmt->rowCount() == 1) {
                    $name_err = "This name is already taken.";
                } else {
                    $name = trim($_POST["name"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = "Please enter a password.";
        echo $password_err;
    } else {
        $password_err = validatePassword(trim($_POST['password']));
        if (empty($password_err)) {
            $password = trim($_POST['password']);
        } else {
            echo $password_err;
        }
    }

    // Validate confirm password
    if (empty(trim($_POST["confirmPassword"]))) {
        $confirmPassword_err = "Please confirm password.";
    } else {
        $confirmPassword = trim($_POST['confirmPassword']);
        if (empty($password_err) && ($password != $confirmPassword)) {
            $confirmPassword_err = "Password did not match.";
        }
    }

    // Check input errors before inserting into database
    $sql = "INSERT INTO tbl_users (name,email,password) VALUES (:name,:email, :password)";
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters to the prepared statement
        $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);
        $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
        $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);

        // Set parameters
        $param_name = $name;
        $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
        $param_email = trim($_POST['email']); // Ensure you have this line to set $email

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect to login page
            header("location: index.php");
            exit;
        } else {
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        unset($stmt);
    }

    // Close connection
    unset($conn);
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
