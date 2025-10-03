<?php
// includes/functions.php - Enhanced Helper functions v0.2.1 with FIXED SESSION HANDLING

// Define constants
if (!defined('SUBTITLES_DIR')) {
    define('SUBTITLES_DIR', dirname(__DIR__) . '/uploads/subtitles/');
}

if (!defined('THUMBNAILS_DIR')) {
    define('THUMBNAILS_DIR', dirname(__DIR__) . '/uploads/thumbnails/');
}

// =============================================================================
// SESSION AND AUTHENTICATION FUNCTIONS
// =============================================================================

/**
 * Generate secure session token
 */
function generateSessionToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate user session using session token
 */
function validateUserSession($pdo, $user_id, $session_token) {
    if (!$user_id || !$session_token) {
        return false;
    }
    
    try {
        // Check if session exists and is valid
        $stmt = $pdo->prepare("
            SELECT us.*, u.status, u.expiry_date, u.username
            FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            WHERE us.user_id = ? AND us.session_token = ? AND us.is_active = 1 AND us.expires_at > NOW()
        ");
        $stmt->execute([$user_id, $session_token]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return false;
        }
        
        // Auto-expire user if needed but keep session valid
        if ($session['status'] === 'active' && $session['expiry_date'] && date('Y-m-d') > $session['expiry_date']) {
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$user_id]);
            $session['status'] = 'inactive';
        }
        
        // Update last activity
        $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$session['id']]);
        
        return $session;
    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current user session data
 */
function getCurrentUserSession($pdo) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return null;
    }
    
    $session = validateUserSession($pdo, $_SESSION['user_id'], $_SESSION['session_token']);
    
    if (!$session) {
        // Invalid session, clear it
        unset($_SESSION['user_id']);
        unset($_SESSION['session_token']);
        unset($_SESSION['user_status']);
        return null;
    }
    
    // Update session data
    $_SESSION['user_status'] = $session['status'];
    
    return [
        'user_id' => $session['user_id'],
        'username' => $session['username'],
        'status' => $session['status'],
        'expiry_date' => $session['expiry_date']
    ];
}

/**
 * Check if user can access subtitles (only active users)
 */
function userCanAccessSubtitles($user_status) {
    return $user_status === 'active';
}

/**
 * Check if user can watch videos (everyone can watch)
 */
function userCanWatchVideos($user_status = null) {
    // Everyone can watch videos regardless of login status
    return true;
}

/**
 * Clean expired sessions
 */
function cleanExpiredSessions($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW() OR is_active = FALSE");
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error cleaning expired sessions: " . $e->getMessage());
        return false;
    }
}

/**
 * Logout user (invalidate session)
 */
function logoutUser($pdo, $user_id, $session_token) {
    try {
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND session_token = ?");
        $stmt->execute([$user_id, $session_token]);
        
        // Clear session variables
        unset($_SESSION['user_id']);
        unset($_SESSION['session_token']);
        unset($_SESSION['user_status']);
        
        return true;
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
        return false;
    }
}

// =============================================================================
// USER FUNCTIONS
// =============================================================================

/**
 * Get user details by ID
 */
