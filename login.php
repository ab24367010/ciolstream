<?php
// login.php - Fixed for v0.2.0 with proper session token creation
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, password, status, expiry_date FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Auto-expire user if needed
                if ($user['status'] === 'active' && $user['expiry_date'] && date('Y-m-d') > $user['expiry_date']) {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $user['status'] = 'inactive';
                }
                
                // Generate session token
                $session_token = generateSessionToken();
                $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
                
                // Store session in database
                $stmt = $pdo->prepare("
                    INSERT INTO user_sessions (user_id, session_token, device_info, ip_address, user_agent, expires_at, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Agent';
                
                $stmt->execute([
                    $user['id'],
                    $session_token,
                    $device_info,
                    $ip_address,
                    $user_agent,
                    $expires_at
                ]);
                
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['session_token'] = $session_token;
                $_SESSION['user_status'] = $user['status'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Log user activity
                logUserActivity($pdo, $user['id'], 'login', null, [
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent
                ]);
                
                header('Location: public/index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CiolStream</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CiolStream">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <a href="public/index.php">
                        <img src="img/logo.png" alt="CiolStream" style="height: 50px; width: auto;">
                    </a>
                </div>
                <div class="nav-links">
                    <a href="register.php" class="btn">Register</a>
                    <a href="public/index.php" class="btn">Browse Movies</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h2>Login to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-submit">Login</button>
            </form>

            <div class="form-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="admin/login.php">Admin Login</a></p>
            </div>
        </div>
    </main>
</body>
</html>