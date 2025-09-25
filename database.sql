-- MovieStream Database Schema v0.2.0 - Fixed and Enhanced with Series Support
-- Drop existing database and recreate for clean upgrade
DROP DATABASE IF EXISTS movie_streaming;
CREATE DATABASE movie_streaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE movie_streaming;

-- Users table with expiry date and enhanced features
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('inactive', 'active') DEFAULT 'inactive',
    expiry_date DATE NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    date_of_birth DATE NULL,
    country VARCHAR(100) DEFAULT NULL,
    preferred_language VARCHAR(10) DEFAULT 'en',
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admins table with enhanced permissions
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content Types: movie, series
CREATE TABLE content_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name ENUM('movie', 'series') NOT NULL,
    display_name VARCHAR(50) NOT NULL
);

-- Series table for TV shows/series
CREATE TABLE series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail_url VARCHAR(500),
    release_year YEAR NULL,
    imdb_rating DECIMAL(3,1) DEFAULT NULL,
    language VARCHAR(50) DEFAULT 'English',
    director VARCHAR(100) DEFAULT NULL,
    cast TEXT DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    view_count INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'coming_soon') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seasons table
CREATE TABLE seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT NOT NULL,
    season_number INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    thumbnail_url VARCHAR(500),
    release_year YEAR NULL,
    episode_count INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    UNIQUE KEY unique_series_season (series_id, season_number)
);

-- Videos table - now supports both movies and episodes
CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('movie', 'episode') DEFAULT 'movie',
    series_id INT NULL,
    season_id INT NULL,
    episode_number INT NULL,
    genre VARCHAR(100) NOT NULL,
    youtube_id VARCHAR(50) NOT NULL,
    description TEXT,
    thumbnail_url VARCHAR(500),
    duration_seconds INT DEFAULT 0,
    release_year YEAR NULL,
    imdb_rating DECIMAL(3,1) DEFAULT NULL,
    language VARCHAR(50) DEFAULT 'English',
    director VARCHAR(100) DEFAULT NULL,
    cast TEXT DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    view_count INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'coming_soon') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode (series_id, season_id, episode_number)
);

-- Subtitles table for comprehensive .srt file management
CREATE TABLE subtitles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    language VARCHAR(10) DEFAULT 'en',
    language_name VARCHAR(50) DEFAULT 'English',
    srt_file_path VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    subtitle_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_video_language (video_id, language)
);

-- Enhanced Video Progress Tracking
CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    progress_seconds INT DEFAULT 0,
    total_duration INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    watch_count INT DEFAULT 1,
    first_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_video (user_id, video_id)
);

-- Rating & Review System
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NULL,
    series_id INT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT DEFAULT NULL,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
);

-- Enhanced Watchlist Feature - supports both movies and series
CREATE TABLE watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NULL,
    series_id INT NULL,
    priority INT DEFAULT 1 CHECK (priority BETWEEN 1 AND 5),
    notes TEXT DEFAULT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
);

-- User Sessions for enhanced security
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(128) UNIQUE NOT NULL,
    device_info TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin Activity Logs
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type ENUM('user', 'video', 'series', 'season', 'subtitle', 'system') DEFAULT NULL,
    target_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- User Activity Logs
CREATE TABLE user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'video_play', 'video_pause', 'video_complete', 'rating', 'watchlist_add', 'profile_update') NOT NULL,
    video_id INT DEFAULT NULL,
    series_id INT DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE SET NULL,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE SET NULL
);

-- Video Categories/Genres Management
CREATE TABLE genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    color_code VARCHAR(7) DEFAULT '#667eea',
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Video-Genre Many-to-Many Relationship
CREATE TABLE video_genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    genre_id INT NOT NULL,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_video_genre (video_id, genre_id)
);

-- Series-Genre Many-to-Many Relationship
CREATE TABLE series_genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT NOT NULL,
    genre_id INT NOT NULL,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_series_genre (series_id, genre_id)
);

