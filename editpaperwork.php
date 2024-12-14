<?php
require_once 'telegram/telegram_handlers.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    error_log("Unauthorized access attempt to editpaperwork.php");
    header('Location: index.php');
    exit;
}

// Add after session validation
if (!$rbac->checkPermission('edit_paperwork')) {
    handlePermissionError('edit_paperwork', 'user_dashboard.php');
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

// Rate limiting
if (!isset($_SESSION['edit_attempts'])) {
    $_SESSION['edit_attempts'] = 1;
    $_SESSION['edit_time'] = time();
} else {
    if (time() - $_SESSION['edit_time'] < 300) { // 5 minute window
        if ($_SESSION['edit_attempts'] > 5) { // Max 5 edits per 5 minutes
            error_log("Rate limit exceeded for user: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many edit attempts. Please try again later.");
        }
        $_SESSION['edit_attempts']++;
    } else {
        $_SESSION['edit_attempts'] = 1;
        $_SESSION['edit_time'] = time();
    }
}

include 'dbconnect.php';
include 'includes/header.php';

try {
    // Sanitize and validate email
    $email = filter_var($_SESSION['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Get user details with prepared statement
    $userQuery = "SELECT id, name, user_type FROM tbl_users WHERE email = ? AND active = 1";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([$email]);
    
    if ($userStmt->rowCount() === 0) {
        error_log("Access attempt from inactive/nonexistent user: " . $email);
        session_destroy();
        header('Location: index.php');
        exit;
    }

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // Verify user permissions
    if (!in_array($user['user_type'], ['admin', 'user'])) {
        error_log("Invalid user type attempting access: " . $user['user_type']);
        header('Location: index.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Error in editpaperwork.php: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Get paperwork details
if (isset($_GET['ppw_id'])) {
    $ppw_id = $_GET['ppw_id'];
    // Allow admin to edit any paperwork, user can edit their own
    $sql = $user['user_type'] === 'admin' 
        ? "SELECT * FROM tbl_ppw WHERE ppw_id = ?" 
        : "SELECT * FROM tbl_ppw WHERE ppw_id = ? AND id = ?";
    
    $stmt = $conn->prepare($sql);
    $params = $user['user_type'] === 'admin' ? [$ppw_id] : [$ppw_id, $user['id']];
    $stmt->execute($params);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paperwork) {
        echo "<script>alert('Paperwork not found or unauthorized.'); window.location.href='" . 
             ($user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php') . 
             "';</script>";
        exit;
    }

    // Check if paperwork is already approved
    if ($paperwork['status'] == 1) {
        echo "<script>alert('Cannot edit approved paperwork.'); window.location.href='" . 
             ($user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php') . 
             "';</script>";
        exit;
    }
} else {
    header('Location: user_dashboard.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // File upload handling
    $fileName = $paperwork['document_path']; // Keep existing file by default
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $uploadDir = 'uploads/';
        $fileName = time() . '_' . basename($_FILES['document']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            // Delete old file if exists
            if (!empty($paperwork['document_path'])) {
                @unlink($uploadDir . $paperwork['document_path']);
            }
        } else {
            echo "<script>alert('Error uploading file.');</script>";
            exit;
        }
    }

    try {
        // Update paperwork with notification
        $sql = "UPDATE tbl_ppw SET 
                ref_number = ?,
                project_name = ?,
                ppw_type = ?,
                session = ?,
                document_path = ?
                WHERE ppw_id = ? AND id = ?";
                
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([
            $_POST['ref_number'],
            $_POST['project_name'],
            $_POST['ppw_type'],
            $_POST['session'],
            $fileName ?: $paperwork['document_path'],
            $_GET['ppw_id'],
            $user['id']
        ])) {
            // Notify about successful paperwork update
            notifySystemError(
                'Paperwork Updated',
                "Paperwork ID: {$_GET['ppw_id']}\n" .
                "Updated by: {$_SESSION['email']}\n" .
                "Project: {$_POST['project_name']}\n" .
                "Type: {$_POST['ppw_type']}\n" .
                "Time: " . date('Y-m-d H:i:s'),
                __FILE__,
                __LINE__
            );

            // Handle file upload notification
            if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
                notifySystemError(
                    'Document Updated',
                    "Paperwork ID: {$_GET['ppw_id']}\n" .
                    "New file: $fileName\n" .
                    "Old file: {$paperwork['document_path']}\n" .
                    "Updated by: {$_SESSION['email']}",
                    __FILE__,
                    __LINE__
                );
            }

            header("Location: " . ($_SESSION['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
            exit;
        }

    } catch (Exception $e) {
        error_log("Paperwork update error: " . $e->getMessage());
        
        // Notify admin about error
        notifySystemError(
            'Database Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        
        die("An error occurred while updating paperwork. Please try again later.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Paperwork - SOC PMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <main class="pt-5 mt-5">
        <div class="container py-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Edit Paperwork
                    </h4>
                    <form action="editpaperwork.php?ppw_id=<?php echo htmlspecialchars($ppw_id); ?>" method="post" class="needs-validation" enctype="multipart/form-data" novalidate>
                        <!-- Reference Number -->
                        <div class="row mb-4">
                            <label for="ref_number" class="col-sm-3 col-form-label fw-medium">Reference Number: <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control form-control-lg shadow-sm" 
                                       id="ref_number" name="ref_number" 
                                       value="<?php echo htmlspecialchars($paperwork['ref_number']); ?>"
                                       required>
                                <div class="invalid-feedback">Please enter a reference number.</div>
                            </div>
                        </div>

                        <!-- Paperwork Name -->
                        <div class="row mb-4">
                            <label for="project_name" class="col-sm-3 col-form-label fw-medium">Paperwork Name: <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control form-control-lg shadow-sm" 
                                       id="project_name" name="project_name" 
                                       value="<?php echo htmlspecialchars($paperwork['project_name']); ?>"
                                       required>
                                <div class="invalid-feedback">Please enter the paperwork name.</div>
                            </div>
                        </div>

                        <!-- Paperwork Type -->
                        <div class="row mb-4">
                            <label for="ppw_type" class="col-sm-3 col-form-label fw-medium">Paperwork Type: <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select class="form-select form-select-lg shadow-sm" id="ppw_type" name="ppw_type" required>
                                    <option value="">Select paperwork type</option>
                                    <option value="Project Proposal" <?php echo $paperwork['ppw_type'] == 'Project Proposal' ? 'selected' : ''; ?>>Project Proposal</option>
                                    <option value="Research Paper" <?php echo $paperwork['ppw_type'] == 'Research Paper' ? 'selected' : ''; ?>>Research Paper</option>
                                    <option value="Technical Report" <?php echo $paperwork['ppw_type'] == 'Technical Report' ? 'selected' : ''; ?>>Technical Report</option>
                                    <option value="Documentation" <?php echo $paperwork['ppw_type'] == 'Documentation' ? 'selected' : ''; ?>>Documentation</option>
                                    <option value="Other" <?php echo $paperwork['ppw_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">Please select a paperwork type.</div>
                            </div>
                        </div>

                        <!-- Session -->
                        <div class="row mb-4">
                            <label for="session" class="col-sm-3 col-form-label fw-medium">Session: <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control form-control-lg shadow-sm" 
                                       id="session" name="session" 
                                       value="<?php echo htmlspecialchars($paperwork['session']); ?>"
                                       required>
                                <div class="invalid-feedback">Please enter the session.</div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="row mb-4">
                            <label for="document" class="col-sm-3 col-form-label fw-medium">Document:</label>
                            <div class="col-sm-9">
                                <?php if (!empty($paperwork['document_path'])): ?>
                                    <div class="mb-3">
                                        <p class="mb-2">Current document:</p>
                                        <div class="d-flex align-items-center gap-3">
                                            <a href="uploads/<?php echo htmlspecialchars($paperwork['document_path']); ?>" 
                                               class="btn btn-outline-primary" 
                                               target="_blank">
                                                <i class="fas fa-file-alt me-2"></i>
                                                <?php echo htmlspecialchars(substr($paperwork['document_path'], strpos($paperwork['document_path'], '_') + 1)); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="mb-2">Upload new document:</p>
                                <input type="file" 
                                       class="form-control form-control-lg shadow-sm" 
                                       id="document" 
                                       name="document" 
                                       accept=".pdf,.doc,.docx">
                                <div class="invalid-feedback">Please upload a document.</div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Accepted formats: PDF, DOC, DOCX. Leave empty to keep current document.
                                </small>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="<?php echo $user['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn btn-light btn-lg px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>                                
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
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>