<?php
require_once 'telegram/telegram_handlers.php';
include 'dbconnect.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Strict session validation with timing attack prevention
if (!isset($_SESSION['email']) || 
    !isset($_SESSION['user_type']) || 
    !hash_equals($_SESSION['user_type'], 'user')) {
    $email = $_SESSION['email'] ?? 'unknown';
    error_log("Unauthorized paperwork view attempt: " . $email);
    
    // Notify admin using [`notifySystemError`](telegram/telegram_handlers.php)
    notifySystemError(
        'Unauthorized Access',
        "Unauthorized attempt to view user paperwork by: $email",
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

// Rate limiting for paperwork views
if (!isset($_SESSION['view_attempts'])) {
    $_SESSION['view_attempts'] = 1;
    $_SESSION['view_time'] = time();
} else {
    if (time() - $_SESSION['view_time'] < 300) { // 5 minute window
        if ($_SESSION['view_attempts'] > 20) { // Max 20 views per 5 minutes
            error_log("Paperwork view rate limit exceeded for user: " . $_SESSION['email']);
            http_response_code(429);
            die("Too many requests. Please try again later.");
        }
        $_SESSION['view_attempts']++;
    } else {
        $_SESSION['view_attempts'] = 1;
        $_SESSION['view_time'] = time();
    }
}

try {
    // Get paperwork details with error handling
    $ppw_id = filter_input(INPUT_GET, 'ppw_id', FILTER_VALIDATE_INT);
    if (!$ppw_id) {
        throw new Exception("Invalid paperwork ID");
    }

    // Verify user owns this paperwork
    $stmt = $conn->prepare("SELECT * FROM tbl_ppw WHERE ppw_id = ? AND user_email = ?");
    if (!$stmt->execute([$ppw_id, $_SESSION['email']])) {
        throw new Exception("Error fetching paperwork details");
    }

    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$paperwork) {
        // Notify admin of potential unauthorized access attempt
        notifySystemError(
            'Access Violation',
            "User {$_SESSION['email']} attempted to view unauthorized paperwork ID: $ppw_id",
            __FILE__,
            __LINE__
        );
        throw new Exception("Paperwork not found or access denied");
    }

} catch (Exception $e) {
    error_log("Paperwork view error: " . $e->getMessage());
    
    // Notify admin about system error using [`notifySystemError`](telegram/telegram_handlers.php)
    notifySystemError(
        'Database Error',
        $e->getMessage(),
        __FILE__,
        __LINE__
    );
    
    die("An error occurred while viewing paperwork. Please try again later.");
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

                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Reference Number:</label>
                        <div class="col-sm-9">
                            <input type="text" 
                                class="form-control form-control-lg shadow-sm" 
                                value="<?php echo htmlspecialchars($paperwork['ref_number']); ?>" 
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Paperwork Name:</label>
                        <div class="col-sm-9">
                            <input type="text" 
                                class="form-control form-control-lg shadow-sm" 
                                value="<?php echo htmlspecialchars($paperwork['project_name']); ?>" 
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Type:</label>
                        <div class="col-sm-9">
                            <input type="text" 
                                class="form-control form-control-lg shadow-sm" 
                                value="<?php echo htmlspecialchars($paperwork['ppw_type']); ?>" 
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Session:</label>
                        <div class="col-sm-9">
                            <input type="text" 
                                class="form-control form-control-lg shadow-sm" 
                                value="<?php echo htmlspecialchars($paperwork['session']); ?>" 
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Submission Date:</label>
                        <div class="col-sm-9">
                            <input type="text" 
                                class="form-control form-control-lg shadow-sm" 
                                value="<?php echo htmlspecialchars($paperwork['submission_time']); ?>" 
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Status:</label>
                        <div class="col-sm-9">
                            <span class="badge <?php echo $paperwork['status'] == 1 ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $paperwork['status'] == 1 ? 'Approved' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($paperwork['document_path']): ?>
                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label fw-medium">Document:</label>
                        <div class="col-sm-9">
                            <a href="uploads/<?php echo htmlspecialchars($paperwork['document_path']); ?>" 
                               class="btn btn-primary" 
                               target="_blank">
                                <i class="fas fa-download me-2"></i>Download Document
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

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
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
