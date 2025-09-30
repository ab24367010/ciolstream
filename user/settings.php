<?php
// user/settings.php - User Settings Page
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
$current_user = getCurrentUserSession($pdo);
if (!$current_user) {
    header('Location: ../login.php');
    exit;
}

$user_id = $current_user['user_id'];
$username = $current_user['username'];
$user_status = $current_user['status'];

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
                } elseif (strlen($new_password) < 6) {
                    $error_message = 'New password must be at least 6 characters long.';
                } else {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();

                    if ($user_data && password_verify($current_password, $user_data['password'])) {
                        // Update password
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");

                        if ($stmt->execute([$new_password_hash, $user_id])) {
                            $success_message = 'Password changed successfully.';

                            // Log activity
                            logUserActivity($pdo, $user_id, 'profile_update', null, ['type' => 'password_change']);
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
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();

                    if ($user_data && password_verify($password_confirm, $user_data['password'])) {
                        // Check if username already exists
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                        $stmt->execute([$new_username, $user_id]);

                        if ($stmt->fetch()) {
                            $error_message = 'Username already exists. Please choose a different one.';
                        } else {
                            // Update username
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE id = ?");

                            if ($stmt->execute([$new_username, $user_id])) {
                                $success_message = 'Username changed successfully.';
                                $username = $new_username; // Update local variable

                                // Log activity
                                logUserActivity($pdo, $user_id, 'profile_update', null, ['type' => 'username_change', 'new_username' => $new_username]);
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
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();

                    if ($user_data && password_verify($password_confirm, $user_data['password'])) {
                        // Check if email already exists
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $stmt->execute([$new_email, $user_id]);

                        if ($stmt->fetch()) {
                            $error_message = 'Email address already exists. Please choose a different one.';
                        } else {
                            // Update email
                            $stmt = $pdo->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");

                            if ($stmt->execute([$new_email, $user_id])) {
                                $success_message = 'Email changed successfully.';

                                // Log activity
                                logUserActivity($pdo, $user_id, 'profile_update', null, ['type' => 'email_change', 'new_email' => $new_email]);
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

// Get user details for display
$stmt = $pdo->prepare("SELECT username, email, status, expiry_date, created_at, last_login FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_details = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MovieStream v0.2.0</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo">
                    <a href="../public/index.php" style="color: white; text-decoration: none;">MovieStream v0.2.0</a>
                </h1>
                <div class="nav-links">
                    <span class="welcome">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <a href="dashboard.php" class="btn-secondary">Dashboard</a>
                    <a href="../public/index.php" class="btn-secondary">Browse</a>
                    <a href="../logout.php" class="btn-secondary">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="settings-container">
            <!-- Settings Header - Match Dashboard Style -->
            <div class="settings-header">
                <h2>Account Settings</h2>
                <div class="user-info">
                    <span class="welcome">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                    <span class="status-badge status-<?php echo $user_details['status']; ?>">
                        <?php echo ucfirst($user_details['status']); ?> Member
                    </span>
                    <?php if ($user_details['expiry_date'] && $user_details['status'] === 'active'): ?>
                        <span class="expiry-info">
                            Valid until: <?php echo date('M d, Y', strtotime($user_details['expiry_date'])); ?>
                        </span>
                    <?php elseif ($user_details['status'] === 'inactive'): ?>
                        <span class="inactive-notice">Contact admin to activate your account</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Account Information -->
            <div class="settings-section">
                <h3>Account Information</h3>
                <div class="info-card">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_details['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_details['email']); ?></p>
                    <p><strong>Status:</strong>
                        <span class="status-badge status-<?php echo $user_details['status']; ?>">
                            <?php echo ucfirst($user_details['status']); ?>
                        </span>
                    </p>
                    <?php if ($user_details['expiry_date']): ?>
                        <p><strong>Account Expires:</strong> <?php echo date('F j, Y', strtotime($user_details['expiry_date'])); ?></p>
                    <?php endif; ?>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user_details['created_at'])); ?></p>
                    <?php if ($user_details['last_login']): ?>
                        <p><strong>Last Login:</strong> <?php echo date('F j, Y g:i A', strtotime($user_details['last_login'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Change Password -->
            <div class="settings-section">
                <h3>Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <small style="color: #666;">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn-submit">Change Password</button>
                </form>
            </div>

            <!-- Change Username -->
            <div class="settings-section">
                <h3>Change Username</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_username">

                    <div class="form-group">
                        <label for="new_username">New Username:</label>
                        <input type="text" id="new_username" name="new_username" required minlength="3"
                               pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed"
                               value="<?php echo htmlspecialchars($user_details['username']); ?>">
                        <small style="color: #666;">Only letters, numbers, and underscores. Minimum 3 characters.</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirm Password:</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                        <small style="color: #666;">Enter your current password to confirm this change</small>
                    </div>

                    <button type="submit" class="btn-submit">Change Username</button>
                </form>
            </div>

            <!-- Change Email -->
            <div class="settings-section">
                <h3>Change Email Address</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_email">

                    <div class="form-group">
                        <label for="new_email">New Email Address:</label>
                        <input type="email" id="new_email" name="new_email" required
                               value="<?php echo htmlspecialchars($user_details['email']); ?>">
                        <small style="color: #666;">Enter a valid email address</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm_email">Confirm Password:</label>
                        <input type="password" id="password_confirm_email" name="password_confirm_email" required>
                        <small style="color: #666;">Enter your current password to confirm this change</small>
                    </div>

                    <button type="submit" class="btn-submit">Change Email</button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 MovieStream v0.2.0. All rights reserved.</p>
        </div>
    </footer>

    <script>
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