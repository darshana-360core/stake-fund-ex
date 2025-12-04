-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 14, 2025 at 02:55 PM
-- Server version: 11.4.8-MariaDB-ubu2404
-- PHP Version: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dexora_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_roi_logs`
--

CREATE TABLE `contract_roi_logs` (
  `id` bigint(20) NOT NULL,
  `stake_id` int(11) DEFAULT NULL COMMENT 'from contract',
  `plan_id` int(11) DEFAULT NULL COMMENT 'days (30.. 45..)',
  `package_id` int(11) DEFAULT NULL COMMENT 'single(1), lp(2), stable(3)',
  `amount` float DEFAULT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL COMMENT 'Alpha numeric',
  `created_on` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `developer_pools`
--

CREATE TABLE `developer_pools` (
  `id` int(11) NOT NULL,
  `amount` varchar(50) DEFAULT NULL,
  `transaction_hash` varchar(250) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earning_logs`
--

CREATE TABLE `earning_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(65,18) NOT NULL,
  `flush_amount` varchar(255) DEFAULT '0',
  `tag` varchar(255) NOT NULL,
  `refrence` varchar(255) DEFAULT NULL,
  `refrence_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `isCount` int(11) NOT NULL DEFAULT 0,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `contract_stakeid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `level_earning_logs`
--

CREATE TABLE `level_earning_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(65,18) NOT NULL,
  `flush_amount` varchar(255) DEFAULT '0',
  `tag` varchar(255) NOT NULL,
  `refrence` varchar(255) DEFAULT NULL,
  `refrence_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `isCount` int(11) NOT NULL DEFAULT 0,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `level_profit_sharing`
--

CREATE TABLE `level_profit_sharing` (
  `id` int(11) NOT NULL,
  `level` varchar(255) NOT NULL,
  `level_display_name` varchar(255) NOT NULL,
  `percentage` varchar(255) NOT NULL,
  `direct` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `level_roi`
--

CREATE TABLE `level_roi` (
  `id` int(11) NOT NULL,
  `level` varchar(255) NOT NULL,
  `level_display_name` varchar(255) NOT NULL,
  `percentage` varchar(255) NOT NULL,
  `direct` int(11) NOT NULL,
  `business` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `login_type` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `device` varchar(255) DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `ip_address_2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `login_type`, `email`, `device`, `created_on`, `password`, `ip_address`, `ip_address_2`) VALUES
