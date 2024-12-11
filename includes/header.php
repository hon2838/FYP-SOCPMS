<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo isset($_SESSION['user_type']) ? ($_SESSION['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php') : 'index.php'; ?>">
                <i class="fas fa-file-alt text-primary me-2"></i>
                <span class="fw-bold">SOC Paperwork System</span>
            </a>
            
            <?php if(isset($_SESSION['email'])): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === ($_SESSION['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php') ? 'active' : ''; ?> px-3" 
                           href="<?php echo $_SESSION['user_type'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'create_paperwork.php' ? 'active' : ''; ?> px-3" 
                           href="create_paperwork.php">
                            <i class="fas fa-plus me-1"></i> New Paperwork
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === ($_SESSION['user_type'] === 'admin' ? 'admin_manage_account.php' : 'user_manage_account.php') ? 'active' : ''; ?> px-3" 
                           href="<?php echo $_SESSION['user_type'] === 'admin' ? 'admin_manage_account.php' : 'user_manage_account.php'; ?>">
                            <i class="fas fa-users me-1"></i> 
                            <?php echo $_SESSION['user_type'] === 'admin' ? 'Manage Accounts' : 'Manage Account'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="#" data-bs-toggle="modal" data-bs-target="#modal1">
                            <i class="fas fa-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst(htmlspecialchars($_SESSION['user_type'])); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo $_SESSION['user_type'] === 'admin' ? 'admin_manage_account.php' : 'user_manage_account.php'; ?>">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <main class="pt-5 mt-5">