<?php
// includes/functions.php - Enhanced Helper functions v0.2.0 with Series Support

// Define constants
if (!defined('SUBTITLES_DIR')) {
    define('SUBTITLES_DIR', dirname(__DIR__) . '/uploads/subtitles/');
}

if (!defined('THUMBNAILS_DIR')) {
    define('THUMBNAILS_DIR', dirname(__DIR__) . '/uploads/thumbnails/');
}

// Parse SRT file content into array with enhanced error handling
function parseSrtContent($srt_content) {
    $subtitles = [];
    $srt_content = trim($srt_content);
    
    if (empty($srt_content)) {
        return $subtitles;
    }
    
    // Normalize line endings
    $srt_content = str_replace(["\r\n", "\r"], "\n", $srt_content);
    
    $blocks = preg_split('/\n\s*\n/', $srt_content);
    
    foreach ($blocks as $block) {
        $lines = explode("\n", trim($block));
        if (count($lines) >= 3) {
            $index = (int)trim($lines[0]);
            $time_line = trim($lines[1]);
            $text = implode("\n", array_slice($lines, 2));
            
            // Parse time format: 00:00:20,000 --> 00:00:24,400
            if (preg_match('/(\d{2}):(\d{2}):(\d{2})[,\.](\d{3})\s*-->\s*(\d{2}):(\d{2}):(\d{2})[,\.](\d{3})/', $time_line, $matches)) {
                $start_time = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3] + ($matches[4] / 1000);
                $end_time = ($matches[5] * 3600) + ($matches[6] * 60) + $matches[7] + ($matches[8] / 1000);
                
                // Clean subtitle text
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = strip_tags($text);
                $text = trim($text);
                
                if (!empty($text)) {
                    $subtitles[] = [
                        'index' => $index,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'text' => $text
                    ];
                }
            }
        }
    }
    
    // Sort by start time to ensure proper order
    usort($subtitles, function($a, $b) {
        return $a['start_time'] <=> $b['start_time'];
    });
    
    return $subtitles;
}

// Get video thumbnail from YouTube with fallback options
function getYouTubeThumbnail($youtube_id, $quality = 'maxresdefault') {
    $qualities = ['maxresdefault', 'hqdefault', 'mqdefault', 'sddefault', 'default'];
    
    if (!in_array($quality, $qualities)) {
        $quality = 'maxresdefault';
    }
    
    return "https://i.ytimg.com/vi/{$youtube_id}/{$quality}.jpg";
}

// Enhanced filename sanitization
function sanitizeFilename($filename) {
    // Remove path information
    $filename = basename($filename);
    
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove special characters but keep dots, hyphens, and underscores
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Ensure filename is not empty
    if (empty($filename)) {
        $filename = 'untitled_' . time();
    }
    
    return $filename;
}

// Get all available genres from database
function getGenres($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM genres WHERE is_active = TRUE ORDER BY display_order, name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching genres: " . $e->getMessage());
        return [];
    }
}

// Enhanced search function with series support
function searchContent($pdo, $search = '', $genre = '', $content_type = '', $filters = []) {
    try {
        if ($content_type === 'series') {
            return searchSeries($pdo, $search, $genre, $filters);
        } else {
            return searchVideos($pdo, $search, $genre, $filters);
        }
    } catch (PDOException $e) {
        error_log("Error searching content: " . $e->getMessage());
        return [];
    }
}

