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
header("Content-Security-Policy: default-src 'self'; 
    script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net/; 
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net/;
    font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com;
    img-src 'self' data: https:;
    connect-src 'self';");
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            Account Details
                        </h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Staff ID</th>
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Email</th>
                                    <th scope="col" class="px-4 py-3">User Type</th>
                                    <th scope="col" class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row) { ?>
                                    <tr>
                                        <td class="px-4"><?php echo $row['id']; ?></td>
                                        <td class="px-4"><?php echo $row['name']; ?></td>
                                        <td class="px-4"><?php echo $row['email']; ?></td>
                                        <td class="px-4"><?php echo $row['user_type']; ?></td>
                                        <td class="px-4">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
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
</script>
</body>
</html>
