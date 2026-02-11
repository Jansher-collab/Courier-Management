-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 11, 2026 at 07:19 PM
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
-- Database: `courier_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `agent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`agent_id`, `user_id`, `branch`, `approved`) VALUES
(7, 22, 'Karachi', 0),
(10, 30, 'Karachi', 0),
(11, 35, 'Karachi', 1);

-- --------------------------------------------------------

--
-- Table structure for table `couriers`
--

CREATE TABLE `couriers` (
  `courier_id` int(11) NOT NULL,
  `tracking_number` varchar(20) DEFAULT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `from_location` varchar(100) NOT NULL,
  `to_location` varchar(100) NOT NULL,
  `courier_type` varchar(50) DEFAULT NULL,
  `status` enum('booked','in-progress','delivered') DEFAULT 'booked',
  `delivery_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `couriers`
--

INSERT INTO `couriers` (`courier_id`, `tracking_number`, `sender_id`, `receiver_id`, `from_location`, `to_location`, `courier_type`, `status`, `delivery_date`, `created_by`, `agent_id`, `created_at`, `updated_at`) VALUES
(33, '4262124C8B', 8, 9, 'London', 'Pakistan, Karachi', 'Parcel', 'in-progress', '2026-02-07', 21, NULL, '2026-02-07 13:03:09', '2026-02-07 18:07:59'),
(34, '867C8DD73E', 8, 9, 'Karachi', 'Islamabad', 'Documents', 'delivered', '2026-02-08', 21, NULL, '2026-02-07 13:10:32', '2026-02-08 09:58:09'),
(35, '26BAEE618E', 8, 9, 'Karachi', 'Islamabad', 'Documents', 'in-progress', '2026-02-11', 21, 10, '2026-02-07 13:11:02', '2026-02-10 19:06:07'),
(36, '01D8688F9C', 8, 9, 'Karachi', 'Islamabad', 'Documents', 'booked', '2026-02-07', 21, NULL, '2026-02-07 13:47:02', '2026-02-07 13:47:02'),
(37, '2604B4ED03', 8, 9, 'Pindi', 'Islamabad', 'Parcel', 'booked', '2026-02-07', 21, NULL, '2026-02-07 13:47:30', '2026-02-07 17:06:07'),
(40, '8565CF3676', 9, 35, 'Lahore', 'Karachi', 'Documents', 'booked', '2026-02-08', 21, NULL, '2026-02-08 13:15:09', '2026-02-08 13:15:09'),
(41, '752B929C39', 9, 35, 'Lahore', 'Karachi', 'Documents', 'booked', '2026-02-08', 21, NULL, '2026-02-08 13:21:11', '2026-02-08 13:21:11');

-- --------------------------------------------------------

--
-- Table structure for table `courier_logs`
--

CREATE TABLE `courier_logs` (
  `log_id` int(11) NOT NULL,
  `courier_id` int(11) NOT NULL,
  `status` enum('booked','in-progress','delivered') NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `notified_via` enum('sms','email','none') DEFAULT 'none',
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courier_logs`
--

INSERT INTO `courier_logs` (`log_id`, `courier_id`, `status`, `message`, `notified_via`, `log_time`) VALUES
(11, 36, 'booked', 'Courier booked by admin', 'email', '2026-02-07 13:47:02'),
(12, 37, 'booked', 'Courier booked by admin', 'email', '2026-02-07 13:47:30'),
(14, 37, 'booked', 'Courier updated by admin', 'none', '2026-02-07 17:06:07'),
(15, 33, 'in-progress', 'Courier updated by admin', 'none', '2026-02-07 18:07:59'),
(16, 33, '', 'Hello, your courier is being processed. Please check your tracking number for updates.<br />\r\n    ', 'email', '2026-02-07 18:51:01'),
(17, 33, '', '<h3>Delivery Notification</h3>\r\n             <p>Hello Jan Sher Khan,</p>\r\n             <p>Your courier with tracking number <b>4262124C8B</b> is scheduled to be delivered today to <b>Pakistan, Karachi</b>.</p>\r\n             <p>Thank you for using our Cour', 'email', '2026-02-07 18:51:28'),
(18, 34, 'delivered', 'Courier updated by admin', 'none', '2026-02-08 09:58:09'),
(21, 34, '', 'Here Agentone!', 'email', '2026-02-08 10:23:33'),
(23, 40, 'booked', 'Courier booked by admin', 'email', '2026-02-08 13:15:09'),
(24, 41, 'booked', 'Courier booked by admin', 'email', '2026-02-08 13:21:11'),
(25, 35, 'in-progress', 'Courier updated by agent', 'email', '2026-02-10 19:06:07'),
(26, 33, '', 'Delivery Notification\r\n\r\nHello Jan Sher Khan,\r\n\r\nYour courier with tracking number 4262124C8B \r\nis scheduled to be delivered today to Pakistan, Karachi.\r\n\r\nThank you for using our Courier Management System.', 'email', '2026-02-11 10:28:38'),
(27, 33, '', 'Delivery Notification\r\n\r\nHello Jan Sher Khan,\r\n\r\nYour courier with tracking number 4262124C8B \r\nis scheduled to be delivered today to Pakistan, Karachi.\r\n\r\nThank you for using our Courier Management System.', 'email', '2026-02-11 10:31:30'),
(28, 33, '', 'Delivery Notification\r\n\r\nHello Jan Sher Khan,\r\n\r\nYour courier with tracking number 4262124C8B \r\nis scheduled to be delivered today to Pakistan, Karachi.\r\n\r\nThank you for using our Courier Management System.', 'email', '2026-02-11 10:37:24'),
(29, 33, '', 'Delivery Notification\r\n\r\nHello Jan Sher Khan,\r\n\r\nYour courier with tracking number 4262124C8B \r\nis scheduled to be delivered today to Pakistan, Karachi.\r\n\r\nThank you for using our Courier Management System.', 'email', '2026-02-11 12:57:20');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
(8, 'John Doe', 'john@example.com', '1234567890', '123 Main St', '2026-02-07 12:03:19'),
(9, 'Jan Sher Khan', 'janxp670@gmail.com', '03272007787', 'Orangi Town Karachi', '2026-02-07 12:03:19'),
(29, 'Abu Bakar', 'abubakar@gmail.com', '03272007787', 'Orangi Town', '2026-02-08 11:44:27'),
(31, 'Wahab', 'wahab@gmail.com', '03272007787', 'Orangi Town', '2026-02-08 12:36:23'),
(32, 'Abdul Wahab', 'abdulwahab@gmail.com', '03220664241', 'Orangi Town', '2026-02-08 12:39:53'),
(33, 'Abuzar', 'abuzar@gmail.com', '03220664241', 'Orangi Town', '2026-02-08 12:42:23'),
(34, 'Jan Sher Khan', 'jansher@gmail.com', '03272007787', 'Orangi Town', '2026-02-09 18:25:36'),
(35, 'Anas', 'anasjan@gmail.com', '03272007787', 'Orangi Town Karrachi', '2026-02-08 13:15:09');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_type` enum('date-wise','city-wise') NOT NULL,
  `generated_by` int(11) NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `report_type`, `generated_by`, `from_date`, `to_date`, `branch`, `file_path`, `created_at`) VALUES
(10, '', 21, NULL, NULL, NULL, 'reports/courier_report_20260207_194933.csv', '2026-02-07 18:49:33'),
(11, '', 21, NULL, NULL, NULL, 'reports/courier_report_20260209_120927.csv', '2026-02-09 11:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','agent','user') NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `branch`, `created_at`) VALUES
(21, 'Admin', 'admin@gmail.com', '$2y$10$wQ6yA.2pG7ojrKj.xgK0ruUd8U1QmTvU.XDPnA62rpw9GpzbyoQtu', 'admin', 'Head Office', '2026-02-07 11:53:36'),
(22, 'Kaleem', 'janxp670@gmail.com', '$2y$10$1EruDAcygnbZF5zPaDyTquoX98Y624REuwz250/GsuZtJNK5C3qY2', 'agent', NULL, '2026-02-07 18:16:40'),
(23, 'Danish', 'danish@gmail.com', '$2y$10$SWPG1U1ICBBAeE8n4kuXqug2/Jp0bYh09NKBbBZSNLDZ.Rm9kmjWq', 'agent', NULL, '2026-02-07 18:19:17'),
(27, 'Sami', 'sami@gmail.com', '$2y$10$uIpPX8R5ZJdhzb1Qjo1uLuUl8gVj4wwN1sum/dC3XJKw2FA2whc2W', 'agent', NULL, '2026-02-07 18:44:47'),
(28, 'Anas Jan', 'jansherkhan385@gmail.com', '$2y$10$wIGdySI.vHWReeP0ruUyTeCktF4.HsWM4M6pPfI9i35LRA5nFMSfG', 'user', NULL, '2026-02-08 11:32:58'),
(29, 'Abu Bakar', 'abubakar@gmail.com', '$2y$10$2DCbMoqfWK0pkcfdBf5NQ.1E3zKhFKlopwOvadz2aPlJzrE8qniGi', 'user', NULL, '2026-02-08 11:44:27'),
(30, 'Anas', 'anas@gmail.com', '$2y$10$Ma3VJ.1EH.0rNayjxt9pkON5NYsjW04vhemWudQksBiRo.zJIeSV.', 'agent', NULL, '2026-02-08 12:24:11'),
(31, 'Wahab', 'wahab@gmail.com', '$2y$10$IZ4V4qLBEXfqnmUZjUdKHue.MoQxPnYy4RQXOZ5bFp7mpkOr8gNSe', 'user', NULL, '2026-02-08 12:36:23'),
(32, 'Abdul Wahab', 'abdulwahab@gmail.com', '$2y$10$Rq7AaJ7dTqqBB6zeB7NCG.fJsiw52RbwWJFzZ99FiNqsXWQVIakJi', 'user', NULL, '2026-02-08 12:39:53'),
(33, 'Abuzar', 'abuzar@gmail.com', '$2y$10$9bY6Wpl2I2GD6MTu5Qct/u2U6q9ZsOXv0bFAORd35g6YuTyACuexy', 'user', NULL, '2026-02-08 12:42:23'),
(34, 'Jan Sher Khan', 'jansher@gmail.com', '$2y$10$2ZMABFr1/4Svat0mKj33Yuzhmzdi7qevsN.JxKaFEcyo75sixCAl.', 'user', NULL, '2026-02-09 18:25:36'),
(35, 'Kalaam', 'kalam@gmail.com', '$2y$10$enf4wEkIbqPoQModBglRd.JEjVskVHUjotU5hZxmRSkzoIHtnuy0e', 'agent', 'Karachi', '2026-02-10 20:04:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`agent_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `couriers`
--
ALTER TABLE `couriers`
  ADD PRIMARY KEY (`courier_id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `courier_logs`
--
ALTER TABLE `courier_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `courier_id` (`courier_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `agent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `couriers`
--
ALTER TABLE `couriers`
  MODIFY `courier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `courier_logs`
--
ALTER TABLE `courier_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `agents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `couriers`
--
ALTER TABLE `couriers`
  ADD CONSTRAINT `couriers_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `couriers_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `couriers_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `couriers_ibfk_4` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`agent_id`) ON DELETE SET NULL;

--
-- Constraints for table `courier_logs`
--
ALTER TABLE `courier_logs`
  ADD CONSTRAINT `courier_logs_ibfk_1` FOREIGN KEY (`courier_id`) REFERENCES `couriers` (`courier_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
