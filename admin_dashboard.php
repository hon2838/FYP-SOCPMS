<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Enable error logging
error_log("Admin Dashboard Session: " . print_r($_SESSION, true));

// Strict session validation with timing attack prevention
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'admin')) {
    error_log("Admin access denied: " . print_r($_SESSION, true));
    session_destroy();
    header('Location: index.php');
    exit;
}

// Set secure headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > 1800)) {
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

// Rate limiting for admin actions
if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 1;
    $_SESSION['request_time'] = time();
} else {
    if (time() - $_SESSION['request_time'] < 60) { // 1 minute window
        if ($_SESSION['request_count'] > 30) { // Max 30 requests per minute
            error_log("Rate limit exceeded for admin: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many requests. Please try again later.");
        }
        $_SESSION['request_count']++;
    } else {
        $_SESSION['request_count'] = 1;
        $_SESSION['request_time'] = time();
    }
}

// Include database connection with additional security checks
try {
    include 'dbconnect.php';
    
    // Verify admin status in database
    $stmt = $conn->prepare("SELECT active FROM tbl_users WHERE email = ? AND user_type = 'admin' AND active = 1");
    $stmt->execute([$_SESSION['email']]);
    
    if ($stmt->rowCount() === 0) {
        error_log("Invalid admin access attempt: " . $_SESSION['email']);
        session_destroy();
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in admin dashboard: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

include 'includes/header.php';

// Get user type based on email from database
$email = $_SESSION['email'];

// Load patients with safe column handling
$sqlloadpatients = "SELECT 
    p.*,
    u.name,
    u.email,
    COALESCE(u.phone, 'Not provided') as phone
FROM tbl_ppw p 
JOIN tbl_users u ON p.id = u.id 
ORDER BY p.submission_time DESC";
try {
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in admin_dashboard: " . $e->getMessage());
    $rows = [];
}

// Handle delete request
if (isset($_GET['submit']) && $_GET['submit'] == 'delete') {
    $ppw_id = $_GET['ppw_ppw_id'];
    try {
        $sqldeletepatient = "DELETE FROM tbl_ppw WHERE ppw_id = ?";
        $stmt = $conn->prepare($sqldeletepatient);
        $stmt->execute([$ppw_id]);
        echo "<script>alert('Patient deleted successfully.');</script>";
        echo "<script>window.location.href='admin_dashboard.php';</script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle search request
if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
    $search_query = $_GET['search_query'];
    $search_option = $_GET['search_option'];

    if ($search_option == 'name') {
        $sqlloadpatients = "SELECT * FROM tbl_ppw WHERE name LIKE ?";
    } else if ($search_option == 'email') {
        $sqlloadpatients = "SELECT * FROM tbl_ppw WHERE email LIKE ?";
    }

    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute(['%' . $search_query . '%']);
    $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows = $stmt->fetchAll();

    if (count($rows) == 0) {
        echo "<script>alert('No results found.');</script>";
        echo "<script>window.location.href='main.php';</script>";
    }
}

// Pagination
$results_per_page = 10;
if (isset($_GET['pageno'])) {
    $pageno = (int)$_GET['pageno'];
    $page_first_result = ($pageno - 1) * $results_per_page;
} else {
    $pageno = 1;
    $page_first_result = 0;
}

$stmt = $conn->prepare($sqlloadpatients);
$stmt->execute();

$number_of_results = $stmt->rowCount();
$number_of_pages = ceil($number_of_results / $results_per_page);
$sqlloadpatients = $sqlloadpatients . " LIMIT " . $page_first_result . ',' . $results_per_page;
$stmt = $conn->prepare($sqlloadpatients);
$stmt->execute();

$results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">
    <!-- Main Content with top margin to account for fixed navbar -->
    <main class="pt-5 mt-5">
        <!-- Welcome Section -->
        <div class="container mb-2 py-5">
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

        <!-- Paperworks Table Section -->
        <div class="container">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-list text-primary me-2"></i>
                        Paperworks Pending Approval
                    </h5>
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
                                <?php if (!empty($rows)): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="px-4"><?php echo htmlspecialchars($row['ref_number']); ?></td>
                                            <td class="px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="px-4"><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td class="px-4"><?php echo htmlspecialchars($row['session']); ?></td>
                                            <td class="px-4">
                                                <div class="btn-group" role="group">
                                                    <a href="viewpaperworkadmin.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>
                                                    <a href="editpaperwork.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </a>
                                                    <?php if ($row['status'] != 1): // Only show delete if not approved ?>
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
                                                        class="btn btn-sm <?php 
                                                            switch($row['current_stage']) {
                                                                case 'submitted':
                                                                    echo 'btn-secondary';
                                                                    break;
                                                                case 'hod_review':
                                                                    echo 'btn-warning';
                                                                    break;
                                                                case 'ceo_review':
                                                                    echo 'btn-info';
                                                                    break;
                                                                case 'approved':
                                                                    echo 'btn-success';
                                                                    break;
                                                                case 'rejected':
                                                                    echo 'btn-danger';
                                                                    break;
                                                                default:
                                                                    echo 'btn-secondary';
                                                            }
                                                        ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal<?php echo $row['ppw_id']; ?>">
                                                    <?php 
                                                        switch($row['current_stage']) {
                                                            case 'submitted':
                                                                echo 'Submitted';
                                                                break;
                                                            case 'hod_review':
                                                                echo 'Pending HOD Review';
                                                                break;
                                                            case 'ceo_review':
                                                                echo 'Pending CEO Review';
                                                                break;
                                                            case 'approved':
                                                                echo 'Approved';
                                                                break;
                                                            case 'rejected':
                                                                echo 'Returned';
                                                                break;
                                                            default:
                                                                echo 'Processing';
                                                        }
                                                    ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No paperworks found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="d-flex justify-content-center">
        <?php
        for ($page = 1; $page <= $number_of_pages; $page++) {
            echo '<a href="main.php?pageno=' . $page . '" class="btn btn-primary">' . $page . '</a>';
        }
        ?>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

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
<div class="modal fade" id="statusModal<?php echo $row['ppw_id']; ?>" 
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
            <div class="modal-body p-4">
                <!-- Status Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3 text-muted">
                            <i class="fas fa-file-alt me-2"></i>Submission Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="mb-1"><small class="fw-medium">Date:</small></p>
                                <p class="mb-0"><?php echo date('d M Y, h:i A', strtotime($row['submission_time'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><small class="fw-medium">Submitted By:</small></p>
                                <p class="mb-0"><?php echo htmlspecialchars($row['name']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HOD Review Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3 text-muted">
                            <i class="fas fa-user-tie me-2"></i>HOD Review
                        </h6>
                        <?php if($row['hod_approval'] !== null): ?>
                            <div class="mb-3">
                                <p class="mb-1"><small class="fw-medium">Status:</small></p>
                                <span class="badge <?php echo $row['hod_approval'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $row['hod_approval'] ? 'Approved' : 'Returned'; ?>
                                </span>
                            </div>
                            <?php if($row['hod_note']): ?>
                                <div class="mb-3">
                                    <p class="mb-1"><small class="fw-medium">Note:</small></p>
                                    <p class="mb-0"><?php echo htmlspecialchars($row['hod_note']); ?></p>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">
                                <?php echo date('d M Y, h:i A', strtotime($row['hod_approval_date'])); ?>
                            </small>
                        <?php else: ?>
                            <p class="mb-0 text-muted">Pending Review</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CEO Review Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3 text-muted">
                            <i class="fas fa-user-shield me-2"></i>CEO Review
                        </h6>
                        <?php if($row['hod_approval'] && $row['ceo_approval'] !== null): ?>
                            <div class="mb-3">
                                <p class="mb-1"><small class="fw-medium">Status:</small></p>
                                <span class="badge <?php echo $row['ceo_approval'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $row['ceo_approval'] ? 'Approved' : 'Returned'; ?>
                                </span>
                            </div>
                            <?php if($row['ceo_note']): ?>
                                <div class="mb-3">
                                    <p class="mb-1"><small class="fw-medium">Note:</small></p>
                                    <p class="mb-0"><?php echo htmlspecialchars($row['ceo_note']); ?></p>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">
                                <?php echo date('d M Y, h:i A', strtotime($row['ceo_approval_date'])); ?>
                            </small>
                        <?php else: ?>
                            <p class="mb-0 text-muted">
                                <?php echo $row['hod_approval'] ? 'Pending Review' : 'Awaiting HOD Approval'; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php foreach ($rows as $row) { ?>
    <div class="modal fade" id="patientModal<?php echo $row['ppw_id']; ?>" tabindex="-1" aria-labelledby="patientModalLabel<?php echo $row['ppw_id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientModalLabel<?php echo $row['ppw_id']; ?>">Your Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><b>Your ID:</b> <?php echo htmlspecialchars($row['ppw_id']); ?></p>
                    <p><b>Name:</b> <?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></p>
                    <p><b>Email:</b> <?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></p>
                    <p><b>Phone:</b> <?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></p>
                    <p><b>Address:</b> <?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></p>
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
