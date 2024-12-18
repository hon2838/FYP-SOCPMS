<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'user')) {
    error_log("Unauthorized account management access attempt: " . ($_SESSION['email'] ?? 'unknown'));
    session_destroy();
    header('Location: index.php');
    exit;
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: strict-origin-when-cross-origin");

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Session fixation prevention
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Rate limiting for account management actions
if (!isset($_SESSION['manage_attempts'])) {
    $_SESSION['manage_attempts'] = 1;
    $_SESSION['manage_time'] = time();
} else {
    if (time() - $_SESSION['manage_time'] < 300) { // 5 minute window
        if ($_SESSION['manage_attempts'] > 10) { // Max 10 attempts per 5 minutes
            error_log("Account management rate limit exceeded for user: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many requests. Please try again later.");
        }
        $_SESSION['manage_attempts']++;
    } else {
        $_SESSION['manage_attempts'] = 1;
        $_SESSION['manage_time'] = time();
    }
}

// Include database connection
include 'dbconnect.php';
include 'includes/header.php';

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        throw new Exception("User not found");
    }

    // Get user settings
    $settings = json_decode($row['settings'] ?? '{}', true) ?: [
        'theme' => 'light',
        'email_notifications' => true,
        'browser_notifications' => false,
        'compact_view' => false
    ];
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

