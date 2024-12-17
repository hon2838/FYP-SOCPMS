-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2024 at 09:30 AM
-- Server version: 8.0.38
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
-- Table structure for table `tbl_permissions`
--

CREATE TABLE `tbl_permissions` (
  `permission_id` int NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_permissions`
--

INSERT INTO `tbl_permissions` (`permission_id`, `permission_name`, `description`) VALUES
(1, 'create_users', 'Create new user accounts'),
(2, 'edit_users', 'Edit existing user accounts'),
(3, 'delete_users', 'Delete user accounts'),
(4, 'manage_roles', 'Manage user roles and permissions'),
(5, 'approve_paperwork', 'Approve submitted paperwork'),
(6, 'view_all_paperwork', 'View all paperwork in system'),
(7, 'edit_all_paperwork', 'Edit any paperwork in system'),
(8, 'delete_all_paperwork', 'Delete any paperwork in system'),
(9, 'manage_settings', 'Manage system settings'),
(10, 'view_reports', 'View system reports and analytics'),
(11, 'create_users', 'Create new user accounts'),
(12, 'edit_users', 'Edit existing user accounts'),
(13, 'delete_users', 'Delete user accounts'),
(14, 'manage_roles', 'Manage user roles and permissions'),
(15, 'approve_paperwork', 'Approve submitted paperwork'),
(16, 'view_all_paperwork', 'View all paperwork in system'),
(17, 'edit_all_paperwork', 'Edit any paperwork in system'),
(18, 'delete_all_paperwork', 'Delete any paperwork in system'),
(19, 'manage_settings', 'Manage system settings'),
(20, 'view_reports', 'View system reports and analytics'),
(21, 'create_users', 'Create new user accounts'),
(22, 'edit_users', 'Edit existing user accounts'),
(23, 'delete_users', 'Delete user accounts'),
(24, 'manage_roles', 'Manage user roles and permissions'),
(25, 'approve_paperwork', 'Approve submitted paperwork'),
(26, 'view_all_paperwork', 'View all paperwork in system'),
(27, 'edit_all_paperwork', 'Edit any paperwork in system'),
(28, 'delete_all_paperwork', 'Delete any paperwork in system'),
(29, 'manage_settings', 'Manage system settings'),
(30, 'view_reports', 'View system reports and analytics');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ppw`
--

CREATE TABLE `tbl_ppw` (
  `ppw_id` int NOT NULL,
  `id` int NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `project_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `project_date` date NOT NULL,
  `submission_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ref_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ppw_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `document_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `current_stage` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'hod_review',
  `hod_approval` tinyint(1) DEFAULT NULL,
  `hod_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hod_approval_date` datetime DEFAULT NULL,
  `ceo_approval` tinyint(1) DEFAULT NULL,
  `ceo_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ceo_approval_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ppw`
--

INSERT INTO `tbl_ppw` (`ppw_id`, `id`, `name`, `session`, `project_name`, `project_date`, `submission_time`, `status`, `ref_number`, `ppw_type`, `document_path`, `admin_note`, `current_stage`, `hod_approval`, `hod_note`, `hod_approval_date`, `ceo_approval`, `ceo_note`, `ceo_approval_date`) VALUES
(3, 1, 'HON JUN YOON', '1', '1', '2024-12-11', '2024-12-11 03:19:21', NULL, '1', 'Project Proposal', '1733887161_Paper_v5.1_Improved.pdf', NULL, 'hod_review', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_roles`
--

CREATE TABLE `tbl_roles` (
  `role_id` int NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_roles`
--

INSERT INTO `tbl_roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'superadmin', 'Full system access with all permissions'),
(2, 'admin', 'System administrator with limited permissions'),
(3, 'hod', 'Head of Department'),
(4, 'staff', 'Regular staff member'),
(5, 'ceo', 'Chief Executive Officer'),
(6, 'superadmin', 'Full system access with all permissions'),
(7, 'admin', 'System administrator with limited permissions'),
(8, 'hod', 'Head of Department'),
(9, 'staff', 'Regular staff member'),
(10, 'ceo', 'Chief Executive Officer'),
(11, 'superadmin', 'Full system access with all permissions'),
(12, 'admin', 'System administrator with limited permissions'),
(13, 'hod', 'Head of Department'),
(14, 'staff', 'Regular staff member'),
(15, 'ceo', 'Chief Executive Officer');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role_permissions`
--

CREATE TABLE `tbl_role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_role_permissions`
--

INSERT INTO `tbl_role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int NOT NULL,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `register_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `settings` json DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reporting_to` int DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `role_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `name`, `email`, `password`, `user_type`, `register_time`, `active`, `settings`, `department`, `reporting_to`, `phone`, `address`, `last_login`, `last_updated`, `role_id`) VALUES
(1, 'HON JUN YOON', 'joanchoo2201@hotmail.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'admin', '2024-07-25 21:13:36', 1, '{\"theme\": \"light\", \"compact_view\": false, \"email_notifications\": true, \"browser_notifications\": false}', NULL, NULL, NULL, NULL, NULL, '2024-12-17 08:27:24', 1),
(2, 'Matthew Hon', 'honjunyoon@hotmail.com', '$2y$10$rw8q9qHhIS4jQOEAhddhEOMHZ4bblaSLiRoip8T9SkBqMRsYq6Kuq', 'user', '2024-07-25 21:18:15', 1, '{\"theme\": \"light\", \"compact_view\": false, \"email_notifications\": true, \"browser_notifications\": false}', NULL, NULL, NULL, NULL, NULL, '2024-12-12 08:27:37', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_permissions`
--
ALTER TABLE `tbl_permissions`
  ADD PRIMARY KEY (`permission_id`);

--
-- Indexes for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD PRIMARY KEY (`ppw_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

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
-- AUTO_INCREMENT for table `tbl_permissions`
--
ALTER TABLE `tbl_permissions`
  MODIFY `permission_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  MODIFY `ppw_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  MODIFY `role_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD CONSTRAINT `tbl_ppw_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_users` (`id`);

--
-- Constraints for table `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  ADD CONSTRAINT `tbl_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`role_id`),
  ADD CONSTRAINT `tbl_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`permission_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
