<?php
// Start session and enforce HTTPS
session_start();
// Strict session validation
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header('Location: index.php');
    exit;
}

// Validate admin access
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit; 
}

// Include database connection with PDO security options
include 'dbconnect.php';
include 'includes/header.php';

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

// Rate limiting
$rate_limit_minutes = 5;
$max_attempts = 10;
$current_time = time();

// Check for rate limiting using sessions
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 1;
    $_SESSION['first_attempt'] = $current_time;
} else if ($_SESSION['login_attempts'] >= $max_attempts) {
    if ($current_time - $_SESSION['first_attempt'] < $rate_limit_minutes * 60) {
        die("Too many requests. Please try again later.");
    } else {
        $_SESSION['login_attempts'] = 1;
        $_SESSION['first_attempt'] = $current_time;
    }
} else {
    $_SESSION['login_attempts']++;
}

// Get user type with prepared statement and input validation
$email = filter_var($_SESSION['email'], FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format");
}

try {
    $userQuery = "SELECT user_type FROM tbl_users WHERE email = ? AND active = 1";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([$email]);
    
    if ($userStmt->rowCount() === 0) {
        // Log potential security breach
        error_log("Failed admin access attempt from email: " . $email);
        header('Location: index.php');
        exit;
    }
    
    $userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Double check user type
    if ($userResult['user_type'] !== 'admin') {
        // Log unauthorized access attempt
        error_log("Unauthorized admin access attempt from email: " . $email);
        header('Location: index.php');
        exit;
    }

} catch (PDOException $e) {
    // Log error securely
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Set session timeout after 30 minutes of inactivity
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

    $sqlloadpatients = "SELECT * FROM tbl_users";
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();
    $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows=$stmt->fetchAll();

    if (isset($_GET['submit']) && $_GET['submit'] == 'delete') {
      $id = $_GET['id'];
      try {
        $sqldeletepatient = "DELETE FROM tbl_users WHERE id = ?";
        $stmt = $conn->prepare($sqldeletepatient);
        $stmt->execute([$id]);
        echo "<script>alert('User deleted successfully.');</script>";
        echo "<script>window.location.href='admin_manage_account.php';</script>";
      } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
      }
  }

    if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
      $search_query = $_GET['search_query'];
      $search_option = $_GET['search_option'];

      if ($search_option == 'name') {
        $sqlloadpatients = "SELECT * FROM tbl_users WHERE name LIKE ?";
        $stmt = $conn->prepare($sqlloadpatients);
        $stmt->execute(['%'.$search_query.'%']);
        $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $rows=$stmt->fetchAll();
      } else if ($search_option == 'email') {
        $sqlloadpatients = "SELECT * FROM tbl_users WHERE email LIKE ?";
        $stmt = $conn->prepare($sqlloadpatients);
        $stmt->execute(['%'.$search_query.'%']);
        $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $rows=$stmt->fetchAll();
      }

      if (count($rows) == 0) {
        echo "<script>alert('No results found.');</script>";
        echo "<script>window.location.href='main.php';</script>";
      }
    }

    $results_per_pages = 10;
    if (isset($_GET['pageno'])) {
        $pageno = (int)$_GET['pageno'];
        $page_first_result = ($pageno - 1) * $results_per_pages;
    } else {
        $pageno = 1;
        $page_first_result = 0;
    } 
    
    
    
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();
    
    $number_of_results = $stmt->rowCount();
    $number_of_pages = ceil($number_of_results / $results_per_pages);
    $sqlloadpatients = $sqlloadpatients . " LIMIT " . $page_first_result . ',' . $results_per_pages;
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();

    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows=$stmt->fetchAll();
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
                        Manage Accounts
                    </h2>
                    <p class="card-text text-muted mb-0">
                        Manage and control user accounts in the system.
                    </p>
                </div>
            </div>
        </div>

        <!-- Users Table Section -->
        <div class="container">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            User Accounts
                        </h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>
                            Add New User
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Staff ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">User Type</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <th scope="row"><?php echo $row['id']; ?></th>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['user_type']; ?></td>
                                    <td>
                                        <a href="admin_manage_account.php?submit=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                                        <button type="button" class="btn btn-primary editUserBtn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>" data-email="<?php echo $row['email']; ?>" data-user_type="<?php echo $row['user_type']; ?>">Edit</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-center">
            <?php
              for ($page=1;$page<=$number_of_pages;$page++) {
                echo '<a href="main.php?pageno=' . $page . '" class="btn btn-primary">' . $page . '</a>';
              }
            ?>
        </div>
    </main>

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

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="edit_user.php" method="post">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-4">
                        <label for="editUserName" class="form-label fw-medium">Name</label>
                        <input type="text" class="form-control form-control-lg shadow-sm" id="editUserName" name="name" required>
                    </div>
                    <div class="mb-4">
                        <label for="editUserEmail" class="form-label fw-medium">Email</label>
                        <input type="email" class="form-control form-control-lg shadow-sm" id="editUserEmail" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="editUserPassword" class="form-label fw-medium">Password</label>
                        <input type="password" class="form-control form-control-lg shadow-sm" id="editUserPassword" name="password">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                    <div class="mb-4">
                        <label for="edituser_type" class="form-label fw-medium">User Type</label>
                        <select class="form-select form-select-lg shadow-sm" id="edituser_type" name="user_type" required>
                            <option value="admin">Admin</option>
                            <option value="user">Normal User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="add_user.php" method="post">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-user-plus text-primary me-2"></i>
                        Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-4">
                        <label for="addUserName" class="form-label fw-medium">Name</label>
                        <input type="text" class="form-control form-control-lg shadow-sm" id="addUserName" name="name" required>
                    </div>
                    <div class="mb-4">
                        <label for="addUserEmail" class="form-label fw-medium">Email</label>
                        <input type="email" class="form-control form-control-lg shadow-sm" id="addUserEmail" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="addUserPassword" class="form-label fw-medium">Password</label>
                        <input type="password" class="form-control form-control-lg shadow-sm" id="addUserPassword" name="password" required>
                    </div>
                    <div class="mb-4">
                        <label for="addUserType" class="form-label fw-medium">User Type</label>
                        <select class="form-select form-select-lg shadow-sm" id="addUserType" name="user_type" required>
                            <option value="">Select user type</option>
                            <option value="admin">System Admin</option>
                            <option value="user">Staff</option>
                            <option value="hod">Head of Department</option>
                            <option value="ceo">CEO</option>
                        </select>
                        <div class="mb-3">
                            <label for="department" class="form-label fw-medium mt-3">Department</label>
                            <select class="form-select form-select-lg shadow-sm" id="department" name="department">
                                <option value="">Select department</option>
                                <option value="computing">School of Computing</option>
                                <option value="business">School of Business</option>
                                <option value="engineering">School of Engineering</option>
                                <!-- Add more departments as needed -->
                            </select>
                            <small class="text-muted">Required for Staff and Head of Department</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    // JavaScript to populate the modal with user data
    document.querySelectorAll('.editUserBtn').forEach(item => {
        item.addEventListener('click', event => {
            const userId = item.getAttribute('data-id');
            const userName = item.getAttribute('data-name');
            const userEmail = item.getAttribute('data-email');
            const user_type = item.getAttribute('data-user_type');

            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').value = userName;
            document.getElementById('editUserEmail').value = userEmail;
            document.getElementById('edituser_type').value = user_type;
        });
    });

    document.getElementById('addUserType').addEventListener('change', function() {
        const departmentField = document.getElementById('department');
        const departmentGroup = departmentField.closest('.mb-3');
        
        if (this.value === 'user' || this.value === 'hod') {
            departmentGroup.style.display = 'block';
            departmentField.required = true;
        } else {
            departmentGroup.style.display = 'none';
            departmentField.required = false;
            departmentField.value = '';
        }
    });

    // For edit modal
    document.getElementById('editUserType').addEventListener('change', function() {
        const departmentField = document.getElementById('editDepartment');
        const departmentGroup = departmentField.closest('.mb-3');
        
        if (this.value === 'user' || this.value === 'hod') {
            departmentGroup.style.display = 'block';
            departmentField.required = true;
        } else {
            departmentGroup.style.display = 'none';
            departmentField.required = false;
            departmentField.value = '';
        }
    });
    </script>
</body>
</html>
