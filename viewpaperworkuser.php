<?php
session_start();
if (!(isset($_SESSION['email']) && $_SESSION['user_type'] == 'user')) {
    header('Location: index.php');
    exit;
}

// Include database connection
include 'dbconnect.php';

// Get user type based on email from database
$email = $_SESSION['email'];

// Check if ppw_id is provided
if (isset($_GET['ppw_id'])) {
    $ppw_id = $_GET['ppw_id'];
    $sql = "SELECT * FROM tbl_ppwfull WHERE ppw_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ppw_id]);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "<script>alert('No Paperwork ID provided.'); window.location.href='user_dashboard.php';</script>";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
                    <!-- Form Fields -->
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
                                value="<?php echo htmlspecialchars($paperwork['name']); ?>" 
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

                    <!-- Back Button -->
                    <div class="d-flex justify-content-end mt-4">
                        <a href="user_dashboard.php" class="btn btn-light px-4">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
