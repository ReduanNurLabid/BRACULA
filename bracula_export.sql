-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 03:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bracula_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodations`
--

CREATE TABLE `accommodations` (
  `accommodation_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `contact_info` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accommodations`
--

INSERT INTO `accommodations` (`accommodation_id`, `owner_id`, `title`, `room_type`, `price`, `location`, `description`, `contact_info`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 'Cozy single room', 'single', 10000.00, 'Merul Badda', 'Everything available', '01732037023', 'active', '2025-05-15 23:17:49', '2025-05-15 23:17:49'),
(2, 6, 'Single Room In Badda', 'single', 8000.00, 'Badda', 'Others facilities are:\r\n-Attached Washroom\r\n-Fridge\r\n- Filter\r\n-Wifi\r\n-Maid\r\nThe flat is well organised and environment  is Clean so you can live here without much hastle.', '01732071007', 'active', '2025-05-17 13:09:31', '2025-05-17 13:09:31');

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_favorites`
--

CREATE TABLE `accommodation_favorites` (
  `id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_images`
--

CREATE TABLE `accommodation_images` (
  `image_id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accommodation_images`
--

INSERT INTO `accommodation_images` (`image_id`, `accommodation_id`, `image_url`, `created_at`) VALUES
(1, 1, 'uploads/accommodations/6826761d242a4.jpg', '2025-05-15 23:17:49'),
(2, 2, '/uploads/accommodations/68288a8b55c95.jpeg', '2025-05-17 13:09:31');

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_inquiries`
--

CREATE TABLE `accommodation_inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_reviews`
--

CREATE TABLE `accommodation_reviews` (
  `review_id` int(11) NOT NULL,
  `accommodation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `user_id`, `content`, `parent_id`, `created_at`, `updated_at`) VALUES
(11, 3, 3, 'Sheiii\nYess\n\ncan we do it?', NULL, '2025-05-14 23:06:11', '2025-05-14 23:18:49'),
(14, 3, 3, 'mm', NULL, '2025-05-14 23:27:29', '2025-05-14 23:27:29'),
(15, 3, 3, 'sdasda', 11, '2025-05-15 00:36:08', '2025-05-15 00:36:08'),
(17, 3, 6, 'dfsda', 14, '2025-05-15 15:29:40', '2025-05-15 15:29:40'),
(18, 8, 6, '.....ok', NULL, '2025-05-16 11:30:45', '2025-05-16 11:30:45'),
(19, 8, 6, '..ok', 18, '2025-05-16 11:30:51', '2025-05-16 11:30:51'),
(20, 8, 6, 'works?', NULL, '2025-05-17 12:54:06', '2025-05-17 12:54:06');

-- --------------------------------------------------------

--
-- Table structure for table `driver_reviews`
--

CREATE TABLE `driver_reviews` (
  `review_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ride_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_reviews`
--

INSERT INTO `driver_reviews` (`review_id`, `driver_id`, `user_id`, `ride_id`, `rating`, `comment`, `created_at`) VALUES
(2, 6, 3, 4, 5, '', '2025-05-15 02:08:19'),
(3, 3, 6, 6, 5, 'sdasd', '2025-05-16 10:21:15'),
(4, 3, 6, 7, 3, '', '2025-05-16 10:21:18');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cover_image` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('registered','cancelled') DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `caption` text DEFAULT NULL,
  `content` text NOT NULL,
  `community` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `votes` int(11) DEFAULT 0,
  `comment_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `caption`, `content`, `community`, `image_url`, `votes`, `comment_count`, `created_at`, `updated_at`) VALUES
(3, 3, 'Guys!!', 'What&#039;s down?', 'general', NULL, 0, 4, '2025-05-14 22:41:06', '2025-05-15 15:29:40'),
(4, 3, 'HYell', 'ASSSS', 'general', NULL, 0, 0, '2025-05-15 17:07:53', '2025-05-15 17:07:53'),
(5, 3, 'Hello', 'what\'s up?', 'general', NULL, 0, 0, '2025-05-15 19:05:18', '2025-05-15 19:05:18'),
(6, 3, 'hhll', 'hghjg', 'cse', NULL, 0, 0, '2025-05-15 19:16:56', '2025-05-15 19:16:56'),
(7, 3, 'gfhf', 'gfhgfg', 'design', NULL, 0, 0, '2025-05-15 19:17:08', '2025-05-15 19:17:08'),
(8, 6, 'Ni hao', 'Hello Everyone at BRACU!!!', 'general', NULL, 0, 3, '2025-05-16 11:26:43', '2025-05-17 12:54:06'),
(9, 6, 'New version checkkkk', 'Guys it\'s finally working???', 'general', NULL, 0, 0, '2025-05-17 12:54:55', '2025-05-17 12:54:55');

-- --------------------------------------------------------

--
-- Table structure for table `post_votes`
--

CREATE TABLE `post_votes` (
  `vote_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('upvote','downvote') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(20) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`resource_id`, `user_id`, `course_code`, `semester`, `file_name`, `file_type`, `file_url`, `downloads`, `created_at`) VALUES
(3, 3, 'CSE489', 'Spring 2024', 'CSE489_22101803_Reduan Nur Labid_PetHood.pdf', 'other', '68251ba51cbe3_1747262373.pdf', 0, '2025-05-14 22:39:33'),
(49, 6, 'CSE321', 'Spring 2024', 'Project 02.pdf', 'past_paper', '68272209944b1_1747395081.pdf', 0, '2025-05-16 11:31:21'),
(53, 6, 'CSE321', 'Spring 2024', 'CSE321 Section 16 Lab scores.pdf', 'slides', '6828874e952f7_1747486542.pdf', 1, '2025-05-17 12:55:42');

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

CREATE TABLE `rides` (
  `ride_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_type` enum('car','bike','cng','rickshaw') NOT NULL,
  `seats` int(11) NOT NULL,
  `fare` decimal(10,2) NOT NULL,
  `pickup_location` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `departure_time` datetime DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('available','full','completed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rides`
--

INSERT INTO `rides` (`ride_id`, `user_id`, `vehicle_type`, `seats`, `fare`, `pickup_location`, `destination`, `departure_time`, `contact_info`, `notes`, `status`, `created_at`) VALUES
(3, 3, 'car', 1, 222.00, 'Pocket gate 2', 'Dhanmondi 4', '2025-05-15 08:02:00', '0173203823', 'Anyone?', '', '2025-05-15 01:52:18'),
(4, 6, 'bike', 0, 75.00, 'pocket gate 2 ', 'dmd', '2025-05-15 10:04:00', '23132421242', 'jknbnjkn', '', '2025-05-15 02:05:11'),
(5, 6, 'bike', 1, 222.00, 'pocket gate 2 ', 'dmd', '2025-05-15 11:55:00', '23132421242', 'sdad', '', '2025-05-15 02:55:42'),
(6, 3, 'car', 0, 80.00, 'Pocket gate 2', 'Dhanmondi 4', '2025-05-15 09:56:00', '0173203823', 'sdsadas', '', '2025-05-15 02:57:53'),
(7, 3, 'bike', 0, 222.00, 'sda', 'sdasd', '2025-05-15 10:34:00', '01632061007', 'sdadas', '', '2025-05-15 15:32:11'),
(8, 6, 'car', 1, 80.00, 'Pocket gate 2', 'dmd', '2025-05-16 20:58:00', '23132421242', 'jhnbhjj', '', '2025-05-16 11:55:56'),
(9, 6, 'bike', 1, 400.00, 'Main Gate', 'Uttara', '2025-05-20 18:00:00', '01723423122', 'Jaba??', '', '2025-05-17 13:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `ride_requests`
--

CREATE TABLE `ride_requests` (
  `request_id` int(11) NOT NULL,
  `ride_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seats` int(11) NOT NULL,
  `pickup` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ride_requests`
--

INSERT INTO `ride_requests` (`request_id`, `ride_id`, `user_id`, `status`, `created_at`, `seats`, `pickup`, `notes`) VALUES
(5, 3, 6, 'rejected', '2025-05-15 01:55:27', 1, 'Pocket gate 2', 'Where are you now??'),
(6, 4, 3, 'accepted', '2025-05-15 02:06:23', 1, 'pocket gate 2 ', 'j'),
(7, 6, 6, 'accepted', '2025-05-15 02:58:13', 2, 'Pocket gate 2', 'Wait 10 minutes.'),
(8, 7, 6, 'accepted', '2025-05-15 15:32:49', 1, 'sda', 'sdasd'),
(9, 8, 3, 'accepted', '2025-05-16 11:56:40', 1, 'Pocket gate 2', '232');

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

CREATE TABLE `saved_posts` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `saved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_posts`
--

INSERT INTO `saved_posts` (`id`, `post_id`, `user_id`, `saved_at`) VALUES
(2, 3, 6, '2025-05-15 08:54:28'),
(4, 4, 3, '2025-05-15 23:16:34'),
(6, 5, 3, '2025-05-16 01:10:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `interests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `student_id`, `email`, `password_hash`, `avatar_url`, `bio`, `department`, `interests`, `created_at`, `updated_at`) VALUES
(3, 'Reduan Nur', '22101803', 'reduan.nur.labid@g.bracu.ac.bd', '$2y$10$1pbJfjg6gaSF8Ihdem3fPu/DHIck713AHpFZV88Ueg5oFuia.6.MS', 'https://images.hdqwalls.com/wallpapers/batman-minimalist-art-4k-z0.jpg', NULL, 'CSE', NULL, '2025-05-14 22:37:21', '2025-05-14 22:38:47'),
(6, 'Rafi', '22101806', 'rafi@g.bracu.ac.bd', '$2y$10$RLAqm2r9zva/7KhAdCN24u/M8nI/F0rXS2kvtv/KezBp9KvNstBoe', 'https://upload.wikimedia.org/wikipedia/en/a/aa/Hulk_%28circa_2019%29.png', NULL, 'CSE', NULL, '2025-05-15 01:54:35', '2025-05-15 02:09:12'),
(12, 'John Doe', '20301001', 'john.doe@g.bracu.ac.bd', '$2y$10$UU7QSdGgdYaM7.m8vxPhFeHWei/mTWx/5ckAhFFyRI34Cs/gynxsa', 'https://avatar.iran.liara.run/public/1', 'Computer Science student interested in AI and Machine Learning', 'Computer Science and Engineering (CSE)', NULL, '2025-05-15 17:18:47', '2025-05-15 17:18:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('post','comment','like') NOT NULL,
  `content_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`activity_id`, `user_id`, `activity_type`, `content_id`, `created_at`) VALUES
(1, 3, 'post', 3, '2025-05-14 22:41:06'),
(5, 3, 'comment', 6, '2025-05-14 23:02:38'),
(9, 3, 'comment', 10, '2025-05-14 23:05:51'),
(10, 3, 'comment', 11, '2025-05-14 23:06:11'),
(12, 3, 'comment', 13, '2025-05-14 23:20:03'),
(13, 3, 'comment', 14, '2025-05-14 23:27:29'),
(14, 3, 'comment', 15, '2025-05-15 00:36:08'),
(15, 3, 'comment', 16, '2025-05-15 02:14:58'),
(16, 6, 'comment', 17, '2025-05-15 15:29:40'),
(17, 3, 'post', 4, '2025-05-15 17:07:53'),
(18, 3, 'post', 5, '2025-05-15 19:05:18'),
(19, 3, 'post', 6, '2025-05-15 19:16:56'),
(20, 3, 'post', 7, '2025-05-15 19:17:08'),
(21, 6, 'post', 8, '2025-05-16 11:26:43'),
(22, 6, 'comment', 18, '2025-05-16 11:30:45'),
(23, 6, 'comment', 19, '2025-05-16 11:30:51'),
(24, 6, 'comment', 20, '2025-05-17 12:54:06'),
(25, 6, 'post', 9, '2025-05-17 12:54:55');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `vote_type` enum('up','down') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`vote_id`, `user_id`, `post_id`, `vote_type`, `created_at`) VALUES
(12, 6, 3, 'down', '2025-05-15 15:29:51'),
(20, 3, 3, 'down', '2025-05-15 17:19:15'),
(25, 3, 5, 'up', '2025-05-16 10:05:04'),
(26, 3, 8, 'up', '2025-05-16 12:08:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodations`
--
ALTER TABLE `accommodations`
  ADD PRIMARY KEY (`accommodation_id`),
  ADD KEY `idx_accommodations_owner` (`owner_id`),
  ADD KEY `idx_accommodations_status` (`status`),
  ADD KEY `idx_accommodations_location` (`location`),
  ADD KEY `idx_accommodations_room_type` (`room_type`);

--
-- Indexes for table `accommodation_favorites`
--
ALTER TABLE `accommodation_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`accommodation_id`,`user_id`),
  ADD KEY `idx_accommodation_favorites_user` (`user_id`);

--
-- Indexes for table `accommodation_images`
--
ALTER TABLE `accommodation_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_accommodation_images` (`accommodation_id`);

--
-- Indexes for table `accommodation_inquiries`
--
ALTER TABLE `accommodation_inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `accommodation_id` (`accommodation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_accommodation_inquiries_status` (`status`);

--
-- Indexes for table `accommodation_reviews`
--
ALTER TABLE `accommodation_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `accommodation_id` (`accommodation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_parent_comment` (`parent_id`);

--
-- Indexes for table `driver_reviews`
--
ALTER TABLE `driver_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_driver_reviews_driver_id` (`driver_id`),
  ADD KEY `idx_driver_reviews_user_id` (`user_id`),
  ADD KEY `idx_driver_reviews_ride_id` (`ride_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_votes`
--
ALTER TABLE `post_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `unique_vote` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`ride_id`),
  ADD KEY `idx_rides_user_id` (`user_id`),
  ADD KEY `idx_rides_status` (`status`);

--
-- Indexes for table `ride_requests`
--
ALTER TABLE `ride_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_request` (`ride_id`,`user_id`),
  ADD KEY `idx_ride_requests_ride_id` (`ride_id`),
  ADD KEY `idx_ride_requests_user_id` (`user_id`),
  ADD KEY `idx_ride_requests_status` (`status`);

--
-- Indexes for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_save` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `unique_vote` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accommodations`
--
ALTER TABLE `accommodations`
  MODIFY `accommodation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `accommodation_favorites`
--
ALTER TABLE `accommodation_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accommodation_images`
--
ALTER TABLE `accommodation_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `accommodation_inquiries`
--
ALTER TABLE `accommodation_inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accommodation_reviews`
--
ALTER TABLE `accommodation_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `driver_reviews`
--
ALTER TABLE `driver_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `post_votes`
--
ALTER TABLE `post_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `ride_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ride_requests`
--
ALTER TABLE `ride_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accommodations`
--
ALTER TABLE `accommodations`
  ADD CONSTRAINT `accommodations_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `accommodation_favorites`
--
ALTER TABLE `accommodation_favorites`
  ADD CONSTRAINT `accommodation_favorites_ibfk_1` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`accommodation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accommodation_favorites_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `accommodation_images`
--
ALTER TABLE `accommodation_images`
  ADD CONSTRAINT `accommodation_images_ibfk_1` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`accommodation_id`) ON DELETE CASCADE;

--
-- Constraints for table `accommodation_inquiries`
--
ALTER TABLE `accommodation_inquiries`
  ADD CONSTRAINT `accommodation_inquiries_ibfk_1` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`accommodation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accommodation_inquiries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `accommodation_reviews`
--
ALTER TABLE `accommodation_reviews`
  ADD CONSTRAINT `accommodation_reviews_ibfk_1` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`accommodation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accommodation_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `driver_reviews`
--
ALTER TABLE `driver_reviews`
  ADD CONSTRAINT `driver_reviews_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `driver_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `driver_reviews_ibfk_3` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`ride_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_votes`
--
ALTER TABLE `post_votes`
  ADD CONSTRAINT `post_votes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ride_requests`
--
ALTER TABLE `ride_requests`
  ADD CONSTRAINT `ride_requests_ibfk_1` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`ride_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ride_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD CONSTRAINT `saved_posts_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
