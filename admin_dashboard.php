<?php
    session_start();
    if (!(isset($_SESSION['email']) && $_SESSION['user_type'] != 'admin')) {
      header('Location: index.php');
      exit;
    }
  
    // Include database connection
    include 'dbconnect.php';
  
    // Get user type based on email from database
    $email = $_SESSION['email'];
// Load patients
$sqlloadpatients = "SELECT * FROM tbl_ppw";
$stmt = $conn->prepare($sqlloadpatients);
$stmt->execute();
$results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
$rows = $stmt->fetchAll();

// Handle delete request
if (isset($_GET['submit']) && $_GET['submit'] == 'delete') {
    $ppw_id = $_GET['ppw_ppw_id'];
    try {
        $sqldeletepatient = "DELETE FROM tbl_ppw WHERE ppw_id = ?";
        $stmt = $conn->prepare($sqldeletepatient);
        $stmt->execute([$ppw_id]);
        echo "<script>alert('Patient deleted successfully.');</script>";
        echo "<script>window.location.href='admin_dashboard.php';</script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle search request
if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
    $search_query = $_GET['search_query'];
    $search_option = $_GET['search_option'];

    if ($search_option == 'name') {
        $sqlloadpatients = "SELECT * FROM tbl_ppw WHERE name LIKE ?";
    } else if ($search_option == 'email') {
        $sqlloadpatients = "SELECT * FROM tbl_ppw WHERE email LIKE ?";
    }

    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute(['%' . $search_query . '%']);
    $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows = $stmt->fetchAll();

    if (count($rows) == 0) {
        echo "<script>alert('No results found.');</script>";
        echo "<script>window.location.href='main.php';</script>";
    }
}

// Pagination
$results_per_page = 10;
if (isset($_GET['pageno'])) {
    $pageno = (int)$_GET['pageno'];
    $page_first_result = ($pageno - 1) * $results_per_page;
} else {
    $pageno = 1;
    $page_first_result = 0;
}

$stmt = $conn->prepare($sqlloadpatients);
$stmt->execute();

$number_of_results = $stmt->rowCount();
$number_of_pages = ceil($number_of_results / $results_per_page);
$sqlloadpatients = $sqlloadpatients . " LIMIT " . $page_first_result . ',' . $results_per_page;
$stmt = $conn->prepare($sqlloadpatients);
$stmt->execute();

$results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOC Paperwork Management System</title>
    <link rel="stylesheet" href="mystyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
<header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom" style="background-color: #f5f5f5;">
    <a href="main.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
        <span class="fs-4 ms-4">SOC Paperwork Management System</span>
    </a>

    <ul class="nav nav-pills">
        <li class="nav-item"><a href="main.php" class="nav-link active" aria-current="page">Home</a></li>
        <li class="nav-item"><a href="create_paperwork.php" class="nav-link">Create New Paperwork</a></li>
        <li class="nav-item"><a href="admin_manage_account.php" class="nav-link">Manage Account</a></li>
        <li class="nav-item"><a href="#" data-bs-toggle="modal" data-bs-target="#modal1" class="nav-link">About</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
    </ul>
</header>

<div class="container mb-2">
    <div class="row">
        <div>
            <h2>Welcome to School of Computing Paperwork Management System</h2>
            <p>School of Computing Paperwork Management System is a web application that helps you to manage your paperworks.</p>   
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div>
            <h2>Paperworks to Approve</h2>
            <table class="table table-striped table-bordered table-hover d-none d-md-block">
                <thead>
                    <tr>
                        <th scope="col">Paperwork ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Staff ID</th>
                        <th scope="col">Session</th>
                        <th scope="col">Project Name</th>
                        <th scope="col">Project Date</th>
                        <th scope="col">Submission Time</th>
                        <th scope="col">Status</th>
                        <th scope="col">Note</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) { ?>
                        <tr>
                            <th scope="row"><?php echo $row['ppw_id']; ?></th>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['session']; ?></td>
                            <td><?php echo $row['project_name']; ?></td>
                            <td><?php echo $row['project_date']; ?></td>
                            <td><?php echo $row['submission_time']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['note']; ?></td>
                            <td>
                                <a href="viewpaperworkadmin.php?ppw_id=<?php echo $row['ppw_id']; ?>" class="btn btn-primary">View</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div class="row d-block d-sm-block d-md-none">
                <?php foreach ($rows as $row) { ?>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['ppw_id']; ?></h5>
                                <p class="card-text"><?php echo $row['name']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center">
    <?php
    for ($page = 1; $page <= $number_of_pages; $page++) {
        echo '<a href="main.php?pageno=' . $page . '" class="btn btn-primary">' . $page . '</a>';
    }
    ?>
</div>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <p class="text-center">School of Computing Paperwork Management System © 2024</p>
            </div>
        </div>
    </div>
</footer>

<div class="modal fade" id="modal1" tabindex="-1" aria-labelledby="modal1Title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">About Us</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>School of Computing Paperwork Management System is a web application that helps you to manage your paperworks.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php foreach ($rows as $row) { ?>
    <div class="modal fade" id="patientModal<?php echo $row['ppw_id']; ?>" tabindex="-1" aria-labelledby="patientModalLabel<?php echo $row['ppw_id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientModalLabel<?php echo $row['ppw_id']; ?>">Your Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><b>Your ID:</b> <?php echo $row['ppw_id']; ?></p>
                    <p><b>Name:</b> <?php echo $row['name']; ?></p>
                    <p><b>Email:</b> <?php echo $row['email']; ?></p>
                    <p><b>Phone:</b> <?php echo $row['phone']; ?></p>
                    <p><b>Address:</b> <?php echo $row['address']; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>
