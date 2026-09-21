-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 09:22 AM
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
-- Database: `armor_crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `city`
--

DROP TABLE IF EXISTS `city`;
CREATE TABLE IF NOT EXISTS `city` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_id` varchar(255) NOT NULL,
  `state_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `city`
--

INSERT INTO `city` (`id`, `country_id`, `state_id`, `name`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, '1', '4', 'Udaypur', 1, NULL, NULL, '2026-09-14 10:44:06', '2026-09-14 10:48:32'),
(2, '2', '3', 'rajkot', 1, NULL, NULL, '2026-09-14 10:50:58', '2026-09-14 11:02:04'),
(3, '1', '4', 'jaypur', 1, NULL, NULL, '2026-09-14 10:51:33', '2026-09-14 10:51:33'),
(5, '1', '1', 'rajkot', 1, NULL, NULL, '2026-09-14 11:02:16', '2026-09-14 11:03:18');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
CREATE TABLE IF NOT EXISTS `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `person_name` varchar(100) DEFAULT NULL,
  `mobile_no` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `country_id` int(11) DEFAULT 0,
  `state_id` int(11) DEFAULT 0,
  `city_id` int(11) DEFAULT 0,
  `plan_id` int(11) DEFAULT 0,
  `status` tinyint(4) DEFAULT 1,
  `created_by` int(11) DEFAULT 0,
  `updated_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `name`, `person_name`, `mobile_no`, `email`, `password`, `country_id`, `state_id`, `city_id`, `plan_id`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'ocean infotech', 'ocean', '9876543210', 'ocean@gmail.com', '$2y$10$YN1ydW8pvETuLnjfZM/hDe8Lhai5s7JVsvojvuUWOwB7o2yIQC89C', 1, 1, 5, 1, 1, 0, 0, '2026-09-15 11:50:12', '2026-09-15 11:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

DROP TABLE IF EXISTS `country`;
CREATE TABLE IF NOT EXISTS `country` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `flag` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`id`, `name`, `code`, `short_name`, `flag`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'India', NULL, 'IN', NULL, 1, NULL, NULL, '2026-09-14 05:02:00', '2026-09-14 06:16:13'),
(2, 'China', NULL, 'CHN', NULL, 1, NULL, NULL, '2026-09-14 06:16:40', '2026-09-14 06:19:14'),
(3, 'Afghanistan', NULL, 'AF', NULL, 1, NULL, NULL, '2026-09-14 06:19:22', '2026-09-14 06:19:22');

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

DROP TABLE IF EXISTS `module`;
CREATE TABLE IF NOT EXISTS `module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT 0,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `route` varchar(50) DEFAULT NULL,
  `order_by` int(11) DEFAULT 0,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module`
--

INSERT INTO `module` (`id`, `parent_id`, `name`, `slug`, `icon`, `route`, `order_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, 'Dashboard', 'dashboard', '', '/', 0, 1, '2026-09-15 09:08:36', '2026-09-15 09:47:27'),
(2, 1, 'MIS Dashboard', 'mis-dashboard', '', '/', 0, 1, '2026-09-15 09:08:53', '2026-09-15 09:49:40'),
(3, 1, 'Tracking Dash', 'tracking-dash', '', '/', 0, 1, '2026-09-15 09:12:02', '2026-09-15 09:49:46'),
(4, 0, 'Sales & Marketing', 'sales-marketing', 'layout-dashboard', '/', 0, 1, '2026-09-15 09:12:15', '2026-09-15 09:51:14'),
(5, 4, 'Quotation', 'quotation', '', '/', 0, 1, '2026-09-15 09:51:48', '2026-09-15 09:51:48'),
(6, 0, 'Order History', 'order-history', 'layout-dashboard', '/', 0, 1, '2026-09-15 09:52:09', '2026-09-15 09:52:09'),
(7, 6, 'All Orders', 'all-orders', '', '/', 0, 1, '2026-09-15 09:52:32', '2026-09-15 09:52:32'),
(8, 0, 'HR', 'hr', 'layout-dashboard', '/', 0, 1, '2026-09-15 09:54:36', '2026-09-15 09:54:36'),
(9, 8, 'Sales Person', 'sales-person', '', '/', 0, 1, '2026-09-15 09:54:56', '2026-09-15 09:54:56'),
(10, 0, 'Master', 'master', 'layout-dashboard', '/', 0, 1, '2026-09-15 10:05:05', '2026-09-15 10:05:05'),
(11, 10, 'Category', 'category', '', '/', 0, 1, '2026-09-15 10:05:25', '2026-09-15 10:05:25'),
(12, 10, 'Sub Category', 'sub-category', '', '/', 0, 1, '2026-09-15 10:05:43', '2026-09-15 10:05:43'),
(13, 0, 'Sub Master', 'sub-master', 'layout-dashboard', '/', 0, 1, '2026-09-15 10:05:59', '2026-09-15 10:05:59'),
(14, 13, 'Country', 'country', '', 'country', 0, 1, '2026-09-15 10:06:18', '2026-09-15 10:06:18'),
(15, 13, 'State', 'state', '', 'state', 0, 1, '2026-09-15 10:06:33', '2026-09-15 10:06:33'),
(16, 13, 'City', 'city', '', 'city', 0, 1, '2026-09-15 10:06:50', '2026-09-15 10:06:50'),
(17, 0, 'Utility', 'utility', 'layout-dashboard', '/', 0, 1, '2026-09-15 10:07:11', '2026-09-15 10:07:11'),
(18, 17, 'News', 'news', '', 'news', 0, 1, '2026-09-15 10:07:32', '2026-09-15 10:07:32'),
(19, 17, 'Banner', 'banner', '', 'banner', 0, 1, '2026-09-15 10:07:52', '2026-09-15 10:07:52'),
(20, 0, 'Customer Reports', 'customer-reports', 'layout-dashboard', '/', 0, 1, '2026-09-15 10:08:16', '2026-09-15 10:08:16'),
(21, 20, 'Inquiry Reports', 'inquiry-reports', '', '/', 0, 1, '2026-09-15 10:08:37', '2026-09-15 10:08:37'),
(22, 0, 'Sales Team Reports', 'sales-team-reports', 'layout-dashboard', '/', 0, 1, '2026-09-15 10:08:55', '2026-09-15 10:08:55'),
(23, 22, 'Daily Sales Report', 'daily-sales-report', '', '/', 0, 1, '2026-09-15 10:09:16', '2026-09-15 10:09:16'),
(24, 0, 'Chat', 'chat', 'layout-dashboard', 'chat', 0, 1, '2026-09-15 10:09:31', '2026-09-15 10:09:31'),
(25, 0, 'Remark Analysis Reports', 'remark-analysis-reports', 'layout-dashboard', '/', 0, 1, '2026-09-15 10:09:53', '2026-09-15 10:09:53'),
(26, 25, 'Remark Wise Report', 'remark-wise-report', '', '/', 0, 1, '2026-09-15 10:10:17', '2026-09-15 10:10:17');

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

