● # CiolStream v1 - Your Movie World

  A feature-rich movie and TV series streaming platform with advanced
  subtitle management, user engagement features, and comprehensive admin
  controls. Built with PHP and MySQL for LAMP/XAMPP environments.

  ## 📋 Project Overview

  CiolStream is a professional-grade streaming platform that allows users
  to browse, watch, and manage their favorite movies and TV series with
  multi-language subtitle support. The platform features user
  authentication, progress tracking, ratings & reviews, watchlist
  management, and a powerful admin dashboard for content management.

  ### Key Features

  - 🎬 **Movies & TV Series**: Browse and watch movies and episodes with
  organized seasons
  - 🔤 **Multi-language Subtitles**: Dynamic subtitle loading with sync
  controls and language switching
  - 👥 **User Management**: Registration, login, membership expiry, and
  access control
  - 📊 **Watch Progress**: Automatic progress tracking with resume playback
  - ⭐ **Ratings & Reviews**: Community engagement with star ratings and
  written reviews
  - 📝 **Watchlist**: Personal collections with priority management
  - 🎯 **Smart Recommendations**: Personalized suggestions based on viewing
  history
  - 🔐 **Admin Panel**: Complete content, user, and subtitle management
  - 📱 **Responsive Design**: Mobile-friendly interface across all devices

  ## 🛠️ Tech Stack

  - **Backend**: PHP 8.3+ (compatible with PHP 7.4+)
  - **Database**: MySQL 8.0+ / MariaDB 10.2+
  - **Web Server**: Apache 2.4+ with mod_rewrite
  - **Frontend**: HTML5, CSS3, Vanilla JavaScript
  - **Video Player**: YouTube iframe API integration
  - **Subtitle Format**: SRT (SubRip Text)

  ### PHP Extensions Required

  - PDO & PDO_MySQL (database connectivity)
  - JSON (API responses)
  - MBString (multi-byte string handling)
  - FileInfo (file type detection)
  - Session (user authentication)

  ## 📁 Project Structure

  ciolstream/
  ├── config/
  │   └── database.php           # Database configuration
  ├── includes/
  │   └── functions.php          # Helper functions & utilities
  ├── assets/
  │   └── css/
  │       └── style.css          # Unified responsive styles
  ├── public/
  │   ├── index.php              # Main content browser
  │   ├── watch.php              # Video player with subtitles
  │   └── series.php             # TV series episodes listing
  ├── user/
  │   ├── dashboard.php          # User dashboard & statistics
  │   ├── settings.php           # Account settings & password change
  │   └── ajax/
  │       ├── add_watchlist.php      # Watchlist management
  │       ├── remove_watchlist.php
  │       ├── rate_video.php         # Rating system
  │       ├── rate_content.php       # Series rating
  │       └── update_progress.php    # Progress tracking
  ├── admin/
  │   ├── login.php              # Admin authentication
  │   ├── dashboard.php          # Complete admin panel
  │   ├── settings.php           # Admin account settings
  │   ├── logout.php             # Admin logout
  │   └── download_subtitle.php  # Subtitle file downloads
  ├── ajax/
  │   ├── get_subtitles.php      # Dynamic subtitle loading
  │   └── get_seasons.php        # Season data for episodes
  ├── uploads/
  │   ├── subtitles/             # SRT subtitle files
  │   └── thumbnails/            # Video thumbnails
  ├── vendor/                    # Composer dependencies (phpstan)
  ├── database.sql               # Complete database schema
  ├── setup.php                  # Installation checker
  ├── index.php                  # Root redirector
  ├── login.php                  # User login
  ├── register.php               # User registration
  ├── logout.php                 # User logout
  ├── .htaccess                  # Apache configuration
  ├── composer.json              # PHP dependencies
  └── README.md                  # This file

  ## 🚀 Installation Guide

  ### Prerequisites

  - **Server**: Apache 2.4+ or Nginx 1.18+
  - **PHP**: 8.3+ (minimum 7.4)
  - **Database**: MySQL 8.0+ or MariaDB 10.2+
  - **Memory**: 512MB minimum (1GB recommended)
  - **Disk Space**: 1GB minimum

  ### Step 1: Clone/Download Project

  ```bash
  cd /var/www/html/
  git clone <repository-url> ciolstream
  cd ciolstream

  Or extract the ZIP file to your web server's document root.

  Step 2: Set Permissions

  # Set ownership
  sudo chown -R www-data:www-data /var/www/html/ciolstream

  # Set directory permissions
  sudo chmod 755 /var/www/html/ciolstream
  sudo chmod -R 777 uploads/
  sudo chmod -R 777 uploads/subtitles/
  sudo chmod -R 777 uploads/thumbnails/

  Step 3: Create Database

  mysql -u root -p

  CREATE DATABASE ciolstream CHARACTER SET utf8mb4 COLLATE
  utf8mb4_unicode_ci;
  CREATE USER 'ciolstream_user'@'localhost' IDENTIFIED BY
  'your_secure_password';
  GRANT ALL PRIVILEGES ON ciolstream.* TO
  'ciolstream_user'@'localhost';
  FLUSH PRIVILEGES;
  EXIT;

  Step 4: Import Database Schema

  mysql -u ciolstream_user -p ciolstream < database.sql

  The database includes:
  - 19 tables (users, videos, series, seasons, subtitles, etc.)
  - Default admin account (username: admin, password: admin123)
  - Sample genres and content types
  - Indexes for optimized queries

  Step 5: Configure Database Connection

  Edit config/database.php:

  $host = 'localhost';
  $dbname = 'ciolstream';
  $username = 'ciolstream_user';     // Your database username
  $password = 'your_secure_password'; // Your database password

  Step 6: Web Server Configuration

  For Apache (ensure mod_rewrite is enabled):

  sudo a2enmod rewrite
  sudo systemctl restart apache2

  The .htaccess file is already configured.

  For Nginx, add to your site configuration:

  location / {
      try_files $uri $uri/ /index.php?$query_string;
  }

  location ~ \.php$ {
      fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
      fastcgi_index index.php;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }

  Step 7: Verify Installation

  Visit: http://localhost/ciolstream/setup.php

  The setup checker will verify:
  - ✅ PHP version and required extensions
  - ✅ Directory write permissions
  - ✅ Database connectivity
  - ✅ Configuration files

  🎯 Quick Start

  Default Accounts

  Admin Access:
  - URL: http://localhost/ciolstream/admin/login.php
  - Username: admin
  - Password: admin123
  - ⚠️ Change this password immediately after first login!

  Create User Account:
  - Register at: http://localhost/ciolstream/register.php
  - New users are inactive by default
  - Admin must activate users from admin dashboard

  First Steps

  1. Login as Admin
    - Go to /admin/login.php
    - Navigate to "Series Management" to add TV series
    - Navigate to "Seasons Management" to add seasons
    - Navigate to "Video Management" to add movies or episodes
  2. Add Content
    - For Movies: Select content type "Movie", add YouTube ID and metadata
    - For TV Series:
        - Create series → Add seasons → Add episodes
      - Episodes require series, season, and episode number
  3. Upload Subtitles
    - Go to "Subtitles Management"
    - Upload SRT files for videos
    - Supported languages: en, es, fr, de, etc.
  4. Manage Users
    - Activate registered users
    - Set membership expiry (1, 3, 6, or 12 months)
    - Active users can view subtitles; inactive users cannot

  📊 Database Schema

  Core Tables

  | Table         | Description                                |
  | ------------- | ------------------------------------------ |
  | users         | User accounts with status and expiry dates |
  | series        | TV series metadata                         |
  | seasons       | Seasons belonging to series                |
  | videos        | Movies and episodes with metadata          |
  | subtitles     | Multi-language subtitle files              |
  | genres        | Content genres                             |
  | series_genres | Many-to-many: series ↔ genres              |
  | video_genres  | Many-to-many: videos ↔ genres              |

  User Engagement Tables

  | Table         | Description                          |
  | ------------- | ------------------------------------ |
  | watchlist     | User's saved movies/series           |
  | ratings       | User ratings and reviews (1-5 stars) |
  | user_progress | Watch progress and completion status |
  | user_stats    | User viewing statistics              |

  Admin & System Tables

  | Table              | Description                      |
  | ------------------ | -------------------------------- |
  | admins             | Admin accounts                   |
  | user_sessions      | Active user sessions with tokens |
  | admin_logs         | Admin activity tracking          |
  | user_activity_logs | User behavior analytics          |
  | settings           | System configuration             |

  Key Relationships

  - series → seasons → videos (episodes)
  - videos → subtitles (one-to-many)
  - users → user_progress (watch history)
  - users → watchlist (saved content)
  - users → ratings (reviews)

  ✨ Features in Detail

  User Features

  Content Access:
  - ✅ All users (logged-in or not) can watch videos
  - ✅ Only logged-in active users can see subtitles
  - ✅ Inactive users can watch but without subtitle access

  Dashboard:
  - Continue watching with progress tracking
  - Personalized recommendations based on viewing history
  - Watch statistics (total watched, time spent)
  - Recent activity timeline

  Watchlist:
  - Add movies and series to personal collection
  - Priority management
  - Quick access from dashboard

  Ratings & Reviews:
  - Rate content 1-5 stars
  - Write detailed text reviews
  - View community ratings

  Admin Features

  Series Management:
  - Add TV series with metadata (title, description, genres, cast, director)
  - Multi-genre selection
  - Season and episode count tracking
  - Status management (active/inactive/coming soon)

  Seasons Management:
  - Add seasons to series with season numbers
  - Prevent duplicate seasons
  - Episode count auto-updates
  - Individual season metadata

  Video Management:
  - Add movies or TV episodes
  - Multi-genre assignment
  - YouTube integration (video ID required)
  - Rich metadata (duration, release year, director, cast, language, tags)
  - Automatic thumbnail fetching from YouTube
  - Episode validation (requires series, season, episode number)

  User Management:
  - Activate/deactivate user accounts
  - Set membership expiry (1-12 months)
  - View registration date and status
  - Expired members auto-deactivate

  Subtitle Management:
  - Upload SRT subtitle files
  - Multi-language support
  - File validation and storage
  - Download existing subtitles
  - Per-video language management

  Security Features

  - Password Hashing: bcrypt with password_hash()
  - Session Tokens: Secure session management with database storage
  - SQL Injection Protection: Prepared statements throughout
  - XSS Prevention: Input sanitization with htmlspecialchars()
  - Access Control: Role-based permissions (user/admin)
  - Session Expiry: Automatic cleanup of expired sessions

  ⚙️ Configuration

  PHP Settings (php.ini)

  memory_limit = 256M
  upload_max_filesize = 50M
  post_max_size = 50M
  max_execution_time = 300
  max_input_time = 300

  Debug Mode

  Enable in config/database.php:

  define('DEBUG_MODE', true);  // Development only!

  🐛 Known Issues & Limitations

  1. Video Hosting: Currently only supports YouTube-hosted videos (no direct
   file uploads)
  2. Subtitle Format: Only SRT format supported (no VTT or other formats)
  3. Thumbnail Management: Thumbnails fetched from YouTube (no custom
  uploads)
  4. Email Notifications: Not implemented (password reset, expiry reminders)
  5. Payment Integration: No payment gateway for subscriptions
  6. Mobile Apps: No native mobile applications
  7. Content Delivery: No CDN integration for static assets
  8. Search Functionality: Basic search only (no advanced filters)
  9. Social Features: No user profiles, friends, or sharing
  10. Analytics: Basic statistics only (no advanced analytics dashboard)

  Browser Compatibility

  - ✅ Chrome/Edge 90+
  - ✅ Firefox 88+
  - ✅ Safari 14+
  - ⚠️ IE 11 not supported

  🔧 Troubleshooting

  Subtitles Not Loading

  # Check permissions
  ls -la uploads/subtitles/
  chmod 777 uploads/subtitles/

  # Check file exists and is readable
  cat uploads/subtitles/1_en.srt

  Database Connection Failed

  # Verify MySQL is running
  sudo systemctl status mysql

  # Test connection
  mysql -u ciolstream_user -p ciolstream

  # Check credentials in config/database.php

  Videos Not Playing

  - Verify YouTube video ID is correct
  - Check browser console for JavaScript errors
  - Ensure valid YouTube embed permissions
  - Test with a known working YouTube video ID

  Episodes Not Showing on Series Page

  - Fixed in v0.2.0 - SQL GROUP BY issue resolved
  - Ensure series → season → episode relationships are correct
  - Check that video content_type is set to 'episode'

  Upload Directory Not Writable

  sudo chown -R www-data:www-data /var/www/html/ciolstream/uploads
  sudo chmod -R 777 /var/www/html/ciolstream/uploads

  Admin Cannot Login

  # Reset admin password
  mysql -u root -p ciolstream
  UPDATE admins SET password =
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE
  username = 'admin';
  # Password will be: admin123

  🚀 Future Improvements

  Planned Features

  - Direct Video Upload: Support for MP4/WebM files (not just YouTube)
  - Advanced Search: Filters by genre, year, rating, duration
  - User Profiles: Public profiles with watch history
  - Social Features: Follow users, share watchlists
  - Payment Integration: Stripe/PayPal for subscriptions
  - Email System: Registration confirmation, password reset, expiry
  reminders
  - Content Recommendations AI: Machine learning-based suggestions
  - Multi-CDN Support: CloudFlare, AWS CloudFront integration
  - Advanced Analytics: Detailed viewing statistics and charts
  - Mobile Apps: Native iOS and Android applications
  - Live Streaming: RTMP/HLS streaming support
  - Multi-language UI: Interface translation (currently English only)
  - Bulk Operations: Import/export content via CSV
  - Content Moderation: Report system and admin review queue
  - API: RESTful API for third-party integrations

  Performance Optimizations

  - Redis/Memcached caching layer
  - Database query optimization with EXPLAIN
  - Lazy loading for images and videos
  - Asset minification and bundling
  - Service worker for offline capability

  📝 Developer Notes

  Code Quality

  - Static analysis with PHPStan (via Composer)
  - Run: ./vendor/bin/phpstan analyze
  - Configuration: phpstan.neon

  Adding New Features

  1. Follow existing file structure and naming conventions
  2. Use PDO prepared statements for all queries
  3. Sanitize all user input with htmlspecialchars()
  4. Log errors to PHP error log, not to users
  5. Test on both logged-in and guest users
  6. Verify responsive design on mobile devices

  Database Migrations

  When modifying schema:

  -- Add new column
  ALTER TABLE videos ADD COLUMN imdb_id VARCHAR(20) AFTER youtube_id;

  -- Add index
  CREATE INDEX idx_videos_imdb ON videos(imdb_id);

  -- Update data
  UPDATE videos SET imdb_id = NULL WHERE imdb_id = '';

  📄 License

  This project is open-source and available under the MIT License.

  🆘 Support

  For issues and questions:

  1. Run setup.php to verify installation
  2. Check PHP error logs: sudo tail -f /var/log/apache2/error.log
  3. Enable debug mode in config/database.php
  4. Review browser console for JavaScript errors
  5. Check database connectivity and credentials

  👥 Credits

  CiolStream v1 - A complete streaming platform solution

  Built with ❤️ for educational and personal use.

  ---
  ⚠️ Important Security Note: Change default admin password and database
  credentials before deploying to production!
  ```
