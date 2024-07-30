-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2024 at 05:15 PM
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
-- Table structure for table `tbl_ppw`
--

CREATE TABLE `tbl_ppw` (
  `ppw_id` int NOT NULL,
  `id` int NOT NULL,
  `name` text NOT NULL,
  `session` varchar(255) NOT NULL,
  `project_name` text NOT NULL,
  `project_date` date NOT NULL,
  `submission_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `note` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_ppw`
--

INSERT INTO `tbl_ppw` (`ppw_id`, `id`, `name`, `session`, `project_name`, `project_date`, `submission_time`, `status`, `note`) VALUES
(1, 1, 'HON JUN YOON', '1', '1', '2024-07-16', '2024-07-25 13:34:46', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ppwfull`
--

CREATE TABLE `tbl_ppwfull` (
  `ppw_id` int NOT NULL,
  `id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ppw_type` varchar(255) NOT NULL,
  `session` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `objective` text NOT NULL,
  `purpose` text NOT NULL,
  `background` text NOT NULL,
  `aim` text NOT NULL,
  `startdate` date NOT NULL,
  `end_date` date NOT NULL,
  `pgrm_involve` int NOT NULL,
  `external_sponsor` int NOT NULL,
  `sponsor_name` varchar(255) DEFAULT NULL,
  `english_lang_req` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_ppwfull`
--

INSERT INTO `tbl_ppwfull` (`ppw_id`, `id`, `name`, `ppw_type`, `session`, `project_name`, `objective`, `purpose`, `background`, `aim`, `startdate`, `end_date`, `pgrm_involve`, `external_sponsor`, `sponsor_name`, `english_lang_req`) VALUES
(1, 1, 'HON JUN YOON', '1', '1', '1', '1', '1', '1', '1', '2024-07-16', '2024-07-24', 1, 1, '1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'user',
  `register_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `name`, `email`, `password`, `user_type`, `register_time`) VALUES
(1, 'HON JUN YOON', 'joanchoo2201@hotmail.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'admin', '2024-07-25 21:13:36'),
(2, 'Matthew Hon', 'honjunyoon@hotmail.com', '$2y$10$rw8q9qHhIS4jQOEAhddhEOMHZ4bblaSLiRoip8T9SkBqMRsYq6Kuq', 'user', '2024-07-25 21:18:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD PRIMARY KEY (`ppw_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `tbl_ppwfull`
--
ALTER TABLE `tbl_ppwfull`
  ADD PRIMARY KEY (`ppw_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  MODIFY `ppw_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_ppwfull`
--
ALTER TABLE `tbl_ppwfull`
  MODIFY `ppw_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD CONSTRAINT `tbl_ppw_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_users` (`id`);

--
-- Constraints for table `tbl_ppwfull`
--
ALTER TABLE `tbl_ppwfull`
  ADD CONSTRAINT `tbl_ppwfull_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_users` (`id`),
  ADD CONSTRAINT `tbl_ppwfull_ibfk_2` FOREIGN KEY (`ppw_id`) REFERENCES `tbl_ppw` (`ppw_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
