-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2024 at 06:05 PM
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
-- Database: `soc_pms`
--

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`) VALUES
(1, 'manage_users', 'Can manage user accounts'),
(2, 'view_admin_dashboard', 'Can access admin dashboard'),
(3, 'approve_paperwork', 'Can approve paperwork'),
(4, 'create_paperwork', 'Can create new paperwork'),
(5, 'edit_paperwork', 'Can edit paperwork'),
(6, 'delete_paperwork', 'Can delete paperwork'),
(7, 'view_reports', 'Can view system reports'),
(8, 'approve_hod_paperwork', 'Can approve paperwork as HOD'),
(9, 'approve_ceo_paperwork', 'Can approve paperwork as CEO'),
(10, 'view_paperwork', 'Can view paperwork details'),
(11, 'export_reports', 'Can export system reports'),
(12, 'manage_settings', 'Can manage system settings');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'super_admin', 'Full system access'),
(2, 'admin', 'System administration'),
(3, 'hod', 'Head of Department'),
(4, 'ceo', 'Chief Executive Officer'),
(5, 'staff', 'Regular staff member');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(3, 8),
(3, 10),
(4, 9),
(4, 10);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ppw`
--

CREATE TABLE `tbl_ppw` (
  `ppw_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `session` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_date` date NOT NULL,
  `submission_time` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `ref_number` varchar(50) NOT NULL,
  `ppw_type` varchar(50) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `current_stage` varchar(50) DEFAULT 'hod_review',
  `hod_approval` tinyint(1) DEFAULT NULL,
  `hod_note` text DEFAULT NULL,
  `hod_approval_date` datetime DEFAULT NULL,
  `ceo_approval` tinyint(1) DEFAULT NULL,
  `ceo_note` text DEFAULT NULL,
  `ceo_approval_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ppw`
--

INSERT INTO `tbl_ppw` (`ppw_id`, `id`, `name`, `session`, `project_name`, `project_date`, `submission_time`, `status`, `ref_number`, `ppw_type`, `document_path`, `admin_note`, `current_stage`, `hod_approval`, `hod_note`, `hod_approval_date`, `ceo_approval`, `ceo_note`, `ceo_approval_date`) VALUES
(3, 1, 'HON JUN YOON', '1', '1', '2024-12-11', '2024-12-11 03:19:21', NULL, '1', 'Project Proposal', '1733887161_Paper_v5.1_Improved.pdf', NULL, 'hod_review', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'user',
  `register_time` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `department` varchar(100) DEFAULT NULL,
  `reporting_to` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `name`, `email`, `password`, `user_type`, `register_time`, `active`, `settings`, `department`, `reporting_to`, `phone`, `address`, `last_login`, `last_updated`, `role_id`) VALUES
(1, 'HON JUN YOON', 'joanchoo2201@hotmail.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'admin', '2024-07-25 21:13:36', 1, '{\"email_notifications\":false,\"browser_notifications\":false,\"theme\":\"dark\",\"compact_view\":false}', NULL, NULL, NULL, NULL, NULL, '2024-12-12 10:08:46', NULL),
(2, 'Matthew Hon', 'honjunyoon@hotmail.com', '$2y$10$rw8q9qHhIS4jQOEAhddhEOMHZ4bblaSLiRoip8T9SkBqMRsYq6Kuq', 'user', '2024-07-25 21:18:15', 1, '{\"theme\": \"light\", \"compact_view\": false, \"email_notifications\": true, \"browser_notifications\": false}', NULL, NULL, NULL, NULL, NULL, '2024-12-12 08:27:37', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD PRIMARY KEY (`ppw_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  MODIFY `ppw_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD CONSTRAINT `tbl_ppw_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_users` (`id`);

--
-- Constraints for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD CONSTRAINT `tbl_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
