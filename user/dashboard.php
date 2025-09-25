<?php
// user/dashboard.php - Fixed User Dashboard v0.2.0
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("
    SELECT * FROM users WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../logout.php');
    exit;
}

// Get detailed user stats using function
$user_stats = getUserStats($pdo, $user_id);

// Get recently watched videos - FIXED QUERY
$stmt = $pdo->prepare("
    SELECT v.*,
           up.last_watched,
           up.progress_seconds,
           up.completed,
           up.watch_count,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM user_progress up
    JOIN videos v ON up.video_id = v.id
    LEFT JOIN ratings r ON v.id = r.video_id
    WHERE up.user_id = ? AND v.status = 'active'
    GROUP BY v.id, up.last_watched, up.progress_seconds, up.completed, up.watch_count
    ORDER BY up.last_watched DESC
    LIMIT 8
");
$stmt->execute([$user_id]);
$recently_watched = $stmt->fetchAll();

// Get watchlist - FIXED QUERY
$stmt = $pdo->prepare("
    SELECT v.*,
           w.added_at,
           w.priority,
           w.notes,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM watchlist w
    JOIN videos v ON w.video_id = v.id
    LEFT JOIN ratings r ON v.id = r.video_id
    WHERE w.user_id = ? AND v.status = 'active'
    GROUP BY v.id, w.added_at, w.priority, w.notes
    ORDER BY w.priority DESC, w.added_at DESC
");
$stmt->execute([$user_id]);
$watchlist = $stmt->fetchAll();

// Get user's recent activity
$stmt = $pdo->prepare("
    SELECT ual.*, v.title as video_title
    FROM user_activity_logs ual
    LEFT JOIN videos v ON ual.video_id = v.id
    WHERE ual.user_id = ?
    ORDER BY ual.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_activity = $stmt->fetchAll();

// Get favorite genres based on watch history
$stmt = $pdo->prepare("
    SELECT g.name, g.color_code, COUNT(*) as watch_count
    FROM user_progress up
    JOIN video_genres vg ON up.video_id = vg.video_id
    JOIN genres g ON vg.genre_id = g.id
    WHERE up.user_id = ?
    GROUP BY g.id, g.name, g.color_code
    ORDER BY watch_count DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$favorite_genres = $stmt->fetchAll();

// Get recommendations
$recommendations = getVideoRecommendations($pdo, $user_id, null, 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - MovieStream v0.2.0</title>
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
                    <a href="../public/index.php" class="btn">Browse Content</a>
                    <a href="../logout.php" class="btn">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                <div class="user-status">
                    <span class="status status-<?php echo $user['status']; ?>">
                        <?php echo ucfirst($user['status']); ?> Member
                    </span>
                    <?php if ($user['expiry_date'] && $user['status'] === 'active'): ?>
                        <span class="expiry-info">
                            Valid until: <?php echo date('M d, Y', strtotime($user['expiry_date'])); ?>
                        </span>
                    <?php elseif ($user['status'] === 'inactive'): ?>
                        <span class="inactive-notice">Contact admin to activate your account</span>
                    <?php endif; ?>
                </div>
                
                <div class="user-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $user_stats['videos_watched']; ?></div>
                        <div class="stat-label">Videos Watched</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $user_stats['watchlist_count']; ?></div>
                        <div class="stat-label">In Watchlist</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $user_stats['completed_videos']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($user_stats['avg_rating_given'], 1); ?>⭐</div>
                        <div class="stat-label">Avg Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $user_stats['reviews_written']; ?></div>
                        <div class="stat-label">Reviews Written</div>
                    </div>
                </div>
            </div>

            <!-- Continue Watching Section -->
            <?php if (!empty($recently_watched)): ?>
            <section class="dashboard-section">
                <h2>Continue Watching</h2>
                <div class="content-grid">
                    <?php foreach ($recently_watched as $video): ?>
                        <div class="content-card" onclick="window.location.href='../public/watch.php?id=<?php echo $video['id']; ?>'">
                            <div class="content-thumbnail">
                                <img src="<?php echo $video['thumbnail_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>"
                                     loading="lazy">
                                <div class="progress-overlay">
                                    <?php 
                                    $progress = ($video['duration_seconds'] > 0) ? 
                                        ($video['progress_seconds'] / $video['duration_seconds']) * 100 : 0;
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%"></div>
                                    </div>
                                </div>
                                <?php if (!$video['completed']): ?>
                                    <div class="resume-badge">Resume</div>
                                <?php else: ?>
                                    <div class="completed-badge">✓ Completed</div>
                                <?php endif; ?>
                            </div>
                            <div class="content-info">
                                <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                <div class="content-meta">
                                    <span class="meta-item">
                                        Last watched: <?php echo date('M d', strtotime($video['last_watched'])); ?>
                                    </span>
                                    <span class="meta-item">
                                        Watched <?php echo $video['watch_count']; ?> time<?php echo $video['watch_count'] > 1 ? 's' : ''; ?>
                                    </span>
                                    <?php if ($video['avg_rating'] > 0): ?>
                                        <span class="meta-item">⭐ <?php echo number_format($video['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="content-actions">
                                    <a href="../public/watch.php?id=<?php echo $video['id']; ?>&t=<?php echo $video['progress_seconds']; ?>" 
                                       class="btn btn-primary"
                                       onclick="event.stopPropagation()">
                                        <?php echo $video['completed'] ? 'Watch Again' : 'Continue'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- My Watchlist Section -->
            <?php if (!empty($watchlist)): ?>
            <section class="dashboard-section">
                <h2>My Watchlist</h2>
                <div class="content-grid">
                    <?php foreach ($watchlist as $video): ?>
                        <div class="content-card" onclick="window.location.href='../public/watch.php?id=<?php echo $video['id']; ?>'">
                            <div class="content-thumbnail">
                                <img src="<?php echo $video['thumbnail_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>"
                                     loading="lazy">
                                <div class="play-overlay">
                                    <div class="play-button">▶</div>
                                </div>
                                <button class="remove-watchlist" 
                                        data-video-id="<?php echo $video['id']; ?>" 
                                        title="Remove from watchlist"
                                        onclick="event.stopPropagation()"
                                        style="position: absolute; top: 10px; right: 10px; background: rgba(220, 53, 69, 0.8); border: none; border-radius: 50%; width: 30px; height: 30px; color: white; cursor: pointer; font-size: 0.8rem;">
                                    ❌
                                </button>
                                <div class="priority-indicator priority-<?php echo $video['priority']; ?>"
                                     style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.7); padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; color: #ffd700;">
                                    <?php echo str_repeat('★', $video['priority']); ?>
                                </div>
                            </div>
                            <div class="content-info">
                                <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                <div class="content-meta">
                                    <span class="meta-item">
                                        Added: <?php echo date('M d', strtotime($video['added_at'])); ?>
                                    </span>
                                    <?php if ($video['avg_rating'] > 0): ?>
                                        <span class="meta-item">⭐ <?php echo number_format($video['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($video['notes']): ?>
                                    <p class="description" style="font-size: 0.9rem; color: #ccc; margin-bottom: 1rem;">
                                        <?php echo htmlspecialchars($video['notes']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="content-actions">
                                    <a href="../public/watch.php?id=<?php echo $video['id']; ?>" 
                                       class="btn btn-primary"
                                       onclick="event.stopPropagation()">Watch Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="section-footer">
                    <p style="text-align: center; margin-top: 1rem;">
                        Showing <?php echo count($watchlist); ?> of <?php echo $user_stats['watchlist_count']; ?> items in your watchlist
                    </p>
                </div>
            </section>
            <?php endif; ?>

            <!-- Recommendations Section -->
            <?php if (!empty($recommendations)): ?>
            <section class="dashboard-section">
                <h2>Recommended for You</h2>
                <div class="content-grid">
                    <?php foreach ($recommendations as $video): ?>
                        <div class="content-card" onclick="window.location.href='../public/watch.php?id=<?php echo $video['id']; ?>'">
                            <div class="content-thumbnail">
                                <img src="<?php echo $video['thumbnail_url'] ?: getYouTubeThumbnail($video['youtube_id']); ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>"
                                     loading="lazy">
                                <div class="play-overlay">
                                    <div class="play-button">▶</div>
                                </div>
                            </div>
                            <div class="content-info">
                                <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                <p class="genre"><?php echo htmlspecialchars($video['genres']); ?></p>
                                <div class="content-meta">
                                    <?php if ($video['avg_rating'] > 0): ?>
                                        <span class="meta-item">⭐ <?php echo number_format($video['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                    <span class="meta-item">👀 <?php echo number_format($video['view_count']); ?></span>
                                </div>
                                <div class="content-actions">
                                    <a href="../public/watch.php?id=<?php echo $video['id']; ?>" 
                                       class="btn btn-primary"
                                       onclick="event.stopPropagation()">Watch</a>
                                    <button class="add-watchlist btn btn-small" 
                                            data-video-id="<?php echo $video['id']; ?>"
                                            onclick="event.stopPropagation()">
                                        + Watchlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Favorite Genres -->
            <?php if (!empty($favorite_genres)): ?>
            <section class="dashboard-section">
                <h2>Your Favorite Genres</h2>
                <div class="genre-stats" style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
                    <?php foreach ($favorite_genres as $genre): ?>
                        <div class="genre-item" style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="genre-tag" style="background-color: <?php echo $genre['color_code']; ?>; color: white; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </span>
                            <span class="genre-count" style="color: #ccc; font-size: 0.8rem;">
                                <?php echo $genre['watch_count']; ?> videos
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Recent Activity -->
            <?php if (!empty($recent_activity)): ?>
            <section class="dashboard-section">
                <h2>Recent Activity</h2>
                <div class="activity-list" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                        <div class="activity-item" style="display: flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px;">
                            <div class="activity-icon" style="font-size: 1.2rem;">
                                <?php
                                $icons = [
                                    'login' => '🔑',
                                    'video_play' => '▶️',
                                    'video_complete' => '✅',
                                    'rating' => '⭐',
                                    'watchlist_add' => '❤️',
                                    'watchlist_remove' => '💔'
                                ];
                                echo $icons[$activity['activity_type']] ?? '📝';
                                ?>
                            </div>
                            <div class="activity-content" style="flex: 1;">
                                <div class="activity-description" style="font-weight: 500;">
                                    <?php
                                    $descriptions = [
                                        'login' => 'Signed in to MovieStream',
                                        'video_play' => 'Started watching',
                                        'video_complete' => 'Finished watching',
                                        'rating' => 'Rated',
                                        'watchlist_add' => 'Added to watchlist',
                                        'watchlist_remove' => 'Removed from watchlist'
                                    ];
                                    echo $descriptions[$activity['activity_type']] ?? 'Unknown activity';
                                    
                                    if ($activity['video_title']) {
                                        echo ': ' . htmlspecialchars($activity['video_title']);
                                    }
                                    ?>
                                </div>
                                <div class="activity-time" style="font-size: 0.8rem; color: #ccc;">
                                    <?php echo timeAgo($activity['created_at']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Account Management -->
            <section class="dashboard-section">
                <h2>Quick Actions</h2>
                <div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                    <div class="setting-card" style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <div class="setting-icon" style="font-size: 2rem; margin-bottom: 1rem;">🎬</div>
                        <h4>Browse Movies</h4>
                        <p style="color: #ccc; margin-bottom: 1rem;">Discover new movies and series</p>
                        <a href="../public/index.php" class="btn">Browse Content</a>
                    </div>
                    <div class="setting-card" style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <div class="setting-icon" style="font-size: 2rem; margin-bottom: 1rem;">📺</div>
                        <h4>TV Series</h4>
                        <p style="color: #ccc; margin-bottom: 1rem;">Explore TV series and episodes</p>
                        <a href="../public/index.php?type=series" class="btn">View Series</a>
                    </div>
                    <div class="setting-card" style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <div class="setting-icon" style="font-size: 2rem; margin-bottom: 1rem;">🔍</div>
                        <h4>Search Content</h4>
                        <p style="color: #ccc; margin-bottom: 1rem;">Find specific movies or shows</p>
                        <a href="../public/index.php" class="btn">Search Now</a>
                    </div>
                    <?php if ($user['status'] === 'inactive'): ?>
                    <div class="setting-card" style="background: rgba(255, 193, 7, 0.1); padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid #ffc107;">
                        <div class="setting-icon" style="font-size: 2rem; margin-bottom: 1rem;">🔓</div>
                        <h4>Activate Account</h4>
                        <p style="color: #ffc107; margin-bottom: 1rem;">Get access to subtitles and more features</p>
                        <p style="font-size: 0.9rem; color: #ccc;">Contact admin to activate your account</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Remove from watchlist functionality
        document.querySelectorAll('.remove-watchlist').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const videoId = this.dataset.videoId;
                
                if (confirm('Remove this movie from your watchlist?')) {
                    fetch('ajax/remove_watchlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ video_id: parseInt(videoId) })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.content-card').remove();
                            // Update watchlist count if visible
                            updateWatchlistCount(-1);
                        } else {
                            alert('Failed to remove from watchlist');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred');
                    });
                }
            });
        });

        // Add to watchlist functionality
        document.querySelectorAll('.add-watchlist').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const videoId = this.dataset.videoId;
                
                fetch('ajax/add_watchlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ video_id: parseInt(videoId) })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = '✓ Added';
                        this.disabled = true;
                        this.style.background = '#28a745';
                        updateWatchlistCount(1);
                    } else {
                        alert('Failed to add to watchlist');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            });
        });

        function updateWatchlistCount(change) {
            const countElements = document.querySelectorAll('.stat-card:nth-child(2) .stat-number');
            countElements.forEach(element => {
                const currentCount = parseInt(element.textContent);
                element.textContent = Math.max(0, currentCount + change);
            });
        }

        // Add loading states
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth hover effects
            document.querySelectorAll('.content-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>

<?php
// Helper function for time ago display
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        return floor($time/60) . ' minutes ago';
    } elseif ($time < 86400) {
        return floor($time/3600) . ' hours ago';
    } elseif ($time < 2592000) {
        return floor($time/86400) . ' days ago';
    } else {
        return date('M d, Y', strtotime($datetime));
    }
}
?>