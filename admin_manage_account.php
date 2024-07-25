<?php
    session_start();
    if (!isset($_SESSION['email']) && !isset($_SESSION['password']) && !isset($_SESSION['usertype']) && $_SESSION['usertype'] != "Admin") {
        header('Location: index.php');
        exit;
    }

    include 'dbconnect.php';
    $sqlloadpatients = "SELECT * FROM tbl_users";
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();
    $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows=$stmt->fetchAll();

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

    if (isset($_GET['search_query']) && isset($_GET['search_option'])) {
      $search_query = $_GET['search_query'];
      $search_option = $_GET['search_option'];

      if ($search_option == 'name') {
        $sqlloadpatients = "SELECT * FROM tbl_users WHERE name LIKE ?";
        $stmt = $conn->prepare($sqlloadpatients);
        $stmt->execute(['%'.$search_query.'%']);
        $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $rows=$stmt->fetchAll();
      } else if ($search_option == 'email') {
        $sqlloadpatients = "SELECT * FROM tbl_users WHERE email LIKE ?";
        $stmt = $conn->prepare($sqlloadpatients);
        $stmt->execute(['%'.$search_query.'%']);
        $results = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $rows=$stmt->fetchAll();
      }

      if (count($rows) == 0) {
        echo "<script>alert('No results found.');</script>";
        echo "<script>window.location.href='main.php';</script>";
      }
    }

    $results_per_pages = 10;
    if (isset($_GET['pageno'])) {
        $pageno = (int)$_GET['pageno'];
        $page_first_result = ($pageno - 1) * $results_per_pages;
    } else {
        $pageno = 1;
        $page_first_result = 0;
    } 
    
    
    
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();
    
    $number_of_results = $stmt->rowCount();
    $number_of_pages = ceil($number_of_results / $results_per_pages);
    $sqlloadpatients = $sqlloadpatients . " LIMIT " . $page_first_result . ',' . $results_per_pages;
    $stmt = $conn->prepare($sqlloadpatients);
    $stmt->execute();

    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $rows=$stmt->fetchAll();
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
        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="create_paperwork.php" class="nav-link">Create New Paperwork</a></li>
        <li class="nav-item"><a href="admin_manage_account.php" class="nav-link active" aria-current="page">Manage Account</a></li>
        <li class="nav-item"><a href="#" data-bs-toggle="modal" data-bs-target="#modal1" class="nav-link">About</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
    </ul>
</header>

        <div class="container mb-2">
            <div class="row">
              <div>
                <h2>Manage Accounts</h2>
                <p>Your can Manage your accounts here.</p>   
              </div>
            </div>
        </div>

        <div class="container ">
            <div class="row">
              <div>
                <h2>Accounts</h2>
                <table class="table table-striped table-bordered table-hover d-none d-md-block">
                    <thead>
                      <tr>
                        <th scope="col">Staff ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Password</th>
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
                            <td>
                              <div class="input-group">
                                <input type="password" class="form-control" value="<?php echo $row['password']; ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="showPasswordBtn">Show</button>
                              </div>
                            </td>
                            <td><?php echo $row['usertype']; ?></td>
                            <td>
                              <a href="admin_manage_account.php?submit=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                              <button type="button" class="btn btn-primary editUserBtn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>" data-email="<?php echo $row['email']; ?>" data-usertype="<?php echo $row['usertype']; ?>">Edit</button>
                            </td>
                        </tr>
                      <?php } ?>
                        <tr>
                            <form action="add_user.php" method="post">
                                <td>#</td>
                                <td><input type="text" name="name" required></td>
                                <td><input type="email" name="email" required></td>
                                <td><input type="password" name="password" required></td>
                                <td>
                                    <select name="usertype" required>
                                        <option value="admin">Admin</option>
                                        <option value="normal">Normal User</option>
                                    </select>
                                </td>
                                <td><button type="submit" class="btn btn-success">Add User</button></td>
                            </form>
                        </tr>
                    </tbody>
                </table>

                
                    <div class="row d-block d-sm-block d-md-none">
                      <?php foreach ($rows as $row) { ?>
                        <div class="col-md-6">
                            <div class="card mb-3">
                              <div class="card-body">
                                <h5 class="card-title"><?php echo $row['u_id']; ?></h5>
                                <p class="card-text"><?php echo $row['u_Name']; ?></p>
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
              for ($page=1;$page<=$number_of_pages;$page++) {
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
                          <div class="mb-3">
                              <label for="editUserType" class="form-label">User Type</label>
                              <select class="form-select" id="editUserType" name="usertype" required>
                                  <option value="admin">Admin</option>
                                  <option value="normal">Normal User</option>
                              </select>
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

      </body>

      <script>
      // JavaScript to populate the modal with user data
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
      </script>
      
</html>