// Enhanced video search with multiple filters
function searchVideos($pdo, $search = '', $genre = '', $filters = []) {
    try {
        $sql = "
            SELECT DISTINCT v.*, 
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres,
                   s.title as series_title,
                   se.season_number,
                   CASE 
                       WHEN v.content_type = 'episode' THEN CONCAT(s.title, ' - S', se.season_number, 'E', v.episode_number, ': ', v.title)
                       ELSE v.title
                   END as display_title
            FROM videos v
            LEFT JOIN ratings r ON v.id = r.video_id
            LEFT JOIN video_genres vg ON v.id = vg.video_id
            LEFT JOIN genres g ON vg.genre_id = g.id
            LEFT JOIN series s ON v.series_id = s.id
            LEFT JOIN seasons se ON v.season_id = se.id
            WHERE v.status = 'active'
        ";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (v.title LIKE ? OR v.description LIKE ? OR v.director LIKE ? OR v.cast LIKE ? OR s.title LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        if (!empty($genre)) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM video_genres vg2 
                JOIN genres g2 ON vg2.genre_id = g2.id 
                WHERE vg2.video_id = v.id AND g2.name = ?
            )";
            $params[] = $genre;
        }
        
        if (!empty($filters['content_type'])) {
            $sql .= " AND v.content_type = ?";
            $params[] = $filters['content_type'];
        }
        
        if (!empty($filters['year_from'])) {
            $sql .= " AND v.release_year >= ?";
            $params[] = $filters['year_from'];
        }
        
        if (!empty($filters['year_to'])) {
            $sql .= " AND v.release_year <= ?";
            $params[] = $filters['year_to'];
        }
        
        $sql .= " GROUP BY v.id";
        
        if (!empty($filters['min_rating'])) {
            $sql .= " HAVING avg_rating >= ?";
            $params[] = $filters['min_rating'];
        }
        
        // Default ordering
        $orderBy = " ORDER BY ";
        switch ($filters['sort'] ?? 'newest') {
            case 'oldest':
                $orderBy .= "v.created_at ASC";
                break;
            case 'title_asc':
                $orderBy .= "display_title ASC";
                break;
            case 'title_desc':
                $orderBy .= "display_title DESC";
                break;
            case 'rating':
                $orderBy .= "avg_rating DESC, rating_count DESC";
                break;
            case 'popular':
                $orderBy .= "v.view_count DESC";
                break;
            case 'featured':
                $orderBy .= "v.featured DESC, v.created_at DESC";
                break;
            default:
                $orderBy .= "v.content_type ASC, s.title ASC, se.season_number ASC, v.episode_number ASC, v.created_at DESC";
        }
        
        $sql .= $orderBy;
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error searching videos: " . $e->getMessage());
        return [];
    }
}

// Search series function
function searchSeries($pdo, $search = '', $genre = '', $filters = []) {
    try {
        $sql = "
            SELECT DISTINCT s.*, 
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres,
                   COUNT(DISTINCT se.id) as season_count,
                   COUNT(DISTINCT v.id) as episode_count
            FROM series s
            LEFT JOIN ratings r ON s.id = r.series_id
            LEFT JOIN series_genres sg ON s.id = sg.series_id
            LEFT JOIN genres g ON sg.genre_id = g.id
            LEFT JOIN seasons se ON s.id = se.series_id
            LEFT JOIN videos v ON s.id = v.series_id AND v.content_type = 'episode'
            WHERE s.status = 'active'
        ";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (s.title LIKE ? OR s.description LIKE ? OR s.director LIKE ? OR s.cast LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        if (!empty($genre)) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM series_genres sg2 
                JOIN genres g2 ON sg2.genre_id = g2.id 
                WHERE sg2.series_id = s.id AND g2.name = ?
            )";
            $params[] = $genre;
        }
        
        $sql .= " GROUP BY s.id ORDER BY s.featured DESC, s.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error searching series: " . $e->getMessage());
        return [];
    }
}

