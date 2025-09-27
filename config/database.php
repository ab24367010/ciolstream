<?php
// config/database.php - Fixed Database configuration v0.2.0
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable debug mode (set to true for development, false for production)
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false); // Change to false in production
}

// Database configuration - UPDATE THESE VALUES
$host = 'localhost';
$dbname = 'movie_streaming';
$username = 'root';  // Change this to your database username
$password = 'MNAng3l_112';      // Change this to your database password (empty for default XAMPP)
$charset = 'utf8mb4';

// PDO options for better error handling and security
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
    PDO::ATTR_PERSISTENT         => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
];
// Create PDO connection
try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, $options);

    // Set timezone to UTC
    $pdo->exec("SET time_zone = '+00:00'");

    // Test connection with a simple query
    $pdo->query("SELECT 1")->fetch();
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database connection failed: " . $e->getMessage());

    // Show user-friendly error in development
    if (isset($_GET['debug']) || (defined('DEBUG_MODE') && DEBUG_MODE)) {
        die("Database connection failed: " . $e->getMessage() .
            "\n\nPlease check your database credentials in config/database.php" .
            "\nMake sure your database server is running and the database '{$dbname}' exists.");
    } else {
        die("Database connection failed. Please check your configuration or contact administrator.");
    }
}

// Define constants for file paths
if (!defined('SUBTITLES_DIR')) {
    define('SUBTITLES_DIR', dirname(__DIR__) . '/uploads/subtitles/');
}

if (!defined('THUMBNAILS_DIR')) {
    define('THUMBNAILS_DIR', dirname(__DIR__) . '/uploads/thumbnails/');
}

// Optional: Enable query logging in development
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
}
