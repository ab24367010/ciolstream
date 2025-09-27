<?php
// admin/dashboard.php - Admin dashboard v0.2.0
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success_msg = '';
$error_msg = '';

// Handle user status change with expiry
if (isset($_POST['change_status']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 1;
    
    if ($new_status === 'active') {
        $expiry_date = date('Y-m-d', strtotime("+{$duration} month"));
        $stmt = $pdo->prepare("UPDATE users SET status = ?, expiry_date = ? WHERE id = ?");
        $stmt->execute([$new_status, $expiry_date, $user_id]);
        $success_msg = "User activated until " . date('M d, Y', strtotime($expiry_date));
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, expiry_date = NULL WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        $success_msg = "User deactivated successfully!";
    }
}

// Handle video upload
if (isset($_POST['add_video'])) {
    $title = trim($_POST['title']);
    $genre = trim($_POST['genre']);
    $youtube_id = trim($_POST['youtube_id']);
    $description = trim($_POST['description']);
    
    if (!empty($title) && !empty($genre) && !empty($youtube_id)) {
        $thumbnail_url = getYouTubeThumbnail($youtube_id);
        $stmt = $pdo->prepare("INSERT INTO videos (title, genre, youtube_id, description, thumbnail_url) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $genre, $youtube_id, $description, $thumbnail_url])) {
            $success_msg = "Video added successfully!";
        } else {
            $error_msg = "Failed to add video. Please try again.";
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Handle subtitle file upload
if (isset($_POST['upload_subtitle']) && isset($_FILES['srt_file'])) {
    $video_id = (int)$_POST['video_id'];
    $language = trim($_POST['language']) ?: 'en';
    
    if ($_FILES['srt_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['srt_file']['tmp_name'];
        $file_name = sanitizeFilename($_FILES['srt_file']['name']);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_extension === 'srt') {
            $upload_path = SUBTITLES_DIR . $video_id . '_' . $language . '.srt';
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Remove existing subtitle for this video/language
                $stmt = $pdo->prepare("DELETE FROM subtitles WHERE video_id = ? AND language = ?");
                $stmt->execute([$video_id, $language]);
                
                // Insert new subtitle record
                $stmt = $pdo->prepare("INSERT INTO subtitles (video_id, language, srt_file_path) VALUES (?, ?, ?)");
                if ($stmt->execute([$video_id, $language, $upload_path])) {
                    $success_msg = "Subtitle file uploaded successfully!";
                } else {
                    $error_msg = "Failed to save subtitle record.";
                }
            } else {
                $error_msg = "Failed to upload subtitle file.";
            }
        } else {
            $error_msg = "Please upload a valid .srt file.";
        }
    } else {
        $error_msg = "File upload error.";
    }
}

// Handle video deletion
if (isset($_POST['delete_video']) && isset($_POST['video_id'])) {
    $video_id = (int)$_POST['video_id'];
    
    // Delete subtitle files
    $stmt = $pdo->prepare("SELECT srt_file_path FROM subtitles WHERE video_id = ?");
    $stmt->execute([$video_id]);
    $subtitle_files = $stmt->fetchAll();
    
    foreach ($subtitle_files as $file) {
        if (file_exists($file['srt_file_path'])) {
            unlink($file['srt_file_path']);
        }
    }
    
    // Delete video record (cascades to subtitles)
    $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
    if ($stmt->execute([$video_id])) {
        $success_msg = "Video deleted successfully!";
    } else {
        $error_msg = "Failed to delete video.";
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT id, username, email, status, expiry_date, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all videos
$stmt = $pdo->prepare("SELECT v.*, s.language, s.srt_file_path FROM videos v LEFT JOIN subtitles s ON v.id = s.video_id ORDER BY v.created_at DESC");
$stmt->execute();
$videos = $stmt->fetchAll();

// Group videos by id to handle multiple subtitle languages
$videos_grouped = [];
foreach ($videos as $video) {
    $id = $video['id'];
    if (!isset($videos_grouped[$id])) {
        $videos_grouped[$id] = $video;
        $videos_grouped[$id]['subtitles'] = [];
    }
    if ($video['language']) {
        $videos_grouped[$id]['subtitles'][] = [
            'language' => $video['language'],
            'file_path' => $video['srt_file_path']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MovieStream v0.2.0</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo">
                    <a href="../public/index.php" style="color: white; text-decoration: none;">MovieStream Admin v0.2.0</a>
                </h1>
                <div class="nav-links">
                    <a href="../public/index.php" class="btn">View Site</a>
                    <a href="logout.php" class="btn">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Manage users, videos, and content</p>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <div class="admin-nav">
                <a href="#users" class="btn active" onclick="showSection('users')">User Management</a>
                <a href="#videos" class="btn" onclick="showSection('videos')">Video Management</a>
                <a href="#subtitles" class="btn" onclick="showSection('subtitles')">Subtitles Management</a>
            </div>

            <!-- Users Management Section -->
            <div id="users-section" class="admin-section">
                <h2>User Management</h2>
                <p>Total Users: <?php echo count($users); ?></p>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Expiry Date</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="status status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($user['expiry_date']) {
                                    echo date('M d, Y', strtotime($user['expiry_date']));
                                    if (date('Y-m-d') > $user['expiry_date']) {
                                        echo ' <span class="expired">(Expired)</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php if ($user['status'] === 'inactive'): ?>
                                        <select name="duration" style="width: 80px; margin-right: 5px;">
                                            <option value="1">1 Month</option>
                                            <option value="3">3 Months</option>
                                            <option value="6">6 Months</option>
                                            <option value="12">1 Year</option>
                                        </select>
                                        <input type="hidden" name="new_status" value="active">
                                        <button type="submit" name="change_status" class="status-btn active"
                                                onclick="return confirm('Activate this user?')">
                                            Activate
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="inactive">
                                        <button type="submit" name="change_status" class="status-btn inactive"
                                                onclick="return confirm('Deactivate this user?')">
                                            Deactivate
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Videos Management Section -->
            <div id="videos-section" class="admin-section" style="display: none;">
                <h2>Video Management</h2>
                
                <div class="add-video-form">
                    <h3>Add New Video</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Title:</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="genre">Genre:</label>
                                <input type="text" name="genre" required placeholder="e.g. Action, Comedy, Drama">
                            </div>
                            <div class="form-group">
                                <label for="youtube_id">YouTube ID:</label>
                                <input type="text" name="youtube_id" required placeholder="dQw4w9WgXcQ">
                            </div>
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_video" class="btn btn-primary">Add Video</button>
                    </form>
                </div>

                <h3>All Videos (<?php echo count($videos_grouped); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thumbnail</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>YouTube ID</th>
                            <th>Subtitles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos_grouped as $video): ?>
                        <tr>
                            <td><?php echo $video['id']; ?></td>
                            <td>
                                <img src="<?php echo $video['thumbnail_url']; ?>" 
                                     alt="Thumbnail" 
                                     style="width: 60px; height: 45px; object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($video['title']); ?></td>
                            <td><?php echo htmlspecialchars($video['genre']); ?></td>
                            <td><?php echo htmlspecialchars($video['youtube_id']); ?></td>
                            <td>
                                <?php if (!empty($video['subtitles'])): ?>
                                    <?php foreach ($video['subtitles'] as $sub): ?>
                                        <span class="subtitle-badge"><?php echo strtoupper($sub['language']); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="no-subtitles">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                    <button type="submit" name="delete_video" class="status-btn inactive"
                                            onclick="return confirm('Delete this video and all its subtitles?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Subtitles Management Section -->
            <div id="subtitles-section" class="admin-section" style="display: none;">
                <h2>Subtitles Management</h2>
                
                <div class="upload-subtitle-form">
                    <h3>Upload Subtitle File</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="video_id">Select Video:</label>
                                <select name="video_id" required>
                                    <option value="">Choose a video...</option>
                                    <?php foreach ($videos_grouped as $video): ?>
                                        <option value="<?php echo $video['id']; ?>">
                                            <?php echo htmlspecialchars($video['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="language">Language:</label>
                                <input type="text" name="language" value="en" placeholder="en, es, fr, etc.">
                            </div>
                            <div class="form-group">
                                <label for="srt_file">SRT File:</label>
                                <input type="file" name="srt_file" accept=".srt" required>
                            </div>
                        </div>
                        <button type="submit" name="upload_subtitle" class="btn btn-primary">Upload Subtitle</button>
                    </form>
                </div>

                <h3>Subtitle Files</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Video</th>
                            <th>Language</th>
                            <th>File Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos_grouped as $video): ?>
                            <?php if (!empty($video['subtitles'])): ?>
                                <?php foreach ($video['subtitles'] as $subtitle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td><?php echo strtoupper($subtitle['language']); ?></td>
                                    <td>
                                        <?php if (file_exists($subtitle['file_path'])): ?>
                                            <span class="status-active">✓ Available</span>
                                        <?php else: ?>
                                            <span class="status-inactive">✗ Missing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (file_exists($subtitle['file_path'])): ?>
                                            <a href="download_subtitle.php?video_id=<?php echo $video['id']; ?>&lang=<?php echo $subtitle['language']; ?>" 
                                               class="btn btn-small">Download</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td colspan="3"><em>No subtitles uploaded</em></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function showSection(section) {
            // Hide all sections
            document.getElementById('users-section').style.display = 'none';
            document.getElementById('videos-section').style.display = 'none';
            document.getElementById('subtitles-section').style.display = 'none';
            
            // Remove active class from all nav buttons
            const navButtons = document.querySelectorAll('.admin-nav .btn');
            navButtons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>