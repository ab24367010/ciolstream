<?php
// admin/settings.php - Admin Settings Page
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Get admin details
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error_message = 'All password fields are required.';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } elseif (strlen($new_password) < 8) {
                    $error_message = 'New password must be at least 8 characters long for security.';
                } else {
                    // Verify current password
                    if (password_verify($current_password, $admin['password'])) {
                        // Update password
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");

                        if ($stmt->execute([$new_password_hash, $admin_id])) {
                            $success_message = 'Admin password changed successfully.';

                            // Log admin activity
                            logAdminActivity($pdo, $admin_id, 'profile_update', 'admin', $admin_id, 'Admin password changed');
                        } else {
                            $error_message = 'Failed to update password. Please try again.';
                        }
                    } else {
                        $error_message = 'Current password is incorrect.';
                    }
                }
                break;

            case 'change_username':
                $new_username = trim($_POST['new_username'] ?? '');
                $password_confirm = $_POST['password_confirm'] ?? '';

                if (empty($new_username) || empty($password_confirm)) {
                    $error_message = 'Username and password confirmation are required.';
                } elseif (strlen($new_username) < 3) {
                    $error_message = 'Username must be at least 3 characters long.';
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
                    $error_message = 'Username can only contain letters, numbers, and underscores.';
                } else {
                    // Verify password
                    if (password_verify($password_confirm, $admin['password'])) {
                        // Check if username already exists
                        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                        $stmt->execute([$new_username, $admin_id]);

                        if ($stmt->fetch()) {
                            $error_message = 'Admin username already exists. Please choose a different one.';
                        } else {
                            // Update username
                            $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");

                            if ($stmt->execute([$new_username, $admin_id])) {
                                $success_message = 'Admin username changed successfully.';
                                $admin['username'] = $new_username; // Update local variable

                                // Log admin activity
                                logAdminActivity($pdo, $admin_id, 'profile_update', 'admin', $admin_id, "Admin username changed to: $new_username");
                            } else {
                                $error_message = 'Failed to update username. Please try again.';
                            }
                        }
                    } else {
                        $error_message = 'Password is incorrect.';
                    }
                }
                break;

            case 'change_email':
                $new_email = trim($_POST['new_email'] ?? '');
                $password_confirm = $_POST['password_confirm_email'] ?? '';

                if (empty($new_email) || empty($password_confirm)) {
                    $error_message = 'Email and password confirmation are required.';
                } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Please enter a valid email address.';
                } else {
                    // Verify password
                    if (password_verify($password_confirm, $admin['password'])) {
                        // Check if email already exists
                        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
                        $stmt->execute([$new_email, $admin_id]);

                        if ($stmt->fetch()) {
                            $error_message = 'Email address already exists. Please choose a different one.';
                        } else {
                            // Update email
                            $stmt = $pdo->prepare("UPDATE admins SET email = ? WHERE id = ?");

                            if ($stmt->execute([$new_email, $admin_id])) {
                                $success_message = 'Admin email changed successfully.';
                                $admin['email'] = $new_email; // Update local variable

                                // Log admin activity
                                logAdminActivity($pdo, $admin_id, 'profile_update', 'admin', $admin_id, "Admin email changed to: $new_email");
                            } else {
                                $error_message = 'Failed to update email. Please try again.';
                            }
                        }
                    } else {
                        $error_message = 'Password is incorrect.';
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - CiolStream</title>
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../img/apple-touch-icon.png">
    <link rel="manifest" href="../site.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CiolStream">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <a href="dashboard.php">
                        <img src="../img/logo.png" alt="CiolStream" style="height: 50px; width: auto;">
                    </a>
                </div>
                <div class="nav-links">
                    <span class="welcome">Admin: <?php echo htmlspecialchars($admin['username']); ?></span>
                    <a href="dashboard.php" class="btn">Dashboard</a>
                    <a href="../public/index.php" class="btn">View Site</a>
                    <a href="logout.php" class="btn">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h2>Admin Settings</h2>
                <div class="admin-info">
                    <span class="role-badge"><?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?></span>
                    <span style="margin-left: 1rem;"><?php echo htmlspecialchars($admin['email']); ?></span>
                </div>
            </div>

            <div class="settings-section">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="settings-tabs">
                    <button class="tab-button active" onclick="showTab('account')">Account Info</button>
                    <button class="tab-button" onclick="showTab('password')">Change Password</button>
                    <button class="tab-button" onclick="showTab('username')">Change Username</button>
                    <button class="tab-button" onclick="showTab('email')">Change Email</button>
                </div>

                <!-- Account Information Tab -->
                <div id="account" class="tab-content active">
                    <div class="info-card">
                        <h5>Account Information</h5>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                        <p><strong>Role:</strong>
                            <span class="role-badge"><?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?></span>
                        </p>
                        <p><strong>Account Created:</strong> <?php echo date('F j, Y g:i A', strtotime($admin['created_at'])); ?></p>
                        <?php if ($admin['last_login']): ?>
                            <p><strong>Last Login:</strong> <?php echo date('F j, Y g:i A', strtotime($admin['last_login'])); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="security-note">
                        <strong>Security Notice:</strong> As an admin, please ensure your credentials are secure.
                        Use strong passwords and avoid sharing your account details.
                    </div>
                </div>

                <!-- Change Password Tab -->
                <div id="password" class="tab-content">
                    <div class="form-section">
                        <h4>Change Password</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label for="current_password">Current Password:</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password:</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                                <small style="color: #666;">Minimum 8 characters for admin security</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password:</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn-admin">Change Password</button>
                        </form>
                    </div>
                </div>

                <!-- Change Username Tab -->
                <div id="username" class="tab-content">
                    <div class="form-section">
                        <h4>Change Username</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_username">

                            <div class="form-group">
                                <label for="new_username">New Username:</label>
                                <input type="text" id="new_username" name="new_username" required minlength="3"
                                       pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed"
                                       value="<?php echo htmlspecialchars($admin['username']); ?>">
                                <small style="color: #666;">Only letters, numbers, and underscores. Minimum 3 characters.</small>
                            </div>

                            <div class="form-group">
                                <label for="password_confirm">Confirm Password:</label>
                                <input type="password" id="password_confirm" name="password_confirm" required>
                                <small style="color: #666;">Enter your current password to confirm this change</small>
                            </div>

                            <button type="submit" class="btn-admin">Change Username</button>
                        </form>
                    </div>
                </div>

                <!-- Change Email Tab -->
                <div id="email" class="tab-content">
                    <div class="form-section">
                        <h4>Change Email Address</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_email">

                            <div class="form-group">
                                <label for="new_email">New Email Address:</label>
                                <input type="email" id="new_email" name="new_email" required
                                       value="<?php echo htmlspecialchars($admin['email']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="password_confirm_email">Confirm Password:</label>
                                <input type="password" id="password_confirm_email" name="password_confirm_email" required>
                                <small style="color: #666;">Enter your current password to confirm this change</small>
                            </div>

                            <button type="submit" class="btn-admin">Change Email</button>
                        </form>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="dashboard.php" class="btn">← Back to Admin Dashboard</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 CiolStream v1 Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked tab button
            event.target.classList.add('active');
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Username validation
        document.getElementById('new_username').addEventListener('input', function() {
            const username = this.value;
            const pattern = /^[a-zA-Z0-9_]+$/;

            if (!pattern.test(username)) {
                this.setCustomValidity('Only letters, numbers, and underscores are allowed');
            } else if (username.length < 3) {
                this.setCustomValidity('Username must be at least 3 characters long');
            } else {
                this.setCustomValidity('');
            }
        });

        // Email validation
        document.getElementById('new_email').addEventListener('input', function() {
            const email = this.value;
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!pattern.test(email)) {
                this.setCustomValidity('Please enter a valid email address');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>