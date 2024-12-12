<?php
// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation with timing attack prevention
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'admin')) {
    error_log("Unauthorized paperwork view attempt: " . ($_SESSION['email'] ?? 'unknown'));
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

include 'dbconnect.php';
include 'includes/header.php';
include 'includes/email_functions.php';

// Rate limiting for paperwork actions
if (!isset($_SESSION['paperwork_actions'])) {
    $_SESSION['paperwork_actions'] = 1;
    $_SESSION['action_time'] = time();
} else {
    if (time() - $_SESSION['action_time'] < 300) { // 5 minute window
        if ($_SESSION['paperwork_actions'] > 10) { // Max 10 actions per 5 minutes
            error_log("Paperwork action rate limit exceeded for admin: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many actions. Please try again later.");
        }
        $_SESSION['paperwork_actions']++;
    } else {
        $_SESSION['paperwork_actions'] = 1;
        $_SESSION['action_time'] = time();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['ppw_id'])) {
    // Validate and sanitize inputs
    $ppw_id = filter_var($_POST['ppw_id'], FILTER_VALIDATE_INT);
    if (!$ppw_id) {
        error_log("Invalid paperwork ID attempt: " . $_POST['ppw_id']);
        die("Invalid paperwork ID");
    }

    $note = isset($_POST['admin_note']) ? 
        htmlspecialchars(trim($_POST['admin_note']), ENT_QUOTES, 'UTF-8') : null;
    
    $current_time = date('Y-m-d H:i:s');
    
    try {
        // Verify paperwork exists and admin has permission
        $checkStmt = $conn->prepare("SELECT ppw_id FROM tbl_ppw WHERE ppw_id = ?");
        $checkStmt->execute([$ppw_id]);
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception("Paperwork not found");
        }

        if ($_SESSION['user_type'] == 'hod') {
            if ($_POST['action'] == 'approve') {
                $sql = "UPDATE tbl_ppw SET 
                        hod_approval = 1,
                        hod_note = ?,
                        hod_approval_date = ?,
                        current_stage = 'ceo_review'
                        WHERE ppw_id = ?";
                $message = "Paperwork approved and forwarded to CEO";
            } else {
                $sql = "UPDATE tbl_ppw SET 
                        hod_approval = 0,
                        hod_note = ?,
                        hod_approval_date = ?,
                        current_stage = 'rejected'
                        WHERE ppw_id = ?";
                $message = "Paperwork returned for modification";
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute([$note, $current_time, $ppw_id]);

            // Get CEO email
            $ceoQuery = "SELECT email FROM tbl_users WHERE user_type = 'ceo' LIMIT 1";
            $ceoStmt = $conn->prepare($ceoQuery);
            $ceoStmt->execute();
            $ceoEmail = $ceoStmt->fetchColumn();

            if ($ceoEmail) {
                sendCEONotificationEmail($ceoEmail, [
                    'ref_number' => $paperwork['ref_number'],
                    'project_name' => $paperwork['project_name'],
                    'hod_approval_date' => date('Y-m-d H:i:s')
                ], $_SESSION['name']);
            }
        } 
        elseif ($_SESSION['user_type'] == 'ceo') {
            if ($_POST['action'] == 'approve') {
                $sql = "UPDATE tbl_ppw SET 
                        ceo_approval = 1,
                        ceo_note = ?,
                        ceo_approval_date = ?,
                        current_stage = 'approved',
                        status = 1
                        WHERE ppw_id = ?";
                $message = "Paperwork approved";
            } else {
                $sql = "UPDATE tbl_ppw SET 
                        ceo_approval = 0,
                        ceo_note = ?,
                        ceo_approval_date = ?,
                        current_stage = 'rejected'
                        WHERE ppw_id = ?";
                $message = "Paperwork returned for modification";
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute([$note, $current_time, $ppw_id]);
        }

        echo "<script>alert('$message'); window.location.href='admin_dashboard.php';</script>";
        exit;
    } catch (Exception $e) {
        error_log("Error processing paperwork action: " . $e->getMessage());
        die("An error occurred while processing your request.");
    }
}

if (isset($_GET['ppw_id'])) {
    $ppw_id = $_GET['ppw_id'];
    $sql = "SELECT p.*, u.name as submitted_by 
            FROM tbl_ppw p 
            JOIN tbl_users u ON p.id = u.id 
            WHERE p.ppw_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ppw_id]);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paperwork) {
        echo "<script>alert('Paperwork not found.'); window.location.href='admin_dashboard.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('No Paperwork ID provided.'); window.location.href='admin_dashboard.php';</script>";
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
                        <!-- Reference Number -->
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Reference Number:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    value="<?php echo htmlspecialchars($paperwork['ref_number']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <!-- Paperwork Name -->
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Paperwork Name:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    value="<?php echo htmlspecialchars($paperwork['project_name']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <!-- Paperwork Type -->
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Type:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    value="<?php echo htmlspecialchars($paperwork['ppw_type']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <!-- Session -->
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Session:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    value="<?php echo htmlspecialchars($paperwork['session']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <!-- Submitted By -->
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Submitted By:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    value="<?php echo htmlspecialchars($paperwork['submitted_by']); ?>" 
                                    readonly>
                            </div>
                        </div>

                        <!-- Document -->
                        <?php if ($paperwork['document_path']): ?>
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Document:</label>
                            <div class="col-sm-9">
                                <a href="uploads/<?php echo htmlspecialchars($paperwork['document_path']); ?>" 
                                   class="btn btn-primary" 
                                   target="_blank">
                                    <i class="fas fa-download me-2"></i>View Document
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Status -->
                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label fw-medium">Current Status:</label>
                            <div class="col-sm-9">
                                <span class="badge <?php echo $paperwork['status'] == 1 ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $paperwork['status'] == 1 ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Approval Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disapproveModal">
                                <i class="fas fa-times me-2"></i>Return for Modification
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

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="viewpaperworkadmin.php" method="post">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Approve Paperwork
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <input type="hidden" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>">
                        <div class="mb-4">
                            <label for="approveNote" class="form-label fw-medium">Approval Note:</label>
                            <textarea 
                                class="form-control form-control-lg shadow-sm" 
                                id="approveNote" 
                                name="admin_note" 
                                rows="3"
                                placeholder="Add any notes or comments (optional)"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="approve" class="btn btn-success px-4">
                            <i class="fas fa-check me-2"></i>Confirm Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Disapprove Modal -->
    <div class="modal fade" id="disapproveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="viewpaperworkadmin.php" method="post">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-undo text-danger me-2"></i>
                            Return for Modification
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <input type="hidden" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>">
                        <div class="mb-4">
                            <label for="disapproveNote" class="form-label fw-medium">Feedback Note: <span class="text-danger">*</span></label>
                            <textarea 
                                class="form-control form-control-lg shadow-sm" 
                                id="disapproveNote" 
                                name="admin_note" 
                                rows="3"
                                placeholder="Provide feedback on required modifications"
                                required
                            ></textarea>
                            <div class="form-text">Please provide specific feedback on what needs to be modified.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="not_approve" class="btn btn-danger px-4">
                            <i class="fas fa-undo me-2"></i>Return for Modification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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