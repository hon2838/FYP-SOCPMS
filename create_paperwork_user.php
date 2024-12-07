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
  
    // Include database connection
    include 'dbconnect.php';
  
    // Get user type based on email from database
    $email = $_SESSION['email'];
// Initialize $loggedInUserId
$loggedInUserId = null;
// Get user_id based on email from database
$email = $_SESSION['email'] ?? ''; // Use null coalescing operator to avoid undefined index notice
$userQuery = "SELECT id, name FROM tbl_users WHERE email = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->execute([$email]);
$userResult = $userStmt->fetch(PDO::FETCH_ASSOC);
if ($userResult) {
    $loggedInUserId = $userResult['id']; // Use this user_id in your insert statement
    $loggedInUserName = $userResult['name']; // Fetch and assign user's name
} else {
    $loggedInUserName = "Unknown User"; // Default value or handle error appropriately
}
// Fetch the maximum ppw_id from the database
$ppwIdQuery = "SELECT MAX(ppw_id) as max_ppw_id FROM tbl_ppw";
$ppwIdStmt = $conn->prepare($ppwIdQuery);
$ppwIdStmt->execute();
$ppwIdResult = $ppwIdStmt->fetch(PDO::FETCH_ASSOC);
$newPpwId = $ppwIdResult ? $ppwIdResult['max_ppw_id'] + 1 : 1; // Increment the ppw_id or start from 1 if no records