-- System Settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT DEFAULT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indexes for better performance
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_expiry ON users(expiry_date);
CREATE INDEX idx_videos_content_type ON videos(content_type);
CREATE INDEX idx_videos_series ON videos(series_id);
CREATE INDEX idx_videos_season ON videos(season_id);
CREATE INDEX idx_videos_genre ON videos(genre);
CREATE INDEX idx_videos_status ON videos(status);
CREATE INDEX idx_videos_featured ON videos(featured);
CREATE INDEX idx_user_progress_user ON user_progress(user_id);
CREATE INDEX idx_user_progress_video ON user_progress(video_id);
CREATE INDEX idx_user_progress_last_watched ON user_progress(last_watched);
CREATE INDEX idx_ratings_video ON ratings(video_id);
CREATE INDEX idx_ratings_series ON ratings(series_id);
CREATE INDEX idx_ratings_user ON ratings(user_id);
CREATE INDEX idx_watchlist_user ON watchlist(user_id);
CREATE INDEX idx_admin_logs_admin ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_created ON admin_logs(created_at);
CREATE INDEX idx_user_activity_user ON user_activity_logs(user_id);
CREATE INDEX idx_user_activity_type ON user_activity_logs(activity_type);

-- Insert content types
INSERT INTO content_types (name, display_name) VALUES 
('movie', 'Movie'),
('series', 'TV Series');

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, email, password, role) VALUES 
('admin', 'admin@moviestream.local', '$2y$10$q.2aYNNtd3Z8L4gicejLwu5NO.zMnqYp4cED9lCPDlm1BNFZz3XXq', 'super_admin');

-- Insert default genres
INSERT INTO genres (name, description, color_code, display_order) VALUES 
('Action', 'High-energy movies with thrilling sequences', '#ff6b6b', 1),
('Comedy', 'Funny and entertaining movies', '#feca57', 2),
('Drama', 'Serious and emotional storylines', '#48dbfb', 3),
('Horror', 'Scary and suspenseful movies', '#ff9ff3', 4),
('Romance', 'Love stories and romantic comedies', '#ff6b9d', 5),
('Sci-Fi', 'Science fiction and futuristic themes', '#54a0ff', 6),
('Thriller', 'Suspenseful and tension-filled movies', '#5f27cd', 7),
('Documentary', 'Educational and informative content', '#00d2d3', 8),
('Animation', '3D and 2D animated movies', '#ff9f43', 9),
('Fantasy', 'Magical and mythical adventures', '#9c88ff', 10),
('Adventure', 'Exciting journeys and explorations', '#10ac84', 11),
('Mystery', 'Puzzling and investigative stories', '#2f3640', 12);

-- Insert enhanced sample videos (movies)
INSERT INTO videos (title, content_type, genre, youtube_id, description, thumbnail_url, duration_seconds, release_year, language, director, cast, tags, featured, status) VALUES 
('Big Buck Bunny', 'movie', 'Animation', 'YE7VzlLtp-4', 'A delightful comedy 3D animation featuring a giant rabbit with a heart bigger than himself. This short film showcases the power of friendship and kindness.', 'https://i.ytimg.com/vi/YE7VzlLtp-4/maxresdefault.jpg', 596, 2008, 'English', 'Sacha Goedegebure', 'N/A', 'comedy,3d,animation,rabbit,friendship', TRUE, 'active'),
('Tears of Steel', 'movie', 'Sci-Fi', 'R6MlUcmOul8', 'A gripping science fiction short film about robots, emotions, and what it means to be human in a world where technology and humanity collide.', 'https://i.ytimg.com/vi/R6MlUcmOul8/maxresdefault.jpg', 734, 2012, 'English', 'Ian Hubert', 'Derek de Lint, Rogier Schippers', 'scifi,robots,technology,future', TRUE, 'active'),
('Sintel', 'movie', 'Fantasy', 'eRsGyueVLvQ', 'An epic fantasy tale of a lonely young woman, Sintel, who helps and befriends a dragon, embarking on a journey that will change both their lives forever.', 'https://i.ytimg.com/vi/eRsGyueVLvQ/maxresdefault.jpg', 888, 2010, 'English', 'Colin Levy', 'Halina Reijn', 'fantasy,dragon,adventure,friendship', TRUE, 'active'),
('Elephant Dream', 'movie', 'Fantasy', 'TLkA0RELQ1g', 'Two strange characters explore a capricious and seemingly infinite machine in this surreal and thought-provoking animated short film.', 'https://i.ytimg.com/vi/TLkA0RELQ1g/maxresdefault.jpg', 654, 2006, 'English', 'Bassam Kurdali', 'Tygo Gernandt, Cas Jansen', 'surreal,machine,exploration,abstract', FALSE, 'active'),
('Coffee Run', 'movie', 'Comedy', 'dQw4w9WgXcQ', 'A hilarious short about the daily adventures of getting the perfect cup of coffee. Comedy at its finest!', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 180, 2020, 'English', 'John Doe', 'Various', 'comedy,coffee,daily life,humor', FALSE, 'active');

