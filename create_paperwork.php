<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}

include 'dbconnect.php';
include 'includes/header.php';

// Get user details
$email = $_SESSION['email'];
$userQuery = "SELECT id, name, user_type FROM tbl_users WHERE email = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->execute([$email]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // File upload handling
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = '';
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $fileName = time() . '_' . basename($_FILES['document']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            echo "<script>alert('Error uploading file.');</script>";
            exit;
        }
    }

    // Insert into tbl_ppw
    $sql = "INSERT INTO tbl_ppw (id, name, session, project_name, ref_number, ppw_type, project_date, document_path) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE(), ?)";
            
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([
        $user['id'],
        $user['name'],
        trim($_POST['session']),
        trim($_POST['project_name']),
        trim($_POST['ref_number']),
        trim($_POST['ppw_type']),
        $fileName
    ])) {
        $redirectPath = ($user['user_type'] === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
        echo "<script>alert('Paperwork created successfully.');</script>";
        echo "<script>window.location.href='" . $redirectPath . "';</script>";
    } else {
        echo "<script>alert('Error creating paperwork.');</script>";
    }
}
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
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
                <i class="fas fa-file-alt text-primary me-2"></i>
                <span class="fw-bold">SOC Paperwork System</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active px-3" href="create_paperwork.php">
                            <i class="fas fa-plus me-1"></i> New Paperwork
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $user['user_type'] === 'admin' ? 'admin_manage_account.php' : 'user_manage_account.php'; ?>">
                            <i class="fas fa-users me-1"></i> <?php echo $user['user_type'] === 'admin' ? 'Manage Accounts' : 'Manage Account'; ?>
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
<!-- Modern Form Container -->
<div class="container py-5"> <!-- Changed py-4 to py-5 for more spacing -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="card-title mb-4">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Create New Paperwork
            </h4>

            <form action="create_paperwork.php" method="post" class="needs-validation" enctype="multipart/form-data" novalidate>
                <!-- Add these fields at the beginning of your form -->
                <!-- Reference Number -->
                <div class="row mb-4">
                    <label for="ref_number" class="col-sm-3 col-form-label fw-medium">Reference Number: <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input 
                            type="text" 
                            class="form-control form-control-lg shadow-sm" 
                            id="ref_number" 
                            name="ref_number"
                            placeholder="Enter paperwork reference number"
                            required
                        >
                        <div class="invalid-feedback">Please enter a reference number.</div>
                    </div>
                </div>

                <!-- Paperwork Name -->
                <div class="row mb-4">
                    <label for="project_name" class="col-sm-3 col-form-label fw-medium">Paperwork Name: <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input 
                            type="text" 
                            class="form-control form-control-lg shadow-sm" 
                            id="project_name" 
                            name="project_name"
                            placeholder="Enter paperwork name"
                            required
                        >
                        <div class="invalid-feedback">Please enter the paperwork name.</div>
                    </div>
                </div>

                <!-- Paperwork Type -->
                <div class="row mb-4">
                    <label for="ppw_type" class="col-sm-3 col-form-label fw-medium">Paperwork Type: <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select 
                            class="form-select form-select-lg shadow-sm" 
                            id="ppw_type" 
                            name="ppw_type" 
                            required
                        >
                            <option value="">Select paperwork type</option>
                            <option value="Project Proposal">Project Proposal</option>
                            <option value="Research Paper">Research Paper</option>
                            <option value="Technical Report">Technical Report</option>
                            <option value="Documentation">Documentation</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please select a paperwork type.</div>
                    </div>
                </div>

                <!-- Session -->
                <div class="row mb-4">
                    <label for="session" class="col-sm-3 col-form-label fw-medium">Session: <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input 
                            type="text" 
                            class="form-control form-control-lg shadow-sm" 
                            id="session" 
                            name="session"
                            placeholder="Enter session (e.g., 2024/2025)"
                            required
                        >
                        <div class="invalid-feedback">Please enter the session.</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <label for="document" class="col-sm-3 col-form-label fw-medium">Upload Document:</label>
                    <div class="col-sm-9">
                        <input 
                            type="file" 
                            class="form-control form-control-lg shadow-sm" 
                            id="document" 
                            name="document" 
                            accept=".pdf,.doc,.docx"
                            required
                        >
                        <div class="invalid-feedback">Please upload a document.</div>
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
                    </div>
                </div>

                <!-- Background Section -->
                <div class="row mb-4">
                    <label for="background" class="col-sm-3 col-form-label fw-medium">Background:</label>
                    <div class="col-sm-9">
                        <textarea 
                            class="form-control form-control-lg shadow-sm" 
                            id="background" 
                            name="background" 
                            rows="4" 
                            placeholder="Enter project background"
                            required
                        ></textarea>
                        <div class="invalid-feedback">Please provide the background information.</div>
                    </div>
                </div>

                <!-- Aim Section -->
                <div class="row mb-4">
                    <label for="aim" class="col-sm-3 col-form-label fw-medium">Aim:</label>
                    <div class="col-sm-9">
                        <textarea 
                            class="form-control form-control-lg shadow-sm" 
                            id="aim" 
                            name="aim" 
                            rows="4" 
                            placeholder="Enter project aims"
                            required
                        ></textarea>
                        <div class="invalid-feedback">Please provide the project aims.</div>
                    </div>
                </div>

                <!-- Dates Section -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="startdate" class="form-label fw-medium">Start Date:</label>
                        <input 
                            type="date" 
                            class="form-control form-control-lg shadow-sm" 
                            id="startdate" 
                            name="startdate" 
                            required
                        >
                        <div class="invalid-feedback">Please select a start date.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-medium">End Date:</label>
                        <input 
                            type="date" 
                            class="form-control form-control-lg shadow-sm" 
                            id="end_date" 
                            name="end_date" 
                            required
                        >
                        <div class="invalid-feedback">Please select an end date.</div>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="d-grid gap-2 mt-5">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Submit Paperwork
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>

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

<!-- Form Validation Script -->
<script>
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            
            // Additional file validation
            const fileInput = form.querySelector('#document');
            if (fileInput.files.length > 0) {
                const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 10) { // 10MB limit
                    event.preventDefault();
                    alert('File size must be less than 10MB');
                }
            }
            
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>


</html>