// Fetch the maximum id from tbl_ppw
$idQuery = "SELECT MAX(id) as max_id FROM tbl_ppw";
$idStmt = $conn->prepare($idQuery);
$idStmt->execute();
$idResult = $idStmt->fetch(PDO::FETCH_ASSOC);
$newId = $idResult ? $idResult['max_id'] + 1 : 1; // Increment the id or start from 1 if no records

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $ppw_id = $newPpwId; // Use the new ppw_id generated above
    $ppw_type = filter_input(INPUT_POST, 'ppw_type', FILTER_SANITIZE_STRING);
    $session = filter_input(INPUT_POST, 'session', FILTER_SANITIZE_STRING);
    $project_name = filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING);
    $objective = filter_input(INPUT_POST, 'objective', FILTER_SANITIZE_STRING);
    $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING);
    $background = filter_input(INPUT_POST, 'background', FILTER_SANITIZE_STRING);
    $aim = filter_input(INPUT_POST, 'aim', FILTER_SANITIZE_STRING);
    $startdate = filter_input(INPUT_POST, 'startdate', FILTER_SANITIZE_STRING); // Correctly capturing startdate
    $enddate = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $pgrm_involve = filter_input(INPUT_POST, 'pgrm_involve', FILTER_SANITIZE_NUMBER_INT);
    $external_sponsor = filter_input(INPUT_POST, 'external_sponsor', FILTER_SANITIZE_NUMBER_INT);
    $sponsor_name = filter_input(INPUT_POST, 'sponsor_name', FILTER_SANITIZE_STRING);
    $english_lang_req = filter_input(INPUT_POST, 'english_lang_req', FILTER_SANITIZE_NUMBER_INT);
    // Step 1: Insert into tbl_ppw
    $sql1 = "INSERT INTO tbl_ppw (id, ppw_id, name, session, project_name, project_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    if ($stmt1->execute([$newId, $ppw_id, $name, $session, $project_name, $startdate])) {
        // Step 2: Insert into tbl_ppwfull
        $sql = "INSERT INTO tbl_ppwfull (id, name, ppw_id, ppw_type, session, project_name, objective, purpose, background, aim, startdate, end_date, pgrm_involve, external_sponsor, sponsor_name, english_lang_req) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$id, $name, $ppw_id, $ppw_type, $session, $project_name, $objective, $purpose, $background, $aim, $startdate, $enddate, $pgrm_involve, $external_sponsor, $sponsor_name, $english_lang_req])) {
            // Success: Both statements executed successfully
            if ($_SESSION['usertype'] == "admin") {
                echo "<script>alert('Paperwork created successfully.');</script>";
                echo "<script>window.location.href='admin_dashboard.php';</script>";
            } else {
                echo "<script>alert('Paperwork created successfully.');</script>";
                echo "<script>window.location.href='user_dashboard.php';</script>";
            }
        } else {
            // Error handling for the second statement
            echo "<script>alert('Error: Could not create paperwork in tbl_ppwfull.');</script>";
        }
    } else {
        // Error handling for the first statement
        echo "<script>alert('Error: Could not create paperwork in tbl_ppw.');</script>";
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
                        <a class="nav-link active px-3" href="create_paperwork_user.php">
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
    <main class="pt-5 mt-5">
        <!-- Modern Form Container -->
        <div class="container py-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        Create New Paperwork
                    </h4>

                    <form action="create_paperwork_user.php" method="post" class="needs-validation" novalidate>
                        <!-- Your existing form fields here with updated styling -->
                        <div class="row mb-4">
                            <label for="name" class="col-sm-3 col-form-label fw-medium">Name:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="name" 
                                    name="name" 
                                    value="<?php echo htmlspecialchars($loggedInUserName); ?>" 
                                    readonly 
                                    required>
                            </div>
                        </div>

                        <!-- Add similar styling to other form fields -->
                        <div class="row mb-4">
                            <label for="user_id" class="col-sm-3 col-form-label fw-medium">User ID:</label>
                            <div class="col-sm-9">
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="id" 
                                    name="id" 
                                    value="<?php echo htmlspecialchars($loggedInUserId); ?>" 
                                    readonly 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="ppw_id" class="col-sm-3 col-form-label fw-medium">Paperwork ID:</label>
                            <div class="col-sm-9">
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="ppw_id" 
                                    name="ppw_id" 
                                    value="<?php echo htmlspecialchars($newPpwId); ?>" 
                                    readonly 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="ppw_type" class="col-sm-3 col-form-label fw-medium">Paperwork Type:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="ppw_type" 
                                    name="ppw_type" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="session" class="col-sm-3 col-form-label fw-medium">Session:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="session" 
                                    name="session" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="project_name" class="col-sm-3 col-form-label fw-medium">Paperwork Name:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="project_name" 
                                    name="project_name" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="objective" class="col-sm-3 col-form-label fw-medium">Objective:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control form-control-lg shadow-sm" 
                                    id="objective" 
                                    name="objective" 
                                    rows="4" 
                                    required></textarea>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="purpose" class="col-sm-3 col-form-label fw-medium">Purpose:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control form-control-lg shadow-sm" 
                                    id="purpose" 
                                    name="purpose" 
                                    rows="4" 
                                    required></textarea>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="background" class="col-sm-3 col-form-label fw-medium">Background:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control form-control-lg shadow-sm" 
                                    id="background" 
                                    name="background" 
                                    rows="4" 
                                    required></textarea>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="aim" class="col-sm-3 col-form-label fw-medium">Aim:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control form-control-lg shadow-sm" 
                                    id="aim" 
                                    name="aim" 
                                    rows="4" 
                                    required></textarea>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="startdate" class="col-sm-3 col-form-label fw-medium">Start Date:</label>
                            <div class="col-sm-9">
                                <input type="date" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="startdate" 
                                    name="startdate" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="end_date" class="col-sm-3 col-form-label fw-medium">End Date:</label>
                            <div class="col-sm-9">
                                <input type="date" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="end_date" 
                                    name="end_date" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="pgrm_involve" class="col-sm-3 col-form-label fw-medium">Program Involve:</label>
                            <div class="col-sm-9">
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="pgrm_involve" 
                                    name="pgrm_involve" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="external_sponsor" class="col-sm-3 col-form-label fw-medium">External Sponsor:</label>
                            <div class="col-sm-9">
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="external_sponsor" 
                                    name="external_sponsor" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="sponsor_name" class="col-sm-3 col-form-label fw-medium">Sponsor Name:</label>
                            <div class="col-sm-9">
                                <input type="text" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="sponsor_name" 
                                    name="sponsor_name" 
                                    required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label for="english_lang_req" class="col-sm-3 col-form-label fw-medium">English Language Required:</label>
                            <div class="col-sm-9">
                                <input type="number" 
                                    class="form-control form-control-lg shadow-sm" 
                                    id="english_lang_req" 
                                    name="english_lang_req" 
                                    required>
                            </div>
                        </div>

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
</body>
</html>
