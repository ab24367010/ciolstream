# 🎬 CiolStream

**Your Movie World** - A modern web-based movie and TV series streaming platform built with PHP and MySQL.

![Version](https://img.shields.io/badge/version-1.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Admin Panel](#admin-panel)
- [API Endpoints](#api-endpoints)
- [Security Features](#security-features)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [License](#license)

---

## 🎯 Overview

CiolStream is a feature-rich streaming platform that allows users to watch movies and TV series with integrated subtitle support, progress tracking, watchlists, and ratings. The platform supports both standalone movies and multi-season TV series with episode management.

Built with modern web technologies and security best practices, CiolStream provides an intuitive interface for both users and administrators.

---

## ✨ Features

### User Features
- **🎬 Movie Streaming** - Watch movies via YouTube integration
- **📺 TV Series Support** - Multi-season series with episode management
- **🔍 Search & Filter** - Search by title, filter by genre and content type
- **📝 Subtitle Support** - Upload and sync SRT subtitles
- **📊 Progress Tracking** - Automatic playback position saving
- **❤️ Watchlist** - Save content for later viewing
- **⭐ Ratings & Reviews** - Rate and review content
- **👤 User Dashboard** - View watch history, stats, and manage watchlist
- **🔐 User Authentication** - Secure registration and login with session management
- **📱 Responsive Design** - Mobile-friendly interface
- **🌐 PWA Support** - Progressive Web App capabilities

### Admin Features
- **📊 Admin Dashboard** - Comprehensive content and user management
- **🎥 Content Management** - Add/edit/delete movies and series
- **🎭 Genre Management** - Create and manage content genres
- **👥 User Management** - Manage user accounts and permissions
- **📈 Analytics** - View count tracking and user activity logs
- **🎬 Season/Episode Management** - Multi-season TV series support
- **📝 Subtitle Management** - Upload and download subtitle files
- **⚙️ Settings** - Configure site name, tagline, and system settings
- **🔒 Role-Based Access** - Super Admin, Admin, and Moderator roles
- **📋 Activity Logging** - Track all admin actions

### Content Organization
- **12 Pre-configured Genres** - Action, Comedy, Drama, Horror, Romance, Sci-Fi, Thriller, Documentary, Animation, Fantasy, Adventure, Mystery
- **Content Types** - Movies and TV Series
- **Featured Content** - Highlight popular content on homepage
- **View Analytics** - Track video views and engagement

---

## 💻 Requirements

### Server Requirements
- **Operating System**: Linux (Ubuntu 24.04+ recommended) or Windows
- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **PHP**: 7.4 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.5+

### PHP Extensions (Required)
- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`
- `session`
- `fileinfo`

### Recommended
- **RAM**: Minimum 512MB, 1GB+ recommended
- **Disk Space**: 1GB+ for application and uploads
- **SSL Certificate**: For HTTPS (Let's Encrypt recommended)

---

## 📦 Installation

### Step 1: Clone Repository

```bash
cd /var/www/html
git clone https://github.com/yourusername/ciolstream.git
cd ciolstream
```

### Step 2: Set Directory Permissions

```bash
# Create upload directories if they don't exist
mkdir -p uploads/subtitles uploads/thumbnails

# Set proper permissions
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
```

### Step 3: Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE movie_streaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### Step 4: Import Database Schema

```bash
mysql -u root -p movie_streaming < database.sql
```

The database includes:
- **Default Admin Account**:
  - Username: `admin`
  - Email: `admin@moviestream.local`
  - Password: `admin123` (⚠️ **Change immediately after first login**)
- 12 pre-configured genres
- Sample content types (Movie, TV Series)
- Default system settings

### Step 5: Configure Database Connection

Edit `config/database.php`:

```php
$host = 'localhost';
$dbname = 'movie_streaming';
$username = 'root';        // Your MySQL username
$password = 'your_password'; // Your MySQL password
```

### Step 6: Configure Apache Virtual Host (Optional)

Create `/etc/apache2/sites-available/ciolstream.conf`:

```apache
<VirtualHost *:80>
    ServerName ciolstream.local
    DocumentRoot /var/www/html/ciolstream

    <Directory /var/www/html/ciolstream>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ciolstream-error.log
    CustomLog ${APACHE_LOG_DIR}/ciolstream-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite ciolstream
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### Step 7: Install PHP Dependencies (Optional)

```bash
composer install
```

### Step 8: Run Setup Script

Navigate to `http://your-domain/setup.php` to verify installation:
- PHP version check
- Required extensions check
- Directory permissions check
- Database connection test

---

## ⚙️ Configuration

### Database Configuration
Edit `config/database.php`:
- Update database credentials
- Set `DEBUG_MODE` to `false` for production
- Configure timezone settings

### Site Settings
Access Admin Panel → Settings to configure:
- **Site Name**: CiolStream (customizable)
- **Site Tagline**: Your Movie World
- **User Registration**: Enable/disable new registrations
- **Default User Expiry**: Set default membership duration (30 days)
- **Session Lifetime**: Configure session timeout (24 hours default)
- **Subtitle Sync Tolerance**: Adjust subtitle synchronization (100ms default)

### Security Configuration
The `.htaccess` file includes:
- HTTPS redirect (disabled for localhost)
- Security headers (X-Frame-Options, CSP, etc.)
- Static file caching
- Protection for sensitive files (.sql, .srt, config/)
- PHP execution prevention in uploads directory

---

## 🚀 Usage

### For Users

#### Registration & Login
1. Navigate to `http://your-domain/public/`
2. Click **Register** to create an account
3. Login with your credentials
4. Browse movies and series

#### Watching Content
1. **Search** for content using the search bar
2. **Filter** by genre or content type (Movies/Series)
3. Click on any content card to view details
4. Click **Watch Now** to start streaming
5. **Subtitles**: Select available subtitles from the player
6. **Progress**: Your position is automatically saved

#### Managing Watchlist
- Click **❤️ Watchlist** button on any content
- View your watchlist in the User Dashboard
- Remove items from watchlist as needed

#### User Dashboard
Access at `http://your-domain/user/dashboard.php`:
- View watch history and progress
- Manage watchlist
- See user statistics
- Update profile settings

### For Administrators

#### Admin Login
Navigate to `http://your-domain/admin/login.php`

**Default Credentials** (⚠️ Change immediately):
- Username: `admin`
- Password: `admin123`

#### Adding Movies
1. Go to Admin Dashboard
2. Fill in the "Add New Movie" form:
   - Title (required)
   - YouTube ID (required)
   - Description
   - Genre(s)
   - Release year
   - Duration (seconds)
   - Thumbnail URL
   - Director, Cast, Language, Tags
3. Click **Add Movie**

#### Adding TV Series
1. Go to Admin Dashboard
2. Fill in the "Add New Series" form:
   - Series title and description
   - Genres
   - Release year, director, cast
3. Click **Add Series**
4. Add Seasons to the series:
   - Season number
   - Title and description
   - Episode count
5. Add Episodes to each season:
   - Episode number
   - Title and YouTube ID
   - Duration

#### Managing Subtitles
1. Navigate to Admin → Subtitle Management
2. Select a video/episode
3. Upload `.srt` subtitle file
4. Specify language and uploader name
5. Download or delete existing subtitles

#### User Management
- View all registered users
- Update user status (active/inactive)
- Extend or modify expiry dates
- Monitor user activity logs

---

## 📁 Project Structure

```
ciolstream/
│
├── admin/                      # Admin panel
│   ├── dashboard.php          # Main admin dashboard
│   ├── login.php              # Admin login
│   ├── logout.php             # Admin logout
│   ├── settings.php           # Site settings management
│   └── download_subtitle.php  # Subtitle download handler
│
├── ajax/                      # AJAX endpoints
│   ├── get_seasons.php        # Get seasons for series
│   └── get_subtitles.php      # Get subtitles for video
│
├── assets/                    # Static assets
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   └── js/                    # JavaScript files
│
├── config/                    # Configuration files
│   └── database.php          # Database connection & config
│
├── includes/                  # PHP includes
│   └── functions.php         # Helper functions library
│
├── public/                    # Public-facing pages
│   ├── index.php             # Homepage (browse content)
│   ├── watch.php             # Video player page
│   └── series.php            # Series details & episodes
│
├── user/                      # User area
│   ├── dashboard.php         # User dashboard
│   ├── settings.php          # User settings
│   └── ajax/                 # User AJAX endpoints
│       ├── add_watchlist.php
│       ├── remove_watchlist.php
│       ├── update_progress.php
│       ├── rate_content.php
│       └── rate_video.php
│
├── uploads/                   # User uploads (writable)
│   ├── subtitles/            # Subtitle files (.srt)
│   └── thumbnails/           # Custom thumbnails
│
├── vendor/                    # Composer dependencies
│
├── .htaccess                 # Apache configuration
├── .gitignore                # Git ignore rules
├── composer.json             # PHP dependencies
├── database.sql              # Database schema & seed data
├── index.php                 # Root redirect to public/
├── login.php                 # User login page
├── register.php              # User registration page
├── logout.php                # User logout handler
├── setup.php                 # Installation checker
├── site.webmanifest          # PWA manifest
├── CHANGELOG.md              # Version history
└── README.md                 # This file
```

### Key Files Explained

**Core Files**
- `config/database.php` - Database PDO connection and configuration
- `includes/functions.php` - Reusable PHP functions (session management, content queries, etc.)
- `.htaccess` - Apache rewrites, security headers, and access controls

**Public Pages**
- `public/index.php` - Browse movies and series with search/filter
- `public/watch.php` - Video player with subtitle support
- `public/series.php` - Series page with season/episode navigation

**Admin Panel**
- `admin/dashboard.php` - Manage movies, series, seasons, episodes, and users
- `admin/settings.php` - Configure site-wide settings

**User Area**
- `user/dashboard.php` - User watch history, stats, and watchlist
- `user/ajax/*.php` - AJAX endpoints for progress tracking, ratings, watchlist

---

## 🔐 Admin Panel

Access the admin panel at `/admin/login.php`

### Admin Roles
1. **Super Admin** - Full system access
2. **Admin** - Content and user management
3. **Moderator** - Limited content management

### Admin Dashboard Features
- **Content Statistics** - Total movies, series, users, views
- **Recent Activity** - Latest user logins and video plays
- **Quick Actions**:
  - Add movies and series
  - Manage genres
  - View user list
  - Configure settings

### Admin Logging
All admin actions are logged with:
- Admin ID and username
- Action performed
- Target type and ID
- IP address and user agent
- Timestamp

---

## 🔌 API Endpoints

### AJAX Endpoints

**Get Seasons** - `/ajax/get_seasons.php`
```javascript
GET /ajax/get_seasons.php?series_id=4
```

**Get Subtitles** - `/ajax/get_subtitles.php`
```javascript
GET /ajax/get_subtitles.php?video_id=28
```

**User Endpoints** (Requires Authentication)

**Add to Watchlist** - `/user/ajax/add_watchlist.php`
```javascript
POST /user/ajax/add_watchlist.php
Content-Type: application/json
{
  "video_id": 28  // or "series_id": 4
}
```

**Remove from Watchlist** - `/user/ajax/remove_watchlist.php`
```javascript
POST /user/ajax/remove_watchlist.php
Content-Type: application/json
{
  "watchlist_id": 12
}
```

**Update Progress** - `/user/ajax/update_progress.php`
```javascript
POST /user/ajax/update_progress.php
Content-Type: application/json
{
  "video_id": 28,
  "progress_seconds": 120,
  "total_duration": 500
}
```

**Rate Content** - `/user/ajax/rate_content.php`
```javascript
POST /user/ajax/rate_content.php
Content-Type: application/json
{
  "video_id": 28,  // or "series_id": 4
  "rating": 5,
  "review_text": "Great movie!"
}
```

---

## 🔒 Security Features

### Authentication & Sessions
- **Secure Password Hashing** - bcrypt with cost factor 10
- **Session Management** - Token-based sessions with expiration
- **Session Regeneration** - Prevents session fixation attacks
- **CSRF Protection** - Form tokens (implement as needed)

### Database Security
- **Prepared Statements** - All queries use PDO prepared statements
- **SQL Injection Protection** - Parameterized queries throughout
- **Input Validation** - Server-side validation and sanitization
- **Output Escaping** - `htmlspecialchars()` on all user-generated content

### File Security
- **Upload Validation** - File type and extension checks
- **PHP Execution Prevention** - .htaccess blocks PHP in uploads/
- **Sensitive File Protection** - .sql and config files blocked via .htaccess
- **Directory Listing** - Disabled via .htaccess

### Headers & Transport
- **HTTPS Redirect** - Automatic redirect to HTTPS (production)
- **Security Headers**:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `X-XSS-Protection: 1; mode=block`
  - `Content-Security-Policy` - Restricts resource loading
  - `Referrer-Policy: strict-origin-when-cross-origin`

### Access Control
- **Role-Based Access** - Admin roles (Super Admin, Admin, Moderator)
- **User Status** - Active/Inactive user management
- **Expiry Dates** - Automatic account expiration
- **Activity Logging** - Track user and admin actions

### Best Practices
- **Error Handling** - Production mode hides detailed errors
- **Debug Mode** - Disable in production environments
- **Regular Updates** - Keep PHP, MySQL, and dependencies updated
- **Strong Passwords** - Enforce password policies (recommended)
- **Backup Strategy** - Regular database and file backups

---

## 🗺️ Roadmap

### Planned Features (v1.1)
- [ ] User profile avatars
- [ ] Email verification system
- [ ] Password reset functionality
- [ ] Advanced search filters (year, duration, rating)
- [ ] Video quality selection
- [ ] Playlist creation
- [ ] Social sharing features
- [ ] Comments and discussions

### Planned Features (v1.2)
- [ ] Multi-language support (i18n)
- [ ] Advanced analytics dashboard
- [ ] Content recommendations
- [ ] User notifications system
- [ ] Mobile app integration
- [ ] CDN integration for thumbnails
- [ ] Video upload support (beyond YouTube)
- [ ] Live streaming support

### Planned Features (v2.0)
- [ ] Multi-user profiles (family accounts)
- [ ] Parental controls and content rating
- [ ] Offline download support
- [ ] API for third-party integrations
- [ ] Payment gateway integration
- [ ] Subscription management
- [ ] Advanced content moderation
- [ ] AI-powered recommendations

### Technical Improvements
- [ ] Implement REST API
- [ ] Add unit tests (PHPUnit)
- [ ] Implement caching (Redis/Memcached)
- [ ] Database query optimization
- [ ] Frontend framework integration (Vue.js/React)
- [ ] Containerization (Docker)
- [ ] CI/CD pipeline
- [ ] Performance monitoring

---

## 📜 Changelog

### Version 1.0 - October 2025

#### Major Changes
- **Rebranded** from MovieStream v0.2.0 to **CiolStream v1**
- **New Tagline**: "Your Movie World"
- **New Visual Identity**: Custom favicon with CS logo

#### Features & Improvements
- ✅ Complete brand refresh across all pages
- ✅ SVG favicon for crisp display on all devices
- ✅ Web manifest for PWA support
- ✅ Enhanced user experience with consistent branding

#### Technical Updates
- Updated all PHP files with new branding
- Updated configuration files (.htaccess, database.sql)
- Updated documentation (README.md)
- Maintained backward compatibility with existing database

#### Files Modified
- All public pages (index, watch, series)
- All user pages (dashboard, settings, login, register)
- All admin pages (login, dashboard, settings)
- Configuration and documentation files

### Version 0.2.0 - Previous Release

#### Bug Fixes
- Fixed session fixation vulnerability in login pages
- Fixed view count inflation in progress tracking
- Fixed watchlist duplicate entry prevention
- Fixed series watchlist display issues

#### Security Enhancements
- Added `session_regenerate_id()` on authentication
- Improved view count tracking logic
- Enhanced watchlist duplicate prevention

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Code Style
- Follow PSR-12 coding standards
- Comment complex logic
- Use meaningful variable names
- Keep functions focused and small

### Testing
- Test all changes locally
- Verify database migrations
- Check security implications
- Test on multiple browsers

---

## 📄 License

This project is licensed under the MIT License - see below for details:

```
MIT License

Copyright (c) 2025 CiolStream

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 📞 Support

For issues, questions, or contributions:

- **Issues**: [GitHub Issues](https://github.com/yourusername/ciolstream/issues)
- **Documentation**: See this README and inline code comments
- **Email**: support@ciolstream.local (if configured)

---

## 🙏 Acknowledgments

- YouTube API for video integration
- PHP community for excellent documentation
- Open source contributors
- All users and testers

---

## ⚠️ Important Notes

### Security Reminders
1. **Change default admin password immediately after installation**
2. Set `DEBUG_MODE` to `false` in production (`config/database.php`)
3. Update database credentials in `config/database.php`
4. Ensure HTTPS is enabled in production
5. Keep PHP and MySQL updated
6. Regular backups of database and uploads directory
7. Monitor `uploads/` directory size and content

### Performance Tips
- Enable PHP OPcache for better performance
- Implement database indexing for large datasets
- Use CDN for static assets in production
- Enable Gzip compression in Apache
- Optimize images and thumbnails

### Browser Compatibility
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

---

**CiolStream v1.0** - *Your Movie World* 🎬

Built with ❤️ using PHP, MySQL, and modern web technologies.
