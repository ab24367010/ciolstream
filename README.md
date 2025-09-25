# MovieStream v0.1.0 - Complete Movie Streaming Platform

A professional-grade movie streaming platform with advanced subtitle management, user analytics, and modern responsive design. Perfect for XAMPP/LAMP environments on Ubuntu localhost.

## 🚀 New Features in v0.1.0

### Enhanced User Experience
- ✅ **Complete User Dashboard** with watch history and statistics
- ✅ **Advanced Video Recommendations** based on viewing history
- ✅ **Interactive Rating & Review System** with star ratings
- ✅ **Smart Watchlist Management** with priority levels
- ✅ **Real-time Progress Tracking** with resume functionality
- ✅ **User Activity Logging** for detailed analytics

### Advanced Subtitle System
- ✅ **Multi-language Subtitle Support** with language switching
- ✅ **Dynamic Subtitle Loading** via AJAX
- ✅ **Subtitle Synchronization Controls** with timing adjustment
- ✅ **Enhanced SRT Parser** with better error handling
- ✅ **Fullscreen Subtitle Compatibility** across all browsers

### Professional Admin Panel
- ✅ **Comprehensive User Management** with membership controls
- ✅ **Advanced Video Management** with metadata support
- ✅ **Bulk Subtitle Operations** with file validation
- ✅ **System Analytics Dashboard** with usage statistics
- ✅ **Admin Activity Logging** for security tracking

### Database & Performance
- ✅ **Optimized Database Schema** with proper indexing
- ✅ **Advanced Search & Filtering** with multiple criteria
- ✅ **Caching & Performance** optimizations
- ✅ **Database Views & Procedures** for complex queries
- ✅ **Automatic Session Management** with cleanup

## 📁 Complete Project Structure

```
moviestream/
├── setup.php                   # Automated setup checker
├── .htaccess                    # Apache configuration
├── database.sql                 # Complete database schema
├── index.php                    # Redirect to public
├── login.php                    # User login
├── register.php                 # User registration
├── logout.php                   # Logout handler
│
├── config/
│   └── database.php             # Database configuration
│
├── includes/
│   └── functions.php            # Enhanced helper functions
│
├── assets/css/
│   └── style.css               # Complete responsive styles
│
├── public/
│   ├── index.php               # Main movie browser
│   └── watch.php               # Enhanced video player
│
├── admin/
│   ├── login.php               # Admin authentication
│   ├── logout.php              # Admin logout
│   ├── dashboard.php           # Complete admin panel
│   └── download_subtitle.php   # Subtitle file downloads
│
├── user/
│   ├── dashboard.php           # User dashboard
│   └── ajax/
│       ├── add_watchlist.php   # Watchlist management
│       ├── remove_watchlist.php
│       ├── rate_video.php      # Rating system
│       └── update_progress.php # Progress tracking
│
├── ajax/
│   └── get_subtitles.php       # Dynamic subtitle loading
│
└── uploads/                    # Auto-created directories
    ├── subtitles/              # SRT subtitle files
    └── thumbnails/             # Video thumbnails
```

## 🛠️ Installation Guide

### Step 1: System Requirements

**Minimum Requirements:**
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.2+
- Apache with mod_rewrite
- 512MB RAM (1GB recommended)
- 1GB disk space

**Required PHP Extensions:**
- PDO & PDO_MySQL
- JSON
- MBString
- FileInfo
- GD (optional, for future features)

### Step 2: Download and Setup

1. **Extract files** to your web server directory:
```bash
cd /var/www/html/
sudo mkdir moviestream
sudo chown $USER:$USER moviestream
cd moviestream
# Extract all files here
```

2. **Set file permissions**:
```bash
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
```

### Step 3: Database Configuration

1. **Create MySQL database**:
```sql
CREATE DATABASE movie_streaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'moviestream'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON movie_streaming.* TO 'moviestream'@'localhost';
FLUSH PRIVILEGES;
```

2. **Import database schema**:
```bash
mysql -u moviestream -p movie_streaming < database.sql
```

3. **Update database configuration** in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'movie_streaming');
define('DB_USER', 'moviestream');
define('DB_PASS', 'your_secure_password');
```

### Step 4: Web Server Configuration

**For Apache**, ensure `.htaccess` is in place and mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**For Nginx**, add this to your site configuration:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### Step 5: Run Setup Checker

Visit `http://localhost/moviestream/setup.php` to verify installation:
- ✅ PHP version and extensions
- ✅ Directory permissions
- ✅ Database connectivity
- ✅ Configuration validation

## 🎬 Quick Start Guide

### Default Accounts

**Admin Access:**
- URL: `/admin/login.php`
- Username: `admin`
- Password: `admin123`

**Test User:**
- URL: `/login.php`
- Username: `testuser`
- Password: `testuser`

