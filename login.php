<?php
// login.php - Updated for v0.2.0
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: public/index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MovieStream v0.2.0</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo">
                    <a href="public/index.php" style="color: white; text-decoration: none;">MovieStream v0.2.0</a>
                </h1>
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
                <p>Test User: <strong>testuser</strong> / <strong>testuser</strong></p>
            </div>
        </div>
    </main>
</body>
</html>