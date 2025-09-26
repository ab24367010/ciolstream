<?php
// public/index.php - Enhanced Main page v0.2.0 with Series Support
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and get status
$user_status = null;
$username = null;
$expiry_date = null;
$user_id = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Validate user session
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
        // Invalid session, clear it
        unset($_SESSION['user_id']);
        $user_id = null;
    }
}

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$content_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Determine what to show
$show_movies = empty($content_type) || $content_type === 'movie';
$show_series = empty($content_type) || $content_type === 'series';

$movies = [];
$series = [];

if ($show_movies) {
    // Get movies
    $movies = searchVideos($pdo, $search, $genre_filter, ['content_type' => 'movie']);
}

if ($show_series) {
    // Get series
    $series = searchSeries($pdo, $search, $genre_filter);
}

// Get genres for filter
$genres = getGenres($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieStream v0.2.0 - Watch Movies & Series Online</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="description" content="Stream movies and TV series online with MovieStream. Watch your favorite content with subtitles and track your progress.">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo">MovieStream v0.2.0</h1>
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
            <?php if (!$username): ?>
                <div class="alert alert-info">
                    <h3>🎬 Welcome to MovieStream v0.2.0!</h3>
                    <p>Discover amazing movies and TV series! <a href="../login.php">Login</a> or <a href="../register.php">register</a> to unlock subtitles, track your progress, and create watchlists.</p>
                </div>
            <?php endif; ?>

            <?php if ($username && $user_status === 'inactive'): ?>
                <div class="alert alert-warning">
                    <h3>⏸️ Account Inactive</h3>
                    <p>You can watch content, but some features like subtitles are limited. Contact admin to activate your account for the full experience!</p>
                </div>
            <?php endif; ?>

            <!-- Content Type Selector -->
            <div class="content-type-selector">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => ''])); ?>" 
                   class="content-type-btn <?php echo empty($content_type) ? 'active' : ''; ?>">
                    🎭 All Content
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'movie'])); ?>" 
                   class="content-type-btn <?php echo $content_type === 'movie' ? 'active' : ''; ?>">
                    🎬 Movies
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'series'])); ?>" 
                   class="content-type-btn <?php echo $content_type === 'series' ? 'active' : ''; ?>">
                    📺 TV Series
                </a>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-section">
                <h2>Browse Content</h2>
                <form method="GET" class="search-form">
                    <div class="search-controls">
                        <input type="text" 
                               name="search" 
                               placeholder="Search movies and series..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="search-input">
                        
                        <select name="genre" class="genre-select">
                            <option value="">All Genres</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo htmlspecialchars($genre); ?>" 
                                        <?php echo $genre_filter === $genre ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($content_type); ?>">
                        
                        <button type="submit" class="btn btn-primary">🔍 Search</button>
                        <a href="index.php" class="btn">🔄 Clear</a>
                    </div>
                </form>
            </div>

            <!-- Movies Section -->
            <?php if ($show_movies && !empty($movies)): ?>
            <div class="content-section">
                <h3>🎬 Movies (<?php echo count($movies); ?>)</h3>
                
                <div class="content-grid">
                    <?php foreach ($movies as $video): ?>
                        <div class="content-card" onclick="window.location.href='/moviestream/public/watch.php?id=<?php echo $video['id']; ?>'">
                            <div class="content-thumbnail">
                                <img src="<?php echo $video['thumbnail_url'] ?: getYouTubeThumbnail($video['youtube_id']); ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>"
                                     loading="lazy">
                                <div class="play-overlay">
                                    <div class="play-button">▶</div>
                                </div>
                                <div class="content-type-badge badge-movie">Movie</div>
                                
                                <?php if ($user_id && isset($video['progress_seconds']) && $video['progress_seconds'] > 0): ?>
                                    <div class="progress-overlay">
                                        <div class="progress-bar">
                                            <div class="progress-fill" 
                                                 style="width: <?php echo min(100, ($video['progress_seconds'] / max(1, $video['duration_seconds'])) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <?php if ($video['completed']): ?>
                                        <div class="completed-badge">✓ Completed</div>
                                    <?php else: ?>
                                        <div class="resume-badge">Resume</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="content-info">
                                <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                <p class="genre"><?php echo htmlspecialchars($video['genre']); ?></p>
                                <?php if ($video['description']): ?>
                                    <p class="description"><?php echo htmlspecialchars(substr($video['description'], 0, 120) . '...'); ?></p>
                                <?php endif; ?>
                                
                                <div class="content-meta">
                                    <?php if ($video['release_year']): ?>
                                        <span class="meta-item">📅 <?php echo $video['release_year']; ?></span>
                                    <?php endif; ?>
                                    <?php if ($video['duration_seconds']): ?>
                                        <span class="meta-item">⏱️ <?php echo formatDuration($video['duration_seconds']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($video['avg_rating'] > 0): ?>
                                        <span class="meta-item">⭐ <?php echo number_format($video['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                    <span class="meta-item">👀 <?php echo number_format($video['view_count']); ?></span>
                                </div>
                                
                                <div class="content-actions">
                                    <a href="/moviestream/public/watch.php?id=<?php echo $video['id']; ?>" 
                                    class="btn btn-primary"
                                    onclick="event.stopPropagation()">
                                    ▶ Watch Now
                                </a>

                                    <?php if ($user_id): ?>
                                        <button class="btn btn-small add-to-watchlist" 
                                                data-video-id="<?php echo $video['id']; ?>"
                                                onclick="event.stopPropagation()">
                                            ❤️ Watchlist
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Series Section -->
            <?php if ($show_series && !empty($series)): ?>
            <div class="content-section">
                <h3>📺 TV Series (<?php echo count($series); ?>)</h3>
                
                <div class="content-grid">
                    <?php foreach ($series as $show): ?>
                        <div class="content-card series-card" onclick="window.location.href='series.php?id=<?php echo $show['id']; ?>'">
                            <div class="content-thumbnail">
                                <img src="<?php echo $show['thumbnail_url'] ?: getYouTubeThumbnail('dQw4w9WgXcQ'); ?>" 
                                     alt="<?php echo htmlspecialchars($show['title']); ?>"
                                     loading="lazy">
                                <div class="play-overlay">
                                    <div class="play-button">📺</div>
                                </div>
                                <div class="content-type-badge badge-series">Series</div>
                            </div>
                            <div class="content-info">
                                <h4><?php echo htmlspecialchars($show['title']); ?></h4>
                                <?php if ($show['genres']): ?>
                                    <p class="genre"><?php echo htmlspecialchars($show['genres']); ?></p>
                                <?php endif; ?>
                                <?php if ($show['description']): ?>
                                    <p class="description"><?php echo htmlspecialchars(substr($show['description'], 0, 120) . '...'); ?></p>
                                <?php endif; ?>
                                
                                <div class="content-meta series-meta">
                                    <?php if ($show['release_year']): ?>
                                        <span class="meta-item">📅 <?php echo $show['release_year']; ?></span>
                                    <?php endif; ?>
                                    <span class="meta-item season-count">📺 <?php echo $show['season_count']; ?> Season<?php echo $show['season_count'] != 1 ? 's' : ''; ?></span>
                                    <span class="meta-item episode-count">🎬 <?php echo $show['episode_count']; ?> Episode<?php echo $show['episode_count'] != 1 ? 's' : ''; ?></span>
                                    <?php if ($show['avg_rating'] > 0): ?>
                                        <span class="meta-item">⭐ <?php echo number_format($show['avg_rating'], 1); ?></span>
                                    <?php endif; ?>
                                    <span class="meta-item">👀 <?php echo number_format($show['view_count']); ?></span>
                                </div>
                                
                                <div class="content-actions">
                                    <a href="series.php?id=<?php echo $show['id']; ?>" 
                                       class="btn btn-primary"
                                       onclick="event.stopPropagation()">
                                        📺 View Series
                                    </a>
                                    <?php if ($user_id): ?>
                                        <button class="btn btn-small add-to-watchlist" 
                                                data-series-id="<?php echo $show['id']; ?>"
                                                onclick="event.stopPropagation()">
                                            ❤️ Watchlist
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- No Results -->
            <?php if (($show_movies && empty($movies)) && ($show_series && empty($series))): ?>
                <div class="no-results">
                    <h3>🔍 No content found</h3>
                    <p>No <?php echo $content_type ? htmlspecialchars($content_type) . 's' : 'content'; ?> found matching your search criteria.</p>
                    <p>Try adjusting your search terms or browse all content.</p>
                    <div style="margin-top: 2rem;">
                        <a href="index.php" class="btn btn-primary">🏠 Browse All Content</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Featured Content (show when no search) -->
            <?php if (empty($search) && empty($genre_filter) && empty($content_type)): ?>
                <?php $featured = getFeaturedContent($pdo, 6); ?>
                <?php if (!empty($featured)): ?>
                <div class="content-section">
                    <h3>⭐ Featured Content</h3>
                    
                    <div class="content-grid">
                        <?php foreach ($featured as $item): ?>
                            <?php $isMovie = $item['item_type'] === 'movie'; ?>
                            <div class="content-card <?php echo $isMovie ? '' : 'series-card'; ?>" 
                                 onclick="window.location.href='<?php echo $isMovie ? 'watch.php?id=' . $item['id'] : 'series.php?id=' . $item['id']; ?>'">
                                <div class="content-thumbnail">
                                    <img src="<?php echo $item['thumbnail_url'] ?: getYouTubeThumbnail($isMovie ? $item['youtube_id'] : 'dQw4w9WgXcQ'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>"
                                         loading="lazy">
                                    <div class="play-overlay">
                                        <div class="play-button"><?php echo $isMovie ? '▶' : '📺'; ?></div>
                                    </div>
                                    <div class="content-type-badge badge-<?php echo $item['item_type']; ?>">
                                        <?php echo $isMovie ? 'Movie' : 'Series'; ?>
                                    </div>
                                </div>
                                <div class="content-info">
                                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                    <?php if ($item['genres']): ?>
                                        <p class="genre"><?php echo htmlspecialchars($item['genres']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="content-meta">
                                        <?php if ($item['release_year']): ?>
                                            <span class="meta-item">📅 <?php echo $item['release_year']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($isMovie && $item['duration_seconds']): ?>
                                            <span class="meta-item">⏱️ <?php echo formatDuration($item['duration_seconds']); ?></span>
                                        <?php elseif (!$isMovie): ?>
                                            <span class="meta-item">📺 <?php echo $item['season_count']; ?> Season<?php echo $item['season_count'] != 1 ? 's' : ''; ?></span>
                                            <span class="meta-item">🎬 <?php echo $item['episode_count']; ?> Episodes</span>
                                        <?php endif; ?>
                                        <?php if ($item['avg_rating'] > 0): ?>
                                            <span class="meta-item">⭐ <?php echo number_format($item['avg_rating'], 1); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="content-actions">
                                        <a href="<?php echo $isMovie ? 'watch.php?id=' . $item['id'] : 'series.php?id=' . $item['id']; ?>" 
                                           class="btn btn-primary"
                                           onclick="event.stopPropagation()">
                                            <?php echo $isMovie ? '▶ Watch Now' : '📺 View Series'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 MovieStream v0.2.0. All rights reserved. | <a href="../admin/login.php">Admin</a></p>
            <p>Now with Multi-Season/Episode Support!</p>
        </div>
    </footer>

    <script>
        // Add to watchlist functionality
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($user_id): ?>
            // Handle watchlist buttons
            document.querySelectorAll('.add-to-watchlist').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const videoId = this.dataset.videoId;
                    const seriesId = this.dataset.seriesId;
                    
                    if (!videoId && !seriesId) {
                        console.error('No video or series ID found');
                        return;
                    }
                    
                    // Prepare data for request
                    const data = {};
                    if (videoId) {
                        data.video_id = parseInt(videoId);
                    } else if (seriesId) {
                        data.series_id = parseInt(seriesId);
                    }
                    
                    // Show loading state
                    const originalText = this.textContent;
                    this.textContent = '⏳ Adding...';
                    this.disabled = true;
                    
                    fetch('../user/ajax/add_watchlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.textContent = '✅ Added!';
                            this.style.background = '#28a745';
                            setTimeout(() => {
                                this.textContent = '❤️ In Watchlist';
                                this.classList.add('in-watchlist');
                            }, 1500);
                        } else {
                            this.textContent = originalText;
                            this.disabled = false;
                            alert('Failed to add to watchlist: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.textContent = originalText;
                        this.disabled = false;
                        alert('An error occurred while adding to watchlist');
                    });
                });
            });
            <?php endif; ?>
            
            // Enhance search functionality
            const searchForm = document.querySelector('.search-form');
            const searchInput = document.querySelector('.search-input');
            
            if (searchInput) {
                // Add search suggestions/autocomplete could be implemented here
                searchInput.addEventListener('input', function() {
                    // Debounce search suggestions
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        // Could implement live search suggestions here
                    }, 300);
                });
            }
            
            // Add loading states for better UX
            if (searchForm) {
                searchForm.addEventListener('submit', function() {
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.innerHTML = '⏳ Searching...';
                        submitButton.disabled = true;
                    }
                });
            }
            
            // Add keyboard navigation for cards
            document.querySelectorAll('.content-card').forEach(card => {
                card.setAttribute('tabindex', '0');
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // Lazy loading for images (if not supported natively)
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        });
        
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Add error handling for broken images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                // Replace with placeholder if image fails to load
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjY2NjIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
                this.alt = 'Image not available';
            });
        });
    </script>
</body>
</html>