### First Steps

1. **Login as admin** and upload your first video
2. **Add subtitle files** for enhanced user experience
3. **Create user accounts** and set membership expiry
4. **Configure system settings** for optimal performance
5. **Test video playback** with subtitles

## 🔧 Configuration Options

### System Settings (via Database)

Key settings in the `settings` table:

```sql
-- Site branding
UPDATE settings SET setting_value = 'Your Movie Site' WHERE setting_key = 'site_name';

-- User registration
UPDATE settings SET setting_value = '1' WHERE setting_key = 'enable_user_registration';

-- Default membership duration
UPDATE settings SET setting_value = '90' WHERE setting_key = 'default_user_expiry_days';

-- File upload limits
UPDATE settings SET setting_value = '104857600' WHERE setting_key = 'max_file_upload_size'; -- 100MB
```

### Performance Tuning

**PHP Settings** (in `php.ini`):
```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
```

**MySQL Optimization**:
```sql
-- Enable event scheduler for automatic cleanup
SET GLOBAL event_scheduler = ON;

-- Add custom indexes for better performance
CREATE INDEX idx_videos_featured_status ON videos(featured, status);
CREATE INDEX idx_user_progress_completed ON user_progress(user_id, completed);
```

## 🎯 Feature Highlights

### User Dashboard
- **Watch History** with progress tracking
- **Personalized Recommendations** based on viewing habits
- **Watchlist Management** with priority settings
- **Rating & Review System** for community engagement
- **Account Statistics** and activity timeline

### Advanced Video Player
- **YouTube Integration** with custom controls
- **Multi-language Subtitles** with real-time switching
- **Subtitle Synchronization** with manual adjustment
- **Fullscreen Compatibility** across all browsers
- **Progress Tracking** with resume functionality

### Admin Panel
- **User Management** with membership control
- **Video Library** with metadata management
- **Subtitle Management** with bulk operations
- **Analytics Dashboard** with usage statistics
- **System Monitoring** and maintenance tools

### Security Features
- **Password Hashing** with PHP password_hash()
- **Session Management** with automatic cleanup
- **SQL Injection Protection** with prepared statements
- **XSS Prevention** with input sanitization
- **CSRF Protection** for forms and AJAX requests

## 📊 Database Schema Overview

### Core Tables
- **users** - User accounts with membership tracking
- **videos** - Video library with comprehensive metadata
- **subtitles** - Multi-language subtitle file management
- **user_progress** - Watch history and progress tracking
- **ratings** - User ratings and reviews
- **watchlist** - Personal movie collections

### System Tables
- **admins** - Admin user management
- **user_sessions** - Session tracking and security
- **admin_logs** - Admin activity logging
- **user_activity_logs** - User behavior analytics
- **settings** - System configuration

## 🎨 Customization Guide

### Styling
- Edit `assets/css/style.css` for visual customization
- Colors, fonts, and layouts are easily configurable
- Responsive design works on all devices

### Adding Features
- Use the established function library in `includes/functions.php`
- Follow the MVC-like pattern for new pages
- Extend database schema as needed

### Integration
- YouTube API for video embedding
- Email systems for notifications (future)
- Payment gateways for subscriptions (future)

## 🚀 Performance Tips

1. **Enable Gzip Compression** in Apache/Nginx
2. **Use MySQL Query Cache** for better performance
3. **Implement CDN** for static assets
4. **Regular Database Cleanup** using built-in events
5. **Monitor Log Files** for optimization opportunities

## 🔍 Troubleshooting

### Common Issues

**Subtitles Not Loading:**
```bash
# Check file permissions
ls -la uploads/subtitles/
chmod 644 uploads/subtitles/*.srt
```

**Database Connection Errors:**
```php
// Verify credentials in config/database.php
// Check MySQL service status
sudo systemctl status mysql
```

**Video Playback Issues:**
- Ensure YouTube URLs are valid
- Check browser console for JavaScript errors
- Verify iframe permissions in .htaccess

### Debug Mode
Enable detailed error reporting:
```php
// Add to config/database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Future Roadmap

- **Direct Video Upload** (not just YouTube)
- **Advanced Analytics Dashboard**
- **Mobile App Integration**
- **Social Features** (friends, sharing)
- **Payment & Subscription System**
- **Content Recommendation AI**

## 🤝 Contributing

1. Fork the repository
2. Create feature branches
3. Test thoroughly on Ubuntu localhost
4. Submit pull requests with detailed descriptions

## 📝 License

This project is open-source and available under the MIT License.

## 🆘 Support

For support and questions:
1. Check the troubleshooting section
2. Review the setup checker at `/setup.php`
3. Examine browser console and PHP error logs
4. Ensure all requirements are met

---

**MovieStream v0.1.0** - Your complete movie streaming solution! 🎬✨