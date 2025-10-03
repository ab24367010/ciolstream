-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: movie_streaming
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` enum('user','video','series','season','subtitle','system') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_logs_admin` (`admin_id`),
  KEY `idx_admin_logs_created` (`created_at`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','moderator') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','admin@moviestream.local','$2y$10$q.2aYNNtd3Z8L4gicejLwu5NO.zMnqYp4cED9lCPDlm1BNFZz3XXq','super_admin',NULL,'2025-09-25 14:24:10');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content_types`
--

DROP TABLE IF EXISTS `content_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` enum('movie','series') COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_types`
--

LOCK TABLES `content_types` WRITE;
/*!40000 ALTER TABLE `content_types` DISABLE KEYS */;
INSERT INTO `content_types` VALUES (1,'movie','Movie'),(2,'series','TV Series');
/*!40000 ALTER TABLE `content_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color_code` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#667eea',
  `is_active` tinyint(1) DEFAULT '1',
  `display_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genres`
--

LOCK TABLES `genres` WRITE;
/*!40000 ALTER TABLE `genres` DISABLE KEYS */;
INSERT INTO `genres` VALUES (1,'Action','High-energy movies with thrilling sequences','#ff6b6b',1,1,'2025-09-25 14:24:10'),(2,'Comedy','Funny and entertaining movies','#feca57',1,2,'2025-09-25 14:24:10'),(3,'Drama','Serious and emotional storylines','#48dbfb',1,3,'2025-09-25 14:24:10'),(4,'Horror','Scary and suspenseful movies','#ff9ff3',1,4,'2025-09-25 14:24:10'),(5,'Romance','Love stories and romantic comedies','#ff6b9d',1,5,'2025-09-25 14:24:10'),(6,'Sci-Fi','Science fiction and futuristic themes','#54a0ff',1,6,'2025-09-25 14:24:10'),(7,'Thriller','Suspenseful and tension-filled movies','#5f27cd',1,7,'2025-09-25 14:24:10'),(8,'Documentary','Educational and informative content','#00d2d3',1,8,'2025-09-25 14:24:10'),(9,'Animation','3D and 2D animated movies','#ff9f43',1,9,'2025-09-25 14:24:10'),(10,'Fantasy','Magical and mythical adventures','#9c88ff',1,10,'2025-09-25 14:24:10'),(11,'Adventure','Exciting journeys and explorations','#10ac84',1,11,'2025-09-25 14:24:10'),(12,'Mystery','Puzzling and investigative stories','#2f3640',1,12,'2025-09-25 14:24:10');
/*!40000 ALTER TABLE `genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `video_id` int DEFAULT NULL,
  `series_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `review` text COLLATE utf8mb4_unicode_ci,
  `helpful_votes` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ratings_video` (`video_id`),
  KEY `idx_ratings_series` (`series_id`),
  KEY `idx_ratings_user` (`user_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
INSERT INTO `ratings` VALUES (12,5,28,NULL,5,'',0,'2025-10-02 01:56:32','2025-10-02 01:56:32'),(13,5,28,NULL,4,'',0,'2025-10-02 02:02:11','2025-10-02 02:02:11'),(14,5,28,NULL,3,'',0,'2025-10-02 02:02:12','2025-10-02 02:02:12'),(15,5,NULL,4,5,'',0,'2025-10-02 04:36:01','2025-10-02 04:36:01');
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seasons`
--

DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seasons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `series_id` int NOT NULL,
  `season_number` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_year` year DEFAULT NULL,
  `episode_count` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_series_season` (`series_id`,`season_number`),
  CONSTRAINT `seasons_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seasons`
--

LOCK TABLES `seasons` WRITE;
/*!40000 ALTER TABLE `seasons` DISABLE KEYS */;
INSERT INTO `seasons` VALUES (5,4,1,'All My Love Is With You','A compelling and thought-provoking video exploring the human experience, resilience, and emotion. Through powerful visuals and storytelling, this piece invites viewers to reflect on life’s deeply personal moments — its challenges, triumphs, and connections. \r\nDive into a journey that speaks to the heart, reminding us that every story matters and every voice deserves to be heard.','https://img.youtube.com/vi/SUC1aQdIyOE/maxresdefault.jpg',2024,5,'active','2025-10-02 03:19:25'),(6,4,2,'Embraced by Growth','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://img.youtube.com/vi/YYcrRBjmBBE/maxresdefault.jpg',2024,1,'active','2025-10-02 03:44:03');
/*!40000 ALTER TABLE `seasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `series`
--

DROP TABLE IF EXISTS `series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `series` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_year` year DEFAULT NULL,
  `imdb_rating` decimal(3,1) DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `director` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cast` text COLLATE utf8mb4_unicode_ci,
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int DEFAULT '0',
  `featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','coming_soon') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `series`
--

LOCK TABLES `series` WRITE;
/*!40000 ALTER TABLE `series` DISABLE KEYS */;
INSERT INTO `series` VALUES (4,'Jiang Shiqi','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/7iBYMD3Q3aA/maxresdefault.jpg',2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','Jiang Shiqi, chinese dram,',0,0,'active','2025-10-02 03:08:47','2025-10-02 03:30:08');
/*!40000 ALTER TABLE `series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `series_genres`
--

DROP TABLE IF EXISTS `series_genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `series_genres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `series_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_series_genre` (`series_id`,`genre_id`),
  KEY `genre_id` (`genre_id`),
  CONSTRAINT `series_genres_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `series_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `series_genres`
--

LOCK TABLES `series_genres` WRITE;
/*!40000 ALTER TABLE `series_genres` DISABLE KEYS */;
INSERT INTO `series_genres` VALUES (7,4,1),(9,4,2),(10,4,3),(11,4,5),(8,4,11);
/*!40000 ALTER TABLE `series_genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','integer','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','CiolStream','string','Main site name displayed in header',1,'2025-09-30 05:13:50'),(2,'site_tagline','Your Premium Movie & Series Streaming Experience','string','Site tagline or slogan',1,'2025-09-25 14:24:10'),(3,'maintenance_mode','0','boolean','Enable maintenance mode',0,'2025-09-25 14:24:10'),(4,'max_file_upload_size','52428800','integer','Maximum file upload size in bytes (50MB)',0,'2025-09-25 14:24:10'),(5,'default_user_expiry_days','30','integer','Default user membership expiry in days',0,'2025-09-25 14:24:10'),(6,'enable_user_registration','1','boolean','Allow new user registrations',1,'2025-09-25 14:24:10'),(7,'require_email_verification','0','boolean','Require email verification for new accounts',0,'2025-09-25 14:24:10'),(8,'default_video_quality','720p','string','Default video quality setting',1,'2025-09-25 14:24:10'),(9,'subtitle_sync_tolerance','100','integer','Subtitle synchronization tolerance in milliseconds',0,'2025-09-25 14:24:10'),(10,'session_lifetime','86400','integer','User session lifetime in seconds (24 hours)',0,'2025-09-25 14:24:10');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subtitles`
--

DROP TABLE IF EXISTS `subtitles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subtitles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `video_id` int NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `language_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `srt_file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int DEFAULT '0',
  `subtitle_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_video_language` (`video_id`,`language`),
  CONSTRAINT `subtitles_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subtitles`
--

LOCK TABLES `subtitles` WRITE;
/*!40000 ALTER TABLE `subtitles` DISABLE KEYS */;
INSERT INTO `subtitles` VALUES (24,28,'MN','English','/var/www/html/moviestream/uploads/subtitles/28_MN.srt',0,0,'2025-10-02 02:32:48'),(25,29,'MN','English','/var/www/html/moviestream/uploads/subtitles/29_MN.srt',0,0,'2025-10-02 02:41:12');
/*!40000 ALTER TABLE `subtitles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity_logs`
--

DROP TABLE IF EXISTS `user_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `activity_type` enum('login','logout','video_play','video_pause','video_complete','rating','watchlist_add','profile_update') COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_id` int DEFAULT NULL,
  `series_id` int DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `video_id` (`video_id`),
  KEY `series_id` (`series_id`),
  KEY `idx_user_activity_user` (`user_id`),
  KEY `idx_user_activity_type` (`activity_type`),
  CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_activity_logs_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_activity_logs_ibfk_3` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=406 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity_logs`
--

LOCK TABLES `user_activity_logs` WRITE;
/*!40000 ALTER TABLE `user_activity_logs` DISABLE KEYS */;
INSERT INTO `user_activity_logs` VALUES (205,5,'login',NULL,NULL,'{\"ip_address\": \"::1\", \"user_agent\": \"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}','::1','2025-10-02 01:42:24'),(206,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 8}','::1','2025-10-02 01:55:57'),(207,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 18}','::1','2025-10-02 01:56:07'),(208,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 36}','::1','2025-10-02 01:56:17'),(209,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 46}','::1','2025-10-02 01:56:27'),(210,5,'rating',28,NULL,'{\"rating\": 5, \"has_review\": false, \"content_type\": \"video\"}','::1','2025-10-02 01:56:32'),(211,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 56}','::1','2025-10-02 01:56:37'),(212,5,'rating',28,NULL,'{\"rating\": 4, \"has_review\": false, \"content_type\": \"video\"}','::1','2025-10-02 02:02:11'),(213,5,'rating',28,NULL,'{\"rating\": 3, \"has_review\": false, \"content_type\": \"video\"}','::1','2025-10-02 02:02:12'),(214,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 18}','::1','2025-10-02 02:04:56'),(215,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 28}','::1','2025-10-02 02:05:06'),(216,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 33}','::1','2025-10-02 02:05:16'),(217,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 43}','::1','2025-10-02 02:05:26'),(218,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:06:56'),(219,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:07:06'),(220,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:07:16'),(221,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:07:26'),(222,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:07:36'),(223,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:07:46'),(224,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:07:56'),(225,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:08:44'),(226,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:09:44'),(227,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 42}','::1','2025-10-02 02:10:36'),(228,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 108}','::1','2025-10-02 02:10:50'),(229,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:11:00'),(230,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:11:10'),(231,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:11:20'),(232,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:11:31'),(233,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:11:41'),(234,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:11:51'),(235,5,'video_play',28,NULL,'{\"duration\": 545, \"progress\": 109}','::1','2025-10-02 02:12:01'),(236,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:12:22'),(237,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:12:32'),(238,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:12:42'),(239,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:12:52'),(240,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:13:02'),(241,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:13:12'),(242,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:13:22'),(243,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:13:32'),(244,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:13:44'),(245,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:14:44'),(246,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:15:44'),(247,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:16:44'),(248,5,'login',NULL,NULL,'{\"ip_address\": \"192.168.0.123\", \"user_agent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1\"}','192.168.0.123','2025-10-02 02:17:31'),(249,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:17:44'),(250,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:18:44'),(251,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:19:44'),(252,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 39}','::1','2025-10-02 02:20:12'),(253,5,'login',NULL,NULL,'{\"ip_address\": \"192.168.0.128\", \"user_agent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1\"}','192.168.0.128','2025-10-02 02:23:12'),(254,5,'login',NULL,NULL,'{\"ip_address\": \"::1\", \"user_agent\": \"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}','::1','2025-10-02 02:23:48'),(255,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 2}','::1','2025-10-02 02:24:26'),(256,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 20}','::1','2025-10-02 02:24:36'),(257,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:24:46'),(258,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:24:56'),(259,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:25:06'),(260,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:25:17'),(261,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:25:27'),(262,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:25:37'),(263,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:25:47'),(264,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:25:57'),(265,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:26:07'),(266,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:26:44'),(267,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:27:44'),(268,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:28:44'),(269,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:29:44'),(270,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:30:44'),(271,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:31:44'),(272,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:32:44'),(273,5,'video_play',29,NULL,'{\"duration\": 499, \"progress\": 21}','::1','2025-10-02 02:33:18'),(274,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 17}','::1','2025-10-02 02:33:35'),(275,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 27}','::1','2025-10-02 02:33:45'),(276,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 37}','::1','2025-10-02 02:33:55'),(277,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 47}','::1','2025-10-02 02:34:05'),(278,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 57}','::1','2025-10-02 02:34:15'),(279,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 67}','::1','2025-10-02 02:34:25'),(280,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 77}','::1','2025-10-02 02:34:35'),(281,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 335}','::1','2025-10-02 02:34:45'),(282,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 375}','::1','2025-10-02 02:34:55'),(283,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 397}','::1','2025-10-02 02:35:05'),(284,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 407}','::1','2025-10-02 02:35:15'),(285,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 417}','::1','2025-10-02 02:35:25'),(286,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 427}','::1','2025-10-02 02:35:35'),(287,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 437}','::1','2025-10-02 02:35:45'),(288,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 447}','::1','2025-10-02 02:35:55'),(289,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 457}','::1','2025-10-02 02:36:05'),(290,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 467}','::1','2025-10-02 02:36:15'),(291,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 477}','::1','2025-10-02 02:36:25'),(292,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 487}','::1','2025-10-02 02:36:35'),(293,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 291}','::1','2025-10-02 02:36:45'),(294,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 301}','::1','2025-10-02 02:36:55'),(295,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 313}','::1','2025-10-02 02:37:05'),(296,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 255}','::1','2025-10-02 02:37:15'),(297,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:37:25'),(298,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:37:35'),(299,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:37:45'),(300,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:37:56'),(301,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:38:06'),(302,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:38:16'),(303,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:38:26'),(304,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:38:44'),(305,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:39:44'),(306,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:40:44'),(307,5,'video_play',28,NULL,'{\"duration\": 544, \"progress\": 249}','::1','2025-10-02 02:41:27'),(308,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 14}','::1','2025-10-02 02:41:41'),(309,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 24}','::1','2025-10-02 02:41:51'),(310,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 34}','::1','2025-10-02 02:42:01'),(311,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 44}','::1','2025-10-02 02:42:11'),(312,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 54}','::1','2025-10-02 02:42:21'),(313,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 64}','::1','2025-10-02 02:42:31'),(314,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 74}','::1','2025-10-02 02:42:41'),(315,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 84}','::1','2025-10-02 02:42:51'),(316,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 94}','::1','2025-10-02 02:43:01'),(317,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 104}','::1','2025-10-02 02:43:11'),(318,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 114}','::1','2025-10-02 02:43:21'),(319,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 124}','::1','2025-10-02 02:43:31'),(320,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 134}','::1','2025-10-02 02:43:41'),(321,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 144}','::1','2025-10-02 02:43:51'),(322,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 154}','::1','2025-10-02 02:44:01'),(323,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 164}','::1','2025-10-02 02:44:11'),(324,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 174}','::1','2025-10-02 02:44:21'),(325,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 184}','::1','2025-10-02 02:44:31'),(326,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 194}','::1','2025-10-02 02:44:41'),(327,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 204}','::1','2025-10-02 02:44:51'),(328,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 214}','::1','2025-10-02 02:45:01'),(329,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 224}','::1','2025-10-02 02:45:11'),(330,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 234}','::1','2025-10-02 02:45:21'),(331,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 244}','::1','2025-10-02 02:45:31'),(332,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 254}','::1','2025-10-02 02:45:41'),(333,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 264}','::1','2025-10-02 02:45:51'),(334,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 274}','::1','2025-10-02 02:46:01'),(335,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 284}','::1','2025-10-02 02:46:11'),(336,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 294}','::1','2025-10-02 02:46:21'),(337,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 304}','::1','2025-10-02 02:46:31'),(338,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 314}','::1','2025-10-02 02:46:41'),(339,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 324}','::1','2025-10-02 02:46:51'),(340,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 334}','::1','2025-10-02 02:47:01'),(341,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 344}','::1','2025-10-02 02:47:11'),(342,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 354}','::1','2025-10-02 02:47:21'),(343,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 364}','::1','2025-10-02 02:47:31'),(344,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 374}','::1','2025-10-02 02:47:41'),(345,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 384}','::1','2025-10-02 02:47:51'),(346,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 394}','::1','2025-10-02 02:48:01'),(347,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 404}','::1','2025-10-02 02:48:11'),(348,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 414}','::1','2025-10-02 02:48:21'),(349,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 424}','::1','2025-10-02 02:48:31'),(350,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 434}','::1','2025-10-02 02:48:41'),(351,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 444}','::1','2025-10-02 02:48:51'),(352,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 454}','::1','2025-10-02 02:49:01'),(353,5,'video_play',29,NULL,'{\"duration\": 500, \"progress\": 463}','::1','2025-10-02 02:49:11'),(354,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 58}','::1','2025-10-02 03:33:21'),(355,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:33:41'),(356,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:33:51'),(357,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:34:01'),(358,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:34:12'),(359,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:34:22'),(360,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:34:32'),(361,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:34:42'),(362,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:34:52'),(363,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:35:02'),(364,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:35:12'),(365,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:35:22'),(366,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:35:32'),(367,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:35:42'),(368,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:35:52'),(369,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:36:02'),(370,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:36:12'),(371,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:36:22'),(372,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:36:44'),(373,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:37:09'),(374,5,'video_play',32,NULL,'{\"duration\": 232, \"progress\": 4}','::1','2025-10-02 03:37:11'),(375,5,'watchlist_add',NULL,NULL,'{\"priority\": 1}','::1','2025-10-02 03:37:29'),(376,5,'watchlist_add',NULL,NULL,'{\"priority\": 1}','::1','2025-10-02 03:38:18'),(377,5,'login',NULL,NULL,'{\"ip_address\": \"::1\", \"user_agent\": \"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}','::1','2025-10-02 04:27:23'),(378,5,'watchlist_add',29,NULL,'{\"priority\": 1}','::1','2025-10-02 04:27:55'),(379,5,'watchlist_add',NULL,NULL,'{\"priority\": 1}','::1','2025-10-02 04:35:49'),(380,5,'rating',NULL,NULL,'{\"rating\": 5, \"has_review\": false, \"content_type\": \"series\"}','::1','2025-10-02 04:36:01'),(381,5,'watchlist_add',32,NULL,'{\"priority\": 1}','::1','2025-10-02 04:36:13'),(382,5,'watchlist_add',29,NULL,'{\"priority\": 1}','::1','2025-10-02 04:42:14'),(383,5,'watchlist_add',29,NULL,'{\"priority\": 1}','::1','2025-10-02 04:42:25'),(384,5,'watchlist_add',29,NULL,'{\"priority\": 1}','::1','2025-10-02 04:43:58'),(385,5,'video_play',38,NULL,'{\"duration\": 223, \"progress\": 7}','::1','2025-10-02 04:55:31'),(386,5,'video_play',38,NULL,'{\"duration\": 223, \"progress\": 17}','::1','2025-10-02 04:55:41'),(387,5,'video_play',34,NULL,'{\"duration\": 293, \"progress\": 2}','::1','2025-10-02 04:55:59'),(388,5,'watchlist_add',29,NULL,'{\"priority\": 1}','::1','2025-10-02 05:08:08'),(389,5,'login',NULL,NULL,'{\"ip_address\": \"192.168.0.128\", \"user_agent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1\"}','192.168.0.128','2025-10-02 05:14:42'),(390,5,'video_play',31,NULL,'{\"duration\": 263, \"progress\": 6}','192.168.0.128','2025-10-02 05:15:42'),(391,5,'video_play',31,NULL,'{\"duration\": 263, \"progress\": 13}','192.168.0.128','2025-10-02 05:15:52'),(392,5,'video_play',31,NULL,'{\"duration\": 263, \"progress\": 23}','192.168.0.128','2025-10-02 05:16:02'),(393,5,'login',NULL,NULL,'{\"ip_address\": \"192.168.0.128\", \"user_agent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1\"}','192.168.0.128','2025-10-02 05:50:20'),(394,5,'login',NULL,NULL,'{\"ip_address\": \"192.168.0.128\", \"user_agent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1\"}','192.168.0.128','2025-10-02 06:04:00'),(395,5,'login',NULL,NULL,'{\"ip_address\": \"::1\", \"user_agent\": \"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36\"}','::1','2025-10-02 06:20:12'),(396,5,'login',NULL,NULL,'{\"ip_address\": \"192.168.0.128\", \"user_agent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1\"}','192.168.0.128','2025-10-02 07:20:51'),(397,5,'login',NULL,NULL,'{\"ip_address\": \"::1\", \"user_agent\": \"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36\"}','::1','2025-10-02 08:33:34'),(398,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:33:51'),(399,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:34:01'),(400,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:34:11'),(401,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:34:21'),(402,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:34:31'),(403,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:34:41'),(404,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:34:53'),(405,5,'video_play',31,NULL,'{\"duration\": 264, \"progress\": 5}','::1','2025-10-02 08:35:04');
/*!40000 ALTER TABLE `user_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_progress`
--

DROP TABLE IF EXISTS `user_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `video_id` int NOT NULL,
  `progress_seconds` int DEFAULT '0',
  `total_duration` int DEFAULT '0',
  `completed` tinyint(1) DEFAULT '0',
  `watch_count` int DEFAULT '1',
  `first_watched` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_watched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_video` (`user_id`,`video_id`),
  KEY `idx_user_progress_user` (`user_id`),
  KEY `idx_user_progress_video` (`video_id`),
  KEY `idx_user_progress_last_watched` (`last_watched`),
  CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=354 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_progress`
--

LOCK TABLES `user_progress` WRITE;
/*!40000 ALTER TABLE `user_progress` DISABLE KEYS */;
INSERT INTO `user_progress` VALUES (177,5,28,249,544,0,57,'2025-10-02 01:55:57','2025-10-02 02:41:27'),(182,5,29,463,500,0,85,'2025-10-02 02:04:56','2025-10-02 02:49:11'),(319,5,31,5,264,0,12,'2025-10-02 03:33:21','2025-10-02 08:35:04'),(320,5,32,4,232,0,20,'2025-10-02 03:33:41','2025-10-02 03:37:11'),(340,5,38,17,223,0,2,'2025-10-02 04:55:31','2025-10-02 04:55:41'),(342,5,34,2,293,0,1,'2025-10-02 04:55:59','2025-10-02 04:55:59');
/*!40000 ALTER TABLE `user_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_info` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (23,5,'46c7ed2fee5c34339bc5c28d1c0e81002f62328199ef1c36af3a07e070fd255e','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-02 05:42:22','2025-10-03 04:27:23',1),(24,5,'64a4ef55b126f43fcedc7df596a434183ab34bb1c49075329501ed01e13adb4b','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','192.168.0.128','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','2025-10-02 05:15:29','2025-10-03 05:14:42',1),(25,5,'f4d4c2b4b44fcbbfd3de62c5193f14ed9e345fd26e41a807ad3c1b5eb99d8345','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','192.168.0.128','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','2025-10-02 05:50:41','2025-10-03 05:50:20',1),(26,5,'3e6b404203fce288cfc0e5ffd8ba54e51aff0bd8f1172b89eabc578c78fa2887','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','192.168.0.128','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','2025-10-02 06:04:00','2025-10-03 06:04:00',1),(27,5,'afacdfe919d78301e777543b831d9853bbde5e12a81af87c74e313df6779ab2c','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-10-02 07:14:12','2025-10-03 06:20:12',1),(28,5,'7d71e5000701bb884f84d884e14dbae5e66c2a84cdc9fc3a4a5233299664ed7b','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','192.168.0.128','Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1','2025-10-02 07:20:51','2025-10-03 07:20:51',1),(29,5,'f35ae558776ff54098c688a9fcaf2cef1c93dfe215dabdd8641ace03b8488237','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-02 08:34:32','2025-10-03 08:33:34',1);
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `user_stats`
--

DROP TABLE IF EXISTS `user_stats`;
/*!50001 DROP VIEW IF EXISTS `user_stats`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `user_stats` AS SELECT 
 1 AS `id`,
 1 AS `username`,
 1 AS `status`,
 1 AS `expiry_date`,
 1 AS `videos_watched`,
 1 AS `watchlist_count`,
 1 AS `avg_rating_given`,
 1 AS `reviews_written`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('inactive','active') COLLATE utf8mb4_unicode_ci DEFAULT 'inactive',
  `expiry_date` date DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `email_verified` tinyint(1) DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_expiry` (`expiry_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'ciolft','altan112@yahoo.com','$2y$10$UTsLp83PbduMgD/gXhLVvuEBVKSQrx21GWO2aj4M8bbaQpXmiq72i','active','2026-10-02',NULL,NULL,NULL,'en',0,'2025-10-02 08:33:34','2025-10-02 01:42:11','2025-10-02 08:33:34');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_genres`
--

DROP TABLE IF EXISTS `video_genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_genres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `video_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_video_genre` (`video_id`,`genre_id`),
  KEY `genre_id` (`genre_id`),
  CONSTRAINT `video_genres_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_genres`
--

LOCK TABLES `video_genres` WRITE;
/*!40000 ALTER TABLE `video_genres` DISABLE KEYS */;
INSERT INTO `video_genres` VALUES (23,28,4),(24,29,4),(27,31,3),(28,31,5),(29,32,3),(30,32,5),(31,33,3),(32,33,5),(33,34,3),(34,34,5),(35,35,3),(36,35,5),(37,38,3),(38,38,5);
/*!40000 ALTER TABLE `video_genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `video_stats`
--

DROP TABLE IF EXISTS `video_stats`;
/*!50001 DROP VIEW IF EXISTS `video_stats`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `video_stats` AS SELECT 
 1 AS `id`,
 1 AS `title`,
 1 AS `content_type`,
 1 AS `series_id`,
 1 AS `season_id`,
 1 AS `episode_number`,
 1 AS `genre`,
 1 AS `view_count`,
 1 AS `avg_rating`,
 1 AS `rating_count`,
 1 AS `unique_viewers`,
 1 AS `watchlist_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` enum('movie','episode') COLLATE utf8mb4_unicode_ci DEFAULT 'movie',
  `series_id` int DEFAULT NULL,
  `season_id` int DEFAULT NULL,
  `episode_number` int DEFAULT NULL,
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `youtube_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_seconds` int DEFAULT '0',
  `release_year` year DEFAULT NULL,
  `imdb_rating` decimal(3,1) DEFAULT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `director` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cast` text COLLATE utf8mb4_unicode_ci,
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int DEFAULT '0',
  `featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','coming_soon') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_episode` (`series_id`,`season_id`,`episode_number`),
  KEY `idx_videos_content_type` (`content_type`),
  KEY `idx_videos_series` (`series_id`),
  KEY `idx_videos_season` (`season_id`),
  KEY `idx_videos_genre` (`genre`),
  KEY `idx_videos_status` (`status`),
  KEY `idx_videos_featured` (`featured`),
  CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `videos_ibfk_2` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `videos`
--

LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
INSERT INTO `videos` VALUES (28,'CATCH YOUR BREATH - Award Winning Short Horror Film','movie',NULL,NULL,NULL,'Horror','hBj4rcs0AiQ','A shy teenager (Andrew) is peer pressured by his older brother (Mike) into playing a game that is said to summon the ghost of the town legend, \'The Lady Beneath\'. To disprove his brother and impress a girl at school he reluctantly agrees. The rules of the game are simple, as long as Andrew holds his breath, he is safe from The Lady Beneath. But how long can Andrew hold his breath?','https://i.ytimg.com/vi/hBj4rcs0AiQ/maxresdefault.jpg',544,2022,NULL,'English','Matt Sears','Samson Oliver, Toby Oliver, Renee Sears','catch your breath, Award Winning, Short Horror Film, Horror, 2022, Matt Sears,',57,0,'active','2025-10-02 01:50:11','2025-10-02 02:41:27'),(29,'DON\'T LOOK AWAY | Horror Short Film','movie',NULL,NULL,NULL,'Horror','4f3hG-5grlw','When a teenage girl tells her father about a mysterious figure staring at her, all her father can say is: \"DON\'T LOOK AWAY\".','https://i.ytimg.com/vi/4f3hG-5grlw/maxresdefault.jpg',499,2017,NULL,'English','Christopher Cox','Sabrina Twyla, Danny Roy, Jim Marshall','DON\'T LOOK AWAY, horror,  Horror Short Film, Christopher Cox',85,0,'active','2025-10-02 02:03:51','2025-10-02 02:49:11'),(31,'It\'s All Love & You Jiang 17 discovers rich heir as a kid by trash. EP1','episode',4,5,1,'Drama','7iBYMD3Q3aA','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/7iBYMD3Q3aA/maxresdefault.jpg',231,2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi), Chinese Drama, drama, jian shi chi,',1,0,'active','2025-10-02 03:27:03','2025-10-02 03:33:21'),(32,'Jiang 17 nanny for rich heir & prez uncle\'s fav love & career win! EP2','episode',4,5,2,'Drama','csX6uQGIRb8','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/csX6uQGIRb8/maxresdefault.jpg',231,2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','all my love, 姜十七 (Jiang Shiqi), drama,',20,0,'active','2025-10-02 03:33:08','2025-10-02 03:37:11'),(33,'Jiang 17 aids blind lady prez uncle\'s grandma! EP3','episode',4,5,3,'Drama','wx0GJjekr7c','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/wx0GJjekr7c/maxresdefault.jpg',244,2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi), Chinese Drama, drama, jian shi chi,',0,0,'active','2025-10-02 03:37:07','2025-10-02 03:37:07'),(34,'Jiang 17 breaks uncle\'s watch flees faces him next day! EP1','episode',4,6,1,'Drama','YYcrRBjmBBE','\"Jiang Shiqi: Embrace by Growth\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/YYcrRBjmBBE/maxresdefault.jpg',293,2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','Jiang Shiqi, chinese drama, 姜十七 (Jiang Shiqi), embrace my growth',1,0,'active','2025-10-02 03:47:38','2025-10-02 04:55:59'),(35,'It\'s All Love & You Jiang 17 shamed uncle saves & hits bully at reunion. EP4','episode',4,5,4,'Drama','VPyiahoqSFM','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/VPyiahoqSFM/maxresdefault.jpg',245,2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi), Drama, Chinese drama, 姜十七, Kans,',0,0,'active','2025-10-02 04:26:54','2025-10-02 04:26:54'),(38,'Jiang 17 returns rich 2nd gen shelters uncle claims \'wife!\' EP5','episode',4,5,5,'Drama','qKlLQwT09RY','\"Jiang Shiqi: All Is Love With You\" is a heartwarming drama that follows the journey of Jiang Shiqi as she navigates life, relationships, and self-discovery. Blending romance, friendship, and personal growth, the series highlights the beauty of love in all its forms.','https://i.ytimg.com/vi/qKlLQwT09RY/maxresdefault.jpg',223,2024,NULL,'Chinese','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi)','姜十七 (Jiang Shiqi), Drama, Chinese drama, 姜十七, Kans,',1,0,'active','2025-10-02 04:46:35','2025-10-02 04:55:31');
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `watchlist`
--

DROP TABLE IF EXISTS `watchlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `watchlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `video_id` int DEFAULT NULL,
  `series_id` int DEFAULT NULL,
  `priority` int DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `video_id` (`video_id`),
  KEY `series_id` (`series_id`),
  KEY `idx_watchlist_user` (`user_id`),
  CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `watchlist_ibfk_3` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `watchlist_chk_1` CHECK ((`priority` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `watchlist`
--

LOCK TABLES `watchlist` WRITE;
/*!40000 ALTER TABLE `watchlist` DISABLE KEYS */;
INSERT INTO `watchlist` VALUES (21,5,29,NULL,1,'','2025-10-02 05:08:08');
/*!40000 ALTER TABLE `watchlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `user_stats`
--

/*!50001 DROP VIEW IF EXISTS `user_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `user_stats` AS select `u`.`id` AS `id`,`u`.`username` AS `username`,`u`.`status` AS `status`,`u`.`expiry_date` AS `expiry_date`,count(distinct `p`.`video_id`) AS `videos_watched`,count(distinct (case when (`w`.`video_id` is not null) then `w`.`video_id` when (`w`.`series_id` is not null) then `w`.`series_id` end)) AS `watchlist_count`,coalesce(avg((case when (`r`.`video_id` is not null) then `r`.`rating` end)),0) AS `avg_rating_given`,count((case when ((`r`.`video_id` is not null) and (`r`.`review` is not null)) then `r`.`rating` end)) AS `reviews_written` from (((`users` `u` left join `user_progress` `p` on((`u`.`id` = `p`.`user_id`))) left join `watchlist` `w` on((`u`.`id` = `w`.`user_id`))) left join `ratings` `r` on((`u`.`id` = `r`.`user_id`))) group by `u`.`id`,`u`.`username`,`u`.`status`,`u`.`expiry_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `video_stats`
--

/*!50001 DROP VIEW IF EXISTS `video_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `video_stats` AS select `v`.`id` AS `id`,`v`.`title` AS `title`,`v`.`content_type` AS `content_type`,`v`.`series_id` AS `series_id`,`v`.`season_id` AS `season_id`,`v`.`episode_number` AS `episode_number`,`v`.`genre` AS `genre`,`v`.`view_count` AS `view_count`,coalesce(avg(`r`.`rating`),0) AS `avg_rating`,count(`r`.`rating`) AS `rating_count`,count(distinct `p`.`user_id`) AS `unique_viewers`,count(distinct `w`.`user_id`) AS `watchlist_count` from (((`videos` `v` left join `ratings` `r` on((`v`.`id` = `r`.`video_id`))) left join `user_progress` `p` on((`v`.`id` = `p`.`video_id`))) left join `watchlist` `w` on((`v`.`id` = `w`.`video_id`))) where (`v`.`status` = 'active') group by `v`.`id`,`v`.`title`,`v`.`content_type`,`v`.`series_id`,`v`.`season_id`,`v`.`episode_number`,`v`.`genre`,`v`.`view_count` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-03 12:40:30
