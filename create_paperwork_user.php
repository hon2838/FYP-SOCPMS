<?php
    session_start();
    if (!(isset($_SESSION['email']) && $_SESSION['user_type'] != 'user')) {
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
        <li class="nav-item"><a href="user_dashboard.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="create_paperwork_user.php" class="nav-link active" aria-current="page">Create New Paperwork</a></li>
        <li class="nav-item"><a href="user_manage_account.php" class="nav-link">Manage Account</a></li>
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
    <form action="create_paperwork.php" method="post">
        <div class="row mb-3">
            <label for="name" class="col-sm-3 col-form-label">Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($loggedInUserName); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="user_id" class="col-sm-3 col-form-label">User ID:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($loggedInUserId); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="ppw_id" class="col-sm-3 col-form-label">Paperwork ID:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="ppw_id" name="ppw_id" value="<?php echo htmlspecialchars($newPpwId); ?>" readonly required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="ppw_type" class="col-sm-3 col-form-label">Paperwork Type:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="ppw_type" name="ppw_type" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="session" class="col-sm-3 col-form-label">Session:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="session" name="session" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="project_name" class="col-sm-3 col-form-label">Paperwork Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="project_name" name="project_name" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="objective" class="col-sm-3 col-form-label">Objective:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="objective" name="objective" rows="4" required></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="purpose" class="col-sm-3 col-form-label">Purpose:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="purpose" name="purpose" rows="4" required></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="background" class="col-sm-3 col-form-label">Background:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="background" name="background" rows="4" required></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="aim" class="col-sm-3 col-form-label">Aim:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="aim" name="aim" rows="4" required></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="startdate" class="col-sm-3 col-form-label">Start Date:</label>
            <div class="col-sm-9">
                <input type="date" class="form-control" id="startdate" name="startdate" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="end_date" class="col-sm-3 col-form-label">End Date:</label>
            <div class="col-sm-9">
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="pgrm_involve" class="col-sm-3 col-form-label">Program Involve:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="pgrm_involve" name="pgrm_involve" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="external_sponsor" class="col-sm-3 col-form-label">External Sponsor:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="external_sponsor" name="external_sponsor" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="sponsor_name" class="col-sm-3 col-form-label">Sponsor Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="sponsor_name" name="sponsor_name" required>
            </div>
        </div>
        <div class="row mb-3">
            <label for="english_lang_req" class="col-sm-3 col-form-label">English Language Required:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" id="english_lang_req" name="english_lang_req" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>


</html>