// Get series with seasons and episodes
function getSeriesWithEpisodes($pdo, $series_id, $user_id = null) {
    try {
        // Get series info
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
            FROM series s
            LEFT JOIN ratings r ON s.id = r.series_id
            LEFT JOIN series_genres sg ON s.id = sg.series_id
            LEFT JOIN genres g ON sg.genre_id = g.id
            WHERE s.id = ? AND s.status = 'active'
            GROUP BY s.id
        ");
        $stmt->execute([$series_id]);
        $series = $stmt->fetch();
        
        if (!$series) {
            return null;
        }
        
        // Get seasons with episodes
        $stmt = $pdo->prepare("
            SELECT se.*, COUNT(v.id) as episode_count
            FROM seasons se
            LEFT JOIN videos v ON se.id = v.season_id AND v.status = 'active'
            WHERE se.series_id = ? AND se.status = 'active'
            GROUP BY se.id
            ORDER BY se.season_number ASC
        ");
        $stmt->execute([$series_id]);
        $seasons = $stmt->fetchAll();
        
        foreach ($seasons as &$season) {
            // Get episodes for this season
            $episodeSql = "
                SELECT v.*, 
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.rating) as rating_count";
            
            $params = [$season['id']];
            
            if ($user_id) {
                $episodeSql .= ",
                       up.progress_seconds,
                       up.completed,
                       up.watch_count";
            }
            
            $episodeSql .= "
                FROM videos v
                LEFT JOIN ratings r ON v.id = r.video_id";
            
            if ($user_id) {
                $episodeSql .= "
                LEFT JOIN user_progress up ON v.id = up.video_id AND up.user_id = ?";
                $params[] = $user_id;
            }
            
            $episodeSql .= "
                WHERE v.season_id = ? AND v.content_type = 'episode' AND v.status = 'active'
                GROUP BY v.id
                ORDER BY v.episode_number ASC
            ";
            
            if ($user_id) {
                array_unshift($params, $user_id);
            }
            
            $episodeStmt = $pdo->prepare($episodeSql);
            $episodeStmt->execute($params);
            $season['episodes'] = $episodeStmt->fetchAll();
        }
        
        $series['seasons'] = $seasons;
        return $series;
        
    } catch (PDOException $e) {
        error_log("Error getting series with episodes: " . $e->getMessage());
        return null;
    }
}

// Check if user has access to subtitles
function userHasSubtitleAccess($user_status) {
    return $user_status === 'active';
}

// Check if user has access to content
function userHasContentAccess($user_status) {
    // Allow both active and inactive users to watch content
    // But inactive users won't get subtitles
    return in_array($user_status, ['active', 'inactive']) || !$user_status; // Allow guest access too
}

// Format duration from seconds to readable format
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return $minutes . 'm' . ($remainingSeconds > 0 ? ' ' . $remainingSeconds . 's' : '');
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
    }
}

// Add to watchlist (supports both videos and series)
function addToWatchlist($pdo, $user_id, $video_id = null, $series_id = null, $priority = 1, $notes = '') {
    try {
        if (!$video_id && !$series_id) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO watchlist (user_id, video_id, series_id, priority, notes) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            priority = VALUES(priority), 
            notes = VALUES(notes),
            added_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$user_id, $video_id, $series_id, $priority, $notes]);
    } catch (PDOException $e) {
        error_log("Error adding to watchlist: " . $e->getMessage());
        return false;
    }
}

// Remove from watchlist
function removeFromWatchlist($pdo, $user_id, $video_id = null, $series_id = null) {
    try {
        if (!$video_id && !$series_id) {
            return false;
        }
        
        $sql = "DELETE FROM watchlist WHERE user_id = ?";
        $params = [$user_id];
        
        if ($video_id) {
            $sql .= " AND video_id = ?";
            $params[] = $video_id;
        } else {
            $sql .= " AND series_id = ?";
            $params[] = $series_id;
        }
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error removing from watchlist: " . $e->getMessage());
        return false;
    }
}

// Update video progress with enhanced tracking
function updateVideoProgress($pdo, $user_id, $video_id, $progress_seconds, $total_duration = 0, $completed = false) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_progress (user_id, video_id, progress_seconds, total_duration, completed, last_watched)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            progress_seconds = VALUES(progress_seconds),
            total_duration = VALUES(total_duration),
            completed = VALUES(completed),
            last_watched = VALUES(last_watched),
            watch_count = watch_count + 1
        ");
        
        $result = $stmt->execute([$user_id, $video_id, $progress_seconds, $total_duration, $completed]);
        
        if ($result) {
            // Log user activity
            logUserActivity($pdo, $user_id, $completed ? 'video_complete' : 'video_play', $video_id, [
                'progress' => $progress_seconds,
                'duration' => $total_duration
            ]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating video progress: " . $e->getMessage());
        return false;
    }
}

