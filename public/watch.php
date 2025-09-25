<?php
// public/watch.php - Enhanced Video player page v0.2.0 with Series Support
require_once '../config/database.php';
require_once '../includes/functions.php';

// Get video ID and start time
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$start_time = isset($_GET['t']) ? (int)$_GET['t'] : 0;

if (!$video_id) {
    header('Location: index.php');
    exit;
}

// Check if user is logged in and get status
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_status = null;
$username = null;
$expiry_date = null;

if ($user_id) {
    if (isValidUserSession($pdo, $user_id)) {
        $stmt = $pdo->prepare("SELECT username, status, expiry_date FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $user_status = $user['status'];
            $username = $user['username'];
            $expiry_date = $user['expiry_date'];
        }
    } else {
        unset($_SESSION['user_id']);
        $user_id = null;
    }
}

// Get video details with user data (including series info)
$video = getVideoWithUserData($pdo, $video_id, $user_id);

if (!$video) {
    header('Location: index.php');
    exit;
}

// Get subtitles if user has access
$subtitles_data = [];
$available_subtitles = [];

if (userHasSubtitleAccess($user_status)) {
    $stmt = $pdo->prepare("SELECT * FROM subtitles WHERE video_id = ? ORDER BY language");
    $stmt->execute([$video_id]);
    $available_subtitles = $stmt->fetchAll();
    
    // Load default subtitle (English if available, otherwise first available)
    $default_subtitle = null;
    foreach ($available_subtitles as $subtitle) {
        if ($subtitle['language'] === 'en') {
            $default_subtitle = $subtitle;
            break;
        }
    }
    
    if (!$default_subtitle && !empty($available_subtitles)) {
        $default_subtitle = $available_subtitles[0];
    }
    
    if ($default_subtitle && file_exists($default_subtitle['srt_file_path'])) {
        $srt_content = file_get_contents($default_subtitle['srt_file_path']);
        $subtitles_data = parseSrtContent($srt_content);
    }
}

// Get video recommendations
$recommendations = getVideoRecommendations($pdo, $user_id, $video_id, 6);

// Get video ratings and reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM ratings r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.video_id = ? AND r.review IS NOT NULL AND r.review != ''
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->execute([$video_id]);
$reviews = $stmt->fetchAll();

// Determine if this is an episode and get series info
$is_episode = ($video['content_type'] === 'episode');
$series_info = null;
$current_season = null;
$next_episode = null;
$prev_episode = null;

