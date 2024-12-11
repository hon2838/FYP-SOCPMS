<?php
session_start();
error_log("Admin Dashboard Session: " . print_r($_SESSION, true));

if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    error_log("Admin access denied: " . print_r($_SESSION, true));
    header('Location: index.php');
    exit;
}
  
    // Include database connection
    include 'dbconnect.php';
  
    // Get user type based on email from database
    $email = $_SESSION['email'];

// Load patients
$sqlloadpatients = "SELECT * FROM tbl_ppw ORDER BY submission_time DESC";
$stmt = $conn->prepare($sqlloadpatients);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="main.php">
                <i class="fas fa-file-alt text-primary me-2"></i>
                <span class="fw-bold">SOC Paperwork System</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active px-3" href="admin_dashboard.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="create_paperwork.php">
                            <i class="fas fa-plus me-1"></i> New Paperwork
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="admin_manage_account.php">
                            <i class="fas fa-users me-1"></i> Manage Accounts
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
                                    <th scope="col" class="px-4 py-3">Note</th>
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
                                                    <a href="viewpaperworkuser.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
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
                                                <span class="badge <?php echo $row['status'] == 1 ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo $row['status'] == 1 ? 'Approved' : 'Pending'; ?>
                                                </span>
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
    <footer class="py-4 mt-5 bg-white border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">© 2024 SOC Paperwork Management System</p>
        </div>
    </footer>



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
