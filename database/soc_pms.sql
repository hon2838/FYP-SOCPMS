-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2024 at 04:00 AM
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
  `document_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `register_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `name`, `email`, `password`, `user_type`, `register_time`) VALUES
(1, 'HON JUN YOON', 'joanchoo2201@hotmail.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'admin', '2024-07-25 21:13:36'),
(2, 'Matthew Hon', 'honjunyoon@hotmail.com', '$2y$10$rw8q9qHhIS4jQOEAhddhEOMHZ4bblaSLiRoip8T9SkBqMRsYq6Kuq', 'user', '2024-07-25 21:18:15'),
(5, 'matthew hon', 'hon_jun_yoon@soc.uum.edu.my', '$2y$10$qv4nrj9yrKA.CAj93uS.1.03Z1FIVBGzqSubzCwK5orJMq.edgk0W', 'user', '2024-07-26 11:03:17');

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
  MODIFY `ppw_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD CONSTRAINT `tbl_ppw_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
