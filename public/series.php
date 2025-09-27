<?php
// public/series.php - Series page with season/episode navigation v0.2.0 - FIXED
require_once '../config/database.php';
require_once '../includes/functions.php';

// Get series ID
$series_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_season = isset($_GET['season']) ? (int)$_GET['season'] : 1;

if (!$series_id) {
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

// Get series with all seasons and episodes
$series = getSeriesWithEpisodes($pdo, $series_id, $user_id);

if (!$series) {
    header('Location: index.php');
    exit;
}

// Find the selected season or default to first
$current_season = null;
foreach ($series['seasons'] as $season) {
    if ($season['season_number'] == $selected_season) {
        $current_season = $season;
        break;
    }
}

// If selected season not found, use first season
if (!$current_season && !empty($series['seasons'])) {
    $current_season = $series['seasons'][0];
    $selected_season = $current_season['season_number'];
}

// Get series ratings and reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM ratings r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.series_id = ? AND r.review IS NOT NULL AND r.review != ''
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->execute([$series_id]);
$reviews = $stmt->fetchAll();

// Check if series is in user's watchlist
$in_watchlist = false;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM watchlist WHERE user_id = ? AND series_id = ?");
    $stmt->execute([$user_id, $series_id]);
    $in_watchlist = $stmt->fetch() !== false;
}