try {
    // Sanitize and validate email
    $email = filter_var($_SESSION['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Delete account functionality with validation
    if (isset($_GET['submit']) && $_GET['submit'] === 'delete') {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception("Invalid user ID");
        }

        // Verify user owns this account
        $verifyStmt = $conn->prepare("SELECT id FROM tbl_users WHERE id = ? AND email = ? AND user_type = 'user'");
        $verifyStmt->execute([$id, $email]);
        
        if ($verifyStmt->rowCount() === 0) {
            error_log("Unauthorized deletion attempt by user: {$email} for ID: {$id}");
            throw new Exception("Unauthorized action");
        }

        $deleteStmt = $conn->prepare("DELETE FROM tbl_users WHERE id = ? AND email = ?");
        if (!$deleteStmt->execute([$id, $email])) {
            throw new Exception("Failed to delete account");
        }
        
        // Log successful deletion
        error_log("User account deleted successfully: {$email}");
        session_destroy();
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error in user_manage_account.php: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Profile update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_profile') {
            // Validate inputs
            $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            
            // Get current user data
            $stmt = $conn->prepare("SELECT password FROM tbl_users WHERE email = ?");
            $stmt->execute([$_SESSION['email']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update name
            $updateFields = ["name = ?"]; 
            $params = [$name];

            // Update password if provided
            if ($currentPassword && $newPassword) {
                if (!password_verify($currentPassword, $userData['password'])) {
                    throw new Exception("Current password is incorrect");
                }
                
                // Validate new password
                if (strlen($newPassword) < 8) {
                    throw new Exception("Password must be at least 8 characters");
                }
                
                $updateFields[] = "password = ?";
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            // Update preferences
            $settings = [
                'theme' => $_POST['theme'],
                'email_notifications' => isset($_POST['email_notifications']),
            ];
            $updateFields[] = "settings = ?";
            $params[] = json_encode($settings);

            // Add WHERE clause parameter
            $params[] = $_SESSION['email'];

            // Construct and execute update query
            $sql = "UPDATE tbl_users SET " . implode(", ", $updateFields) . " WHERE email = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt->execute($params)) {
                throw new Exception("Failed to update profile");
            }

            // Update session data
            $_SESSION['name'] = $name;
            $_SESSION['settings'] = $settings;

            // Show success message
            echo "<script>alert('Profile updated successfully');</script>";
        }
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

    $results_per_page = 10;
    $pageno = isset($_GET['pageno']) ? (int)$_GET['pageno'] : 1;
    $page_first_result = ($pageno - 1) * $results_per_page;

    if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
        $search_query = $_GET['search_query'];
        $search_option = $_GET['search_option'];

        if ($search_option == 'name') {
            $sqlloadpatients = "SELECT * FROM tbl_users WHERE name LIKE ?";
            $stmt = $conn->prepare($sqlloadpatients);
            $stmt->execute(['%'.$search_query.'%']);
        } else if ($search_option == 'email') {
            $sqlloadpatients = "SELECT * FROM tbl_users WHERE email LIKE ?";
            $stmt = $conn->prepare($sqlloadpatients);
            $stmt->execute(['%'.$search_query.'%']);
        }

        $number_of_results = $stmt->rowCount();
        if ($number_of_results == 0) {
            echo "<script>alert('No results found.');</script>";
            echo "<script>window.location.href='main.php';</script>";
        }
    } else {
        $sqlloadpatients = "SELECT * FROM tbl_users WHERE email = ?";
        $stmt = $conn->prepare($sqlloadpatients);
        $stmt->execute([$email]);
        $number_of_results = $stmt->rowCount();
    }

    $number_of_pages = ceil($number_of_results / $results_per_page);

    $sqlloadpatients = $sqlloadpatients . " LIMIT " . $page_first_result . ',' . $results_per_page;
    $stmt = $conn->prepare($sqlloadpatients);
    if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
        $stmt->execute(['%'.$search_query.'%']);
    } else {
        $stmt->execute([$email]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOC Paperwork Management System</title>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">
 <!-- Main Content with top margin to account for fixed navbar -->
    <main class="pt-5 mt-5">
        <!-- Welcome Section -->
        <div class="container py-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title h4 mb-3">
                        <i class="fas fa-users-cog text-primary me-2"></i>
                        Manage Your Account
                    </h2>
                    <p class="card-text text-muted mb-0">
                        View and manage your account details here.
                    </p>
                </div>
            </div>
        </div>

        <!-- Account Table Section -->
        <div class="container">
            <div class="row">
                <!-- Profile Information Card -->
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-circle text-primary me-2"></i>
                                Profile Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form id="profileForm" method="POST" action="user_manage_account.php">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <!-- Personal Information -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">Personal Details</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="name" 
                                                value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" 
                                                value="<?php echo htmlspecialchars($row['email']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Settings -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">Security</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- About Modal -->
    <div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1Title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        About Us
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-muted mb-0">School of Computing Paperwork Management System is a web application that helps you to manage your paperworks.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit_user.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden input to store user ID -->
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-3">
                        <label for="editUserName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editUserName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editUserEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="editUserPassword" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
  document.querySelectorAll('.editUserBtn').forEach(item => {
      item.addEventListener('click', event => {
          const userId = item.getAttribute('data-id');
          const userName = item.getAttribute('data-name');
          const userEmail = item.getAttribute('data-email');
          const userType = item.getAttribute('data-usertype');

          document.getElementById('editUserId').value = userId;
          document.getElementById('editUserName').value = userName;
          document.getElementById('editUserEmail').value = userEmail;
          document.getElementById('editUserType').value = userType;
      });
  });

  document.querySelectorAll('.togglePasswordBtn').forEach(item => {
      item.addEventListener('click', event => {
          const passwordInput = item.previousElementSibling;
          if (passwordInput.type === 'password') {
              passwordInput.type = 'text';
              item.textContent = 'Hide';
          } else {
              passwordInput.type = 'password';
              item.textContent = 'Show';
          }
      });
  });

document.getElementById('profileForm').addEventListener('submit', function(e) {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const currentPassword = document.querySelector('input[name="current_password"]').value;

    if (newPassword && !currentPassword) {
        e.preventDefault();
        alert('Please enter your current password to change password');
    }

    if (newPassword && newPassword.length < 8) {
        e.preventDefault();
        alert('New password must be at least 8 characters long');
    }
});

// Theme switcher
document.querySelector('select[name="theme"]').addEventListener('change', function() {
    document.body.classList.toggle('dark-theme', this.value === 'dark');
});

// Add this to your existing JavaScript
document.getElementById('preferencesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('update_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Apply theme change immediately
            document.body.classList.toggle('dark-theme', data.theme === 'dark');
            alert('Preferences saved successfully');
        } else {
            throw new Error('Failed to save preferences');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save preferences. Please try again.');
    });
});

// Add password validation
document.querySelectorAll('input[name="new_password"]').forEach(input => {
    input.addEventListener('input', function() {
        const password = this.value;
        let isValid = true;
        let message = [];

        if (password.length < 8) {
            isValid = false;
            message.push('Password must be at least 8 characters');
        }
        if (!/[A-Z]/.test(password)) {
            isValid = false;
            message.push('Include at least one uppercase letter');
        }
        if (!/[0-9]/.test(password)) {
            isValid = false;
            message.push('Include at least one number');
        }

        this.setCustomValidity(isValid ? '' : message.join('\n'));
    });
});

</script>
</body>
</html>
