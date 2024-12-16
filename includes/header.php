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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
                        <button class="nav-link dropdown-toggle btn btn-link" 
                                type="button" 
                                id="navbarDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst(htmlspecialchars($_SESSION['user_type'])); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="nav-link px-3" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
                                    <i class="fas fa-cog me-1"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <main class="pt-5 mt-5">

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="settingsForm" method="POST" action="update_settings.php">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-cog text-primary me-2"></i>
                        System Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <!-- Account Settings Section -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Account Settings</h6>
                        <div class="list-group">
                            <a href="<?php echo $_SESSION['user_type'] === 'admin' ? 'admin_manage_account.php' : 'user_manage_account.php'; ?>" 
                               class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-user-circle me-3"></i>
                                Profile Settings
                            </a>
                            <button type="button" 
                                    class="list-group-item list-group-item-action d-flex align-items-center"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#changePasswordModal">
                                <i class="fas fa-key me-3"></i>
                                Change Password
                            </button>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Notification Settings</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" 
                                   <?php echo isset($_SESSION['settings']['email_notifications']) && $_SESSION['settings']['email_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="emailNotifications">
                                Email Notifications
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="browserNotifications" name="browser_notifications"
                                   <?php echo isset($_SESSION['settings']['browser_notifications']) && $_SESSION['settings']['browser_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="browserNotifications">
                                Browser Notifications
                            </label>
                        </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Display Settings</h6>
                        <div class="mb-3">
                            <label class="form-label">Theme</label>
                            <select class="form-select" name="theme" id="themeSelect">
                                <option value="light" <?php echo isset($_SESSION['settings']['theme']) && $_SESSION['settings']['theme'] === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                                <option value="dark" <?php echo isset($_SESSION['settings']['theme']) && $_SESSION['settings']['theme'] === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                <option value="system" <?php echo isset($_SESSION['settings']['theme']) && $_SESSION['settings']['theme'] === 'system' ? 'selected' : ''; ?>>System Default</option>
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="compactView" name="compact_view"
                                   <?php echo isset($_SESSION['settings']['compact_view']) && $_SESSION['settings']['compact_view'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="compactView">
                                Compact View
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add this JavaScript at the bottom of the file -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Settings form submission
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
            
            fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Apply theme changes immediately
                    applyTheme(document.getElementById('themeSelect').value);
                    
                    // Show success message
                    alert('Settings updated successfully');
                    
                    // Properly close modal and remove backdrop
                    modal.hide();
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    // Apply compact view if enabled
                    document.body.classList.toggle('compact-view', data.settings.compact_view);
                    
                    // Refresh page if needed (optional)
                    // location.reload();
                } else {
                    alert('Error updating settings');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating settings');
                
                // Ensure modal and backdrop are cleaned up on error
                modal.hide();
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
        });
    }

    // Theme switching function
    function applyTheme(theme) {
        if (theme === 'system') {
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        } else {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }

        // Force re-render of some elements
        document.body.style.display = 'none';
        requestAnimationFrame(() => {
            document.body.style.display = '';
            // Force card re-render
            document.querySelectorAll('.card').forEach(card => {
                card.style.display = 'none';
                requestAnimationFrame(() => {
                    card.style.display = '';
                });
            });
        });
    }

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (localStorage.getItem('theme') === 'system') {
            applyTheme('system');
        }
    });

    // Initialize theme on page load
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Theme selector change event
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect) {
        themeSelect.addEventListener('change', (e) => {
            applyTheme(e.target.value);
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Settings form submission
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
            
            fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Apply theme changes immediately
                    if (data.theme) {
                        applyTheme(data.theme);
                    }
                    
                    // Apply compact view if it exists in response
                    if (typeof data.settings !== 'undefined' && 
                        typeof data.settings.compact_view !== 'undefined') {
                        document.body.classList.toggle('compact-view', data.settings.compact_view);
                    }
                    
                    // Show success message
                    alert('Settings updated successfully');
                    
                    // Properly close modal and remove backdrop
                    if (modal) {
                        modal.hide();
                        document.body.classList.remove('modal-open');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                    }
                } else {
                    alert(data.error || 'Error updating settings');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating settings');
                
                // Cleanup on error
                if (modal) {
                    modal.hide();
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                }
            });
        });
    }
});

// Theme switching function
function applyTheme(theme) {
    if (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    }

    // Force re-render of some elements
    document.body.style.display = 'none';
    document.body.offsetHeight; // Trigger reflow
    document.body.style.display = '';
}
</script>
</main>
</body>
</html>