DROP TABLE IF EXISTS `plan`;
CREATE TABLE IF NOT EXISTS `plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `max_team_user` int(11) DEFAULT 0,
  `max_customer` int(11) DEFAULT 0,
  `max_inquiry` int(11) DEFAULT 0,
  `panel_right` text DEFAULT NULL,
  `app_right` text DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `days` int(11) DEFAULT 0,
  `status` tinyint(4) DEFAULT 1,
  `created_by` int(11) DEFAULT 0,
  `updated_by` int(11) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan`
--

INSERT INTO `plan` (`id`, `name`, `price`, `duration`, `max_team_user`, `max_customer`, `max_inquiry`, `panel_right`, `app_right`, `to_date`, `from_date`, `days`, `status`, `created_by`, `updated_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Ocean Infotech Plan 2025', 2000.00, NULL, 2, 20, 100, '2,5,7,15,16,21,24', '', '2026-10-01', '2026-10-31', 30, 1, 0, 0, '2026-09-15 11:21:56', '2026-09-15 11:21:56', '2026-09-15 13:46:51');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT 0,
  `name` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `company_id`, `name`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 0, 'superadmin', NULL, NULL, '2026-09-15 11:24:45', '2026-09-15 11:24:45'),
(2, 1, 'Company Admin', NULL, NULL, '2026-09-15 11:25:27', '2026-09-15 11:25:27');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT 0,
  `company_id` int(11) DEFAULT 0,
  `role_id` int(11) DEFAULT 0,
  `module_id` int(11) DEFAULT 0,
  `views` tinyint(4) DEFAULT 0,
  `adds` tinyint(4) DEFAULT 0,
  `updates` tinyint(4) DEFAULT 0,
  `deletes` tinyint(4) DEFAULT 0,
  `created_by` int(11) DEFAULT 0,
  `updated_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `user_id`, `company_id`, `role_id`, `module_id`, `views`, `adds`, `updates`, `deletes`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 2, 2, 0, 0, 0, 1, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17'),
(2, 5, 1, 2, 5, 0, 0, 0, 0, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17'),
(3, 5, 1, 2, 7, 1, 1, 1, 1, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17'),
(4, 5, 1, 2, 15, 0, 0, 0, 0, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17'),
(5, 5, 1, 2, 16, 1, 1, 1, 1, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17'),
(6, 5, 1, 2, 21, 0, 0, 0, 0, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17'),
(7, 5, 1, 2, 24, 0, 0, 1, 0, 0, 0, '2026-09-16 07:17:42', '2026-09-16 07:18:17');

-- --------------------------------------------------------

--
-- Table structure for table `state`
--

DROP TABLE IF EXISTS `state`;
CREATE TABLE IF NOT EXISTS `state` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `state`
--

INSERT INTO `state` (`id`, `country_id`, `name`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, '1', 'gujarat', 1, NULL, NULL, '2026-09-14 06:42:24', '2026-09-14 06:42:24'),
(2, '1', 'utarpradesh', 1, NULL, NULL, '2026-09-14 07:01:47', '2026-09-14 09:33:29'),
(3, '2', 'gujarat', 1, NULL, NULL, '2026-09-14 07:07:50', '2026-09-14 09:45:18'),
(4, '1', 'rajasthan', 1, NULL, NULL, '2026-09-14 09:29:20', '2026-09-14 09:29:20'),
(5, '1', 'Madhyapradesh', 1, NULL, NULL, '2026-09-14 09:44:56', '2026-09-14 09:44:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT 0,
  `company_id` int(11) DEFAULT 0,
  `company_plan_id` int(11) DEFAULT 0,
  `company_parent_id` int(11) DEFAULT 0,
  `name` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `app_key` varchar(100) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `company_id`, `company_plan_id`, `company_parent_id`, `name`, `username`, `email`, `password`, `user_type`, `app_key`, `ip_address`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 0, 0, 'superadmin', 'superadmin', 'superadmin@gmail.com', '$2y$10$KGUSQBiG0tv3kkQj6OWA4OvzzAjLEaDFSW/FaNFaF03YyJ721ovrC', 'superadmin', NULL, NULL, 1, '2026-09-15 06:25:35', '2026-09-15 06:25:35'),
(5, 2, 1, 1, 0, 'ocean', 'ocean infotech', 'ocean@gmail.com', '$2y$10$YN1ydW8pvETuLnjfZM/hDe8Lhai5s7JVsvojvuUWOwB7o2yIQC89C', 'company_admin', NULL, '::1', 1, '2026-09-15 11:50:12', '2026-09-15 11:50:12');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
