-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 29, 2026 at 04:33 PM
-- Server version: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `acc_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `type` varchar(20) NOT NULL,
  `category_id` varchar(10) NOT NULL DEFAULT '0',
  `language` varchar(2) NOT NULL DEFAULT '',
  `topic` varchar(150) NOT NULL,
  `color` varchar(16) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `{prefix}_category`
--

INSERT INTO `{prefix}_category` (`type`, `category_id`, `language`, `topic`, `color`, `is_active`) VALUES
('category_id', '3', '', 'Ram', NULL, 1),
('category_id', '2', '', 'วัสดุสำนักงาน', NULL, 1),
('model_id', '4', '', 'ACER', NULL, 1),
('model_id', '3', '', 'Cannon', NULL, 1),
('model_id', '2', '', 'Asus', NULL, 1),
('category_id', '1', '', 'เครื่องใช้ไฟฟ้า', NULL, 1),
('model_id', '1', '', 'Apple', NULL, 1),
('unit', 'อัน', '', 'อัน', NULL, 1),
('unit', 'กล่อง', '', 'กล่อง', NULL, 1),
('unit', 'เครื่อง', '', 'เครื่อง', NULL, 1),
('type_id', '4', '', 'จอมอนิเตอร์', NULL, 1),
('type_id', '3', '', 'โปรเจ็คเตอร์', NULL, 1),
('type_id', '2', '', 'เครื่องพิมพ์', NULL, 1),
('type_id', '1', '', 'เครื่องคอมพิวเตอร์', NULL, 1),
('repairstatus', '1', '', 'แจ้งซ่อม', NULL, 1),
('repairstatus', '2', '', 'กำลังดำเนินการ', NULL, 1),
('repairstatus', '3', '', 'รออะไหล่', NULL, 1),
('repairstatus', '4', '', 'ซ่อมสำเร็จ', NULL, 1),
('repairstatus', '5', '', 'ซ่อมไม่สำเร็จ', NULL, 1),
('repairstatus', '6', '', 'ยกเลิกการซ่อม', NULL, 1),
('repairstatus', '7', '', 'ส่งมอบเรียบร้อย', NULL, 1),
('department', '1', '', 'บริหาร', NULL, 1),
('department', '2', '', 'จัดซื้อ', NULL, 1),
('department', '3', '', 'งานซ่อม', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_inventory`
--