// Get user's rating for this series
$user_rating = null;
$user_review = '';
if ($user_id) {
    $stmt = $pdo->prepare("SELECT rating, review FROM ratings WHERE user_id = ? AND series_id = ?");
    $stmt->execute([$user_id, $series_id]);
    $rating_data = $stmt->fetch();
    if ($rating_data) {
        $user_rating = $rating_data['rating'];
        $user_review = $rating_data['review'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($series['title']); ?> - MovieStream v0.2.0</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="description" content="<?php echo htmlspecialchars(substr($series['description'], 0, 160)); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($series['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($series['description'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo $series['thumbnail_url']; ?>">
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
                ['title' => 'Home', 'url' => 'index.php'],
                ['title' => 'TV Series', 'url' => 'index.php?type=series'],
                ['title' => $series['title']]
            ];
            echo generateBreadcrumbs($breadcrumbs);
            ?>

            <div class="video-section">
                <div class="video-header">
                    <h1>📺 <?php echo htmlspecialchars($series['title']); ?></h1>
                    <div class="video-meta">
                        <span class="genre-tags">
                            <?php 
                            if ($series['genres']) {
                                $genres = explode(', ', $series['genres']);
                                foreach ($genres as $genre):
                            ?>
                                <span class="genre-tag"><?php echo htmlspecialchars($genre); ?></span>
                            <?php endforeach; } ?>
                        </span>
                        
                        <div class="video-info-row">
                            <?php if ($series['release_year']): ?>
                                <span class="info-item">📅 <?php echo $series['release_year']; ?></span>
                            <?php endif; ?>
                            
                            <span class="info-item">📺 <?php echo count($series['seasons']); ?> Season<?php echo count($series['seasons']) != 1 ? 's' : ''; ?></span>
                            
                            <?php 
                            $total_episodes = 0;
                            foreach ($series['seasons'] as $season) {
                                $total_episodes += count($season['episodes']);
                            }
                            ?>
                            <span class="info-item">🎬 <?php echo $total_episodes; ?> Episodes</span>
                            
                            <span class="info-item">👀 <?php echo number_format($series['view_count']); ?> views</span>
                            
                            <?php if ($series['avg_rating'] > 0): ?>
                                <span class="info-item">⭐ <?php echo number_format($series['avg_rating'], 1); ?>/5 (<?php echo $series['rating_count']; ?> ratings)</span>
                            <?php endif; ?>
                            
                            <?php if (userHasSubtitleAccess($user_status)): ?>
                                <span class="info-item subtitle-indicator">🔤 Subtitles Available</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($user_id): ?>
                        <div class="user-actions">
                            <button id="watchlist-btn" 
                                    class="action-btn <?php echo $in_watchlist ? 'active' : ''; ?>"
                                    data-series-id="<?php echo $series_id; ?>">
                                <?php echo $in_watchlist ? '❤️ In Watchlist' : '🤍 Add to Watchlist'; ?>
                            </button>
                            
                            <div class="rating-container">
                                <span class="rating-label">Rate this series:</span>
                                <div class="star-rating" data-series-id="<?php echo $series_id; ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo ($user_rating && $i <= $user_rating) ? 'active' : ''; ?>" 
                                              data-rating="<?php echo $i; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($user_rating): ?>
                                    <span class="user-rating-text">Your rating: <?php echo $user_rating; ?>/5</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Series Description -->
                <div class="video-content">
                    <div class="video-details">
                        <h3>About this series</h3>
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($series['description'])); ?>
                        </div>
                        
                        <?php if ($series['director']): ?>
                            <p><strong>Creator/Director:</strong> <?php echo htmlspecialchars($series['director']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($series['cast']): ?>
                            <p><strong>Cast:</strong> <?php echo htmlspecialchars($series['cast']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($series['language']): ?>
                            <p><strong>Language:</strong> <?php echo htmlspecialchars($series['language']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!userHasSubtitleAccess($user_status)): ?>
                            <div class="alert alert-info">
                                <p>📝 Want to see subtitles? <a href="../login.php">Login</a> and ask admin to activate your account!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Season/Episode Navigation -->
                    <div class="series-navigation">
                        <h3>Episodes</h3>
                        
                        <!-- Season Selector -->
                        <?php if (count($series['seasons']) > 1): ?>
                        <div class="seasons-list">
                            <?php foreach ($series['seasons'] as $season): ?>
                                <button class="season-btn <?php echo $season['season_number'] == $selected_season ? 'active' : ''; ?>"
                                        onclick="selectSeason(<?php echo $season['season_number']; ?>)">
                                    Season <?php echo $season['season_number']; ?>
                                    <small>(<?php echo count($season['episodes']); ?> episodes)</small>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Episodes Grid -->
                        <?php if ($current_season && !empty($current_season['episodes'])): ?>
                        <div id="episodes-container">
                            <h4>Season <?php echo $current_season['season_number']; ?> Episodes</h4>
                            <div class="episodes-grid">
                                <?php foreach ($current_season['episodes'] as $episode): ?>
                                    <div class="episode-item" onclick="window.location.href='watch.php?id=<?php echo $episode['id']; ?>'">
                                        <div class="episode-header">
                                            <span class="episode-number">Episode <?php echo $episode['episode_number']; ?></span>
                                            <?php if ($user_id && isset($episode['completed']) && $episode['completed']): ?>
                                                <span class="completed-badge">✓</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="episode-title"><?php echo htmlspecialchars($episode['title']); ?></div>
                                        <?php if ($episode['description']): ?>
                                            <div class="episode-description"><?php echo htmlspecialchars(substr($episode['description'], 0, 100)); ?>...</div>
                                        <?php endif; ?>
                                        <div class="episode-meta">
                                            <?php if ($episode['duration_seconds']): ?>
                                                <span>⏱️ <?php echo formatDuration($episode['duration_seconds']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($episode['avg_rating'] > 0): ?>
                                                <span>⭐ <?php echo number_format($episode['avg_rating'], 1); ?></span>
                                            <?php endif; ?>
                                            <?php if ($user_id && isset($episode['progress_seconds']) && $episode['progress_seconds'] > 0): ?>
                                                <span>📍 <?php echo round(($episode['progress_seconds'] / max(1, $episode['duration_seconds'])) * 100); ?>% watched</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="episode-actions">
                                            <a href="watch.php?id=<?php echo $episode['id']; ?>" class="btn btn-primary" onclick="event.stopPropagation()">
                                                ▶ Watch Now
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                            <p>No episodes available for this season.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Reviews Section -->
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
                            <textarea id="review-text" placeholder="Share your thoughts about this series..." rows="4"><?php echo $user_review ? htmlspecialchars($user_review) : ''; ?></textarea>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Season selection
        function selectSeason(seasonNumber) {
            window.location.href = `series.php?id=<?php echo $series_id; ?>&season=${seasonNumber}`;
        }

        // User interactions
        <?php if ($user_id): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Watchlist toggle
            document.getElementById('watchlist-btn')?.addEventListener('click', function() {
                const seriesId = this.dataset.seriesId;
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
                    body: JSON.stringify({ series_id: parseInt(seriesId) })
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
                    const seriesId = parseInt(this.parentNode.dataset.seriesId);
                    
                    fetch('../user/ajax/rate_content.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ series_id: seriesId, rating: rating })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update star display
                            this.parentNode.querySelectorAll('.star').forEach((s, index) => {
                                s.classList.toggle('active', index < rating);
                            });
                            // Update rating text
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
                
                fetch('../user/ajax/rate_content.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        series_id: <?php echo $series_id; ?>, 
                        rating: <?php echo $user_rating ?: 5; ?>, 
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

        // Add keyboard navigation for episodes
        document.querySelectorAll('.episode-item').forEach(item => {
            item.setAttribute('tabindex', '0');
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    </script>
</body>
</html>