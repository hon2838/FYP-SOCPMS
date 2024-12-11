<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: index.php');
    exit;
}

include 'dbconnect.php';

// Get user details
$email = $_SESSION['email'];
$userQuery = "SELECT id, name, user_type FROM tbl_users WHERE email = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->execute([$email]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get paperwork details
if (isset($_GET['ppw_id'])) {
    $ppw_id = $_GET['ppw_id'];
    $sql = "SELECT * FROM tbl_ppw WHERE ppw_id = ? AND id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ppw_id, $user['id']]);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paperwork) {
        echo "<script>alert('Paperwork not found or unauthorized.'); window.location.href='user_dashboard.php';</script>";
        exit;
    }

    // Check if paperwork is already approved
    if ($paperwork['status'] == 1) {
        echo "<script>alert('Cannot edit approved paperwork.'); window.location.href='user_dashboard.php';</script>";
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

    // Update paperwork
    $sql = "UPDATE tbl_ppw SET 
            ref_number = ?,
            project_name = ?,
            ppw_type = ?,
            session = ?,
            document_path = ?
            WHERE ppw_id = ? AND id = ?";
            
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([
        trim($_POST['ref_number']),
        trim($_POST['project_name']),
        trim($_POST['ppw_type']),
        trim($_POST['session']),
        $fileName,
        $ppw_id,
        $user['id']
    ])) {
        echo "<script>alert('Paperwork updated successfully.'); window.location.href='user_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating paperwork.');</script>";
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
    <!-- Reuse the same navbar as create_paperwork.php -->
    
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

                        <!-- Other fields similar to create_paperwork.php but with values from $paperwork -->
                        
                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2 mt-5">
                            <a href="user_dashboard.php" class="btn btn-light btn-lg px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

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