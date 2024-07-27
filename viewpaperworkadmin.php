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

    // Redirect or display a message
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
<header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom" style="background-color: #f5f5f5;">
    <a href="main.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
        <span class="fs-4 ms-4">SOC Paperwork Management System</span>
    </a>

    <ul class="nav nav-pills">
        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="create_paperwork.php" class="nav-link active" aria-current="page">Create New Paperwork</a></li>
        <li class="nav-item"><a href="admin_manage_account.php" class="nav-link">Manage Account</a></li>
        <li class="nav-item"><a href="#" data-bs-toggle="modal" data-bs-target="#modal1" class="nav-link">About</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
    </ul>
</header>

<div class="container mb-2">
    <div class="row">
        <div>
            <h2>Create a New Paperwork</h2>
            <p>Please fill out the form below to create a new paperwork.</p>
        </div>
    </div>
</div>

<div class="container">
    <form action="viewpaperworkadmin.php" method="post">
        <div class="row mb-3">
            <label for="name" class="col-sm-3 col-form-label">Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($paperwork['name']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="user_id" class="col-sm-3 col-form-label">User ID:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($paperwork['id']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="ppw_id" class="col-sm-3 col-form-label">Paperwork ID:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="ppw_id" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="ppw_type" class="col-sm-3 col-form-label">Paperwork Type:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="ppw_type" name="ppw_type" value="<?php echo htmlspecialchars($paperwork['ppw_type']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="session" class="col-sm-3 col-form-label">Session:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="session" name="session" value="<?php echo htmlspecialchars($paperwork['session']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="project_name" class="col-sm-3 col-form-label">Paperwork Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="project_name" name="project_name" value="<?php echo htmlspecialchars($paperwork['name']); ?>"readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="objective" class="col-sm-3 col-form-label">Objective:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="objective" name="objective" rows="4" readonly required><?php echo htmlspecialchars($paperwork['objective']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="purpose" class="col-sm-3 col-form-label">Purpose:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="purpose" name="purpose" rows="4" readonly required><?php echo htmlspecialchars($paperwork['purpose']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="background" class="col-sm-3 col-form-label">Background:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="background" name="background" rows="4" readonly required><?php echo htmlspecialchars($paperwork['background']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="aim" class="col-sm-3 col-form-label">Aim:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="aim" name="aim" rows="4" readonly required><?php echo htmlspecialchars($paperwork['aim']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="startdate" class="col-sm-3 col-form-label">Start Date:</label>
            <div class="col-sm-9">
                <input type="date" class="form-control" id="startdate" name="startdate" value="<?php echo htmlspecialchars($paperwork['startdate']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="end_date" class="col-sm-3 col-form-label">End Date:</label>
            <div class="col-sm-9">
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($paperwork['end_date']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="pgrm_involve" class="col-sm-3 col-form-label">Program Involve:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="pgrm_involve" name="pgrm_involve" value="<?php echo htmlspecialchars($paperwork['pgrm_involve']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="external_sponsor" class="col-sm-3 col-form-label">External Sponsor:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="external_sponsor" name="external_sponsor" value="<?php echo htmlspecialchars($paperwork['external_sponsor']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="sponsor_name" class="col-sm-3 col-form-label">Sponsor Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="sponsor_name" name="sponsor_name" value="<?php echo htmlspecialchars($paperwork['sponsor_name']); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="english_lang_req" class="col-sm-3 col-form-label">English Language Required:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="english_lang_req" name="english_lang_req" value="<?php echo htmlspecialchars($paperwork['english_lang_req']); ?>" readonly required>
            </div>
        </div>

        <input type="hidden" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>">
        <button type="submit" name="action" value="approve" class="btn btn-primary mb-2 mr-2">Approve</button>
        <button type="submit" name="action" value="not_approve" class="btn btn-danger mb-2">Not Approved</button>
    </form>
</div>
</body>
</html>