-- Insert sample series
INSERT INTO series (title, description, thumbnail_url, release_year, language, director, cast, tags, featured, status) VALUES 
('Tech Chronicles', 'A documentary series exploring the history and future of technology', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 2023, 'English', 'Jane Tech', 'Various Tech Experts', 'technology,documentary,future', TRUE, 'active'),
('Comedy Central', 'A collection of funny skits and comedy shows', 'https://i.ytimg.com/vi/YE7VzlLtp-4/maxresdefault.jpg', 2022, 'English', 'Comedy Director', 'Comedy Cast', 'comedy,sketches,humor', TRUE, 'active');

-- Insert seasons for series
INSERT INTO seasons (series_id, season_number, title, description, thumbnail_url, release_year, episode_count) VALUES 
(1, 1, 'The Beginning of Tech', 'Exploring the origins of modern technology', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 2023, 3),
(1, 2, 'The AI Revolution', 'How artificial intelligence is changing the world', 'https://i.ytimg.com/vi/R6MlUcmOul8/maxresdefault.jpg', 2023, 2),
(2, 1, 'Best of Comedy 2022', 'The funniest moments from 2022', 'https://i.ytimg.com/vi/YE7VzlLtp-4/maxresdefault.jpg', 2022, 4);

-- Insert episodes
INSERT INTO videos (title, content_type, series_id, season_id, episode_number, genre, youtube_id, description, thumbnail_url, duration_seconds, release_year, status) VALUES 
('The Dawn of Computing', 'episode', 1, 1, 1, 'Documentary', 'dQw4w9WgXcQ', 'How computers were invented', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 1800, 2023, 'active'),
('The Internet Revolution', 'episode', 1, 1, 2, 'Documentary', 'YE7VzlLtp-4', 'The birth of the internet', 'https://i.ytimg.com/vi/YE7VzlLtp-4/maxresdefault.jpg', 1900, 2023, 'active'),
('Mobile Technology', 'episode', 1, 1, 3, 'Documentary', 'R6MlUcmOul8', 'How smartphones changed everything', 'https://i.ytimg.com/vi/R6MlUcmOul8/maxresdefault.jpg', 1750, 2023, 'active'),
('Machine Learning Basics', 'episode', 1, 2, 1, 'Documentary', 'eRsGyueVLvQ', 'Understanding AI fundamentals', 'https://i.ytimg.com/vi/eRsGyueVLvQ/maxresdefault.jpg', 2100, 2023, 'active'),
('The Future of AI', 'episode', 1, 2, 2, 'Documentary', 'TLkA0RELQ1g', 'What comes next in AI development', 'https://i.ytimg.com/vi/TLkA0RELQ1g/maxresdefault.jpg', 2000, 2023, 'active'),
('Stand-up Special 1', 'episode', 2, 1, 1, 'Comedy', 'dQw4w9WgXcQ', 'The best stand-up comedy from 2022', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 1500, 2022, 'active'),
('Sketch Comedy Gold', 'episode', 2, 1, 2, 'Comedy', 'YE7VzlLtp-4', 'Hilarious sketches and skits', 'https://i.ytimg.com/vi/YE7VzlLtp-4/maxresdefault.jpg', 1300, 2022, 'active'),
('Improv Night', 'episode', 2, 1, 3, 'Comedy', 'R6MlUcmOul8', 'The best improvisational comedy', 'https://i.ytimg.com/vi/R6MlUcmOul8/maxresdefault.jpg', 1400, 2022, 'active'),
('Comedy Roast', 'episode', 2, 1, 4, 'Comedy', 'eRsGyueVLvQ', 'Celebrity roast comedy special', 'https://i.ytimg.com/vi/eRsGyueVLvQ/maxresdefault.jpg', 1600, 2022, 'active');

