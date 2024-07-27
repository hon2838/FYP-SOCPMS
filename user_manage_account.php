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

    if (isset($_GET['submit']) && $_GET['submit'] == 'delete') {
        $id = $_GET['id'];
        try {
            $sqldeletepatient = "DELETE FROM tbl_users WHERE id = ?";
            $stmt = $conn->prepare($sqldeletepatient);
            $stmt->execute([$id]);
            echo "<script>alert('User deleted successfully.');</script>";
            echo "<script>window.location.href='admin_manage_account.php';</script>";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    $results_per_page = 10;
    $pageno = isset($_GET['pageno']) ? (int)$_GET['pageno'] : 1;
    $page_first_result = ($pageno - 1) * $results_per_page;

    if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
        $search_query = $_GET['search_query'];
        $search_option = $_GET['search_option'];

        if ($search_option == 'name') {
            $sqlloadpatients = "SELECT * FROM tbl_users WHERE name LIKE ?";
            $stmt = $conn->prepare($sqlloadpatients);
            $stmt->execute(['%'.$search_query.'%']);
        } else if ($search_option == 'email') {
            $sqlloadpatients = "SELECT * FROM tbl_users WHERE email LIKE ?";
            $stmt = $conn->prepare($sqlloadpatients);
            $stmt->execute(['%'.$search_query.'%']);
        }

        $number_of_results = $stmt->rowCount();
        if ($number_of_results == 0) {
            echo "<script>alert('No results found.');</script>";
            echo "<script>window.location.href='main.php';</script>";
        }
    } else {
        $sqlloadpatients = "SELECT * FROM tbl_users WHERE email = ?";
        $stmt = $conn->prepare($sqlloadpatients);
        $stmt->execute([$email]);
        $number_of_results = $stmt->rowCount();
    }

    $number_of_pages = ceil($number_of_results / $results_per_page);

    $sqlloadpatients = $sqlloadpatients . " LIMIT " . $page_first_result . ',' . $results_per_page;
    $stmt = $conn->prepare($sqlloadpatients);
    if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
        $stmt->execute(['%'.$search_query.'%']);
    } else {
        $stmt->execute([$email]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <li class="nav-item"><a href="user_dashboard.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="create_paperwork_user.php" class="nav-link">Create New Paperwork</a></li>
        <li class="nav-item"><a href="user_manage_account.php" class="nav-link active" aria-current="page">Manage Account</a></li>
        <li class="nav-item"><a href="#" data-bs-toggle="modal" data-bs-target="#modal1" class="nav-link">About</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
    </ul>
</header>

<div class="container mb-2">
    <div class="row">
        <div>
            <h2>Manage Account</h2>
            <p>You can manage your account here.</p>   
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div>
            <h2>Accounts</h2>
            <table class="table table-striped table-bordered table-hover d-none d-md-block">
                <thead>
                    <tr>
                        <th scope="col">Staff ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">User Type</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) { ?>
                    <tr>
                        <th scope="row"><?php echo $row['id']; ?></th>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['user_type']; ?></td>
                        <td>
                            <button type="button" class="btn btn-primary editUserBtn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>" data-email="<?php echo $row['email']; ?>" data-usertype="<?php echo $row['user_type']; ?>">Edit</button>
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
                            <h5 class="card-title">Staff ID: <?php echo $row['id']; ?></h5>
                            <p class="card-text">Name: <?php echo $row['name']; ?></p>
                            <p class="card-text">Email: <?php echo $row['email']; ?></p>
                            <p class="card-text">User Type: <?php echo $row['user_type']; ?></p>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center">
    <?php for ($page=1; $page<=$number_of_pages; $page++) {
        echo '<a href="main.php?pageno=' . $page . '" class="btn btn-primary">' . $page . '</a>';
    } ?>
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

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit_user.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden input to store user ID -->
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-3">
                        <label for="editUserName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editUserName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editUserEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="editUserPassword" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
  document.querySelectorAll('.editUserBtn').forEach(item => {
      item.addEventListener('click', event => {
          const userId = item.getAttribute('data-id');
          const userName = item.getAttribute('data-name');
          const userEmail = item.getAttribute('data-email');
          const userType = item.getAttribute('data-usertype');

          document.getElementById('editUserId').value = userId;
          document.getElementById('editUserName').value = userName;
          document.getElementById('editUserEmail').value = userEmail;
          document.getElementById('editUserType').value = userType;
      });
  });

  document.querySelectorAll('.togglePasswordBtn').forEach(item => {
      item.addEventListener('click', event => {
          const passwordInput = item.previousElementSibling;
          if (passwordInput.type === 'password') {
              passwordInput.type = 'text';
              item.textContent = 'Hide';
          } else {
              passwordInput.type = 'password';
              item.textContent = 'Show';
          }
      });
  });
</script>
</body>
</html>
