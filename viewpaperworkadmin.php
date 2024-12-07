<?php
session_start();
if (!(isset($_SESSION['email']) && $_SESSION['user_type'] == 'admin')) {
    header('Location: index.php');
    exit;
}

// Include database connection
include 'dbconnect.php';

// Check if ppw_id is provided via GET
if (isset($_GET['ppw_id'])) {
    $ppw_id = $_GET['ppw_id'];
    $sql = "SELECT * FROM tbl_ppwfull WHERE ppw_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ppw_id]);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "<script>alert('No Paperwork ID provided.'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

// Handle POST request for approving/disapproving paperwork
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['ppw_id'])) {
    $ppw_id = $_POST['ppw_id'];
    $status = ($_POST['action'] == 'approve') ? 1 : 0;
    
    $updateQuery = "UPDATE tbl_ppw SET status = ? WHERE ppw_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([$status, $ppw_id]);
    
    echo "<script>alert('Paperwork status updated.'); window.location.href='admin_dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOC Paperwork Management System</title>
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
                        <a class="nav-link px-3" href="admin_dashboard.php">
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

    <!-- Main Content -->
    <main class="pt-5 mt-5">
        <div class="container py-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        View Paperwork Details
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form action="viewpaperworkadmin.php" method="post" class="needs-validation" novalidate>
                        <!-- Form fields with modern styling -->
                        <div class="row mb-4">
                            <label for="ppw_type" class="col-sm-3 col-form-label fw-medium">Paperwork Type:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="ppw_type" 
                                    name="ppw_type" 
                                    value="<?php echo htmlspecialchars($paperwork['ppw_type']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="session" class="col-sm-3 col-form-label fw-medium">Session:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="session" 
                                    name="session" 
                                    value="<?php echo htmlspecialchars($paperwork['session']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="project_name" class="col-sm-3 col-form-label fw-medium">Paperwork Name:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="project_name" 
                                    name="project_name" 
                                    value="<?php echo htmlspecialchars($paperwork['project_name']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="objective" class="col-sm-3 col-form-label fw-medium">Objective:</label>
                            <div class="col-sm-9">
                                <textarea 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="objective" 
                                    name="objective" 
                                    rows="4" 
                                    readonly><?php echo htmlspecialchars($paperwork['objective']); ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="purpose" class="col-sm-3 col-form-label fw-medium">Purpose:</label>
                            <div class="col-sm-9">
                                <textarea 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="purpose" 
                                    name="purpose" 
                                    rows="4" 
                                    readonly><?php echo htmlspecialchars($paperwork['purpose']); ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="background" class="col-sm-3 col-form-label fw-medium">Background:</label>
                            <div class="col-sm-9">
                                <textarea 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="background" 
                                    name="background" 
                                    rows="4" 
                                    readonly><?php echo htmlspecialchars($paperwork['background']); ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label for="aim" class="col-sm-3 col-form-label fw-medium">Aim:</label>
                            <div class="col-sm-9">
                                <textarea 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="aim" 
                                    name="aim" 
                                    rows="4" 
                                    readonly><?php echo htmlspecialchars($paperwork['aim']); ?></textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="startdate" class="form-label fw-medium">Start Date:</label>
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="startdate" 
                                    name="startdate" 
                                    value="<?php echo htmlspecialchars($paperwork['startdate']); ?>"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label fw-medium">End Date:</label>
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="end_date" 
                                    name="end_date" 
                                    value="<?php echo htmlspecialchars($paperwork['end_date']); ?>"
                                    readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="pgrm_involve" class="form-label fw-medium">Program Involve:</label>
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="pgrm_involve" 
                                    name="pgrm_involve" 
                                    value="<?php echo htmlspecialchars($paperwork['pgrm_involve']); ?>"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="external_sponsor" class="form-label fw-medium">External Sponsor:</label>
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="external_sponsor" 
                                    name="external_sponsor" 
                                    value="<?php echo htmlspecialchars($paperwork['external_sponsor']); ?>"
                                    readonly>
                            </div>
                        </div>

                        <!-- Approval Buttons -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <input type="hidden" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                            <button type="submit" name="action" value="not_approve" class="btn btn-danger">
                                <i class="fas fa-times me-2"></i>Not Approve
                            </button>
                            <a href="admin_dashboard.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 mt-5 bg-white border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">© 2024 SOC Paperwork Management System</p>
        </div>
    </footer>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>