// Rate content (video or series)
function rateContent($pdo, $user_id, $video_id = null, $series_id = null, $rating = null, $review = '') {
    try {
        if ((!$video_id && !$series_id) || !$rating) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO ratings (user_id, video_id, series_id, rating, review)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            review = VALUES(review),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $result = $stmt->execute([$user_id, $video_id, $series_id, $rating, $review]);
        
        if ($result) {
            // Log user activity
            $activity_id = $video_id ?: $series_id;
            logUserActivity($pdo, $user_id, 'rating', $video_id, [
                'rating' => $rating,
                'has_review' => !empty($review),
                'content_type' => $video_id ? 'video' : 'series'
            ]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error rating content: " . $e->getMessage());
        return false;
    }
}

// Get video with user-specific data (enhanced for series support)
function getVideoWithUserData($pdo, $video_id, $user_id = null) {
    try {
        $sql = "
            SELECT v.*,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres,
                   GROUP_CONCAT(DISTINCT g.color_code ORDER BY g.name SEPARATOR ',') as genre_colors,
                   s.title as series_title,
                   se.season_number,
                   se.title as season_title";
        
        $params = [$video_id];
        
        if ($user_id) {
            $sql .= ",
                   up.progress_seconds,
                   up.completed,
                   up.watch_count,
                   ur.rating as user_rating,
                   ur.review as user_review,
                   CASE WHEN w.user_id IS NOT NULL THEN 1 ELSE 0 END as in_watchlist,
                   w.priority as watchlist_priority,
                   w.notes as watchlist_notes";
        }
        
        $sql .= "
            FROM videos v
            LEFT JOIN ratings r ON v.id = r.video_id
            LEFT JOIN video_genres vg ON v.id = vg.video_id
            LEFT JOIN genres g ON vg.genre_id = g.id
            LEFT JOIN series s ON v.series_id = s.id
            LEFT JOIN seasons se ON v.season_id = se.id";
        
        if ($user_id) {
            $sql .= "
            LEFT JOIN user_progress up ON v.id = up.video_id AND up.user_id = ?
            LEFT JOIN ratings ur ON v.id = ur.video_id AND ur.user_id = ?
            LEFT JOIN watchlist w ON v.id = w.video_id AND w.user_id = ?";
            $params = array_merge($params, [$user_id, $user_id, $user_id]);
        }
        
        $sql .= "
            WHERE v.id = ? AND v.status = 'active'
            GROUP BY v.id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $video = $stmt->fetch();
        
        if ($video && $video['content_type'] === 'episode') {
            // Get other episodes in the same season
            $stmt = $pdo->prepare("
                SELECT v.id, v.title, v.episode_number, v.duration_seconds,
                       CASE WHEN up.completed = 1 THEN 1 ELSE 0 END as completed
                FROM videos v
                LEFT JOIN user_progress up ON v.id = up.video_id AND up.user_id = ?
                WHERE v.season_id = ? AND v.content_type = 'episode' AND v.status = 'active'
                ORDER BY v.episode_number ASC
            ");
            $stmt->execute([$user_id ?: 0, $video['season_id']]);
            $video['season_episodes'] = $stmt->fetchAll();
            
            // Get all seasons for the series
            $stmt = $pdo->prepare("
                SELECT se.*, COUNT(v.id) as episode_count
                FROM seasons se
                LEFT JOIN videos v ON se.id = v.season_id AND v.status = 'active'
                WHERE se.series_id = ? AND se.status = 'active'
                GROUP BY se.id
                ORDER BY se.season_number ASC
            ");
            $stmt->execute([$video['series_id']]);
            $video['all_seasons'] = $stmt->fetchAll();
        }
        
        return $video;
    } catch (PDOException $e) {
        error_log("Error getting video with user data: " . $e->getMessage());
        return false;
    }
}

// Get featured content (videos and series)
function getFeaturedContent($pdo, $limit = 6) {
    try {
        $content = [];
        
        // Get featured movies
        $stmt = $pdo->prepare("
            SELECT v.*, 'movie' as item_type,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
            FROM videos v
            LEFT JOIN ratings r ON v.id = r.video_id
            LEFT JOIN video_genres vg ON v.id = vg.video_id
            LEFT JOIN genres g ON vg.genre_id = g.id
            WHERE v.status = 'active' AND v.featured = TRUE AND v.content_type = 'movie'
            GROUP BY v.id
            ORDER BY v.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([max(1, $limit / 2)]);
        $content = array_merge($content, $stmt->fetchAll());
        
        // Get featured series
        $stmt = $pdo->prepare("
            SELECT s.*, 'series' as item_type,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres,
                   COUNT(DISTINCT se.id) as season_count,
                   COUNT(DISTINCT v.id) as episode_count
            FROM series s
            LEFT JOIN ratings r ON s.id = r.series_id
            LEFT JOIN series_genres sg ON s.id = sg.series_id
            LEFT JOIN genres g ON sg.genre_id = g.id
            LEFT JOIN seasons se ON s.id = se.series_id
            LEFT JOIN videos v ON s.id = v.series_id AND v.content_type = 'episode'
            WHERE s.status = 'active' AND s.featured = TRUE
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([max(1, $limit / 2)]);
        $content = array_merge($content, $stmt->fetchAll());
        
        // Sort by created_at and limit total results
        usort($content, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($content, 0, $limit);
    } catch (PDOException $e) {
        error_log("Error getting featured content: " . $e->getMessage());
        return [];
    }
}

// Log admin action with enhanced details
function logAdminAction($pdo, $admin_id, $action, $target_type = null, $target_id = null, $details = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $admin_id,
            $action,
            $target_type,
            $target_id,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Error logging admin action: " . $e->getMessage());
        return false;
    }
}

// Log user activity
function logUserActivity($pdo, $user_id, $activity_type, $video_id = null, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity_type, video_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $user_id,
            $activity_type,
            $video_id,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Error logging user activity: " . $e->getMessage());
        return false;
    }
}

// Get user statistics
function getUserStats($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT p.video_id) as videos_watched,
                COUNT(DISTINCT CASE WHEN w.video_id IS NOT NULL THEN w.video_id WHEN w.series_id IS NOT NULL THEN w.series_id END) as watchlist_count,
                COALESCE(AVG(CASE WHEN r.video_id IS NOT NULL THEN r.rating END), 0) as avg_rating_given,
                COUNT(CASE WHEN r.video_id IS NOT NULL AND r.review IS NOT NULL AND r.review != '' THEN r.rating END) as reviews_written,
                SUM(p.watch_count) as total_watch_count,
                SUM(CASE WHEN p.completed = 1 THEN 1 ELSE 0 END) as completed_videos
            FROM users u
            LEFT JOIN user_progress p ON u.id = p.user_id
            LEFT JOIN watchlist w ON u.id = w.user_id
            LEFT JOIN ratings r ON u.id = r.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch();
        
        return $stats ?: [
            'videos_watched' => 0,
            'watchlist_count' => 0,
            'avg_rating_given' => 0,
            'reviews_written' => 0,
            'total_watch_count' => 0,
            'completed_videos' => 0
        ];
    } catch (PDOException $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        return [
            'videos_watched' => 0,
            'watchlist_count' => 0,
            'avg_rating_given' => 0,
            'reviews_written' => 0,
            'total_watch_count' => 0,
            'completed_videos' => 0
        ];
    }
}

// Generate YouTube embed URL with parameters
function getYouTubeEmbedUrl($youtube_id, $params = []) {
    $default_params = [
        'enablejsapi' => 1,
        'modestbranding' => 1,
        'rel' => 0,
        'showinfo' => 0,
        'fs' => 1,
        'cc_load_policy' => 0
    ];
    
    $params = array_merge($default_params, $params);
    $query_string = http_build_query($params);
    
    return "https://www.youtube.com/embed/{$youtube_id}?{$query_string}";
}

// Generate breadcrumb navigation
function generateBreadcrumbs($items) {
    if (empty($items)) {
        return '';
    }
    
    $breadcrumb = '<nav class="breadcrumb" aria-label="Breadcrumb">';
    $breadcrumb .= '<ol class="breadcrumb-list">';
    
    foreach ($items as $index => $item) {
        $isLast = ($index === count($items) - 1);
        
        $breadcrumb .= '<li class="breadcrumb-item">';
        
        if (!$isLast && !empty($item['url'])) {
            $breadcrumb .= '<a href="' . htmlspecialchars($item['url']) . '">';
            $breadcrumb .= htmlspecialchars($item['title']);
            $breadcrumb .= '</a>';
        } else {
            $breadcrumb .= '<span>' . htmlspecialchars($item['title']) . '</span>';
        }
        
        $breadcrumb .= '</li>';
        
        if (!$isLast) {
            $breadcrumb .= '<li class="breadcrumb-separator" aria-hidden="true">›</li>';
        }
    }
    
    $breadcrumb .= '</ol>';
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}

// Get video recommendations (enhanced with series support)
function getVideoRecommendations($pdo, $user_id = null, $current_video_id = null, $limit = 6) {
    try {
        if ($user_id) {
            // Get recommendations based on user's watch history and ratings
            $stmt = $pdo->prepare("
                SELECT DISTINCT v.*, 'movie' as item_type,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.rating) as rating_count,
                       GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
                FROM videos v
                LEFT JOIN ratings r ON v.id = r.video_id
                LEFT JOIN video_genres vg ON v.id = vg.video_id
                LEFT JOIN genres g ON vg.genre_id = g.id
                WHERE v.status = 'active' 
                AND v.id != COALESCE(?, 0)
                AND v.content_type = 'movie'
                AND EXISTS (
                    SELECT 1 FROM video_genres vg2
                    JOIN genres g2 ON vg2.genre_id = g2.id
                    WHERE vg2.video_id = v.id
                    AND g2.name IN (
                        SELECT DISTINCT g3.name
                        FROM user_progress up
                        JOIN video_genres vg3 ON up.video_id = vg3.video_id
                        JOIN genres g3 ON vg3.genre_id = g3.id
                        WHERE up.user_id = ?
                        ORDER BY up.last_watched DESC
                        LIMIT 5
                    )
                )
                AND v.id NOT IN (
                    SELECT DISTINCT up2.video_id 
                    FROM user_progress up2 
                    WHERE up2.user_id = ? AND up2.completed = 1
                )
                GROUP BY v.id
                ORDER BY avg_rating DESC, v.view_count DESC
                LIMIT ?
            ");
            $stmt->execute([$current_video_id, $user_id, $user_id, $limit]);
        } else {
            // Get general recommendations (popular and well-rated movies)
            $stmt = $pdo->prepare("
                SELECT v.*, 'movie' as item_type,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.rating) as rating_count,
                       GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
                FROM videos v
                LEFT JOIN ratings r ON v.id = r.video_id
                LEFT JOIN video_genres vg ON v.id = vg.video_id
                LEFT JOIN genres g ON vg.genre_id = g.id
                WHERE v.status = 'active' AND v.id != COALESCE(?, 0) AND v.content_type = 'movie'
                GROUP BY v.id
                ORDER BY v.view_count DESC, avg_rating DESC
                LIMIT ?
            ");
            $stmt->execute([$current_video_id, $limit]);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting video recommendations: " . $e->getMessage());
        return [];
    }
}

// Sanitize and validate user input
function sanitizeInput($input, $type = 'string') {
    if (is_null($input)) {
        return null;
    }
    
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// Check if user session is valid
function isValidUserSession($pdo, $user_id) {
    if (!$user_id) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT status, expiry_date FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Check if user is expired
        if ($user['expiry_date'] && strtotime($user['expiry_date']) < time() && $user['status'] === 'active') {
            // Auto-expire the user
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$user_id]);
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error validating user session: " . $e->getMessage());
        return false;
    }
}

// Validate and process SRT file upload
function processSubtitleUpload($file, $video_id, $language = 'en', $language_name = 'English') {
    $upload_errors = [];
    
    // Check file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors[] = "File upload failed with error code: " . $file['error'];
        return ['success' => false, 'errors' => $upload_errors];
    }
    
    // Check file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'srt') {
        $upload_errors[] = "Only .srt files are allowed";
        return ['success' => false, 'errors' => $upload_errors];
    }
    
    // Check file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        $upload_errors[] = "File is too large. Maximum size is 5MB";
        return ['success' => false, 'errors' => $upload_errors];
    }
    
    // Validate SRT content
    $srt_content = file_get_contents($file['tmp_name']);
    if (empty($srt_content)) {
        $upload_errors[] = "SRT file is empty or unreadable";
        return ['success' => false, 'errors' => $upload_errors];
    }
    
    $subtitles = parseSrtContent($srt_content);
    if (empty($subtitles)) {
        $upload_errors[] = "Invalid SRT format or no valid subtitles found";
        return ['success' => false, 'errors' => $upload_errors];
    }
    
    // Create filename
    $filename = $video_id . '_' . $language . '.srt';
    $upload_path = SUBTITLES_DIR . $filename;
    
    // Ensure directory exists
    if (!file_exists(SUBTITLES_DIR)) {
        mkdir(SUBTITLES_DIR, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $upload_errors[] = "Failed to save subtitle file";
        return ['success' => false, 'errors' => $upload_errors];
    }
    
    return [
        'success' => true,
        'file_path' => $upload_path,
        'subtitle_count' => count($subtitles),
        'file_size' => filesize($upload_path)
    ];
}

// Get system settings
function getSystemSetting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return $default;
        }
        
        $value = $result['setting_value'];
        
        // Convert based on type
        switch ($result['setting_type']) {
            case 'integer':
                return (int)$value;
            case 'boolean':
                return (bool)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    } catch (PDOException $e) {
        error_log("Error getting system setting: " . $e->getMessage());
        return $default;
    }
}

