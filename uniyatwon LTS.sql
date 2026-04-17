-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 25, 2026 at 08:24 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uniyatwoon`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `Username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Parent_id` int DEFAULT NULL,
  `Created_at` datetime NOT NULL,
  `Updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `Username`, `Description`, `Parent_id`, `Created_at`, `Updated_at`) VALUES
(1, 12, 'Aung Si Phyo', 'Fightin!', NULL, '2026-01-14 11:27:27', '0000-00-00 00:00:00'),
(2, 12, 'Aung Si Phyo', 'Fightin!', NULL, '2026-01-14 11:28:38', '0000-00-00 00:00:00'),
(3, 16, 'Aung Si Phyo', 'Fighting!!!', NULL, '2026-01-14 11:36:11', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `id` int NOT NULL AUTO_INCREMENT,
  `follower_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `following_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_follow` (`follower_uuid`,`following_uuid`),
  KEY `idx_follower` (`follower_uuid`),
  KEY `idx_following` (`following_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_uuid`,`post_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_uuid`, `post_id`, `created_at`) VALUES
(1, 'af828da5-c414-47e9-aa02-d6ec12ebf853', 12, '2026-01-16 22:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_uuid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_id` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_uuid`, `from_uuid`, `post_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, '4db48c2f-ddb3-11f0-948a-b94321dcec09', 'af828da5-c414-47e9-aa02-d6ec12ebf853', 12, 'like', 'Your post was liked by Sitt Aung', 0, '2026-01-16 22:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `User_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `Updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` enum('normal','lost_found','announcement') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `Username`, `User_uuid`, `Description`, `Created_at`, `Updated_at`, `type`) VALUES
(12, 'Aung Si Phyo', '4db48c2f-ddb3-11f0-948a-b94321dcec09', 'TTU Project 2', '2025-12-25 20:42:23', '2025-12-25 20:42:23', 'normal'),
(16, 'Aung Si Phyo', '4db48c2f-ddb3-11f0-948a-b94321dcec09', 'Welcome to our uniyatwoon project,we been doing this project for about month.It will be published too soon.Stay Tuned!', '2026-01-09 18:44:00', '2026-01-14 18:07:18', 'announcement'),
(24, 'UniYatwoon_Admin', '41d5bf4b-3791-4c10-a492-d44dd35ee217', 'Do u Let Your Friends Know Uniyatwoon?', '2026-01-15 20:47:06', '2026-01-15 20:47:06', 'announcement'),
(26, 'Aung Si Phyo', '4db48c2f-ddb3-11f0-948a-b94321dcec09', 'Come and join the art club.Its so fun!!!', '2026-01-16 12:03:51', '2026-01-16 12:03:51', 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `posts_media`
--

DROP TABLE IF EXISTS `posts_media`;
CREATE TABLE IF NOT EXISTS `posts_media` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Post_id` int NOT NULL,
  `Media_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Media_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts_media`
--

INSERT INTO `posts_media` (`Id`, `Post_id`, `Media_url`, `Media_type`) VALUES
(10, 11, 'uploads/1766671607_draw sql 1.PNG', 'image'),
(11, 12, 'uploads/1766671943_1st video.mp4', 'video'),
(12, 13, 'uploads/1766840764_music 2.jpg', 'image'),
(13, 13, 'uploads/1766840764_music 1.jpg', 'image'),
(14, 14, 'uploads/1766840938_grad 3.jpg', 'image'),
(15, 14, 'uploads/1766840938_grad 2.jpg', 'image'),
(16, 14, 'uploads/1766840938_grad 1.jpg', 'image'),
(17, 15, 'uploads/1766841007_art 2.jpg', 'image'),
(18, 15, 'uploads/1766841007_art 1.jpg', 'image'),
(19, 16, 'uploads/1767960840_webDeveloper.jpg', 'image'),
(20, 17, 'uploads/1768058972_webDeveloper.jpg', 'image'),
(21, 18, 'uploads/1768310295_Capture.PNG', 'image');

-- --------------------------------------------------------

--
-- Table structure for table `report_posts`
--

DROP TABLE IF EXISTS `report_posts`;
CREATE TABLE IF NOT EXISTS `report_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Reporter_username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Reporter_user_uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Reported_post_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_posts`
--

INSERT INTO `report_posts` (`id`, `Reporter_username`, `Reporter_user_uuid`, `Reported_post_id`, `Reason`) VALUES
(18, 'Sitt Aung', 'af828da5-c414-47e9-aa02-d6ec12ebf853', '12', 'Spam'),
(14, 'Sitt Aung', 'af828da5-c414-47e9-aa02-d6ec12ebf853', '12', 'Spam'),
(15, 'Sitt Aung', 'af828da5-c414-47e9-aa02-d6ec12ebf853', '12', 'Spam'),
(16, 'Sitt Aung', 'af828da5-c414-47e9-aa02-d6ec12ebf853', '12', 'nudity'),
(17, 'Sitt Aung', 'af828da5-c414-47e9-aa02-d6ec12ebf853', '12', 'Spam');

-- --------------------------------------------------------

--
-- Table structure for table `savedposts`
--

DROP TABLE IF EXISTS `savedposts`;
CREATE TABLE IF NOT EXISTS `savedposts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_saved` (`user_uuid`,`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

DROP TABLE IF EXISTS `search_history`;
CREATE TABLE IF NOT EXISTS `search_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `search_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_type` enum('query','user','post') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'query',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_uuid` (`user_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Student_nrc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Major` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Year` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Can_login` tinyint(1) NOT NULL DEFAULT '1',
  `Ban_until` datetime DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `role` enum('student','teacher') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_uuid` (`user_uuid`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `Username`, `Password`, `Student_nrc`, `Major`, `Year`, `Phone`, `Email`, `Profile_photo`, `user_uuid`, `Can_login`, `Ban_until`, `is_admin`, `role`) VALUES
(1, 'April Daisy', '1234', '', 'Information Technology', 'Fourth', '9', 'naylinaung@gmail.com', 'uploads/viet pf.jpg', '4db44fb5-ddb3-11f0-948a-b94321dcec08', 1, NULL, 0, 'student'),
(2, 'Aung Si Phyo', '$2y$10$ajxC7.LZ1c5DJ', '', 'IT', 'Fourth', '971772158', 'aungsiphyo@gmail.com', 'uploads/StudentID.png', '4db48c2f-ddb3-11f0-948a-b94321dcec09', 1, NULL, 0, 'student'),
(4, 'Nay Lin Aung', '5555', '', 'Information Technology', 'Fourth', '79989889', 'naylinaung@gmail.com', 'uploads/cool dog.jpg', '4ae44fb5-bbj9-11f0-948a-b94321dcec19', 0, NULL, 0, 'student'),
(7, 'Sitt Aung', '$2y$10$hh.KXcClnVzKl', '', 'Computer Science', '', '2147483647', 'sitt@gmail.com', 'default.png', 'af828da5-c414-47e9-aa02-d6ec12ebf853', 1, NULL, 0, 'student'),
(8, 'UniYatwoon_Admin', '$2y$10$vFWV8BMvPsExO', '', 'Information Technology', '', '2147483647', 'uniyatwoon@gmail.com', '', '41d5bf4b-3791-4c10-a492-d44dd35ee217', 1, NULL, 1, 'student');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