function getUserDetails($pdo, $user_id) {
    if (!$user_id) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, status, expiry_date, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user details: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user statistics
 */
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

// =============================================================================
// VIDEO FUNCTIONS
// =============================================================================

/**
 * Get video with user-specific data
 */
function getVideoWithUserData($pdo, $video_id, $user_id = null) {
    try {
        $sql = "
            SELECT v.*,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres,
                   GROUP_CONCAT(DISTINCT g.color_code ORDER BY g.name SEPARATOR ',') as genre_colors,
                   MAX(s.title) as series_title,
                   MAX(se.season_number) as season_number,
                   MAX(se.title) as season_title";
        
        $params = [];

        if ($user_id) {
            $sql .= ",
                   MAX(up.progress_seconds) as progress_seconds,
                   MAX(up.completed) as completed,
                   MAX(up.watch_count) as watch_count,
                   MAX(ur.rating) as user_rating,
                   MAX(ur.review) as user_review,
                   MAX(CASE WHEN w.user_id IS NOT NULL THEN 1 ELSE 0 END) as in_watchlist,
                   MAX(w.priority) as watchlist_priority,
                   MAX(w.notes) as watchlist_notes";
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
            $params = [$user_id, $user_id, $user_id];
        }

        $sql .= "
            WHERE v.id = ? AND v.status = 'active'
            GROUP BY v.id, v.title, v.content_type, v.series_id, v.season_id, v.episode_number,
                     v.genre, v.youtube_id, v.description, v.thumbnail_url, v.duration_seconds,
                     v.release_year, v.imdb_rating, v.language, v.director, v.cast, v.tags,
                     v.view_count, v.featured, v.status, v.created_at, v.updated_at";

        $params[] = $video_id; // Add video_id for WHERE clause

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

/**
 * Get video recommendations
 */
function getVideoRecommendations($pdo, $user_id = null, $current_video_id = null, $limit = 6) {
    try {
        if ($user_id) {
            // Get recommendations based on user's watch history and ratings
            $stmt = $pdo->prepare("
                SELECT v.*, 'movie' as item_type,
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
                AND v.id NOT IN (
                    SELECT DISTINCT up2.video_id
                    FROM user_progress up2
                    WHERE up2.user_id = ? AND up2.completed = 1
                )
                GROUP BY v.id, v.title, v.content_type, v.series_id, v.season_id, v.episode_number,
                         v.genre, v.youtube_id, v.description, v.thumbnail_url, v.duration_seconds,
                         v.release_year, v.imdb_rating, v.language, v.director, v.cast, v.tags,
                         v.view_count, v.featured, v.status, v.created_at, v.updated_at
                ORDER BY COALESCE(AVG(r.rating), 0) DESC, v.view_count DESC
                LIMIT ?
            ");
            $stmt->execute([$current_video_id, $user_id, $limit]);
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
                GROUP BY v.id, v.title, v.content_type, v.series_id, v.season_id, v.episode_number,
                         v.genre, v.youtube_id, v.description, v.thumbnail_url, v.duration_seconds,
                         v.release_year, v.imdb_rating, v.language, v.director, v.cast, v.tags,
                         v.view_count, v.featured, v.status, v.created_at, v.updated_at
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

// =============================================================================
// SUBTITLE FUNCTIONS
// =============================================================================

/**
 * Parse SRT file content into array
 */
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

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Get YouTube thumbnail URL
 */
function getYouTubeThumbnail($youtube_id, $quality = 'maxresdefault') {
    $qualities = ['maxresdefault', 'hqdefault', 'mqdefault', 'sddefault', 'default'];
    
    if (!in_array($quality, $qualities)) {
        $quality = 'maxresdefault';
    }
    
    return "https://i.ytimg.com/vi/{$youtube_id}/{$quality}.jpg";
}

/**
 * Generate YouTube embed URL
 */
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

/**
 * Format duration from seconds
 */
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

/**
 * Sanitize filename
 */
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

/**
 * Generate breadcrumb navigation
 */
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

// =============================================================================
// SEARCH AND CONTENT FUNCTIONS
// =============================================================================

/**
 * Get all available genres
 */
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

/**
 * Search videos
 */
function searchVideos($pdo, $search = '', $genre = '', $filters = []) {
    try {
        $sql = "
            SELECT v.*,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.rating) as rating_count,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres,
                   MAX(s.title) as series_title,
                   MAX(se.season_number) as season_number,
                   CASE
                       WHEN v.content_type = 'episode' THEN CONCAT(MAX(s.title), ' - S', MAX(se.season_number), 'E', v.episode_number, ': ', v.title)
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
        
        $sql .= " GROUP BY v.id, v.title, v.content_type, v.series_id, v.season_id, v.episode_number,
                           v.genre, v.youtube_id, v.description, v.thumbnail_url, v.duration_seconds,
                           v.release_year, v.imdb_rating, v.language, v.director, v.cast, v.tags,
                           v.view_count, v.featured, v.status, v.created_at, v.updated_at
                  ORDER BY v.content_type ASC, MAX(s.title) ASC, MAX(se.season_number) ASC, v.episode_number ASC, v.created_at DESC";
        
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

/**
 * Search series
 */
function searchSeries($pdo, $search = '', $genre = '', $filters = []) {
    try {
        $sql = "
            SELECT s.*,
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
        
        $sql .= " GROUP BY s.id, s.title, s.description, s.thumbnail_url, s.release_year, s.imdb_rating,
                           s.language, s.director, s.cast, s.tags, s.view_count, s.featured,
                           s.status, s.created_at, s.updated_at
                  ORDER BY s.featured DESC, s.created_at DESC";
        
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

/**
 * Get featured content
 */
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
            GROUP BY v.id, v.title, v.content_type, v.series_id, v.season_id, v.episode_number,
                     v.genre, v.youtube_id, v.description, v.thumbnail_url, v.duration_seconds,
                     v.release_year, v.imdb_rating, v.language, v.director, v.cast, v.tags,
                     v.view_count, v.featured, v.status, v.created_at, v.updated_at
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
            GROUP BY s.id, s.title, s.description, s.thumbnail_url, s.release_year, s.imdb_rating,
                     s.language, s.director, s.cast, s.tags, s.view_count, s.featured,
                     s.status, s.created_at, s.updated_at
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

// =============================================================================
// USER INTERACTION FUNCTIONS
// =============================================================================

/**
 * Add to watchlist
 * Note: Uses application-level duplicate checking instead of UNIQUE constraint
 * because MySQL UNIQUE constraints don't handle NULL values properly
 * (NULL != NULL in UNIQUE constraints, allowing duplicate NULLs)
 */
function addToWatchlist($pdo, $user_id, $video_id = null, $series_id = null, $priority = 1, $notes = '') {
    try {
        if (!$video_id && !$series_id) {
            return false;
        }

        // Check if item already exists in watchlist using NULL-safe comparison (<=>)
        $checkStmt = $pdo->prepare("
            SELECT id FROM watchlist
            WHERE user_id = ? AND video_id <=> ? AND series_id <=> ?
        ");
        $checkStmt->execute([$user_id, $video_id, $series_id]);

        if ($checkStmt->fetch()) {
            // Item already in watchlist, update it
            $stmt = $pdo->prepare("
                UPDATE watchlist
                SET priority = ?, notes = ?, added_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND video_id <=> ? AND series_id <=> ?
            ");
            return $stmt->execute([$priority, $notes, $user_id, $video_id, $series_id]);
        } else {
            // Insert new item
            $stmt = $pdo->prepare("
                INSERT INTO watchlist (user_id, video_id, series_id, priority, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$user_id, $video_id, $series_id, $priority, $notes]);
        }
    } catch (PDOException $e) {
        error_log("Error adding to watchlist: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove from watchlist
 */
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

/**
 * Update video progress
 */
function updateVideoProgress($pdo, $user_id, $video_id, $progress_seconds, $total_duration = 0, $completed = false) {
    try {
        // Ensure proper data types
        $user_id = (int)$user_id;
        $video_id = (int)$video_id;
        $progress_seconds = (int)$progress_seconds;
        $total_duration = (int)$total_duration;
        $completed = $completed ? 1 : 0; // Convert to integer (1 or 0)

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

/**
 * Rate content (video or series)
 */
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

/**
 * Rate video (legacy function for backward compatibility)
 */
function rateVideo($pdo, $user_id, $video_id, $rating, $review = '') {
    return rateContent($pdo, $user_id, $video_id, null, $rating, $review);
}

// =============================================================================
// LOGGING FUNCTIONS
// =============================================================================

/**
 * Log user activity
 */
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

/**
 * Log admin action
 */
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

/**
 * Alias for logAdminAction (for backward compatibility)
 */
function logAdminActivity($pdo, $admin_id, $action, $target_type = null, $target_id = null, $details = '') {
    return logAdminAction($pdo, $admin_id, $action, $target_type, $target_id, $details);
}

?>