// Update system setting
function updateSystemSetting($pdo, $key, $value, $type = 'string') {
    try {
        // Convert value based on type
        switch ($type) {
            case 'json':
                $value = json_encode($value);
                break;
            case 'boolean':
                $value = $value ? '1' : '0';
                break;
            default:
                $value = (string)$value;
        }
        
        $stmt = $pdo->prepare("
            UPDATE settings 
            SET setting_value = ?, setting_type = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE setting_key = ?
        ");
        return $stmt->execute([$value, $type, $key]);
    } catch (PDOException $e) {
        error_log("Error updating system setting: " . $e->getMessage());
        return false;
    }
}

// Generate secure session token
function generateSessionToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

// Clean expired sessions
function cleanExpiredSessions($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW() OR is_active = FALSE");
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error cleaning expired sessions: " . $e->getMessage());
        return false;
    }
}

// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

// Check if maintenance mode is enabled
function isMaintenanceMode($pdo) {
    return (bool)getSystemSetting($pdo, 'maintenance_mode', false);
}

// Get trending videos (most watched/rated in recent period)
function getTrendingVideos($pdo, $limit = 10, $days = 30) {
    try {
        $stmt = $pdo->prepare("
            SELECT v.*, 
                   COUNT(DISTINCT p.user_id) as recent_views,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
            FROM videos v
            LEFT JOIN user_progress p ON v.id = p.video_id 
                AND p.last_watched >= DATE_SUB(NOW(), INTERVAL ? DAY)
            LEFT JOIN ratings r ON v.id = r.video_id
            LEFT JOIN video_genres vg ON v.id = vg.video_id
            LEFT JOIN genres g ON vg.genre_id = g.id
            WHERE v.status = 'active'
            GROUP BY v.id
            ORDER BY recent_views DESC, avg_rating DESC, v.view_count DESC
            LIMIT ?
        ");
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting trending videos: " . $e->getMessage());
        return [];
    }
}

?>