if ($is_episode && $video['series_id']) {
    // Get series info
    $stmt = $pdo->prepare("SELECT * FROM series WHERE id = ?");
    $stmt->execute([$video['series_id']]);
    $series_info = $stmt->fetch();
    
    // Get current season info
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE id = ?");
    $stmt->execute([$video['season_id']]);
    $current_season = $stmt->fetch();
    
    // Get next episode
    $stmt = $pdo->prepare("
        SELECT * FROM videos 
        WHERE season_id = ? AND episode_number > ? AND content_type = 'episode' AND status = 'active'
        ORDER BY episode_number ASC 
        LIMIT 1
    ");
    $stmt->execute([$video['season_id'], $video['episode_number']]);
    $next_episode = $stmt->fetch();
    
    // If no next episode in current season, try next season
    if (!$next_episode && $series_info) {
        $stmt = $pdo->prepare("
            SELECT v.* FROM videos v
            JOIN seasons s ON v.season_id = s.id
            WHERE s.series_id = ? AND s.season_number > ? AND v.content_type = 'episode' AND v.status = 'active'
            ORDER BY s.season_number ASC, v.episode_number ASC
            LIMIT 1
        ");
        $stmt->execute([$series_info['id'], $current_season['season_number']]);
        $next_episode = $stmt->fetch();
    }
    
    // Get previous episode
    $stmt = $pdo->prepare("
        SELECT * FROM videos 
        WHERE season_id = ? AND episode_number < ? AND content_type = 'episode' AND status = 'active'
        ORDER BY episode_number DESC 
        LIMIT 1
    ");
    $stmt->execute([$video['season_id'], $video['episode_number']]);
    $prev_episode = $stmt->fetch();
    
    // If no previous episode in current season, try previous season
    if (!$prev_episode && $series_info) {
        $stmt = $pdo->prepare("
            SELECT v.* FROM videos v
            JOIN seasons s ON v.season_id = s.id
            WHERE s.series_id = ? AND s.season_number < ? AND v.content_type = 'episode' AND v.status = 'active'
            ORDER BY s.season_number DESC, v.episode_number DESC
            LIMIT 1
        ");
        $stmt->execute([$series_info['id'], $current_season['season_number']]);
        $prev_episode = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - MovieStream v0.2.0</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="description" content="<?php echo htmlspecialchars(substr($video['description'], 0, 160)); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($video['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($video['description'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo $video['thumbnail_url']; ?>">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo">
                    <a href="index.php" style="color: white; text-decoration: none;">MovieStream v0.2.0</a>
                </h1>
                <div class="nav-links">
                    <?php if ($username): ?>
                        <span class="welcome">Welcome, <?php echo htmlspecialchars($username); ?></span>
                        <span class="status status-<?php echo $user_status; ?>">
                            <?php echo ucfirst($user_status); ?> Member
                        </span>
                        <?php if ($expiry_date && $user_status === 'active'): ?>
                            <span class="expiry">Until: <?php echo date('M d, Y', strtotime($expiry_date)); ?></span>
                        <?php endif; ?>
                        <a href="../user/dashboard.php" class="btn">Dashboard</a>
                        <a href="../logout.php" class="btn">Logout</a>
                    <?php else: ?>
                        <a href="../login.php" class="btn">Login</a>
                        <a href="../register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <?php 
            $breadcrumbs = [
                ['title' => 'Home', 'url' => 'index.php']
            ];
            
            if ($is_episode) {
                $breadcrumbs[] = ['title' => 'TV Series', 'url' => 'index.php?type=series'];
                $breadcrumbs[] = ['title' => $series_info['title'], 'url' => 'series.php?id=' . $series_info['id']];
                $breadcrumbs[] = ['title' => 'Season ' . $current_season['season_number']];
                $breadcrumbs[] = ['title' => 'Episode ' . $video['episode_number']];
            } else {
                $breadcrumbs[] = ['title' => 'Movies', 'url' => 'index.php?type=movie'];
                $breadcrumbs[] = ['title' => $video['title']];
            }
            
            echo generateBreadcrumbs($breadcrumbs);
            ?>

            <div class="video-section">
                <div class="video-header">
                    <h1>
                        <?php if ($is_episode): ?>
                            📺 <?php echo htmlspecialchars($series_info['title']); ?> - 
                            S<?php echo $current_season['season_number']; ?>E<?php echo $video['episode_number']; ?>: 
                            <?php echo htmlspecialchars($video['title']); ?>
                        <?php else: ?>
                            🎬 <?php echo htmlspecialchars($video['title']); ?>
                        <?php endif; ?>
                    </h1>
                    
                    <!-- Episode Navigation for Series -->
                    <?php if ($is_episode): ?>
                    <div class="episode-navigation">
                        <div class="episode-nav-buttons">
                            <?php if ($prev_episode): ?>
                                <a href="watch.php?id=<?php echo $prev_episode['id']; ?>" class="btn btn-secondary">
                                    ⬅️ Previous Episode
                                </a>
                            <?php endif; ?>
                            
                            <a href="series.php?id=<?php echo $series_info['id']; ?>&season=<?php echo $current_season['season_number']; ?>" class="btn">
                                📺 View All Episodes
                            </a>
                            
                            <?php if ($next_episode): ?>
                                <a href="watch.php?id=<?php echo $next_episode['id']; ?>" class="btn btn-secondary">
                                    Next Episode ➡️
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="video-meta">
                        <span class="genre-tags">
                            <?php 
                            $genres = explode(', ', $video['genres']);
                            $colors = explode(',', $video['genre_colors'] ?? '');
                            foreach ($genres as $index => $genre):
                                $color = isset($colors[$index]) ? $colors[$index] : '#667eea';
                            ?>
                                <span class="genre-tag" style="background-color: <?php echo $color; ?>">
                                    <?php echo htmlspecialchars($genre); ?>
                                </span>
                            <?php endforeach; ?>
                        </span>
                        
                        <div class="video-info-row">
                            <?php if ($video['release_year']): ?>
                                <span class="info-item">📅 <?php echo $video['release_year']; ?></span>
                            <?php endif; ?>
                            
                            <?php if ($video['duration_seconds']): ?>
                                <span class="info-item">⏱️ <?php echo formatDuration($video['duration_seconds']); ?></span>
                            <?php endif; ?>
                            
                            <span class="info-item">👀 <?php echo number_format($video['view_count']); ?> views</span>
                            
                            <?php if ($video['avg_rating'] > 0): ?>
                                <span class="info-item">⭐ <?php echo number_format($video['avg_rating'], 1); ?>/5 (<?php echo $video['rating_count']; ?> ratings)</span>
                            <?php endif; ?>
                            
                            <?php if (userHasSubtitleAccess($user_status) && !empty($available_subtitles)): ?>
                                <span class="info-item subtitle-indicator">🔤 Subtitles Available</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($user_id): ?>
                        <div class="user-actions">
                            <button id="watchlist-btn" 
                                    class="action-btn <?php echo $video['in_watchlist'] ? 'active' : ''; ?>"
                                    data-video-id="<?php echo $video_id; ?>">
                                <?php echo $video['in_watchlist'] ? '❤️ In Watchlist' : '🤍 Add to Watchlist'; ?>
                            </button>
                            
                            <div class="rating-container">
                                <span class="rating-label">Rate this <?php echo $is_episode ? 'episode' : 'video'; ?>:</span>
                                <div class="star-rating" data-video-id="<?php echo $video_id; ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo ($video['user_rating'] && $i <= $video['user_rating']) ? 'active' : ''; ?>" 
                                              data-rating="<?php echo $i; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($video['user_rating']): ?>
                                    <span class="user-rating-text">Your rating: <?php echo $video['user_rating']; ?>/5</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="video-container">
                    <div class="video-wrapper">
                        <iframe id="youtube-player" 
                                src="<?php echo getYouTubeEmbedUrl($video['youtube_id'], ['start' => $start_time]); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" 
                                allowfullscreen>
                        </iframe>
                        
                        <?php if (userHasSubtitleAccess($user_status) && !empty($subtitles_data)): ?>
                            <div id="subtitles-overlay"></div>
                            <div id="fullscreen-subtitles"></div>
                            
                            <div class="subtitle-controls">
                                <select id="subtitle-language">
                                    <option value="">No subtitles</option>
                                    <?php foreach ($available_subtitles as $subtitle): ?>
                                        <option value="<?php echo $subtitle['language']; ?>" 
                                                <?php echo ($subtitle['language'] === 'en') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subtitle['language_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button id="subtitle-sync-btn" class="btn btn-small">Sync -100ms</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="video-content">
                    <div class="video-details">
                        <h3>About this <?php echo $is_episode ? 'episode' : 'video'; ?></h3>
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($video['description'])); ?>
                        </div>
                        
                        <?php if ($video['director']): ?>
                            <p><strong>Director:</strong> <?php echo htmlspecialchars($video['director']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($video['cast']): ?>
                            <p><strong>Cast:</strong> <?php echo htmlspecialchars($video['cast']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($video['language']): ?>
                            <p><strong>Language:</strong> <?php echo htmlspecialchars($video['language']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!userHasSubtitleAccess($user_status)): ?>
                            <div class="alert alert-info">
                                <p>📝 Want to see subtitles? <a href="../login.php">Login</a> and ask admin to activate your account!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Series Episodes Navigation -->
                    <?php if ($is_episode && !empty($video['season_episodes'])): ?>
                    <div class="series-navigation">
                        <h3>Season <?php echo $current_season['season_number']; ?> Episodes</h3>
                        <div class="episodes-grid">
                            <?php foreach ($video['season_episodes'] as $episode): ?>
                                <div class="episode-item <?php echo $episode['id'] == $video_id ? 'current' : ''; ?>" 
                                     onclick="<?php echo $episode['id'] != $video_id ? "window.location.href='watch.php?id=" . $episode['id'] . "'" : ''; ?>">
                                    <div class="episode-header">
                                        <span class="episode-number">Episode <?php echo $episode['episode_number']; ?></span>
                                        <?php if ($episode['completed']): ?>
                                            <span class="completed-badge">✓</span>
                                        <?php endif; ?>
                                        <?php if ($episode['id'] == $video_id): ?>
                                            <span class="current-badge">▶ Now Playing</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="episode-title"><?php echo htmlspecialchars($episode['title']); ?></div>
                                    <div class="episode-meta">
                                        <?php if ($episode['duration_seconds']): ?>
                                            <span>⏱️ <?php echo formatDuration($episode['duration_seconds']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Other Seasons -->
                        <?php if (!empty($video['all_seasons']) && count($video['all_seasons']) > 1): ?>
                        <div class="other-seasons">
                            <h4>Other Seasons</h4>
                            <div class="seasons-list">
                                <?php foreach ($video['all_seasons'] as $season): ?>
                                    <?php if ($season['id'] != $video['season_id']): ?>
                                        <a href="series.php?id=<?php echo $video['series_id']; ?>&season=<?php echo $season['season_number']; ?>" 
                                           class="season-btn">
                                            Season <?php echo $season['season_number']; ?>
                                            <small>(<?php echo $season['episode_count']; ?> episodes)</small>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($reviews)): ?>
                    <div class="reviews-section">
                        <h3>User Reviews</h3>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                                        <span class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </span>
                                        <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="review-content">
                                        <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($user_id): ?>
                    <div class="write-review-section">
                        <h3>Write a Review</h3>
                        <form id="review-form">
                            <textarea id="review-text" placeholder="Share your thoughts about this <?php echo $is_episode ? 'episode' : 'video'; ?>..." rows="4"><?php echo $video['user_review'] ? htmlspecialchars($video['user_review']) : ''; ?></textarea>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Auto-play Next Episode -->
            <?php if ($is_episode && $next_episode): ?>
            <div id="next-episode-modal" class="next-episode-modal" style="display: none;">
                <div class="modal-content">
                    <h3>Up Next</h3>
                    <div class="next-episode-info">
                        <img src="<?php echo $next_episode['thumbnail_url'] ?: getYouTubeThumbnail($next_episode['youtube_id']); ?>" 
                             alt="Next Episode">
                        <div class="next-episode-text">
                            <h4>Episode <?php echo $next_episode['episode_number']; ?>: <?php echo htmlspecialchars($next_episode['title']); ?></h4>
                            <p>Starting in <span id="countdown">10</span> seconds...</p>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button onclick="cancelAutoplay()" class="btn">Cancel</button>
                        <button onclick="playNextEpisode()" class="btn btn-primary">Play Now</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($recommendations)): ?>
            <div class="recommendations-section">
                <h2>Recommended for You</h2>
                <div class="content-grid">
                    <?php foreach ($recommendations as $rec_video): ?>
                        <div class="content-card" onclick="window.location.href='watch.php?id=<?php echo $rec_video['id']; ?>'">
                            <div class="content-thumbnail">
                                <img src="<?php echo $rec_video['thumbnail_url'] ?: getYouTubeThumbnail($rec_video['youtube_id']); ?>" 
                                     alt="<?php echo htmlspecialchars($rec_video['title']); ?>">
                                <div class="play-overlay">
                                    <div class="play-button">▶</div>
                                </div>
                            </div>
                            <div class="content-info">
                                <h4><?php echo htmlspecialchars($rec_video['title']); ?></h4>
                                <p class="genre"><?php echo htmlspecialchars($rec_video['genres']); ?></p>
                                <div class="video-stats">
                                    <?php if ($rec_video['avg_rating'] > 0): ?>
                                        <span>⭐ <?php echo number_format($rec_video['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="watch.php?id=<?php echo $rec_video['id']; ?>" 
                                   class="btn btn-primary"
                                   onclick="event.stopPropagation()">Watch Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <style>
        .episode-navigation {
            margin: 1rem 0;
            text-align: center;
        }
        
        .episode-nav-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .current-badge {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
        }
        
        .next-episode-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: rgba(30, 30, 46, 0.95);
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .next-episode-info {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            align-items: center;
        }
        
        .next-episode-info img {
            width: 120px;
            height: 68px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .next-episode-text {
            flex: 1;
            text-align: left;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .episode-nav-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .next-episode-info {
                flex-direction: column;
                text-align: center;
            }
            
            .next-episode-text {
                text-align: center;
            }
        }
    </style>

    <script>
        // Enhanced video player with subtitles and series navigation
        let player;
        let subtitleInterval;
        let isFullscreen = false;
        let subtitleOffset = 0;
        let currentSubtitles = <?php echo json_encode($subtitles_data); ?>;
        let availableSubtitles = <?php echo json_encode($available_subtitles); ?>;
        let autoplayTimer = null;
        let countdownTimer = null;
        
        // Load YouTube API
        function loadYouTubeAPI() {
            if (window.YT) {
                onYouTubeIframeAPIReady();
                return;
            }
            
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        function onYouTubeIframeAPIReady() {
            player = new YT.Player('youtube-player', {
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange
                }
            });
        }

        function onPlayerReady(event) {
            startSubtitleSync();
            setupFullscreenDetection();
            setupProgressTracking();
            
            <?php if ($start_time > 0): ?>
            player.seekTo(<?php echo $start_time; ?>, true);
            <?php endif; ?>
        }

        function onPlayerStateChange(event) {
            if (event.data === YT.PlayerState.PLAYING) {
                trackVideoProgress();
            } else if (event.data === YT.PlayerState.ENDED) {
                <?php if ($is_episode && $next_episode): ?>
                showNextEpisodeModal();
                <?php endif; ?>
            }
        }

        // Next episode functionality
        <?php if ($is_episode && $next_episode): ?>
        function showNextEpisodeModal() {
            const modal = document.getElementById('next-episode-modal');
            if (modal) {
                modal.style.display = 'flex';
                startCountdown();
            }
        }

        function startCountdown() {
            let seconds = 10;
            const countdownElement = document.getElementById('countdown');
            
            countdownTimer = setInterval(() => {
                countdownElement.textContent = seconds;
                seconds--;
                
                if (seconds < 0) {
                    playNextEpisode();
                }
            }, 1000);
            
            // Auto-play after 10 seconds
            autoplayTimer = setTimeout(() => {
                playNextEpisode();
            }, 10000);
        }

        function playNextEpisode() {
            window.location.href = 'watch.php?id=<?php echo $next_episode['id']; ?>';
        }

        function cancelAutoplay() {
            const modal = document.getElementById('next-episode-modal');
            if (modal) {
                modal.style.display = 'none';
            }
            
            if (autoplayTimer) {
                clearTimeout(autoplayTimer);
            }
            
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }
        }
        <?php endif; ?>

        function startSubtitleSync() {
            if (subtitleInterval) {
                clearInterval(subtitleInterval);
            }

            if (currentSubtitles.length === 0) {
                return;
            }

            subtitleInterval = setInterval(() => {
                if (player && player.getCurrentTime) {
                    const currentTime = player.getCurrentTime() + (subtitleOffset / 1000);
                    const currentSubtitle = currentSubtitles.find(sub => 
                        currentTime >= sub.start_time && currentTime <= sub.end_time
                    );
                    
                    updateSubtitleDisplay(currentSubtitle ? currentSubtitle.text : '');
                }
            }, 100);
        }

        function updateSubtitleDisplay(text) {
            const normalOverlay = document.getElementById('subtitles-overlay');
            const fullscreenOverlay = document.getElementById('fullscreen-subtitles');
            
            if (text) {
                if (normalOverlay) {
                    normalOverlay.textContent = text;
                    normalOverlay.style.display = isFullscreen ? 'none' : 'block';
                }
                
                if (fullscreenOverlay) {
                    fullscreenOverlay.textContent = text;
                    fullscreenOverlay.style.display = isFullscreen ? 'block' : 'none';
                }
            } else {
                if (normalOverlay) normalOverlay.style.display = 'none';
                if (fullscreenOverlay) fullscreenOverlay.style.display = 'none';
            }
        }

        function setupFullscreenDetection() {
            document.addEventListener('fullscreenchange', handleFullscreenChange);
            document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
            document.addEventListener('mozfullscreenchange', handleFullscreenChange);
            document.addEventListener('msfullscreenchange', handleFullscreenChange);
        }

        function handleFullscreenChange() {
            const fullscreenElement = document.fullscreenElement || 
                                    document.webkitFullscreenElement || 
                                    document.mozFullScreenElement || 
                                    document.msFullscreenElement;
            
            isFullscreen = !!fullscreenElement;
            updateSubtitleDisplay(getCurrentSubtitleText());
        }

        function getCurrentSubtitleText() {
            if (player && player.getCurrentTime && currentSubtitles.length > 0) {
                const currentTime = player.getCurrentTime() + (subtitleOffset / 1000);
                const currentSubtitle = currentSubtitles.find(sub => 
                    currentTime >= sub.start_time && currentTime <= sub.end_time
                );
                return currentSubtitle ? currentSubtitle.text : '';
            }
            return '';
        }

        function setupProgressTracking() {
            setInterval(() => {
                if (player && player.getCurrentTime && player.getDuration) {
                    const currentTime = Math.floor(player.getCurrentTime());
                    const duration = Math.floor(player.getDuration());
                    const completed = currentTime >= duration - 30;
                    
                    if (currentTime > 0) {
                        updateProgress(currentTime, duration, completed);
                    }
                }
            }, 10000);
        }

        function updateProgress(progress, duration, completed) {
            <?php if ($user_id): ?>
            fetch('../user/ajax/update_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    video_id: <?php echo $video_id; ?>,
                    progress: progress,
                    duration: duration,
                    completed: completed
                })
            }).catch(error => console.error('Progress tracking error:', error));
            <?php endif; ?>
        }

        // Subtitle language switching
        function loadSubtitles(language) {
            if (!language) {
                currentSubtitles = [];
                updateSubtitleDisplay('');
                return;
            }
            
            fetch(`../ajax/get_subtitles.php?video_id=<?php echo $video_id; ?>&lang=${language}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentSubtitles = data.subtitles;
                    } else {
                        currentSubtitles = [];
                    }
                })
                .catch(error => {
                    console.error('Subtitle loading error:', error);
                    currentSubtitles = [];
                });
        }

        // User interactions
        <?php if ($user_id): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Watchlist toggle
            document.getElementById('watchlist-btn')?.addEventListener('click', function() {
                const videoId = this.dataset.videoId;
                const isInWatchlist = this.classList.contains('active');
                const endpoint = isInWatchlist ? '../user/ajax/remove_watchlist.php' : '../user/ajax/add_watchlist.php';
                
                const originalText = this.textContent;
                this.textContent = '⏳ Processing...';
                this.disabled = true;
                
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ video_id: parseInt(videoId) })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('active');
                        this.textContent = this.classList.contains('active') ? '❤️ In Watchlist' : '🤍 Add to Watchlist';
                    } else {
                        this.textContent = originalText;
                        alert('Failed to update watchlist');
                    }
                    this.disabled = false;
                })
                .catch(error => {
                    console.error('Watchlist error:', error);
                    this.textContent = originalText;
                    this.disabled = false;
                    alert('An error occurred');
                });
            });

            // Star rating
            document.querySelectorAll('.star-rating .star').forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    const videoId = parseInt(this.parentNode.dataset.videoId);
                    
                    fetch('../user/ajax/rate_video.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ video_id: videoId, rating: rating })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.parentNode.querySelectorAll('.star').forEach((s, index) => {
                                s.classList.toggle('active', index < rating);
                            });
                            const ratingText = this.parentNode.parentNode.querySelector('.user-rating-text');
                            if (ratingText) {
                                ratingText.textContent = `Your rating: ${rating}/5`;
                            } else {
                                const newText = document.createElement('span');
                                newText.className = 'user-rating-text';
                                newText.textContent = `Your rating: ${rating}/5`;
                                this.parentNode.parentNode.appendChild(newText);
                            }
                        } else {
                            alert('Failed to save rating');
                        }
                    })
                    .catch(error => {
                        console.error('Rating error:', error);
                        alert('An error occurred');
                    });
                });
            });

            // Review submission
            document.getElementById('review-form')?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const reviewText = document.getElementById('review-text').value.trim();
                
                fetch('../user/ajax/rate_video.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        video_id: <?php echo $video_id; ?>, 
                        rating: <?php echo $video['user_rating'] ?: 5; ?>, 
                        review: reviewText 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Review saved successfully!');
                        location.reload();
                    } else {
                        alert('Failed to save review');
                    }
                })
                .catch(error => {
                    console.error('Review error:', error);
                    alert('An error occurred');
                });
            });
        });
        <?php endif; ?>

        // Subtitle controls
        document.getElementById('subtitle-language')?.addEventListener('change', function() {
            loadSubtitles(this.value);
        });

        document.getElementById('subtitle-sync-btn')?.addEventListener('click', function() {
            subtitleOffset -= 100; // Adjust by -100ms
            this.textContent = `Sync ${subtitleOffset}ms`;
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (player && player.getPlayerState) {
                switch(e.key) {
                    case ' ': // Spacebar - play/pause
                        e.preventDefault();
                        if (player.getPlayerState() === YT.PlayerState.PLAYING) {
                            player.pauseVideo();
                        } else {
                            player.playVideo();
                        }
                        break;
                    case 'ArrowLeft': // Left arrow - rewind 10 seconds
                        e.preventDefault();
                        player.seekTo(Math.max(0, player.getCurrentTime() - 10), true);
                        break;
                    case 'ArrowRight': // Right arrow - forward 10 seconds
                        e.preventDefault();
                        player.seekTo(player.getCurrentTime() + 10, true);
                        break;
                    case 'f': // F key - toggle fullscreen
                    case 'F':
                        e.preventDefault();
                        if (document.fullscreenElement) {
                            document.exitFullscreen();
                        } else {
                            document.querySelector('.video-wrapper').requestFullscreen();
                        }
                        break;
                    <?php if ($is_episode): ?>
                    case 'n': // N key - next episode
                    case 'N':
                        <?php if ($next_episode): ?>
                        e.preventDefault();
                        window.location.href = 'watch.php?id=<?php echo $next_episode['id']; ?>';
                        <?php endif; ?>
                        break;
                    case 'p': // P key - previous episode
                    case 'P':
                        <?php if ($prev_episode): ?>
                        e.preventDefault();
                        window.location.href = 'watch.php?id=<?php echo $prev_episode['id']; ?>';
                        <?php endif; ?>
                        break;
                    <?php endif; ?>
                }
            }
        });

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadYouTubeAPI();
            
            // Add keyboard navigation hints
            const hints = document.createElement('div');
            hints.innerHTML = `
                <div style="position: fixed; bottom: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; border-radius: 4px; font-size: 0.8rem; z-index: 50;">
                    <strong>Controls:</strong> Space=Play/Pause, ←/→=Seek, F=Fullscreen<?php if ($is_episode && ($next_episode || $prev_episode)): ?>, N=Next<?php if ($prev_episode): ?>, P=Previous<?php endif; ?><?php endif; ?>
                </div>
            `;
            
            // Auto-hide hints after 5 seconds
            setTimeout(() => {
                if (hints.parentNode) {
                    hints.remove();
                }
            }, 5000);
            
            document.body.appendChild(hints);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (subtitleInterval) {
                clearInterval(subtitleInterval);
            }
            if (autoplayTimer) {
                clearTimeout(autoplayTimer);
            }
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }
        });
    </script>
</body>
</html>