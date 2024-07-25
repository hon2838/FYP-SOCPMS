<?php
session_start();
include 'dbconnect.php'; // Adjust the path if necessary

// Initialize variables for form validation
$name = $password = $confirmPassword = $email = '';
$name_err = $password_err = $confirmPassword_err = '';

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a name.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM tbl_users WHERE name = :name";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters to the prepared statement
            $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);

            // Set parameters
            $param_name = trim($_POST["name"]);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if name already exists
                if ($stmt->rowCount() == 1) {
                    $name_err = "This name is already taken.";
                } else {
                    $name = trim($_POST["name"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = "Please enter a password.";
        echo $password_err;
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_err = "Password must have at least 6 characters.";
        echo $password_err;
    } else {
        $password = trim($_POST['password']);
        
    }

    // Validate confirm password
    if (empty(trim($_POST["confirmPassword"]))) {
        $confirmPassword_err = "Please confirm password.";
    } else {
        $confirmPassword = trim($_POST['confirmPassword']);
        if (empty($password_err) && ($password != $confirmPassword)) {
            $confirmPassword_err = "Password did not match.";
        }
    }

    // Check input errors before inserting into database
    $sql = "INSERT INTO tbl_users (name,email,password) VALUES (:name,:email, :password)";
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters to the prepared statement
        $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);
        $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
        $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);

        // Set parameters
        $param_name = $name;
        $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
        $param_email = trim($_POST['email']); // Ensure you have this line to set $email

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect to login page
            header("location: index.php");
            exit;
        } else {
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        unset($stmt);
    }

    // Close connection
    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOCPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
  </head>

  <body>
    
  <body>
    <div class="d-lg-flex half">
      <div class="bg order-1 order-md-2" style="background-image: url('login-bg.png');"></div>
      <div class="contents order-2 order-md-1">
        <div class="container">
          <div class="row align-items-center justify-content-center">
            <div class="col-md-7">
              <h3>Register to <strong>SOC-PMS</strong></h3>
              <p class="mb-4">Register with your relavant details to user into School of Computing Paperwork Management System.</p>
              <form action="#" method="post">
                <div class="form-group first">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" placeholder="your-name" id="name" name="name">
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" placeholder="your-email@gmail.com" id="email" name="email">
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" placeholder="Your Password" id="password" name="password">
                </div>
                <div class="form-group mb-3">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" class="form-control" placeholder="Confirm Your Password" id="confirmPassword" name="confirmPassword">
                </div>
                <input type="submit" value="Register" class="btn btn-block btn-primary">
                </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>

