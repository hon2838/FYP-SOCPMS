<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 1) {
    header('Location: index.php');
    exit;
}

include 'dbconnect.php';

if (isset($_GET['ppw_id'])) {
    $ppw_id = $_GET['ppw_id'];
    $sql = "SELECT * FROM tbl_ppw WHERE ppw_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ppw_id]);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "<script>alert('No Paperwork ID provided.'); window.location.href='admin_dashboard.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Paperwork Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Paperwork Details</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Paperwork ID: <?php echo htmlspecialchars($paperwork['ppw_id']); ?></h5>
                <p class="card-text"><strong>Name:</strong> <?php echo htmlspecialchars($paperwork['name']); ?></p>
                <p class="card-text"><strong>Staff ID:</strong> <?php echo htmlspecialchars($paperwork['id']); ?></p>
                <p class="card-text"><strong>Reference:</strong> <?php echo htmlspecialchars($paperwork['reference']); ?></p>
                <p class="card-text"><strong>Session:</strong> <?php echo htmlspecialchars($paperwork['session']); ?></p>
                <p class="card-text"><strong>Project Name:</strong> <?php echo htmlspecialchars($paperwork['project_name']); ?></p>
                <p class="card-text"><strong>Project Date:</strong> <?php echo htmlspecialchars($paperwork['project_date']); ?></p>
                <p class="card-text"><strong>Submission Time:</strong> <?php echo htmlspecialchars($paperwork['submission_time']); ?></p>
                <p class="card-text"><strong>Status:</strong> <?php echo htmlspecialchars($paperwork['status']); ?></p>
                <p class="card-text"><strong>Note:</strong> <?php echo htmlspecialchars($paperwork['note']); ?></p>
                <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>