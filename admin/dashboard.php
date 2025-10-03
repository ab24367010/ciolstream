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

// Handle series addition
if (isset($_POST['add_series'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $release_year = !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null;
    $thumbnail_url = trim($_POST['thumbnail_url']) ?: null;
    $director = trim($_POST['director']) ?: null;
    $cast = trim($_POST['cast']) ?: null;
    $language = trim($_POST['language']) ?: 'English';
    $tags = trim($_POST['tags']) ?: null;
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    if (!empty($title)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO series (title, description, release_year, thumbnail_url, director, cast, language, tags)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$title, $description, $release_year, $thumbnail_url, $director, $cast, $language, $tags])) {
                $series_id = $pdo->lastInsertId();

                // Insert genres for series
                if (!empty($selected_genres)) {
                    $stmt = $pdo->prepare("INSERT INTO series_genres (series_id, genre_id) VALUES (?, ?)");
                    foreach ($selected_genres as $genre_id) {
                        $stmt->execute([$series_id, (int)$genre_id]);
                    }
                }

                $success_msg = "Series added successfully!";
            } else {
                $error_msg = "Failed to add series. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Error adding series: " . $e->getMessage());
            $error_msg = "Failed to add series: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in the required fields.";
    }
}

// Handle season addition
if (isset($_POST['add_season'])) {
    $series_id = (int)$_POST['series_id'];
    $season_number = (int)$_POST['season_number'];
    $title = trim($_POST['title']) ?: "Season {$season_number}";
    $description = trim($_POST['description']) ?: null;
    $release_year = !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null;
    $thumbnail_url = trim($_POST['thumbnail_url']) ?: null;

    if ($series_id && $season_number) {
        try {
            // Check if season already exists
            $stmt = $pdo->prepare("SELECT id FROM seasons WHERE series_id = ? AND season_number = ?");
            $stmt->execute([$series_id, $season_number]);
            if ($stmt->fetch()) {
                $error_msg = "Season {$season_number} already exists for this series.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO seasons (series_id, season_number, title, description, release_year, thumbnail_url)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([$series_id, $season_number, $title, $description, $release_year, $thumbnail_url])) {
                    $success_msg = "Season added successfully!";
                } else {
                    $error_msg = "Failed to add season. Please try again.";
                }
            }
        } catch (Exception $e) {
            error_log("Error adding season: " . $e->getMessage());
            $error_msg = "Failed to add season: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in the required fields.";
    }
}

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
    $content_type = $_POST['content_type'] ?? 'movie';
    $youtube_id = trim($_POST['youtube_id']);
    $description = trim($_POST['description']);
    $series_id = !empty($_POST['series_id']) ? (int)$_POST['series_id'] : null;
    $season_id = !empty($_POST['season_id']) ? (int)$_POST['season_id'] : null;
    $episode_number = !empty($_POST['episode_number']) ? (int)$_POST['episode_number'] : null;
    $duration_seconds = !empty($_POST['duration_seconds']) ? (int)$_POST['duration_seconds'] : 0;
    $release_year = !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null;
    $director = trim($_POST['director']) ?: null;
    $cast = trim($_POST['cast']) ?: null;
    $language = trim($_POST['language']) ?: 'English';
    $tags = trim($_POST['tags']) ?: null;
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    if (!empty($title) && !empty($youtube_id)) {
        // Validate episode requirements
        if ($content_type === 'episode' && (!$series_id || !$season_id || !$episode_number)) {
            $error_msg = "Episodes require series, season, and episode number.";
        } else {
            try {
                $thumbnail_url = getYouTubeThumbnail($youtube_id);

                // Get primary genre for legacy genre field (use first selected genre)
                $primary_genre = '';
                if (!empty($selected_genres)) {
                    $stmt = $pdo->prepare("SELECT name FROM genres WHERE id = ?");
                    $stmt->execute([(int)$selected_genres[0]]);
                    $genre_row = $stmt->fetch();
                    $primary_genre = $genre_row ? $genre_row['name'] : '';
                }

                $stmt = $pdo->prepare("
                    INSERT INTO videos (title, content_type, genre, youtube_id, description, thumbnail_url,
                                       series_id, season_id, episode_number, duration_seconds, release_year,
                                       director, cast, language, tags)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([
                    $title,
                    $content_type,
                    $primary_genre,
                    $youtube_id,
                    $description,
                    $thumbnail_url,
                    $series_id,
                    $season_id,
                    $episode_number,
                    $duration_seconds,
                    $release_year,
                    $director,
                    $cast,
                    $language,
                    $tags
                ])) {
                    $video_id = $pdo->lastInsertId();

                    // Insert into video_genres table for all selected genres
                    if (!empty($selected_genres)) {
                        $stmt = $pdo->prepare("INSERT INTO video_genres (video_id, genre_id) VALUES (?, ?)");
                        foreach ($selected_genres as $genre_id) {
                            $stmt->execute([$video_id, (int)$genre_id]);
                        }
                    }

                    // Update season episode count
                    if ($season_id) {
                        $stmt = $pdo->prepare("
                            UPDATE seasons
                            SET episode_count = (SELECT COUNT(*) FROM videos WHERE season_id = ? AND status = 'active')
                            WHERE id = ?
                        ");
                        $stmt->execute([$season_id, $season_id]);
                    }

                    $success_msg = "Video added successfully!";
                } else {
                    $error_msg = "Failed to add video. Please try again.";
                }
            } catch (Exception $e) {
                error_log("Error adding video: " . $e->getMessage());
                $error_msg = "Failed to add video: " . $e->getMessage();
            }
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

    try {
        // Get season_id before deleting
        $stmt = $pdo->prepare("SELECT season_id FROM videos WHERE id = ?");
        $stmt->execute([$video_id]);
        $video_data = $stmt->fetch();
        $season_id = $video_data ? $video_data['season_id'] : null;

        // Delete subtitle files
        $stmt = $pdo->prepare("SELECT srt_file_path FROM subtitles WHERE video_id = ?");
        $stmt->execute([$video_id]);
        $subtitle_files = $stmt->fetchAll();

        foreach ($subtitle_files as $file) {
            if (file_exists($file['srt_file_path'])) {
                unlink($file['srt_file_path']);
            }
        }

        // Delete video record (cascades to subtitles and related records)
        $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
        if ($stmt->execute([$video_id])) {
            // Update season episode count if it was an episode
            if ($season_id) {
                $stmt = $pdo->prepare("
                    UPDATE seasons
                    SET episode_count = (SELECT COUNT(*) FROM videos WHERE season_id = ? AND status = 'active')
                    WHERE id = ?
                ");
                $stmt->execute([$season_id, $season_id]);
            }

            $success_msg = "Video deleted successfully!";
        } else {
            $error_msg = "Failed to delete video.";
        }
    } catch (Exception $e) {
        error_log("Error deleting video: " . $e->getMessage());
        $error_msg = "Failed to delete video: " . $e->getMessage();
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT id, username, email, status, expiry_date, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all series with genre information
$stmt = $pdo->prepare("
    SELECT s.*,
           GROUP_CONCAT(g.name SEPARATOR ', ') as genres,
           COUNT(DISTINCT seasons.id) as season_count,
           COUNT(DISTINCT videos.id) as episode_count
    FROM series s
    LEFT JOIN series_genres sg ON s.id = sg.series_id
    LEFT JOIN genres g ON sg.genre_id = g.id
    LEFT JOIN seasons ON s.id = seasons.series_id
    LEFT JOIN videos ON seasons.id = videos.season_id AND videos.content_type = 'episode'
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute();
$all_series = $stmt->fetchAll();

// Get all seasons with series information
$stmt = $pdo->prepare("
    SELECT seasons.*, series.title as series_title,
           COUNT(videos.id) as actual_episode_count
    FROM seasons
    LEFT JOIN series ON seasons.series_id = series.id
    LEFT JOIN videos ON seasons.id = videos.season_id AND videos.content_type = 'episode' AND videos.status = 'active'
    GROUP BY seasons.id
    ORDER BY series.title, seasons.season_number
");
$stmt->execute();
$all_seasons = $stmt->fetchAll();

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

// Get all genres for forms
$stmt = $pdo->prepare("SELECT id, name FROM genres WHERE is_active = TRUE ORDER BY name");
$stmt->execute();
$all_genres = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CiolStream</title>
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
                    <a href="../public/index.php">
                        <img src="../img/logo.png" alt="CiolStream" style="height: 50px; width: auto;">
                    </a>
                </div>
                <div class="nav-links">
                    <a href="../public/index.php" class="btn">View Site</a>
                    <a href="../setup.php" class="btn">Setup</a>
                    <a href="settings.php" class="btn">Settings</a>
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
                <a href="#series" class="btn" onclick="showSection('series')">Series Management</a>
                <a href="#seasons" class="btn" onclick="showSection('seasons')">Seasons Management</a>
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

            <!-- Series Management Section -->
            <div id="series-section" class="admin-section" style="display: none;">
                <h2>Series Management</h2>

                <div class="add-video-form">
                    <h3>Add New Series</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Title: *</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="genres">Genres:</label>
                                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 8px;">
                                    <?php foreach ($all_genres as $g): ?>
                                        <label style="display: block; margin: 4px 0;">
                                            <input type="checkbox" name="genres[]" value="<?php echo $g['id']; ?>">
                                            <?php echo htmlspecialchars($g['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="release_year">Release Year:</label>
                                <input type="number" name="release_year" min="1900" max="2030" placeholder="2024">
                            </div>
                            <div class="form-group">
                                <label for="director">Creator/Director:</label>
                                <input type="text" name="director" placeholder="Series creator">
                            </div>
                            <div class="form-group">
                                <label for="cast">Cast:</label>
                                <input type="text" name="cast" placeholder="Main cast members">
                            </div>
                            <div class="form-group">
                                <label for="language">Language:</label>
                                <input type="text" name="language" value="English">
                            </div>
                            <div class="form-group">
                                <label for="thumbnail_url">Thumbnail URL:</label>
                                <input type="url" name="thumbnail_url" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label for="tags">Tags (comma-separated):</label>
                                <input type="text" name="tags" placeholder="drama, thriller">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="description">Description:</label>
                                <textarea name="description" rows="4"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_series" class="btn btn-primary">Add Series</button>
                    </form>
                </div>

                <h3>All Series (<?php echo count($all_series); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Genres</th>
                            <th>Seasons</th>
                            <th>Episodes</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_series as $series): ?>
                            <tr>
                                <td><?php echo $series['id']; ?></td>
                                <td><?php echo htmlspecialchars($series['title']); ?></td>
                                <td><?php echo htmlspecialchars($series['genres'] ?: 'None'); ?></td>
                                <td><?php echo $series['season_count']; ?></td>
                                <td><?php echo $series['episode_count']; ?></td>
                                <td>
                                    <span class="status status-<?php echo $series['status']; ?>">
                                        <?php echo ucfirst($series['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($series['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Seasons Management Section -->
            <div id="seasons-section" class="admin-section" style="display: none;">
                <h2>Seasons Management</h2>

                <div class="add-video-form">
                    <h3>Add New Season</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="series_id">Series: *</label>
                                <select name="series_id" required>
                                    <option value="">Select Series...</option>
                                    <?php foreach ($all_series as $s): ?>
                                        <option value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="season_number">Season Number: *</label>
                                <input type="number" name="season_number" min="1" required>
                            </div>
                            <div class="form-group">
                                <label for="title">Title:</label>
                                <input type="text" name="title" placeholder="Auto: Season X">
                            </div>
                            <div class="form-group">
                                <label for="release_year">Release Year:</label>
                                <input type="number" name="release_year" min="1900" max="2030" placeholder="2024">
                            </div>
                            <div class="form-group">
                                <label for="thumbnail_url">Thumbnail URL:</label>
                                <input type="url" name="thumbnail_url" placeholder="https://...">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="description">Description:</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_season" class="btn btn-primary">Add Season</button>
                    </form>
                </div>

                <h3>All Seasons (<?php echo count($all_seasons); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Series</th>
                            <th>Season #</th>
                            <th>Title</th>
                            <th>Episodes</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_seasons as $season): ?>
                            <tr>
                                <td><?php echo $season['id']; ?></td>
                                <td><?php echo htmlspecialchars($season['series_title']); ?></td>
                                <td><?php echo $season['season_number']; ?></td>
                                <td><?php echo htmlspecialchars($season['title']); ?></td>
                                <td><?php echo $season['actual_episode_count']; ?></td>
                                <td>
                                    <span class="status status-<?php echo $season['status']; ?>">
                                        <?php echo ucfirst($season['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($season['created_at'])); ?></td>
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
                                <label for="title">Title: *</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="content_type">Content Type: *</label>
                                <select name="content_type" id="content_type" onchange="toggleSeriesFields()">
                                    <option value="movie">Movie</option>
                                    <option value="episode">TV Episode</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="youtube_id">YouTube ID: *</label>
                                <input type="text" name="youtube_id" required placeholder="dQw4w9WgXcQ">
                            </div>
                            <div class="form-group">
                                <label for="genres">Genres:</label>
                                <div style="max-height: 120px; overflow-y: auto; border: 1px solid #ccc; padding: 8px;">
                                    <?php foreach ($all_genres as $g): ?>
                                        <label style="display: block; margin: 4px 0;">
                                            <input type="checkbox" name="genres[]" value="<?php echo $g['id']; ?>">
                                            <?php echo htmlspecialchars($g['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group series-fields" style="display: none;">
                                <label for="series_id">Series: *</label>
                                <select name="series_id" id="series_id" onchange="loadSeasons()">
                                    <option value="">Select Series...</option>
                                    <?php foreach ($all_series as $s): ?>
                                        <option value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group series-fields" style="display: none;">
                                <label for="season_id">Season: *</label>
                                <select name="season_id" id="season_id">
                                    <option value="">Select Season...</option>
                                </select>
                            </div>
                            <div class="form-group series-fields" style="display: none;">
                                <label for="episode_number">Episode Number: *</label>
                                <input type="number" name="episode_number" min="1">
                            </div>
                            <div class="form-group">
                                <label for="duration_seconds">Duration (seconds):</label>
                                <input type="number" name="duration_seconds" min="0" placeholder="3600">
                            </div>
                            <div class="form-group">
                                <label for="release_year">Release Year:</label>
                                <input type="number" name="release_year" min="1900" max="2030" placeholder="2024">
                            </div>
                            <div class="form-group">
                                <label for="director">Director:</label>
                                <input type="text" name="director" placeholder="Director name">
                            </div>
                            <div class="form-group">
                                <label for="cast">Cast:</label>
                                <input type="text" name="cast" placeholder="Main actors">
                            </div>
                            <div class="form-group">
                                <label for="language">Language:</label>
                                <input type="text" name="language" value="English">
                            </div>
                            <div class="form-group">
                                <label for="tags">Tags (comma-separated):</label>
                                <input type="text" name="tags" placeholder="action, adventure">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="description">Description:</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_video" class="btn btn-primary">Add Video</button>
                    </form>
                </div>

                <script>
                    function toggleSeriesFields() {
                        const contentType = document.getElementById('content_type').value;
                        const seriesFields = document.querySelectorAll('.series-fields');

                        seriesFields.forEach(field => {
                            field.style.display = contentType === 'episode' ? 'block' : 'none';
                        });
                    }

                    function loadSeasons() {
                        const seriesId = document.getElementById('series_id').value;
                        const seasonSelect = document.getElementById('season_id');

                        seasonSelect.innerHTML = '<option value="">Select Season...</option>';

                        if (seriesId) {
                            fetch('../ajax/get_seasons.php?series_id=' + seriesId)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        data.seasons.forEach(season => {
                                            const option = document.createElement('option');
                                            option.value = season.id;
                                            option.textContent = `Season ${season.season_number}: ${season.title}`;
                                            seasonSelect.appendChild(option);
                                        });
                                    }
                                })
                                .catch(error => console.error('Error loading seasons:', error));
                        }
                    }
                </script>

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
            const sections = ['users', 'series', 'seasons', 'videos', 'subtitles'];
            sections.forEach(s => {
                const element = document.getElementById(s + '-section');
                if (element) element.style.display = 'none';
            });

            // Remove active class from all nav buttons
            const navButtons = document.querySelectorAll('.admin-nav .btn');
            navButtons.forEach(btn => btn.classList.remove('active'));

            // Show selected section
            const selectedSection = document.getElementById(section + '-section');
            if (selectedSection) selectedSection.style.display = 'block';

            // Add active class to clicked button
            if (event && event.target) {
                event.target.classList.add('active');
            }
        }
    </script>
</body>

</html>