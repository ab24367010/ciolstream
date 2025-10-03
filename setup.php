<?php
// setup.php - CiolStream Setup Script - FIXED
error_reporting(E_ALL);
ini_set('display_errors', 1);

$setup_steps = [];
$errors = [];

function addStep($step, $status, $message = '')
{
    global $setup_steps;
    $setup_steps[] = [
        'step' => $step,
        'status' => $status,
        'message' => $message
    ];
}

function addError($error)
{
    global $errors;
    $errors[] = $error;
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '>=')) {
    addStep('PHP Version Check', 'success', 'PHP ' . PHP_VERSION . ' is supported');
} else {
    addStep('PHP Version Check', 'error', 'PHP 7.4 or higher required. Current: ' . PHP_VERSION);
    addError('Unsupported PHP version');
}

// Check required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        addStep("PHP Extension: {$ext}", 'success', 'Available');
    } else {
        addStep("PHP Extension: {$ext}", 'error', 'Missing');
        addError("Missing PHP extension: {$ext}");
    }
}

// Check directory permissions
$directories = [
    'uploads' => 'uploads/',
    'uploads/subtitles' => 'uploads/subtitles/',
    'uploads/thumbnails' => 'uploads/thumbnails/'
];

foreach ($directories as $name => $path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            addStep("Create Directory: {$name}", 'success', 'Created successfully');
        } else {
            addStep("Create Directory: {$name}", 'error', 'Failed to create');
            addError("Cannot create directory: {$path}");
        }
    } else {
        addStep("Directory Check: {$name}", 'success', 'Already exists');
    }

    // Check if directory is writable
    if (is_writable($path)) {
        addStep("Directory Writable: {$name}", 'success', 'Writable');
    } else {
        addStep("Directory Writable: {$name}", 'warning', 'Not writable - run: chmod 755 ' . $path);
    }
}

// Test database connection
$db_config_exists = file_exists('config/database.php');
if ($db_config_exists) {
    addStep('Database Config', 'success', 'config/database.php found');

    // Try to connect
    try {
        require_once 'config/database.php';
        addStep('Database Connection', 'success', 'Connected successfully');

        // Check if tables exist - FIXED: removed prepared statement for SHOW TABLES
        // Check if tables exist - FIXED for MySQL 8.0.43
        $tables = ['users', 'admins', 'videos', 'subtitles', 'user_progress', 'ratings', 'watchlist', 'series', 'seasons'];
        $existing_tables = [];

        foreach ($tables as $table) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
                $stmt->execute([$table]);
                if ($stmt->fetchColumn() > 0) {
                    $existing_tables[] = $table;
                }
            } catch (Exception $e) {
                // Table doesn't exist or query failed
                continue;
            }
        }

        if (count($existing_tables) === count($tables)) {
            addStep('Database Tables', 'success', 'All required tables exist (' . count($existing_tables) . '/' . count($tables) . ')');
        } else {
            addStep('Database Tables', 'warning', 'Some tables missing (' . count($existing_tables) . '/' . count($tables) . '). Run database.sql');
        }

        // Test admin user exists
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins WHERE username = 'admin'");
            $admin_count = $stmt->fetch()['count'];
            if ($admin_count > 0) {
                addStep('Default Admin', 'success', 'Default admin user exists');
            } else {
                addStep('Default Admin', 'warning', 'Default admin user missing');
            }
        } catch (Exception $e) {
            addStep('Default Admin', 'warning', 'Could not check admin user');
        }
    } catch (Exception $e) {
        addStep('Database Connection', 'error', 'Connection failed: ' . $e->getMessage());
        addError('Database connection failed: ' . $e->getMessage());
    }
} else {
    addStep('Database Config', 'error', 'config/database.php not found');
    addError('Database configuration missing');
}

// Check .htaccess
if (file_exists('.htaccess')) {
    addStep('Apache Config', 'success', '.htaccess file found');
} else {
    addStep('Apache Config', 'warning', '.htaccess file missing');
}

// Check for sample subtitle files
$sample_subtitle = 'uploads/subtitles/1_en.srt';
if (file_exists($sample_subtitle)) {
    addStep('Sample Subtitles', 'success', 'Sample subtitle file found');
} else {
    addStep('Sample Subtitles', 'warning', 'Sample subtitle file missing');
}

// Performance recommendations
$recommendations = [];

if (ini_get('memory_limit') !== '-1') {
    $memory_limit = ini_get('memory_limit');
    $memory_bytes = return_bytes($memory_limit);
    if ($memory_bytes < 128 * 1024 * 1024) { // 128MB
        $recommendations[] = "Increase memory_limit to at least 128M (current: {$memory_limit})";
    }
}

$max_upload = return_bytes(ini_get('upload_max_filesize'));
if ($max_upload < 50 * 1024 * 1024) { // 50MB
    $recommendations[] = "Increase upload_max_filesize to at least 50M for subtitle uploads";
}

