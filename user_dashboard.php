<?php
require_once 'telegram/telegram_handlers.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation with notification
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'user')) {
    
    $email = $_SESSION['email'] ?? 'unknown';
    error_log("Unauthorized dashboard access attempt: " . $email);
    
    // Notify admin about unauthorized access
    notifySystemError(
        'Unauthorized Access',
        "Unauthorized attempt to access user dashboard\nEmail: $email\nIP: {$_SERVER['REMOTE_ADDR']}",
        __FILE__,
        __LINE__
    );
    
    session_destroy();
    header('Location: index.php');
    exit;
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Session timeout check with notification
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > 1800)) {
    
    // Notify about session timeout
    notifySystemError(
        'Session Timeout',
        "User session timed out\nEmail: {$_SESSION['email']}\nIP: {$_SERVER['REMOTE_ADDR']}",
        __FILE__,
        __LINE__
    );
    
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

// Rate limiting for dashboard access
if (!isset($_SESSION['dashboard_requests'])) {
    $_SESSION['dashboard_requests'] = 1;
    $_SESSION['dashboard_time'] = time();
} else {
    if (time() - $_SESSION['dashboard_time'] < 60) { // 1 minute window
        if ($_SESSION['dashboard_requests'] > 30) { // Max 30 requests per minute
            error_log("Dashboard rate limit exceeded for user: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many requests. Please try again later.");
        }
        $_SESSION['dashboard_requests']++;
    } else {
        $_SESSION['dashboard_requests'] = 1;
        $_SESSION['dashboard_time'] = time();
    }
}

// Include database connection
include 'dbconnect.php';
include 'includes/header.php';

// At the top after database connection
require_once 'includes/PermissionManager.php';
$permManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check permissions for actions
$canCreateSubmission = $permManager->hasPermission('create_submission');
$canEditSubmission = $permManager->hasPermission('edit_submission');
$canDeleteSubmission = $permManager->hasPermission('delete_submission');

try {
    // Sanitize and validate email
    $email = filter_var($_SESSION['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Get user ID with prepared statement
    $userIdQuery = "SELECT id, active FROM tbl_users WHERE email = ? AND user_type = 'user'";
    $userIdStmt = $conn->prepare($userIdQuery);
    $userIdStmt->execute([$email]);
    
    if ($userIdStmt->rowCount() === 0) {
        error_log("Access attempt from invalid user: " . $email);
        session_destroy();
        header('Location: index.php');
        exit;
    }

    $userData = $userIdStmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify user is active
    if ($userData['active'] != 1) {
        error_log("Access attempt from inactive user: " . $email);
        session_destroy();
        header('Location: index.php');
        exit;
    }

    $_SESSION['user_id'] = $userData['id'];

} catch (Exception $e) {
    error_log("Error in user dashboard: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Get user type based on email from database
$email = $_SESSION['email'];

// Get user ID with proper error handling
$userIdQuery = "SELECT id FROM tbl_users WHERE email = ?";
$userIdStmt = $conn->prepare($userIdQuery);
$userIdStmt->execute([$email]);
$userIdResult = $userIdStmt->fetch(PDO::FETCH_ASSOC);

$user_id = $userIdResult['id'] ?? null;

if (!isset($user_id) || !is_numeric($user_id)) {
    error_log("Invalid user ID in dashboard query");
    die("An error occurred. Please try again later.");
}

// Corrected SQL query with proper join condition and alias references
$sql = "SELECT p.*, u.name, u.email,
        p.submission_time, p.status, p.ppw_id,
        p.document_path, p.ref_number, p.session
        FROM tbl_ppw p 
        INNER JOIN tbl_users u ON p.user_email = u.email 
        WHERE p.user_email = :email 
        ORDER BY p.submission_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);

try {
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        // Handle no results
        $rows = [];
    }
} catch (PDOException $e) {
    error_log("Query error in user dashboard: " . $e->getMessage());
    die("An error occurred while retrieving paperwork.");
}

// Add before displaying the pagination
$results_per_page = 10; // Or your desired number
$total_results = count($rows);
$number_of_pages = ceil($total_results / $results_per_page);

if (isset($_GET['pageno'])) {
    $pageno = (int)$_GET['pageno'];
    $page_first_result = ($pageno - 1) * $results_per_page;
} else {
    $pageno = 1;
    $page_first_result = 0;
}

// Handle deletion with proper validation
if (isset($_GET['submit']) && $_GET['submit'] == 'delete') {
    try {
        $ppw_id = filter_input(INPUT_GET, 'ppw_id', FILTER_VALIDATE_INT);
        if (!$ppw_id) {
            throw new Exception("Invalid paperwork ID");
        }

        // Verify user owns this paperwork
        $verifyStmt = $conn->prepare("SELECT ppw_id FROM tbl_ppw WHERE ppw_id = ? AND user_email = ?");
        $verifyStmt->execute([$ppw_id, $email]);
        
        if ($verifyStmt->rowCount() === 0) {
            throw new Exception("Paperwork not found or access denied");
        }

        $deleteStmt = $conn->prepare("DELETE FROM tbl_ppw WHERE ppw_id = ?");
        $deleteStmt->execute([$ppw_id]);
        
        echo "<script>
                alert('Paperwork deleted successfully.');
                window.location.href='user_dashboard.php';
              </script>";
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// Search functionality with prepared statements
if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
    try {
        $search_query = '%' . trim($_GET['search_query']) . '%';
        $search_option = $_GET['search_option'];

        $searchSql = match($search_option) {
            'name' => "SELECT p.*, u.name, u.email 
                      FROM tbl_ppw p 
                      INNER JOIN tbl_users u ON p.user_email = u.email 
                      WHERE p.name LIKE ? AND p.user_email = ?",
            'email' => "SELECT p.*, u.name, u.email 
                       FROM tbl_ppw p 
                       INNER JOIN tbl_users u ON p.user_email = u.email 
                       WHERE p.user_email = ?",
            default => throw new Exception("Invalid search option")
        };

        $searchStmt = $conn->prepare($searchSql);
        $searchParams = $search_option === 'name' ? [$search_query, $email] : [$email];
        $searchStmt->execute($searchParams);
        $rows = $searchStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "<script>alert('No results found.');</script>";
        }
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        echo "<script>alert('Error performing search.');</script>";
    }
}

// At the top of the file after session checks
require_once 'includes/PermissionManager.php';
$permManager = new PermissionManager($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOC Paperwork Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
<!-- Modern Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo ($_SESSION['user_type'] === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
            <i class="fas fa-file-alt text-primary me-2"></i>
            <span class="fw-bold">SOC Paperwork System</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link px-3" href="user_dashboard.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="create_paperwork.php">
                        <i class="fas fa-plus me-1"></i> New Paperwork
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="user_manage_account.php">
                        <i class="fas fa-users me-1"></i> Manage Account
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="#" data-bs-toggle="modal" data-bs-target="#modal1">
                        <i class="fas fa-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger px-3" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content with top margin to account for fixed navbar -->
<main>
    <!-- Welcome Section -->
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title h4 mb-3">
                    <i class="fas fa-wave-square text-primary me-2"></i>
                    Welcome to SOC Paperwork Management System
                </h2>
                <p class="card-text text-muted mb-0">
                    Manage and track your paperwork efficiently with our comprehensive system.
                </p>
            </div>
        </div>
    </div>

<!-- Dashboard Summary Cards -->
<div class="container mb-3">
    <div class="row g-2">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-2">Total Paperworks</h6>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0"><?php echo count($rows); ?></h3>
                        <i class="fas fa-file-alt text-primary ms-auto"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-2">Pending Approval</h6>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0"><?php echo count(array_filter($rows, fn($row) => $row['status'] != 1)); ?></h3>
                        <i class="fas fa-clock text-warning ms-auto"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-2">Approved</h6>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0"><?php echo count(array_filter($rows, fn($row) => $row['status'] == 1)); ?></h3>
                        <i class="fas fa-check-circle text-success ms-auto"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-2">Last Submission</h6>
                    <div class="d-flex align-items-center">
                        <small class="text-muted"><?php echo !empty($rows) ? date('d M Y', strtotime($rows[0]['submission_time'])) : 'N/A'; ?></small>
                        <i class="fas fa-calendar text-info ms-auto"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Activity in same row -->
<div class="container mb-3">
    <div class="row g-2">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="create_paperwork.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>New Paperwork
                        </a>
                        <a href="user_manage_account.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-cog me-2"></i>Account Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h5 class="card-title mb-3">Recent Activity</h5>
                    <div class="list-group list-group-flush">
                        <?php 
                        $recent_activities = array_slice($rows, 0, 3);
                        foreach($recent_activities as $activity): 
                        ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['ref_number']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($activity['project_name']); ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $activity['status'] == 1 ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $activity['status'] == 1 ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list text-primary me-2"></i>
                    Your Paperworks
                </h5>
                <a href="create_paperwork.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    New Paperwork
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col" class="px-4 py-3">Reference Number</th>
                            <th scope="col" class="px-4 py-3">Name</th>
                            <th scope="col" class="px-4 py-3">Staff ID</th>
                            <th scope="col" class="px-4 py-3">Session</th>
                            <th scope="col" class="px-4 py-3">Actions</th>
                            <th scope="col" class="px-4 py-3">Status Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) { ?>
                            <tr>
                                <td class="px-4"><?php echo htmlspecialchars($row['ref_number']); ?></td>
                                <td class="px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-4"><?php echo htmlspecialchars($row['id']); ?></td>
                                <td class="px-4"><?php echo htmlspecialchars($row['session']); ?></td>
                                <td class="px-4">
                                    <div class="btn-group" role="group">
                                        <a href="viewpaperworkuser.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <?php if ($permManager->hasPermission('edit_submission')): ?>
                                            <a href="editpaperwork.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($permManager->hasPermission('delete_submission')): ?>
                                            <a href="user_dashboard.php?submit=delete&ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this paperwork?');">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4">
                                    <button type="button" 
                                            class="btn btn-sm <?php echo $row['status'] == 1 ? 'btn-success' : 'btn-warning'; ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#statusModal<?php echo $row['ppw_id']; ?>">
                                        <?php echo $row['status'] == 1 ? 'Approved' : 'Pending'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile View Cards -->
    <div class="d-md-none">
        <?php foreach ($rows as $row) { ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">ID: <?php echo $row['ppw_id']; ?></h6>
                    <h5 class="card-title"><?php echo $row['project_name']; ?></h5>
                    <p class="card-text mb-1">
                        <small class="text-muted">Session: <?php echo $row['session']; ?></small>
                    </p>
                    <p class="card-text mb-3">
                        <span class="badge <?php echo $row['status'] == 1 ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo $row['status'] == 1 ? 'Approved' : 'Pending'; ?>
                        </span>
                    </p>
                    <a href="viewpaperworkuser.php?ppw_id=<?php echo $row['ppw_id']; ?>" 
                       class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-eye me-1"></i> View Details
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($page = 1; $page <= $number_of_pages; $page++) { ?>
                    <li class="page-item <?php echo $page == $pageno ? 'active' : ''; ?>">
                        <a class="page-link" href="main.php?pageno=<?php echo $page; ?>">
                            <?php echo $page; ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </nav>
    </div>
</div>


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

    <!-- Status Modal -->
    <?php if (!empty($rows)) { foreach ($rows as $row) { ?>
    <div class="modal fade" id="statusModal<?php echo htmlspecialchars($row['ppw_id']); ?>" 
         tabindex="-1" 
         aria-hidden="true"
         data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Paperwork Status Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width: 40%">Submission Details</th>
                                <td>
                                    <?php if (!empty($row['submission_time'])): ?>
                                        <p class="mb-1"><strong>Date:</strong> 
                                            <?php echo date('d M Y, h:i A', strtotime($row['submission_time'])); ?>
                                        </p>
                                        <p class="mb-0"><strong>By:</strong> 
                                            <?php echo htmlspecialchars($row['name'] ?? 'Unknown'); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="mb-0">No submission details available</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php } } ?>

<?php foreach ($rows as $row) { ?>
    <div class="modal fade" id="patientModal<?php echo $row['ppw_id']; ?>" tabindex="-1" aria-labelledby="patientModalLabel<?php echo $row['ppw_id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientModalLabel<?php echo $row['ppw_id']; ?>">Your Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><b>Your ID:</b> <?php echo $row['ppw_id']; ?></p>
                    <p><b>Name:</b> <?php echo $row['name']; ?></p>
                    <p><b>Email:</b> <?php echo $row['email']; ?></p>
                    <p><b>Phone:</b> <?php echo $row['phone']; ?></p>
                    <p><b>Address:</b> <?php echo $row['address']; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