(1, 'FAILED', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:23:34', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(2, 'FAILED', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:27:10', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(3, 'FAILED', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:28:58', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(4, 'FAILED', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:37:46', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(5, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:44:20', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(6, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:44:37', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(7, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:46:54', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(8, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:48:06', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(9, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:50:19', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(10, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:51:13', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(11, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 09:52:26', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(12, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:57:33', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(13, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 09:57:45', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(14, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:58:02', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(15, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:58:08', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(16, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 09:58:43', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(17, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 10:00:46', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(18, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 10:01:56', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(19, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 10:04:56', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(20, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 10:05:26', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(21, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 10:08:30', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(22, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-13 10:10:11', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(23, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 10:10:27', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(24, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 10:10:34', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(25, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 10:12:57', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(26, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-13 10:14:12', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(27, 'FAILED', 'USER', '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 15:32:53', '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', '162.158.22.94', '110.226.126.92'),
(28, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:16:59', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '110.226.126.92', NULL),
(29, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:17:55', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '172.70.108.24', '110.226.126.92'),
(30, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:17:59', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '172.70.108.24', '110.226.126.92'),
(31, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:18:09', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '172.70.108.25', '110.226.126.92'),
(32, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:18:12', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '172.70.108.25', '110.226.126.92'),
(33, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:18:14', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '172.70.108.25', '110.226.126.92'),
(34, 'FAILED', 'USER', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', 'PostmanRuntime/7.48.0', '2025-10-14 04:18:35', '0xb9ad2f5e69654ec10e8812771563951bc7eaa4eb', '172.70.108.24', '110.226.126.92'),
(35, 'FAILED', 'USER', '0x71A3971b88eC5096ac161EcA020F3E034Ca0884b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:17:18', '0x71A3971b88eC5096ac161EcA020F3E034Ca0884b', '172.70.108.24', '110.226.126.92'),
(36, 'FAILED', 'USER', '0x71A3971b88eC5096ac161EcA020F3E034Ca0884b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:17:24', '0x71A3971b88eC5096ac161EcA020F3E034Ca0884b', '172.70.108.25', '110.226.126.92'),
(37, 'FAILED', 'USER', '0x71A3971b88eC5096ac161EcA020F3E034Ca0884b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:17:59', '0x71A3971b88eC5096ac161EcA020F3E034Ca0884b', '172.70.108.25', '110.226.126.92'),
(38, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:44:51', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '172.70.108.24', '110.226.126.92'),
(39, 'FAILED', 'USER', '0xFf48001188770310cCe6ca962e19793A87Bf880C', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:46:18', '0xFf48001188770310cCe6ca962e19793A87Bf880C', '172.68.234.123', '110.226.126.92'),
(40, 'FAILED', 'USER', '0x71874d5e91Ab0988DA665010C1F065ef9b69D9E9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:50:33', '0x71874d5e91Ab0988DA665010C1F065ef9b69D9E9', '162.158.22.149', '110.226.126.92'),
(41, '11', 'USER', '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:55:03', '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', '162.158.22.149', '110.226.126.92'),
(42, '11', 'USER', '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 12:55:50', '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', '172.70.108.25', '110.226.126.92'),
(43, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-14 13:01:07', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(44, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-14 13:01:22', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(45, 'FAILED', 'USER', '0x08AeC635DB26D4135656e8a8DAC7472200e6E03A', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 13:03:22', '0x08AeC635DB26D4135656e8a8DAC7472200e6E03A', '172.70.108.24', '110.226.126.92'),
(46, '2', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-14 13:04:56', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(47, 'FAILED', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-14 13:06:11', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(48, '15', 'USER', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', 'PostmanRuntime/7.48.0', '2025-10-14 13:08:16', '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', '110.226.126.92', NULL),
(49, 'FAILED', 'USER', '0x69e0D8E4eF2C6ACa1E2C85B2f4F8bA1e72236450', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 13:09:38', '0x69e0D8E4eF2C6ACa1E2C85B2f4F8bA1e72236450', '172.70.108.25', '110.226.126.92'),
(50, 'FAILED', 'USER', '0x4eBCcc8664D4e039F5E7e6de903112a6AF3b950b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 13:15:22', '0x4eBCcc8664D4e039F5E7e6de903112a6AF3b950b', '172.70.108.24', '110.226.126.92'),
(51, 'FAILED', 'USER', '0x2CB64eC9A2D973E767e6eB3b2063aFeE71bb803E', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 13:22:09', '0x2CB64eC9A2D973E767e6eB3b2063aFeE71bb803E', '172.68.234.123', '110.226.126.92');

-- --------------------------------------------------------

--
-- Table structure for table `my_team`
--

CREATE TABLE `my_team` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `team_id` varchar(255) NOT NULL,
  `sponser_id` varchar(255) DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `my_team`
--

INSERT INTO `my_team` (`id`, `user_id`, `team_id`, `sponser_id`, `level`, `created_on`) VALUES
(1, '1', '2', '1', 0, '2025-10-13 09:37:46'),
(2, '1', '11', '1', 0, '2025-10-13 15:32:53'),
(3, '2', '12', '2', 0, '2025-10-14 12:46:18'),
(4, '1', '12', '2', 0, '2025-10-14 12:46:19'),
(5, '2', '13', '2', 0, '2025-10-14 12:50:33'),
(6, '1', '13', '2', 0, '2025-10-14 12:50:33'),
(7, '11', '14', '11', 0, '2025-10-14 13:03:22'),
(8, '1', '14', '11', 0, '2025-10-14 13:03:22'),
(9, '1', '15', '1', 0, '2025-10-14 13:06:11'),
(10, '11', '16', '11', 0, '2025-10-14 13:09:38'),
(11, '1', '16', '11', 0, '2025-10-14 13:09:38'),
(12, '16', '17', '16', 0, '2025-10-14 13:15:22'),
(13, '11', '17', '16', 0, '2025-10-14 13:15:22'),
(14, '1', '17', '16', 0, '2025-10-14 13:15:22'),
(15, '2', '18', '2', 0, '2025-10-14 13:22:09'),
(16, '1', '18', '2', 0, '2025-10-14 13:22:09');

-- --------------------------------------------------------

--
-- Table structure for table `orbitx_pools`
--

CREATE TABLE `orbitx_pools` (
  `id` int(11) NOT NULL,
  `founder_pool_amount` varchar(50) NOT NULL,
  `promoter_pool_amount` varchar(50) NOT NULL,
  `marketing_pool_amount` varchar(50) NOT NULL,
  `month_pool` varchar(50) NOT NULL,
  `created_on` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `other_pools`
--

CREATE TABLE `other_pools` (
  `id` int(11) NOT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `pool` varchar(255) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `roi` varchar(255) NOT NULL,
  `days` varchar(255) NOT NULL,
  `max` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_transaction`
--

CREATE TABLE `package_transaction` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `transaction_hash` varchar(855) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `package_id` varchar(255) DEFAULT NULL,
  `lock_period` int(11) NOT NULL,
  `json` longtext DEFAULT NULL,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `isApi` int(11) NOT NULL DEFAULT 0,
  `remarks` longtext DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pay9_payments`
--

CREATE TABLE `pay9_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `fees_amount` varchar(255) NOT NULL,
  `received_amount` varchar(255) NOT NULL,
  `chain` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pins`
--

CREATE TABLE `pins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `used_by` int(11) NOT NULL,
  `for_user_id` int(11) DEFAULT 0,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `for_created_on` datetime DEFAULT NULL,
  `isAdmin` int(11) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ranking`
--

CREATE TABLE `ranking` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `eligible` varchar(255) NOT NULL,
  `direct_referral` varchar(255) DEFAULT '0',
  `account_balance` varchar(255) DEFAULT '0',
  `income` varchar(255) NOT NULL,
  `brokerage_income` int(11) NOT NULL DEFAULT 0,
  `profit_sharing` varchar(255) NOT NULL DEFAULT '0',
  `week` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rank_bonus`
--

CREATE TABLE `rank_bonus` (
  `id` int(11) NOT NULL,
  `rank_id` int(11) NOT NULL,
  `amount` decimal(11,2) NOT NULL,
  `date` date NOT NULL,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reward_bonus`
--

CREATE TABLE `reward_bonus` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `eligible` varchar(255) NOT NULL,
  `direct_referral` varchar(255) DEFAULT '0',
  `income` varchar(255) NOT NULL,
  `days` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `min_withdraw` int(11) NOT NULL,
  `admin_fees` varchar(255) NOT NULL,
  `website_name` varchar(255) DEFAULT NULL,
  `website_title` varchar(255) DEFAULT NULL,
  `withdraw_setting` int(11) NOT NULL,
  `rtx_price` varchar(50) NOT NULL DEFAULT '',
  `treasury_balance` varchar(50) NOT NULL DEFAULT '',
  `created_on` timestamp NULL DEFAULT NULL,
  `updated_on` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `refferal_code` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `reply` varchar(255) DEFAULT NULL,
  `reply_on` timestamp NULL DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_upline_paths`
--

CREATE TABLE `temp_upline_paths` (
  `id` bigint(20) NOT NULL,
  `upline_path` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_upline_paths`
--

CREATE TABLE `tmp_upline_paths` (
  `id` bigint(20) NOT NULL,
  `upline_path` varchar(255) DEFAULT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

CREATE TABLE `transfer` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `for_user_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sponser_code` varchar(255) DEFAULT NULL,
  `sponser_id` int(11) DEFAULT NULL,
  `daily_roi` varchar(255) NOT NULL DEFAULT '0',
  `direct_income` varchar(255) NOT NULL DEFAULT '0',
  `roi_income` varchar(255) NOT NULL DEFAULT '0',
  `level_income` varchar(255) NOT NULL DEFAULT '0',
  `royalty` varchar(255) NOT NULL DEFAULT '0',
  `rank_bonus` varchar(255) NOT NULL DEFAULT '0',
  `reward_bonus` varchar(255) NOT NULL DEFAULT '0',
  `club_bonus` varchar(255) NOT NULL DEFAULT '0',
  `topup_balance` varchar(255) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verified` int(11) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `refferal_code` varchar(255) NOT NULL,
  `my_team` int(11) NOT NULL DEFAULT 0,
  `my_business` varchar(255) DEFAULT '0',
  `strong_business` int(11) NOT NULL DEFAULT 0,
  `power_date` date DEFAULT NULL,
  `weak_business` int(11) NOT NULL DEFAULT 0,
  `my_direct` varchar(255) DEFAULT '0',
  `isCount` int(11) NOT NULL DEFAULT 0,
  `direct_business` varchar(255) DEFAULT '0',
  `active_team` int(11) NOT NULL DEFAULT 0,
  `active_direct` int(11) NOT NULL DEFAULT 0,
  `canWithdraw` int(11) NOT NULL DEFAULT 1,
  `withdraw_limit` int(11) NOT NULL DEFAULT 10,
  `balance` varchar(255) NOT NULL DEFAULT '0',
  `rank_id` int(11) NOT NULL DEFAULT 0,
  `rank` varchar(255) DEFAULT NULL,
  `rank_date` date DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `pg_trc_json` longtext DEFAULT NULL,
  `pg_evm_json` longtext DEFAULT NULL,
  `upline_path` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `wallet_address`, `mobile_number`, `country`, `image`, `sponser_code`, `sponser_id`, `daily_roi`, `direct_income`, `roi_income`, `level_income`, `royalty`, `rank_bonus`, `reward_bonus`, `club_bonus`, `topup_balance`, `status`, `created_on`, `email`, `password`, `verified`, `code`, `refferal_code`, `my_team`, `my_business`, `strong_business`, `power_date`, `weak_business`, `my_direct`, `isCount`, `direct_business`, `active_team`, `active_direct`, `canWithdraw`, `withdraw_limit`, `balance`, `rank_id`, `rank`, `rank_date`, `level`, `otp`, `pg_trc_json`, `pg_evm_json`, `upline_path`) VALUES
(1, 'First User', '0x8a4b086f9c80648E88bB096627D1223BD759A66F', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-13 09:31:02', NULL, 'c4ca4238a0b923820dcc509a6f75849b', NULL, NULL, '', 9, '0', 0, NULL, 0, '3', 0, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838d', NULL, NULL, NULL, '', 1, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-13 09:37:46', NULL, '7c4d0a43c8fb6df09a05061cbcbe896f', 1, '4LjaHNEDvm', '52838d', 3, '0', 0, NULL, 0, '3', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'U1 QA_STAKE_2025_10_11', '0xYvYHsUHMNOcswjilcXViQVXG0Tb2XdljOz5bTOoz', NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:16', 'u1+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$VUH4oSqIDocphCJpuNFGYOMdUzKgWIBLotzwQGB/OW2vT2yY449wu', 1, NULL, 'U1.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'U2 QA_STAKE_2025_10_11', '0x2GKTHno30ueaJJIvqGevdIU9vt04YGF4CYSYjCt6', NULL, NULL, NULL, 'U1.QA_STAKE_2025_10_11', 3, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:16', 'u2+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$ZX3LB4ULYUufPSRqp5KOFeW2it.6RUBjPN6ljJawtrzePp8Gvl5m2', 1, NULL, 'U2.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'U3 QA_STAKE_2025_10_11', '0xXwEBVPcwTOigJBK7SeSsm4Lqxd0K8Sr4vqnJPKbX', NULL, NULL, NULL, 'U2.QA_STAKE_2025_10_11', 4, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:16', 'u3+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$WWb0YsvKGPpzEDYqZf8zb.cwssmRVEoB.9mqom0EQn7Emtsd1cXia', 1, NULL, 'U3.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'U4 QA_STAKE_2025_10_11', '0xCb2kZkwci1QoHVBpcCUi95jFOreitGa4GtbtFnMQ', NULL, NULL, NULL, 'U3.QA_STAKE_2025_10_11', 5, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:16', 'u4+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$LoSVjOtQjVFl9.ChWKqTWeZ/AcwcfNBFvyrnEYvMwGjfAV7uGQgJ6', 1, NULL, 'U4.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'U5 QA_STAKE_2025_10_11', '0xoWqoJrI4KE0Z9eWcMzZ2YXN4idCGNIK0yQT4WD7P', NULL, NULL, NULL, 'U4.QA_STAKE_2025_10_11', 6, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:17', 'u5+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$YukuZfdRQV/RdtYeHt6ij.6ZqjizZqmwtPwe/Q3PrfWPTBh5PNtWq', 1, NULL, 'U5.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'U6 QA_STAKE_2025_10_11', '0xtuKPc9Yk1rcGcQdFv6FzAssGR3b8fvrI9DULAQB6', NULL, NULL, NULL, 'U5.QA_STAKE_2025_10_11', 7, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:17', 'u6+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$oW4q/F6v.y/DdsCk0UzXD.ccTqBU9/0Rde8e7sYifcJwrdaEPbwTe', 1, NULL, 'U6.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'U7 QA_STAKE_2025_10_11', '0x8Nnok8fp4ZpGKBzNvk02m6PLdaeYqD6JSxRJVILz', NULL, NULL, NULL, 'U6.QA_STAKE_2025_10_11', 8, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:17', 'u7+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$9XzpAv.LTpzIxJFVn5pHF.09APcuOcmAdngk68DjFGnP/QLQbJUia', 1, NULL, 'U7.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'U8 QA_STAKE_2025_10_11', '0xnKEVKnsklUZHrnUXppGUEGcvgO0r5j7WTOKZwuxH', NULL, NULL, NULL, 'U7.QA_STAKE_2025_10_11', 9, '0', '0', '0', '0', '0', '0', '0', '0', '10000', 1, '2025-10-13 10:55:17', 'u8+stake.QA_STAKE_2025_10_11@test.local', '$2y$12$qmHTKZbflA7oN9kSYtNkoOjaZitcc9qyTgX4rIzkEc8mzEk2CFmF6', 1, NULL, 'U8.QA_STAKE_2025_10_11', 0, '0', 0, NULL, 0, '0', 0, '0', 0, 0, 1, 10, '10000', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, NULL, '0x5eEC36681A4755742ECc57a3820143e9c8E50C95', NULL, NULL, NULL, '', 1, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-13 15:32:53', NULL, 'a680ac19893572459c24f77466319ae1', 1, 'NT0QEHOIV5', 'E50C95', 3, '0', 0, NULL, 0, '2', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, NULL, '0xFf48001188770310cCe6ca962e19793A87Bf880C', NULL, NULL, NULL, '52838d', 2, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 12:46:18', NULL, '8d5a2244b8efb06fc514671ac885dedc', 1, '3qU5apU46c', 'Bf880C', 0, '0', 0, NULL, 0, '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, NULL, '0x71874d5e91Ab0988DA665010C1F065ef9b69D9E9', NULL, NULL, NULL, '52838d', 2, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 12:50:33', NULL, 'edcdd729654f0afdcd79871817587bb2', 1, 'bRLrePLMna', '69D9E9', 0, '0', 0, NULL, 0, '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, NULL, '0x08AeC635DB26D4135656e8a8DAC7472200e6E03A', NULL, NULL, NULL, 'E50C95', 11, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 13:03:22', NULL, '78e48a09fb07b7569e4f3c399e2c5ef3', 1, 'Bn5y6WcpSi', 'e6E03A', 0, '0', 0, NULL, 0, '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, NULL, '0x61a9974C1FeE7Eb27Ca589e82588841DDb52838dnew', NULL, NULL, NULL, '', 1, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 13:06:11', NULL, '7c4d0a43c8fb6df09a05061cbcbe896f', 1, 'F9C4N4jtcp', '52838d', 0, '0', 0, NULL, 0, '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, NULL, '0x69e0D8E4eF2C6ACa1E2C85B2f4F8bA1e72236450', NULL, NULL, NULL, 'E50C95', 11, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 13:09:38', NULL, 'cafc39dfc252b623063c9b6cf8e7bd50', 1, 'lmQv7PnsZ4', '236450', 1, '0', 0, NULL, 0, '1', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, NULL, '0x4eBCcc8664D4e039F5E7e6de903112a6AF3b950b', NULL, NULL, NULL, '236450', 16, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 13:15:22', NULL, 'b50ff9574fe3cd1b4536dc2e6f6c5e86', 1, 'paqGia8e0e', '3b950b', 0, '0', 0, NULL, 0, '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, NULL, '0x2CB64eC9A2D973E767e6eB3b2063aFeE71bb803E', NULL, NULL, NULL, '52838d', 2, '0', '0', '0', '0', '0', '0', '0', '0', '0', 1, '2025-10-14 13:22:09', NULL, '0cd4795622125496deacc62b220feca4', 1, 'lZAF8rnSqE', 'bb803E', 0, '0', 0, NULL, 0, '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_plans`
--

CREATE TABLE `user_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `lock_period` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `compound_amount` varchar(255) NOT NULL DEFAULT '0',
  `roi` varchar(255) NOT NULL,
  `days` varchar(255) NOT NULL,
  `return` varchar(255) NOT NULL DEFAULT '0',
  `max_return` varchar(255) NOT NULL DEFAULT '0',
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `isSynced` varchar(255) DEFAULT '0',
  `isCount` int(11) NOT NULL DEFAULT 0,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `unique_th` varchar(255) NOT NULL,
  `coin_price` varchar(255) NOT NULL DEFAULT '0',
  `status` varchar(255) DEFAULT '1',
  `contract_stakeid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_plans`
--

INSERT INTO `user_plans` (`id`, `user_id`, `package_id`, `lock_period`, `amount`, `compound_amount`, `roi`, `days`, `return`, `max_return`, `created_on`, `isSynced`, `isCount`, `transaction_hash`, `unique_th`, `coin_price`, `status`, `contract_stakeid`) VALUES
(1, 2, 1, 0, '1', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U2_min:QA_STAKE_2025_10_11', 'stake:U2_min:QA_STAKE_2025_10_11', '2.842510', '1', 1),
(2, 5, 1, 0, '10', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U3_10:QA_STAKE_2025_10_11', 'stake:U3_10:QA_STAKE_2025_10_11', '2.842510', '1', NULL),
(3, 6, 1, 0, '100', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U4_100:QA_STAKE_2025_10_11', 'stake:U4_100:QA_STAKE_2025_10_11', '2.842510', '1', NULL),
(4, 7, 1, 0, '250', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U5_250:QA_STAKE_2025_10_11', 'stake:U5_250:QA_STAKE_2025_10_11', '2.842510', '1', NULL),
(5, 8, 1, 0, '500', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U6_500:QA_STAKE_2025_10_11', 'stake:U6_500:QA_STAKE_2025_10_11', '2.842510', '1', NULL),
(6, 9, 1, 0, '50', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U7_50_A:QA_STAKE_2025_10_11', 'stake:U7_50_A:QA_STAKE_2025_10_11', '2.842510', '1', NULL),
(7, 9, 1, 0, '25', '0', '0.35', '0', '0', '0', '2025-10-13 10:55:18', '0', 0, 'stake:U7_25_B:QA_STAKE_2025_10_11', 'stake:U7_25_B:QA_STAKE_2025_10_11', '2.842510', '1', NULL),
(8, 4, 1, 1, '1', '0', '1', '1', '0', '0', '2025-10-13 11:07:21', '0', 0, NULL, '1', '0', '1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_ranks`
--

CREATE TABLE `user_ranks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rank` varchar(255) NOT NULL,
  `week` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_stablebond_details`
--

CREATE TABLE `user_stablebond_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `whatapp_num` varchar(255) DEFAULT NULL,
  `passport_num` varchar(255) DEFAULT NULL,
  `passport_pic_front` varchar(255) DEFAULT NULL,
  `passport_pic_back` varchar(255) DEFAULT NULL,
  `passport_issue_date` varchar(255) DEFAULT NULL,
  `passport_expiry_date` varchar(255) DEFAULT NULL,
  `address_proof` varchar(255) DEFAULT NULL,
  `user_photo` varchar(255) DEFAULT NULL,
  `rank` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdraw`
--

CREATE TABLE `withdraw` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `withdraw_type` varchar(255) DEFAULT NULL,
  `amount` varchar(255) NOT NULL,
  `net_amount` varchar(255) DEFAULT NULL,
  `admin_charge` varchar(255) DEFAULT NULL,
  `daily_pool_amount` varchar(255) NOT NULL DEFAULT '0',
  `monthly_pool_amount` varchar(255) NOT NULL DEFAULT '0',
  `package_id` varchar(255) NOT NULL DEFAULT '0',
  `fees` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `claim_hash` varchar(255) DEFAULT NULL,
  `coin_price` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `processed_date` timestamp NULL DEFAULT current_timestamp(),
  `json_response` longtext DEFAULT NULL,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `isRequestSynced` int(11) NOT NULL DEFAULT 0,
  `requestResponse` text DEFAULT NULL,
  `claimResponse` text DEFAULT NULL,
  `checkClaim` int(11) NOT NULL DEFAULT 1,
  `checkRequest` int(11) NOT NULL DEFAULT 1,
  `isApi` int(11) NOT NULL DEFAULT 0,
  `signatureJson` longtext DEFAULT NULL,
  `remarks` longtext DEFAULT NULL,
  `isReverse` int(11) NOT NULL DEFAULT 0,
  `contract_stakeid` int(11) DEFAULT NULL,
  `contract_label` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `withdraw`
--

INSERT INTO `withdraw` (`id`, `user_id`, `withdraw_type`, `amount`, `net_amount`, `admin_charge`, `daily_pool_amount`, `monthly_pool_amount`, `package_id`, `fees`, `status`, `transaction_hash`, `claim_hash`, `coin_price`, `created_on`, `processed_date`, `json_response`, `isSynced`, `isRequestSynced`, `requestResponse`, `claimResponse`, `checkClaim`, `checkRequest`, `isApi`, `signatureJson`, `remarks`, `isReverse`, `contract_stakeid`, `contract_label`) VALUES
(1, 2, 'UNSTAKE', '0.0151', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 1, 'static'),
(2, 2, 'CLAIMROI', '0.0151', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 1, 'dynamic'),
(3, 2, 'UNSTAKE', '0.0158', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-15 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 1, 'dynamic'),
(4, 2, 'CLAIMROI', '0.0158', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-16 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 1, 'dynamic'),
(5, 2, 'UNSTAKE', '0.0844', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 5, ''),
(6, 2, 'CLAIMROI', '0.0844', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 6, ''),
(7, 2, 'UNSTAKE', '0.2166', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 7, ''),
(8, 2, 'CLAIMROI', '0.2166', NULL, NULL, '0', '0', '1', 0, 1, NULL, NULL, NULL, '2025-10-14 05:58:11', '2025-10-14 05:58:11', NULL, 0, 0, NULL, NULL, 1, 1, 0, NULL, NULL, 0, 8, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contract_roi_logs`
--
ALTER TABLE `contract_roi_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `developer_pools`
--
ALTER TABLE `developer_pools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_logs`
--
ALTER TABLE `earning_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level_earning_logs`
--
ALTER TABLE `level_earning_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level_profit_sharing`
--
ALTER TABLE `level_profit_sharing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level_roi`
--
ALTER TABLE `level_roi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `my_team`
--
ALTER TABLE `my_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orbitx_pools`
--
ALTER TABLE `orbitx_pools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `other_pools`
--
ALTER TABLE `other_pools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_transaction`
--
ALTER TABLE `package_transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay9_payments`
--
ALTER TABLE `pay9_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pins`
--
ALTER TABLE `pins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ranking`
--
ALTER TABLE `ranking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rank_bonus`
--
ALTER TABLE `rank_bonus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reward_bonus`
--
ALTER TABLE `reward_bonus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temp_upline_paths`
--
ALTER TABLE `temp_upline_paths`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tmp_upline_paths`
--
ALTER TABLE `tmp_upline_paths`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transfer`
--
ALTER TABLE `transfer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wallet_address` (`wallet_address`),
  ADD KEY `id` (`id`,`wallet_address`,`refferal_code`),
  ADD KEY `sponser_id` (`sponser_id`);

--
-- Indexes for table `user_plans`
--
ALTER TABLE `user_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_th` (`unique_th`),
  ADD KEY `id` (`id`,`user_id`,`unique_th`);

--
-- Indexes for table `user_ranks`
--
ALTER TABLE `user_ranks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_stablebond_details`
--
ALTER TABLE `user_stablebond_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdraw`
--
ALTER TABLE `withdraw`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `claim_hash` (`claim_hash`),
  ADD KEY `id` (`id`,`user_id`,`transaction_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract_roi_logs`
--
ALTER TABLE `contract_roi_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `developer_pools`
--
ALTER TABLE `developer_pools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `earning_logs`
--
ALTER TABLE `earning_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `level_earning_logs`
--
ALTER TABLE `level_earning_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `level_profit_sharing`
--
ALTER TABLE `level_profit_sharing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `level_roi`
--
ALTER TABLE `level_roi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `my_team`
--
ALTER TABLE `my_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `orbitx_pools`
--
ALTER TABLE `orbitx_pools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `other_pools`
--
ALTER TABLE `other_pools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_transaction`
--
ALTER TABLE `package_transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pay9_payments`
--
ALTER TABLE `pay9_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pins`
--
ALTER TABLE `pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ranking`
--
ALTER TABLE `ranking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rank_bonus`
--
ALTER TABLE `rank_bonus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_bonus`
--
ALTER TABLE `reward_bonus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temp_upline_paths`
--
ALTER TABLE `temp_upline_paths`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfer`
--
ALTER TABLE `transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_plans`
--
ALTER TABLE `user_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_ranks`
--
ALTER TABLE `user_ranks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_stablebond_details`
--
ALTER TABLE `user_stablebond_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdraw`
--
ALTER TABLE `withdraw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