function return_bytes($val)
{
    $val = trim($val);
    if (empty($val)) return 0;
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int) $val;
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CiolStream Setup</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CiolStream">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            color: #fff;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #667eea;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .step {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
        }

        .step-name {
            font-weight: 500;
            flex: 1;
        }

        .step-status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 1rem;
        }

        .status-success {
            background: #28a745;
            color: white;
        }

        .status-warning {
            background: #ffc107;
            color: #000;
        }

        .status-error {
            background: #dc3545;
            color: white;
        }

        .step-message {
            font-size: 0.9rem;
            color: #ccc;
            max-width: 300px;
            text-align: right;
        }

        .summary {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .summary.success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
        }

        .summary.error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
        }

        .summary.warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
        }

        .errors {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }

        .errors h3 {
            color: #dc3545;
            margin-bottom: 0.5rem;
        }

        .errors ul {
            list-style-position: inside;
            color: #ffcccb;
        }

        .recommendations {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255, 193, 7, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .recommendations h3 {
            color: #ffc107;
            margin-bottom: 0.5rem;
        }

        .recommendations ul {
            list-style-position: inside;
            color: #fff3cd;
        }

        .action-buttons {
            margin-top: 2rem;
            text-align: center;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            transition: width 0.3s ease;
        }

        .installation-guide {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .installation-guide h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .installation-guide ol {
            list-style-position: inside;
            color: #ccc;
            line-height: 1.8;
        }

        .installation-guide li {
            margin-bottom: 0.5rem;
        }

        .installation-guide code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #ffc107;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="img/logo.png" alt="CiolStream" style="max-width: 200px; margin-bottom: 1rem;">
            <p>System Setup & Configuration Check</p>
            <p><small>Now with Multi-Season/Episode Support!</small></p>
        </div>

        <?php
        $success_count = count(array_filter($setup_steps, function ($step) {
            return $step['status'] === 'success';
        }));
        $total_count = count($setup_steps);
        $progress_percentage = ($success_count / $total_count) * 100;
        ?>

        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
        </div>
        <p style="text-align: center; margin-bottom: 2rem; color: #ccc;">
            Setup Progress: <?php echo $success_count; ?>/<?php echo $total_count; ?> checks passed
        </p>

        <div class="setup-steps">
            <?php foreach ($setup_steps as $step): ?>
                <div class="step">
                    <div class="step-name"><?php echo htmlspecialchars($step['step']); ?></div>
                    <div class="step-status status-<?php echo $step['status']; ?>">
                        <?php
                        $icons = ['success' => '✓', 'warning' => '!', 'error' => '✗'];
                        echo $icons[$step['status']] . ' ' . ucfirst($step['status']);
                        ?>
                    </div>
                    <?php if ($step['message']): ?>
                        <div class="step-message"><?php echo htmlspecialchars($step['message']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($errors) && empty($recommendations)): ?>
            <div class="summary success">
                <h2>🎉 Setup Complete!</h2>
                <p>All checks passed successfully. CiolStream is ready to use!</p>
            </div>
        <?php elseif (!empty($errors)): ?>
            <div class="summary error">
                <h2>⚠️ Setup Issues Found</h2>
                <p>Please resolve the following critical issues before proceeding:</p>
            </div>
        <?php else: ?>
            <div class="summary warning">
                <h2>⚡ Setup Complete with Recommendations</h2>
                <p>CiolStream is functional, but consider the recommendations below for optimal performance.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <h3>Critical Issues</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($recommendations)): ?>
            <div class="recommendations">
                <h3>Performance Recommendations</h3>
                <ul>
                    <?php foreach ($recommendations as $rec): ?>
                        <li><?php echo htmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="installation-guide">
            <h3>Quick Installation Guide</h3>
            <ol>
                <li>Import <code>database.sql</code> into your MySQL database</li>
                <li>Update database credentials in <code>config/database.php</code></li>
                <li>Set directory permissions: <code>chmod 755 uploads/ -R</code></li>
                <li>Access admin panel at <code>/admin/login.php</code> (admin/admin123)</li>
                <li>Upload your first video and subtitle files</li>
                <li>Create user accounts and test the system</li>
                <li><strong>New:</strong> Add series with seasons and episodes!</li>
            </ol>
        </div>

        <div class="action-buttons">
            <?php if (empty($errors)): ?>
                <a href="public/index.php" class="btn btn-primary">Launch CiolStream</a>
                <a href="admin/login.php" class="btn btn-secondary">Admin Panel</a>
            <?php else: ?>
                <button onclick="location.reload()" class="btn btn-primary">Recheck Setup</button>
            <?php endif; ?>
            <a href="#" onclick="showSystemInfo()" class="btn btn-secondary">System Info</a>
        </div>
    </div>

    <script>
        function showSystemInfo() {
            const info = [
                'PHP Version: <?php echo PHP_VERSION; ?>',
                'Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>',
                'Memory Limit: <?php echo ini_get('memory_limit'); ?>',
                'Upload Max: <?php echo ini_get('upload_max_filesize'); ?>',
                'Time Limit: <?php echo ini_get('max_execution_time'); ?>s',
                'Extensions: <?php echo implode(', ', get_loaded_extensions()); ?>'
            ];
            alert(info.join('\n'));
        }
    </script>
</body>

</html>