-- Link movies to genres (many-to-many)
INSERT INTO video_genres (video_id, genre_id) VALUES 
(1, 9), -- Big Buck Bunny -> Animation
(2, 6), -- Tears of Steel -> Sci-Fi
(3, 10), (3, 11), -- Sintel -> Fantasy, Adventure
(4, 10), (4, 12), -- Elephant Dream -> Fantasy, Mystery
(5, 2); -- Coffee Run -> Comedy

-- Link series to genres
INSERT INTO series_genres (series_id, genre_id) VALUES 
(1, 8), -- Tech Chronicles -> Documentary
(2, 2); -- Comedy Central -> Comedy

-- Link episodes to genres
INSERT INTO video_genres (video_id, genre_id) VALUES 
(6, 8), (7, 8), (8, 8), (9, 8), (10, 8), -- Tech Chronicles episodes -> Documentary
(11, 2), (12, 2), (13, 2), (14, 2); -- Comedy Central episodes -> Comedy

-- Insert test users with different statuses
INSERT INTO users (username, email, password, status, expiry_date, country, preferred_language) VALUES 
('testuser', 'test@moviestream.local', '$2y$10$j5QhuuDW3nyRovpRXAemPe70ILbOTwQ2.KWZsyEjaABfFsxBkqNKa', 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH), 'United States', 'en'),
('demo', 'demo@moviestream.local', '$2y$10$j5QhuuDW3nyRovpRXAemPe70ILbOTwQ2.KWZsyEjaABfFsxBkqNKa', 'active', DATE_ADD(NOW(), INTERVAL 3 MONTH), 'Canada', 'en'),
('inactive_user', 'inactive@moviestream.local', '$2y$10$j5QhuuDW3nyRovpRXAemPe70ILbOTwQ2.KWZsyEjaABfFsxBkqNKa', 'inactive', NULL, 'United Kingdom', 'en');

-- Insert sample user progress
INSERT INTO user_progress (user_id, video_id, progress_seconds, total_duration, completed, watch_count) VALUES 
(1, 1, 300, 596, FALSE, 1),
(1, 2, 734, 734, TRUE, 2),
(2, 1, 596, 596, TRUE, 1),
(2, 3, 450, 888, FALSE, 1);

-- Insert sample ratings for movies
INSERT INTO ratings (user_id, video_id, rating, review) VALUES 
(1, 1, 5, 'Absolutely delightful! The animation is top-notch and the story is heartwarming.'),
(1, 2, 4, 'Great sci-fi short with impressive visuals and thought-provoking themes.'),
(2, 1, 4, 'Really enjoyed this one. Great for the whole family!'),
(2, 3, 5, 'Epic fantasy adventure! The animation quality is incredible.');

-- Insert sample ratings for series
INSERT INTO ratings (user_id, series_id, rating, review) VALUES 
(1, 1, 5, 'Excellent documentary series about technology!'),
(2, 2, 4, 'Very funny comedy series, loved the sketches.');

-- Insert sample watchlist items
INSERT INTO watchlist (user_id, video_id, priority, notes) VALUES 
(1, 3, 2, 'Want to watch this weekend'),
(1, 4, 1, 'Recommended by friend');

INSERT INTO watchlist (user_id, series_id, priority, notes) VALUES 
(2, 1, 3, 'Looks educational'),
(2, 2, 1, 'Need some laughs');

