<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    error_log("Unauthorized access attempt to create_paperwork.php");
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
header("Referrer-Policy: strict-origin-only");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

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

// Rate limiting
if (!isset($_SESSION['paperwork_submissions'])) {
    $_SESSION['paperwork_submissions'] = 1;
    $_SESSION['submission_time'] = time();
} else {
    if (time() - $_SESSION['submission_time'] < 300) { // 5 minute window
        if ($_SESSION['paperwork_submissions'] > 5) { // Max 5 submissions per 5 minutes
            error_log("Rate limit exceeded for user: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many submissions. Please try again later.");
        }
        $_SESSION['paperwork_submissions']++;
    } else {
        $_SESSION['paperwork_submissions'] = 1;
        $_SESSION['submission_time'] = time();
    }
}

// Include database connection
try {
    require 'dbconnect.php';
    
    // Verify user is active
    $stmt = $conn->prepare("SELECT active FROM tbl_users WHERE email = ? AND active = 1");
    $stmt->execute([$_SESSION['email']]);
    
    if ($stmt->rowCount() === 0) {
        error_log("Inactive user attempted access: " . $_SESSION['email']);
        session_destroy();
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in create_paperwork: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

include 'includes/header.php';
include 'includes/email_functions.php';

/**
 * Validates file uploads for paperwork
 * 
 * @param array $file $_FILES array element
 * @return bool Returns true if file is valid
 * @throws Exception If validation fails
 */
function validateFileUpload($file) {
    // Allowed MIME types
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    // Maximum file size (5MB)
    $maxSize = 5 * 1024 * 1024;

    // Basic file checks
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameter');
    }

    // Check upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File exceeds maximum size limit');
        case UPLOAD_ERR_PARTIAL:
            throw new Exception('File was only partially uploaded');
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file was uploaded');
        default:
            throw new Exception('Unknown upload error');
    }

    // Size validation
    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Maximum size is 5MB');
    }

    // MIME type validation using multiple methods
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new Exception('Invalid file type. Only PDF and DOC files are allowed');
    }

    // Additional file content check
    $fileContent = file_get_contents($file['tmp_name'], false, null, 0, 512);
    if ($fileContent === false) {
        throw new Exception('Unable to read file contents');
    }

    // Check for PHP code in files
    if (preg_match('/<\?php/i', $fileContent)) {
        throw new Exception('File contains potentially malicious content');
    }

    return true;
}

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
        try {
            validateFileUpload($_FILES['document']);
            $fileName = time() . '_' . basename($_FILES['document']['name']);
            $filePath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
                echo "<script>alert('Error uploading file.');</script>";
                exit;
            }
        } catch (Exception $e) {
            echo "<script>alert('" . $e->getMessage() . "');</script>";
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
        // Get HOD email
        $hodQuery = "SELECT email FROM tbl_users WHERE user_type = 'hod' AND department = ?";
        $hodStmt = $conn->prepare($hodQuery);
        $hodStmt->execute([$user['department']]);
        $hodEmail = $hodStmt->fetchColumn();

        // Send confirmation email to user
        sendSubmissionEmail($_SESSION['email'], $_SESSION['name'], [
            'ref_number' => $_POST['ref_number'],
            'project_name' => $_POST['project_name'],
            'submission_time' => date('Y-m-d H:i:s')
        ]);

        // Send notification to HOD
        if ($hodEmail) {
            sendHODNotificationEmail($hodEmail, $_SESSION['name'], [
                'ref_number' => $_POST['ref_number'],
                'project_name' => $_POST['project_name'],
                'submission_time' => date('Y-m-d H:i:s')
            ]);
        }

        // Redirect with success message
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