CREATE TABLE `{prefix}_inventory` (
  `id` int(11) NOT NULL,
  `category_id` varchar(10) DEFAULT NULL,
  `model_id` varchar(10) DEFAULT NULL,
  `type_id` varchar(10) DEFAULT NULL,
  `topic` varchar(150) NOT NULL,
  `inuse` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `{prefix}_inventory`
--

INSERT INTO `{prefix}_inventory` (`id`, `category_id`, `model_id`, `type_id`, `topic`, `inuse`) VALUES
(1, '1', '4', '4', 'จอมอนิเตอร์ ACER S220HQLEBD', 1),
(2, '1', '2', '1', 'ASUS A550JX', 1),
(3, '3', '4', '1', 'Crucial 4GB DDR3L & 1600 SODIMM', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_inventory_assignments`
--

CREATE TABLE `{prefix}_inventory_assignments` (
  `id` int(11) NOT NULL,
  `product_no` varchar(150) NOT NULL,
  `holder_id` int(11) NOT NULL,
  `quantity` float NOT NULL DEFAULT 0,
  `assigned_at` datetime NOT NULL,
  `returned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `{prefix}_inventory_assignments`
--

INSERT INTO `{prefix}_inventory_assignments` (`id`, `product_no`, `holder_id`, `quantity`, `assigned_at`, `returned_at`) VALUES
(1, 'IF111/036/1', 2, 5, '2026-04-28 22:11:05', '2026-04-28 22:17:31'),
(2, 'IF111/036/2', 2, 4, '2026-04-28 22:11:05', '2026-04-28 22:17:13'),
(3, 'IF111/036/2', 2, 1, '2026-04-28 22:17:13', '2026-04-28 22:18:36'),
(4, 'IF111/036/1', 2, 1, '2026-04-28 22:17:31', '2026-04-28 22:18:39'),
(5, 'P87-0057', 1, 1, '2026-04-29 10:28:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_inventory_items`
--

CREATE TABLE `{prefix}_inventory_items` (
  `product_no` varchar(150) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `stock` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `{prefix}_inventory_items`
--

INSERT INTO `{prefix}_inventory_items` (`product_no`, `inventory_id`, `unit`, `stock`) VALUES
('1108-365D', 1, 'กล่อง', 5),
('IF111/036/1', 3, 'อัน', 5),
('IF111/036/2', 3, 'อัน', 4),
('P87-0057', 2, 'เครื่อง', 5);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_inventory_meta`
--

CREATE TABLE `{prefix}_inventory_meta` (
  `inventory_id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `{prefix}_inventory_meta`
--

INSERT INTO `{prefix}_inventory_meta` (`inventory_id`, `name`, `value`) VALUES
(3, 'warranty_expire', '2026-05-15');

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `type` varchar(5) NOT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_number`
--

CREATE TABLE `{prefix}_number` (
  `type` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `auto_increment` int(11) NOT NULL,
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_repair`
--

CREATE TABLE `{prefix}_repair` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_no` varchar(150) NOT NULL,
  `job_id` varchar(20) NOT NULL,
  `job_description` varchar(1000) NOT NULL,
  `created_at` datetime NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `repair_no` varchar(50) DEFAULT NULL,
  `informer` varchar(150) DEFAULT NULL,
  `appraiser` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_repair_status`
--

CREATE TABLE `{prefix}_repair_status` (
  `id` int(11) NOT NULL,
  `repair_id` int(11) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `comment` varchar(1000) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `cost` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) DEFAULT '',
  `password` varchar(64) NOT NULL,
  `token` varchar(512) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text DEFAULT '',
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` varchar(64) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `provinceID` smallint(3) DEFAULT NULL,
  `province` varchar(64) DEFAULT NULL,
  `zipcode` varchar(5) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `created_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `social` enum('user','facebook','google','line','telegram') DEFAULT 'user',
  `line_uid` varchar(33) DEFAULT NULL,
  `telegram_id` varchar(20) DEFAULT NULL,
  `activatecode` varchar(64) DEFAULT NULL,
  `address2` varchar(64) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `company` varchar(64) DEFAULT NULL,
  `phone1` varchar(20) DEFAULT NULL,
  `tax_id` varchar(13) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `visited` int(11) NOT NULL DEFAULT 0,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `language` (`language`);

--
-- Indexes for table `{prefix}_inventory`
--
ALTER TABLE `{prefix}_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `{prefix}_inventory_assignments`
--
ALTER TABLE `{prefix}_inventory_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_no` (`product_no`),
  ADD KEY `holder_id` (`holder_id`),
  ADD KEY `returned_at` (`returned_at`);

--
-- Indexes for table `{prefix}_inventory_items`
--
ALTER TABLE `{prefix}_inventory_items`
  ADD PRIMARY KEY (`product_no`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `{prefix}_inventory_meta`
--
ALTER TABLE `{prefix}_inventory_meta`
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `{prefix}_number`
--
ALTER TABLE `{prefix}_number`
  ADD PRIMARY KEY (`type`,`prefix`);

--
-- Indexes for table `{prefix}_repair`
--
ALTER TABLE `{prefix}_repair`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_id` (`job_id`),
  ADD KEY `product_no` (`product_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_created_at` (`created_at` DESC),
  ADD KEY `idx_cover` (`created_at` DESC,`customer_id`,`product_no`);

--
-- Indexes for table `{prefix}_repair_status`
--
ALTER TABLE `{prefix}_repair_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operator_id` (`operator_id`),
  ADD KEY `repair_id` (`repair_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `idx_repair_id_id` (`repair_id`,`id` DESC);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `id_card` (`id_card`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_status` (`active`,`status`),
  ADD KEY `activatecode` (`activatecode`),
  ADD KEY `line_uid` (`line_uid`),
  ADD KEY `telegram_id` (`telegram_id`);

--
-- Indexes for table `{prefix}_user_meta`
--
ALTER TABLE `{prefix}_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `{prefix}_inventory`
--
ALTER TABLE `{prefix}_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_inventory_assignments`
--
ALTER TABLE `{prefix}_inventory_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_repair`
--
ALTER TABLE `{prefix}_repair`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_repair_status`
--
ALTER TABLE `{prefix}_repair_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;