-- Insert default system settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_public) VALUES 
('site_name', 'MovieStream v0.2.0', 'string', 'Main site name displayed in header', TRUE),
('site_tagline', 'Your Premium Movie & Series Streaming Experience', 'string', 'Site tagline or slogan', TRUE),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode', FALSE),
('max_file_upload_size', '52428800', 'integer', 'Maximum file upload size in bytes (50MB)', FALSE),
('default_user_expiry_days', '30', 'integer', 'Default user membership expiry in days', FALSE),
('enable_user_registration', '1', 'boolean', 'Allow new user registrations', TRUE),
('require_email_verification', '0', 'boolean', 'Require email verification for new accounts', FALSE),
('default_video_quality', '720p', 'string', 'Default video quality setting', TRUE),
('subtitle_sync_tolerance', '100', 'integer', 'Subtitle synchronization tolerance in milliseconds', FALSE),
('session_lifetime', '86400', 'integer', 'User session lifetime in seconds (24 hours)', FALSE);

-- Insert sample subtitle files data
INSERT INTO subtitles (video_id, language, language_name, srt_file_path, file_size, subtitle_count) VALUES 
(1, 'en', 'English', 'uploads/subtitles/1_en.srt', 1024, 25),
(2, 'en', 'English', 'uploads/subtitles/2_en.srt', 1856, 45),
(3, 'en', 'English', 'uploads/subtitles/3_en.srt', 2048, 52),
(6, 'en', 'English', 'uploads/subtitles/6_en.srt', 1024, 35),
(7, 'en', 'English', 'uploads/subtitles/7_en.srt', 1200, 40);

-- Create views for common queries
CREATE VIEW video_stats AS
SELECT 
    v.id,
    v.title,
    v.content_type,
    v.series_id,
    v.season_id,
    v.episode_number,
    v.genre,
    v.view_count,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.rating) as rating_count,
    COUNT(DISTINCT p.user_id) as unique_viewers,
    COUNT(DISTINCT w.user_id) as watchlist_count
FROM videos v
LEFT JOIN ratings r ON v.id = r.video_id
LEFT JOIN user_progress p ON v.id = p.video_id
LEFT JOIN watchlist w ON v.id = w.video_id
WHERE v.status = 'active'
GROUP BY v.id, v.title, v.content_type, v.series_id, v.season_id, v.episode_number, v.genre, v.view_count;

CREATE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.status,
    u.expiry_date,
    COUNT(DISTINCT p.video_id) as videos_watched,
    COUNT(DISTINCT CASE WHEN w.video_id IS NOT NULL THEN w.video_id WHEN w.series_id IS NOT NULL THEN w.series_id END) as watchlist_count,
    COALESCE(AVG(CASE WHEN r.video_id IS NOT NULL THEN r.rating END), 0) as avg_rating_given,
    COUNT(CASE WHEN r.video_id IS NOT NULL AND r.review IS NOT NULL THEN r.rating END) as reviews_written
FROM users u
LEFT JOIN user_progress p ON u.id = p.user_id
LEFT JOIN watchlist w ON u.id = w.user_id
LEFT JOIN ratings r ON u.id = r.user_id
GROUP BY u.id, u.username, u.status, u.expiry_date;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE UpdateVideoViewCount(IN video_id_param INT)
BEGIN
    UPDATE videos 
    SET view_count = view_count + 1 
    WHERE id = video_id_param;
END //

CREATE PROCEDURE CleanExpiredSessions()
BEGIN
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
END //

CREATE PROCEDURE ExpireUsers()
BEGIN
    UPDATE users 
    SET status = 'inactive' 
    WHERE status = 'active' 
    AND expiry_date IS NOT NULL 
    AND expiry_date < CURDATE();
END //

DELIMITER ;

-- Create event scheduler to automatically expire users and clean sessions
CREATE EVENT IF NOT EXISTS expire_users_event
ON SCHEDULE EVERY 1 HOUR
DO
    CALL ExpireUsers();

CREATE EVENT IF NOT EXISTS clean_sessions_event
ON SCHEDULE EVERY 6 HOUR
DO
    CALL CleanExpiredSessions();

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;