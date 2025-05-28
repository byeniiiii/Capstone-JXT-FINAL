-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 07:29 AM
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
-- Database: `tailor_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('customer','staff','manager','admin','sublimator') NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `user_type`, `action_type`, `description`, `ip_address`, `created_at`) VALUES
(1, 18, 'customer', 'order_decline', 'Order #AFRPA declined: way klaro', NULL, '2025-04-22 15:58:38'),
(2, 18, 'customer', 'order_decline', 'Order #TOHUXNP declined: way klaro', NULL, '2025-04-22 16:12:07'),
(3, 18, 'customer', 'order_decline', 'Order #3U739 declined: No amount given', NULL, '2025-05-02 01:21:38'),
(4, 18, 'customer', 'order_decline', 'Order #LSCD8 declined: walay klaro', NULL, '2025-05-05 08:42:11'),
(5, 3, 'admin', 'order_approval', 'Order #7LWIC approved', NULL, '2025-05-20 22:48:16'),
(6, 3, 'admin', 'order_decline', 'Order #NEWT7 declined: asdsad', NULL, '2025-05-20 22:48:34'),
(7, 3, 'admin', 'order_approval', 'Order #03MEJ approved', NULL, '2025-05-20 22:54:11'),
(8, 3, 'admin', 'order_decline', 'Order #648XY declined: sa', NULL, '2025-05-20 22:54:17'),
(9, 3, 'admin', 'order_approval', 'Order #6EWNK approved', NULL, '2025-05-20 22:58:08'),
(10, 3, 'admin', 'order_approval', 'Order #SRXNM approved', NULL, '2025-05-20 23:01:44'),
(11, 3, 'admin', 'order_approval', 'Order #VV4VO approved', NULL, '2025-05-20 23:06:36'),
(12, 3, 'admin', 'order_approval', 'Order #KC5S9 approved', NULL, '2025-05-20 23:06:43'),
(13, 3, 'admin', 'order_approval', 'Order #IZOIP approved', NULL, '2025-05-20 23:07:22'),
(14, 3, 'admin', 'order_approval', 'Order #BGF9D approved', NULL, '2025-05-20 23:07:50'),
(15, 3, 'admin', 'payment_confirmation', 'Payment #32 confirmed for Order #HXNMH', NULL, '2025-05-20 23:43:41'),
(16, 3, 'admin', 'payment_confirmation', 'Payment #31 confirmed for Order #88WDD', NULL, '2025-05-20 23:44:27'),
(17, 3, 'admin', 'payment_confirmation', 'Payment #30 confirmed for Order #ZRQ32', NULL, '2025-05-20 23:44:32'),
(18, 3, 'admin', 'payment_confirmation', 'Payment #29 confirmed for Order #XTK7G', NULL, '2025-05-20 23:44:37'),
(19, 3, 'admin', 'payment_confirmation', 'Payment #28 confirmed for Order #LI5GM', NULL, '2025-05-20 23:44:42'),
(20, 3, 'admin', 'payment_confirmation', 'Payment #27 confirmed for Order #G9YIL', NULL, '2025-05-20 23:44:56'),
(21, 3, 'admin', 'payment_confirmation', 'Payment #26 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:50:34'),
(22, 3, 'admin', 'payment_confirmation', 'Payment #25 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:51:20'),
(23, 3, 'admin', 'payment_confirmation', 'Payment #19 confirmed for Order #EOPR0', NULL, '2025-05-20 23:51:31'),
(24, 3, 'admin', 'payment_confirmation', 'Payment #24 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:53:02'),
(25, 3, 'admin', 'payment_confirmation', 'Payment #23 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:53:06'),
(26, 3, 'admin', 'payment_confirmation', 'Payment #22 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:53:13'),
(27, 3, 'admin', 'payment_confirmation', 'Payment #21 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:53:15'),
(28, 3, 'admin', 'payment_confirmation', 'Payment #20 confirmed for Order #ZBUI0', NULL, '2025-05-20 23:53:18'),
(29, 3, 'admin', 'payment_rejection', 'Payment #17 rejected for Order #RNSWI', NULL, '2025-05-20 23:53:32'),
(30, 3, 'admin', 'order_approval', 'Order #M766L approved', NULL, '2025-05-20 23:53:41'),
(31, 3, 'admin', 'order_approval', 'Order #L1EJL approved', NULL, '2025-05-20 23:53:43'),
(32, 3, 'admin', 'order_approval', 'Order #TYGNB approved', NULL, '2025-05-20 23:53:47'),
(33, 3, 'admin', 'order_approval', 'Order #JTU8A approved', NULL, '2025-05-20 23:53:50'),
(34, 3, 'admin', 'order_approval', 'Order #NFAC6 approved', NULL, '2025-05-20 23:53:53'),
(35, 3, 'admin', 'order_approval', 'Order #HXNMH approved', NULL, '2025-05-20 23:53:57'),
(36, 3, 'admin', 'order_approval', 'Order #U4JDW approved', NULL, '2025-05-20 23:54:00'),
(37, 3, 'admin', 'payment_confirmation', 'Payment #16 confirmed for Order #RNSWI', NULL, '2025-05-21 00:08:02'),
(38, 3, 'admin', 'payment_confirmation', 'Payment #13 confirmed for Order #AOQWW', NULL, '2025-05-21 00:08:04'),
(39, 3, 'admin', 'order_approval', 'Order #HU9YE approved', NULL, '2025-05-21 00:08:13'),
(40, 3, 'admin', 'order_approval', 'Order #HK21X approved', NULL, '2025-05-21 00:08:16'),
(41, 3, 'admin', 'order_approval', 'Order #OEO3O approved', NULL, '2025-05-21 00:08:19'),
(42, 3, 'admin', 'order_approval', 'Order #L1DI5 approved', NULL, '2025-05-21 00:08:22'),
(43, 3, 'admin', 'order_approval', 'Order #ZEDWM approved', NULL, '2025-05-21 00:08:24'),
(44, 3, 'admin', 'order_approval', 'Order #7RI9Q approved', NULL, '2025-05-21 00:08:27'),
(45, 3, 'admin', 'order_approval', 'Order #B566A approved', NULL, '2025-05-21 00:08:29'),
(46, 3, 'admin', 'order_approval', 'Order #89ISO approved', NULL, '2025-05-21 00:08:32'),
(47, 3, 'admin', 'order_approval', 'Order #684KL approved', NULL, '2025-05-21 00:08:35'),
(48, 3, 'admin', 'order_approval', 'Order #DW0OP approved', NULL, '2025-05-21 00:08:38'),
(49, 3, 'admin', 'order_approval', 'Order #3XCG9 approved', NULL, '2025-05-21 00:08:41'),
(50, 3, 'admin', 'order_approval', 'Order #QN88R approved', NULL, '2025-05-21 00:08:43'),
(51, 3, 'admin', 'payment_confirmation', 'Payment #7 confirmed for Order #DYRHS', NULL, '2025-05-21 00:08:50'),
(52, 3, 'admin', 'payment_confirmation', 'Payment #6 confirmed for Order #17F3B', NULL, '2025-05-21 00:08:54'),
(53, 3, 'admin', 'payment_confirmation', 'Payment #5 confirmed for Order #B1PVL', NULL, '2025-05-21 00:08:57'),
(54, 3, 'admin', 'payment_confirmation', 'Payment #4 confirmed for Order #PJP9N', NULL, '2025-05-21 00:08:59'),
(55, 3, 'admin', 'order_approval', 'Order #H34KX approved', NULL, '2025-05-21 01:52:20'),
(56, 3, 'admin', 'payment_confirmation', 'Payment #33 confirmed for Order #HU9YE', NULL, '2025-05-21 01:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `alterations`
--

CREATE TABLE `alterations` (
  `alteration_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `alteration_type` varchar(100) NOT NULL,
  `measurement_method` enum('upload','manual') NOT NULL,
  `measurements` text DEFAULT NULL,
  `measurement_file` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alterations`
--

INSERT INTO `alterations` (`alteration_id`, `order_id`, `alteration_type`, `measurement_method`, `measurements`, `measurement_file`, `instructions`) VALUES
(1, 'OP0M0', 'alterations', 'manual', '{\"notes\":\"Trial\\n\"}', NULL, NULL),
(2, 'B2V53', 'repairs', 'manual', '{\"notes\":\"Trial 2\\n\"}', NULL, NULL),
(3, '15B5T', 'alterations', 'manual', '{\"notes\":\"Test 3\"}', NULL, NULL),
(4, 'EOPR0', 'alterations', 'manual', '{\"notes\":\"Test 6\"}', NULL, NULL),
(5, 'LI5GM', 'custom', 'manual', '{\"chest\":\"55\",\"waist\":\"55\",\"hips\":\"55\",\"shoulders\":\"55\",\"sleeves\":\"55\",\"length\":\"55\"}', NULL, NULL),
(6, 'G9YIL', 'custom', 'manual', '{\"chest\":\"11\",\"waist\":\"11\",\"hips\":\"11\",\"shoulders\":\"11\",\"sleeves\":\"11\",\"length\":\"11\",\"notes\":\"W\\n\"}', NULL, NULL),
(7, '05R84', 'custom made', 'manual', '{\"chest\":\"11\",\"waist\":\"11\",\"hips\":\"1111\",\"shoulders\":\"11\",\"sleeves\":\"11\",\"length\":\"11\"}', NULL, NULL),
(8, '7RI9Q', 'alterations', 'upload', '\"{\\\"notes\\\":\\\"Test\\\"}\"', '../../uploads/measurements/measurement_7RI9Q_1747577878.jpg', NULL),
(9, 'ZEDWM', 'repairs', 'upload', '\"{\\\"notes\\\":\\\"Sjh\\\"}\"', '../../uploads/measurements/measurement_ZEDWM_1747577914.jpg', NULL),
(10, 'L1DI5', 'custom made', 'upload', '\"{\\\"notes\\\":\\\"Jsbs\\\"}\"', '../../uploads/measurements/measurement_L1DI5_1747579611.jpg', NULL),
(11, 'OEO3O', 'custom made', '', '{\"notes\":\"\"}', NULL, NULL),
(12, 'U4JDW', 'custom made', 'manual', '{\"chest\":\"3\",\"waist\":\"3\",\"hips\":\"3\",\"shoulders\":\"3\",\"sleeves\":\"3\",\"length\":\"3\"}', NULL, NULL),
(13, 'HXNMH', 'repairs', 'upload', '\"{}\"', 'uploads/measurements/measurement_HXNMH_1747584108.jpg', NULL),
(17, 'NFAC6', 'alterations', 'manual', '{\"chest\":\"3\",\"waist\":\"56\",\"hips\":\"25\",\"shoulders\":\"16\",\"sleeves\":\"18\",\"length\":\"15\",\"notes\":\"\"}', NULL, NULL),
(18, 'BGF9D', 'custom made', 'manual', '{\"chest\":\"91\",\"waist\":\"100\",\"hips\":\"25\",\"shoulders\":\"61\",\"sleeves\":\"18\",\"length\":\"199\",\"notes\":\"\"}', NULL, NULL),
(19, 'IZOIP', 'alterations', 'upload', '\"{\\\"chest\\\":\\\"0\\\",\\\"waist\\\":\\\"0\\\",\\\"hips\\\":\\\"0\\\",\\\"shoulders\\\":\\\"0\\\",\\\"sleeves\\\":\\\"0\\\",\\\"length\\\":\\\"0\\\",\\\"notes\\\":\\\"Hrh\\\"}\"', 'uploads/measurements/measurement_IZOIP_1747651936.jpg', NULL),
(20, 'TYGNB', 'custom made', 'manual', '\"See attached file\"', NULL, NULL),
(21, 'JTU8A', 'alterations', 'manual', '\"Manual measurements provided\"', NULL, NULL),
(22, 'L1EJL', 'alterations', 'manual', '\"Seamstress appointment scheduled\"', NULL, NULL),
(23, 'M766L', 'custom made', 'upload', '\"{\\\"chest\\\":\\\"0\\\",\\\"waist\\\":\\\"0\\\",\\\"hips\\\":\\\"0\\\",\\\"shoulder_width\\\":\\\"0\\\",\\\"sleeve_length\\\":\\\"0\\\",\\\"garment_length\\\":\\\"0\\\",\\\"notes\\\":\\\"Bab\\\"}\"', 'uploads/measurements/measurement_M766L_1747784849.jpg', NULL),
(24, 'HK21X', 'custom made', 'upload', '\"{\\\"chest\\\":\\\"0\\\",\\\"waist\\\":\\\"0\\\",\\\"hips\\\":\\\"0\\\",\\\"shoulder_width\\\":\\\"0\\\",\\\"sleeve_length\\\":\\\"0\\\",\\\"garment_length\\\":\\\"0\\\",\\\"notes\\\":\\\"\\\"}\"', 'uploads/measurements/measurement_HK21X_1747785355.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `username`, `password`, `email`, `first_name`, `last_name`, `phone_number`, `address`, `created_at`, `updated_at`) VALUES
(2093, 'lando', '$2y$10$ASexsx2gpxPbiUg3CWwNHeF9C4iDllxCYemcv6diIbRCMX7KBKk7C', 'landz@gmail.com', 'Rolando', 'Teopes', '09563674567', 'Palinpinon, Valencia, Negros Oriental', '2025-03-24 14:22:45', '2025-03-24 14:22:45'),
(2452, 'kyle', '$2y$10$N9ZyF6.yW4Jb6oOuwXr2SeM5Lk6HgWsMkWnq.5JFKawDJ3mPOSSvG', 'k@k', 'kyle', 'cadimas', '9999', 'basay', '2025-05-10 16:41:26', '2025-05-10 16:41:26'),
(8739, 'biyah', '$2y$10$by4vg6mmHatpuVWbRcOCoexzJgYPBQiqr/y5Bo1L5YWA0FhceEclO', 'biyah@gmail.com', 'Vieyah', 'Vicente', '09365743627', 'Purok 4, Balugo, Valencia, Negros Oriental', '2025-04-19 06:52:25', '2025-04-19 06:52:25'),
(8740, '', '$2y$10$BGn8TQqtQxrfckKm9qtwg.eV.dU5KioIbcxEyjDsOriMKX5O6kElW', 'lalay@gmail.com', 'Lalay', 'Crocodile', '09674553432', 'Purok 4, Balugo, Valencia, Negros Oriental', '2025-05-05 06:13:37', '2025-05-05 06:13:37'),
(8742, 'gojosatoruc69407', '$2y$10$nfCptmTk3VZ9k3kTDNZ.PutjV5qF1GTOq7RhX5tW3tnk1p6PVP/3e', 'gojo@gmail.com', 'Gojo', 'Satoru', '09664557876', 'Purok 4, Balugo, Valencia, Negros Oriental', '2025-05-05 06:45:00', '2025-05-05 06:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `custom_made`
--

CREATE TABLE `custom_made` (
  `custom_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `design_details` text NOT NULL,
  `body_measurement_file` varchar(255) DEFAULT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `reference_image` varchar(255) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_made`
--

INSERT INTO `custom_made` (`custom_id`, `order_id`, `design_details`, `body_measurement_file`, `fabric_type`, `quantity`, `reference_image`, `special_instructions`) VALUES
(1, '05R84', '', NULL, 'customer', 1, NULL, NULL),
(2, 'L1DI5', 'Jsbs', NULL, 'customer', 1, NULL, NULL),
(3, 'OEO3O', '', NULL, 'customer', 1, NULL, NULL),
(4, 'U4JDW', '', NULL, 'customer', 1, NULL, NULL),
(5, 'BGF9D', '', NULL, 'customer', 1, NULL, NULL),
(6, 'TYGNB', 'Tetsh', NULL, 'customer', 1, NULL, NULL),
(7, 'M766L', 'Bab', NULL, 'customer', 1, NULL, NULL),
(8, 'HK21X', '', NULL, 'customer', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `declined_orders`
--

CREATE TABLE `declined_orders` (
  `decline_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `reason` text NOT NULL,
  `declined_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `declined_orders`
--

INSERT INTO `declined_orders` (`decline_id`, `order_id`, `reason`, `declined_by`, `created_at`) VALUES
(1, 'NEWT7', 'asdsad', 3, '2025-05-20 22:48:34'),
(2, '648XY', 'sa', 3, '2025-05-20 22:54:17');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `ip_address`, `identifier`, `success`, `attempt_time`) VALUES
(1, '192.168.1.45', 'kyle', 1, '2025-05-13 11:32:16'),
(2, '192.168.1.46', 'lando', 1, '2025-05-13 11:32:35'),
(3, '192.168.1.45', 'lando', 1, '2025-05-13 11:32:48'),
(4, '192.168.1.45', 'lando', 1, '2025-05-13 11:35:40'),
(5, '192.168.1.45', 'lando', 1, '2025-05-13 11:35:47'),
(6, '192.168.1.45', 'lando', 1, '2025-05-13 11:40:20'),
(7, '192.168.1.45', 'k@k', 0, '2025-05-13 14:34:00'),
(8, '192.168.1.45', 'k@k', 0, '2025-05-13 14:34:09'),
(9, '192.168.1.45', 'biyah', 1, '2025-05-13 14:34:22'),
(10, '192.168.1.45', 'lando', 0, '2025-05-13 14:35:56'),
(11, '192.168.1.45', 'lando', 1, '2025-05-13 14:36:06'),
(12, '192.168.1.45', 'lando', 0, '2025-05-13 14:44:42'),
(13, '192.168.1.45', 'lando', 0, '2025-05-13 14:44:50'),
(14, '192.168.1.45', 'lando', 1, '2025-05-13 14:44:59'),
(15, '192.168.1.45', 'lando', 1, '2025-05-13 14:47:22'),
(16, '192.168.1.45', 'lando', 1, '2025-05-13 14:48:59'),
(17, '192.168.1.45', 'lando', 1, '2025-05-13 14:49:35'),
(18, '192.168.1.45', 'lando', 1, '2025-05-13 14:51:50'),
(19, '192.168.1.45', 'biyah', 1, '2025-05-13 14:52:44'),
(20, '192.168.1.45', 'biyah', 0, '2025-05-13 14:52:50'),
(21, '192.168.1.45', 'biyah', 1, '2025-05-13 14:53:59'),
(22, '192.168.1.45', 'biyah', 1, '2025-05-13 14:54:06'),
(23, '192.168.1.45', 'biyah', 1, '2025-05-13 14:54:09'),
(24, '192.168.1.45', 'lando', 0, '2025-05-13 14:54:49'),
(25, '192.168.1.45', 'lando', 1, '2025-05-13 14:54:58'),
(26, '192.168.1.45', 'lando', 1, '2025-05-13 14:56:44'),
(27, '192.168.1.45', 'lando', 0, '2025-05-13 14:58:18'),
(28, '192.168.1.45', 'lando', 1, '2025-05-13 14:58:23'),
(29, '192.168.1.45', 'lando', 1, '2025-05-13 14:58:40'),
(30, '192.168.1.45', 'lando', 1, '2025-05-13 14:58:43'),
(31, '192.168.1.45', 'lando', 1, '2025-05-13 14:59:35'),
(32, '192.168.1.45', 'lando', 1, '2025-05-13 14:59:38'),
(33, '192.168.1.45', 'lando', 1, '2025-05-13 15:00:05'),
(34, '192.168.1.45', 'lando', 1, '2025-05-13 15:00:38'),
(35, '192.168.1.45', 'lando', 1, '2025-05-13 15:00:48'),
(36, '192.168.1.45', 'lando', 1, '2025-05-13 15:00:51'),
(37, '192.168.1.51', 'testuser', 0, '2025-05-13 16:11:49'),
(38, '192.168.1.45', 'lando', 1, '2025-05-13 16:19:58'),
(39, '192.168.1.45', 'kyle', 1, '2025-05-13 16:59:37'),
(40, '192.168.196.243', 'kyle', 1, '2025-05-14 08:57:23'),
(41, '192.168.196.243', 'kyle', 1, '2025-05-14 09:49:46'),
(42, '192.168.196.243', 'kyle ', 1, '2025-05-14 09:50:17'),
(43, '192.168.196.243', 'kyle', 0, '2025-05-14 10:24:14'),
(44, '192.168.196.243', 'kyle', 0, '2025-05-14 10:24:18'),
(45, '192.168.196.243', 'kyle', 0, '2025-05-14 10:24:28'),
(46, '192.168.196.243', 'kyle', 0, '2025-05-14 10:25:17'),
(47, '192.168.196.243', 'kyle', 1, '2025-05-14 10:27:53'),
(48, '192.168.196.243', 'kyle', 1, '2025-05-14 10:33:09'),
(49, '192.168.196.243', 'kyle', 1, '2025-05-14 10:46:02'),
(50, '192.168.196.243', 'k@k', 0, '2025-05-15 05:23:01'),
(51, '192.168.196.243', 'k@k', 0, '2025-05-15 05:23:05'),
(52, '192.168.43.143', 'kyle ', 1, '2025-05-15 05:29:45'),
(53, '192.168.101.5', 'kyle', 1, '2025-05-17 16:04:21'),
(54, '192.168.101.3', 'kyle', 0, '2025-05-17 16:24:48'),
(55, '192.168.101.3', 'kyle', 1, '2025-05-17 16:25:09'),
(56, '192.168.102.232', 'kyle', 1, '2025-05-19 10:51:51'),
(57, '192.168.101.5', 'kyle ', 1, '2025-05-20 21:03:33'),
(58, '192.168.101.5', 'kyle ', 1, '2025-05-20 21:27:02'),
(59, '192.168.101.5', 'kyle ', 1, '2025-05-20 21:33:53'),
(60, '192.168.101.5', 'kyle ', 1, '2025-05-20 21:38:25'),
(61, '192.168.168.154', 'kyle', 1, '2025-05-21 01:51:08');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `note_id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`note_id`, `order_id`, `user_id`, `note`, `created_at`) VALUES
(1, 'AOQWW', 18, 'Order marked as ready for pickup', '2025-05-09 07:56:03'),
(2, 'AOQWW', 19, 'ok nani', '2025-05-09 09:08:36'),
(3, 'G9YIL', 18, 'Order moved to production.', '2025-05-14 18:52:28'),
(4, 'G9YIL', 18, 'Order marked as ready for pickup', '2025-05-14 18:58:18'),
(5, '2C4QQ', 19, 'Order marked as ready for pickup', '2025-05-15 03:59:24'),
(6, 'HXNMH', 3, 'Payment of ₱300.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:43:41'),
(7, '88WDD', 3, 'Payment of ₱400.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:44:27'),
(8, 'ZRQ32', 3, 'Payment of ₱400.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:44:32'),
(9, 'XTK7G', 3, 'Payment of ₱400.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:44:37'),
(10, 'LI5GM', 3, 'Payment of ₱1,500.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:44:42'),
(11, 'G9YIL', 3, 'Payment of ₱1,500.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:44:56'),
(12, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:50:34'),
(13, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:51:20'),
(14, 'EOPR0', 3, 'Payment of ₱500.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:51:31'),
(15, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:53:02'),
(16, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:53:06'),
(17, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:53:13'),
(18, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:53:15'),
(19, 'ZBUI0', 3, 'Payment of ₱75.00 confirmed by Vieny Lou Vicente', '2025-05-21 07:53:18'),
(20, 'RNSWI', 3, 'Payment rejected: asrde', '2025-05-21 07:53:32'),
(21, 'RNSWI', 3, 'Payment of ₱200.00 confirmed by Vieny Lou Vicente', '2025-05-21 08:08:02'),
(22, 'AOQWW', 3, 'Payment of ₱400.00 confirmed by Vieny Lou Vicente', '2025-05-21 08:08:04'),
(23, 'DYRHS', 3, 'Payment of ₱225.00 confirmed by Vieny Lou Vicente', '2025-05-21 08:08:50'),
(24, '17F3B', 3, 'Payment of ₱200.00 confirmed by Vieny Lou Vicente', '2025-05-21 08:08:54'),
(25, 'B1PVL', 3, 'Payment of ₱225.00 confirmed by Vieny Lou Vicente', '2025-05-21 08:08:57'),
(26, 'PJP9N', 3, 'Payment of ₱0.00 confirmed by Vieny Lou Vicente', '2025-05-21 08:08:59'),
(27, 'HU9YE', 3, 'Payment of ₱450.00 confirmed by Vieny Lou Vicente', '2025-05-21 09:52:43'),
(28, 'HU9YE', 18, 'Order moved to production.', '2025-05-21 10:09:41'),
(29, 'G9YIL', 18, 'Order marked as completed', '2025-05-21 10:09:51'),
(30, 'HK21X', 18, 'Order moved to production.', '2025-05-21 10:19:48'),
(31, 'HK21X', 18, 'Order is ready for pickup.', '2025-05-21 10:20:10');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `title`, `customer_id`, `order_id`, `message`, `is_read`, `created_at`) VALUES
(1, 'Order Declined', 8739, 'TOHUXNP', 'Your order #TOHUXNP was declined: way klaro', 1, '2025-04-22 16:12:07'),
(2, 'Order Status Update', 2093, '17F3B', 'Your order #17F3B is now being processed.', 1, '2025-05-02 00:14:20'),
(3, 'Order Declined', 2093, '3U739', 'Your order #3U739 was declined: No amount given', 1, '2025-05-02 01:21:38'),
(4, 'Payment Notification', 2093, '2C4QQ', 'New payment submitted for Order #2C4QQ', 1, '2025-05-05 04:35:02'),
(5, 'Order Declined', 2093, 'LSCD8', 'Your order #LSCD8 was declined: walay klaro', 1, '2025-05-05 08:42:11'),
(6, 'Payment Notification', 2093, 'AOQWW', 'New payment submitted for Order #AOQWW', 1, '2025-05-05 08:43:53'),
(9, 'Order Ready for Pickup', 2093, 'AOQWW', 'Your order #AOQWW is now ready for pickup.', 1, '2025-05-08 23:56:03'),
(10, 'Payment Confirmed', 2093, 'AOQWW', 'Your payment of ₱400.00 for Order #AOQWW has been confirmed.', 1, '2025-05-09 01:08:36'),
(11, 'Payment Confirmed', 2093, '2C4QQ', 'Your payment of ₱50.00 for Order #2C4QQ has been confirmed.', 1, '2025-05-09 01:25:49'),
(12, 'Payment Rejected', 2093, '2C4QQ', 'Your payment for Order #2C4QQ was rejected: ayaw rani', 1, '2025-05-09 01:26:04'),
(13, 'Payment Received', 2093, 'AOQWW', 'Your payment of ₱400.00 for order #AOQWW has been received. Thank you!', 1, '2025-05-09 11:09:33'),
(15, 'Payment Verification Required', 2093, 'RNSWI', 'New payment submitted for Order #RNSWI requires verification', 1, '2025-05-09 11:49:13'),
(16, 'Payment Verification Required', 2093, 'RNSWI', 'New payment submitted for Order #RNSWI requires verification', 1, '2025-05-09 12:10:46'),
(17, 'Payment Verification Required', 2452, 'IYGVD', 'New payment submitted for Order #IYGVD requires verification', 1, '2025-05-14 08:15:48'),
(18, 'Payment Confirmed', 2452, 'IYGVD', 'Your payment of ₱450.00 for Order #IYGVD has been confirmed.', 1, '2025-05-14 08:17:02'),
(19, 'Payment Verification Required', 2452, 'EOPR0', 'New payment submitted for Order #EOPR0 requires verification', 1, '2025-05-14 08:55:18'),
(20, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:12:43'),
(21, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:16:18'),
(22, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:19:21'),
(23, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:25:36'),
(24, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:25:59'),
(25, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:28:19'),
(26, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 10:09:13'),
(27, 'Payment Verification Required', 2452, 'G9YIL', 'New payment submitted for Order #G9YIL requires verification', 1, '2025-05-14 10:51:18'),
(28, 'Order In Process', 2452, 'G9YIL', 'Your order #G9YIL is now being processed.', 1, '2025-05-14 10:52:28'),
(29, 'Payment Verification Required', 2452, 'LI5GM', 'New payment submitted for Order #LI5GM requires verification', 1, '2025-05-14 10:53:47'),
(30, 'Order Ready for Pickup', 2452, 'G9YIL', 'Your order #G9YIL is now ready for pickup.', 1, '2025-05-14 10:58:18'),
(31, 'Payment Verification Required', 2452, 'XTK7G', 'New payment submitted for Order #XTK7G requires verification', 1, '2025-05-14 18:47:20'),
(32, 'Payment Verification Required', 2452, 'ZRQ32', 'New payment submitted for Order #ZRQ32 requires verification', 1, '2025-05-14 18:51:38'),
(33, 'Order Ready for Pickup', 2093, '2C4QQ', 'Your order #2C4QQ is now ready for pickup.', 0, '2025-05-14 19:59:24'),
(34, 'Payment Verification Required', 2452, '88WDD', 'New payment submitted for Order #88WDD requires verification', 1, '2025-05-17 17:40:53'),
(35, 'Payment Verification Required', 2452, 'HXNMH', 'New payment submitted for Order #HXNMH requires verification', 1, '2025-05-18 16:04:58'),
(36, 'Order Approved', 2452, '7LWIC', 'Your order #7LWIC has been approved.', 1, '2025-05-20 22:48:16'),
(37, 'Order Declined', 2452, 'NEWT7', 'Your order #NEWT7 was declined: asdsad', 1, '2025-05-20 22:48:34'),
(38, 'Order Approved', 2452, '03MEJ', 'Your order #03MEJ has been approved.', 1, '2025-05-20 22:54:11'),
(39, 'Order Declined', 2452, '648XY', 'Your order #648XY was declined: sa', 1, '2025-05-20 22:54:17'),
(40, 'Order Approved', 2452, '6EWNK', 'Your order #6EWNK has been approved.', 1, '2025-05-20 22:58:08'),
(41, 'Order Approved', 2452, 'SRXNM', 'Your order #SRXNM has been approved.', 1, '2025-05-20 23:01:44'),
(42, 'Order Approved', 2452, 'VV4VO', 'Your order #VV4VO has been approved.', 1, '2025-05-20 23:06:36'),
(43, 'Order Approved', 2093, 'KC5S9', 'Your order #KC5S9 has been approved.', 0, '2025-05-20 23:06:43'),
(44, 'Order Approved', 2452, 'IZOIP', 'Your order #IZOIP has been approved.', 1, '2025-05-20 23:07:22'),
(45, 'Order Approved', 2452, 'BGF9D', 'Your order #BGF9D has been approved.', 1, '2025-05-20 23:07:50'),
(46, 'Payment Confirmed', 2452, 'HXNMH', 'Your payment of ₱300.00 for Order #HXNMH has been confirmed.', 1, '2025-05-20 23:43:41'),
(47, 'Payment Confirmed', 2452, '88WDD', 'Your payment of ₱400.00 for Order #88WDD has been confirmed.', 1, '2025-05-20 23:44:27'),
(48, 'Payment Confirmed', 2452, 'ZRQ32', 'Your payment of ₱400.00 for Order #ZRQ32 has been confirmed.', 1, '2025-05-20 23:44:32'),
(49, 'Payment Confirmed', 2452, 'XTK7G', 'Your payment of ₱400.00 for Order #XTK7G has been confirmed.', 1, '2025-05-20 23:44:37'),
(50, 'Payment Confirmed', 2452, 'LI5GM', 'Your payment of ₱1,500.00 for Order #LI5GM has been confirmed.', 1, '2025-05-20 23:44:42'),
(51, 'Payment Confirmed', 2452, 'G9YIL', 'Your payment of ₱1,500.00 for Order #G9YIL has been confirmed.', 1, '2025-05-20 23:44:56'),
(52, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:50:34'),
(53, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:51:20'),
(54, 'Payment Confirmed', 2452, 'EOPR0', 'Your payment of ₱500.00 for Order #EOPR0 has been confirmed.', 1, '2025-05-20 23:51:31'),
(55, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:53:02'),
(56, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:53:06'),
(57, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:53:13'),
(58, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:53:15'),
(59, 'Payment Confirmed', 2093, 'ZBUI0', 'Your payment of ₱75.00 for Order #ZBUI0 has been confirmed.', 0, '2025-05-20 23:53:18'),
(60, 'Payment Rejected', 2093, 'RNSWI', 'Your payment for Order #RNSWI was rejected: asrde', 0, '2025-05-20 23:53:32'),
(61, 'Order Approved', 2452, 'M766L', 'Your order #M766L has been approved.', 1, '2025-05-20 23:53:41'),
(62, 'Order Approved', 2452, 'L1EJL', 'Your order #L1EJL has been approved.', 1, '2025-05-20 23:53:43'),
(63, 'Order Approved', 2452, 'TYGNB', 'Your order #TYGNB has been approved.', 1, '2025-05-20 23:53:47'),
(64, 'Order Approved', 2452, 'JTU8A', 'Your order #JTU8A has been approved.', 1, '2025-05-20 23:53:50'),
(65, 'Order Approved', 2452, 'NFAC6', 'Your order #NFAC6 has been approved.', 1, '2025-05-20 23:53:53'),
(66, 'Order Approved', 2452, 'HXNMH', 'Your order #HXNMH has been approved.', 1, '2025-05-20 23:53:57'),
(67, 'Order Approved', 2452, 'U4JDW', 'Your order #U4JDW has been approved.', 1, '2025-05-20 23:54:00'),
(68, 'Payment Confirmed', 2093, 'RNSWI', 'Your payment of ₱200.00 for Order #RNSWI has been confirmed.', 0, '2025-05-21 00:08:02'),
(69, 'Payment Confirmed', 2093, 'AOQWW', 'Your payment of ₱400.00 for Order #AOQWW has been confirmed.', 0, '2025-05-21 00:08:04'),
(70, 'Order Approved', 2452, 'HU9YE', 'Your order #HU9YE has been approved.', 1, '2025-05-21 00:08:13'),
(71, 'Order Approved', 2452, 'HK21X', 'Your order #HK21X has been approved.', 1, '2025-05-21 00:08:16'),
(72, 'Order Approved', 2452, 'OEO3O', 'Your order #OEO3O has been approved.', 1, '2025-05-21 00:08:19'),
(73, 'Order Approved', 2452, 'L1DI5', 'Your order #L1DI5 has been approved.', 1, '2025-05-21 00:08:22'),
(74, 'Order Approved', 2452, 'ZEDWM', 'Your order #ZEDWM has been approved.', 1, '2025-05-21 00:08:24'),
(75, 'Order Approved', 2452, '7RI9Q', 'Your order #7RI9Q has been approved.', 1, '2025-05-21 00:08:27'),
(76, 'Order Approved', 2452, 'B566A', 'Your order #B566A has been approved.', 1, '2025-05-21 00:08:29'),
(77, 'Order Approved', 2452, '89ISO', 'Your order #89ISO has been approved.', 1, '2025-05-21 00:08:32'),
(78, 'Order Approved', 2452, '684KL', 'Your order #684KL has been approved.', 1, '2025-05-21 00:08:35'),
(79, 'Order Approved', 2452, 'DW0OP', 'Your order #DW0OP has been approved.', 1, '2025-05-21 00:08:38'),
(80, 'Order Approved', 2452, '3XCG9', 'Your order #3XCG9 has been approved.', 1, '2025-05-21 00:08:41'),
(81, 'Order Approved', 2452, 'QN88R', 'Your order #QN88R has been approved.', 1, '2025-05-21 00:08:43'),
(82, 'Payment Confirmed', 2093, 'DYRHS', 'Your payment of ₱225.00 for Order #DYRHS has been confirmed.', 0, '2025-05-21 00:08:50'),
(83, 'Payment Confirmed', 2093, '17F3B', 'Your payment of ₱200.00 for Order #17F3B has been confirmed.', 0, '2025-05-21 00:08:54'),
(84, 'Payment Confirmed', 2093, 'B1PVL', 'Your payment of ₱225.00 for Order #B1PVL has been confirmed.', 0, '2025-05-21 00:08:57'),
(85, 'Payment Confirmed', 2093, 'PJP9N', 'Your payment of ₱0.00 for Order #PJP9N has been confirmed.', 0, '2025-05-21 00:08:59'),
(86, 'Payment Verification Required', 2452, 'HU9YE', 'New payment submitted for Order #HU9YE requires verification', 1, '2025-05-21 00:15:10'),
(87, 'Order Approved', 2452, 'H34KX', 'Your order #H34KX has been approved.', 1, '2025-05-21 01:52:20'),
(88, 'Payment Confirmed', 2452, 'HU9YE', 'Your payment of ₱450.00 for Order #HU9YE has been confirmed.', 1, '2025-05-21 01:52:43'),
(89, 'Order In Process', 2452, 'HU9YE', 'Your Sublimation order #HU9YE is now being processed.', 1, '2025-05-21 02:09:41'),
(90, 'Order Completed', 2452, 'G9YIL', 'Your Tailoring order #G9YIL has been marked as completed. Thank you for your business!', 1, '2025-05-21 02:09:51'),
(91, 'Payment Verification Required', 2452, 'HK21X', 'New payment submitted for Order #HK21X requires verification', 1, '2025-05-21 02:16:52'),
(92, 'Payment Verification Required', 2452, 'HK21X', 'New payment submitted for Order #HK21X requires verification', 1, '2025-05-21 02:17:38'),
(93, 'Payment Confirmed', 2452, 'HK21X', 'Your payment of ₱250.00 for Order #HK21X has been confirmed.', 1, '2025-05-21 02:18:55'),
(94, 'Payment Confirmed', 2452, 'HK21X', 'Your payment of ₱250.00 for Order #HK21X has been confirmed.', 1, '2025-05-21 02:19:01'),
(95, 'Order In Process', 2452, 'HK21X', 'Your Tailoring order #HK21X is now being processed.', 1, '2025-05-21 02:19:48'),
(96, 'Order Ready for Pickup', 2452, 'HK21X', 'Your order #HK21X is now ready for pickup.', 1, '2025-05-21 02:20:10'),
(97, 'Payment Received', 2452, 'HK21X', 'Your payment of ₱250.00 for order #HK21X has been received. Thank you!', 1, '2025-05-21 02:21:10'),
(98, 'Payment Confirmed', 2452, 'HK21X', 'Your payment of ₱250.00 for Order #HK21X has been confirmed.', 1, '2025-05-21 02:22:06'),
(99, 'Payment Verification Required', 2452, 'TYGNB', 'New payment submitted for Order #TYGNB requires verification', 0, '2025-05-21 02:54:22');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` varchar(10) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_type` enum('tailoring','sublimation') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `downpayment_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','gcash') NOT NULL,
  `payment_status` enum('pending','downpayment_paid','fully_paid') DEFAULT 'pending',
  `order_status` enum('pending_approval','declined','approved','forward_to_sublimator','in_process','printing_done','ready_for_pickup','completed') DEFAULT 'pending_approval',
  `staff_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status_history` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `order_type`, `total_amount`, `downpayment_amount`, `payment_method`, `payment_status`, `order_status`, `staff_id`, `manager_id`, `notes`, `created_at`, `updated_at`, `screenshot_path`, `status_history`) VALUES
('2U78G', 2452, 'sublimation', 550.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 03:13:14', '2025-05-21 03:19:25', NULL, NULL),
('5N62E', 2452, 'sublimation', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 03:11:13', '2025-05-21 03:11:56', NULL, NULL),
('EJ2EJ', 2452, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 03:04:51', '2025-05-21 03:04:51', NULL, NULL),
('R4ABL', 2452, 'sublimation', 1000.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 03:33:04', '2025-05-21 03:42:16', NULL, NULL),
('RLLZK', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 03:25:46', '2025-05-21 03:28:44', NULL, NULL),
('RPSVO', 2452, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 02:57:04', '2025-05-21 02:57:04', NULL, NULL),
('TYGNB', 2452, 'tailoring', 1500.00, 0.00, 'cash', 'pending', 'approved', NULL, 3, 'Tetsh', '2025-05-20 23:36:45', '2025-05-20 23:53:47', NULL, NULL),
('U3EDV', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Tetst', '2025-05-18 09:25:09', '2025-05-18 09:25:09', NULL, NULL),
('U4JDW', 2452, 'tailoring', 1500.00, 0.00, 'cash', 'pending', 'approved', NULL, 3, '', '2025-05-18 15:04:05', '2025-05-20 23:54:00', NULL, NULL),
('UN9M3', 2452, 'sublimation', 550.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-21 02:55:35', '2025-05-21 02:56:21', NULL, NULL),
('VGPGX', 2093, 'sublimation', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-29 09:44:27', '2025-04-29 09:44:27', NULL, NULL),
('VV4VO', 2452, 'sublimation', 5000.00, 0.00, 'cash', 'pending', 'approved', NULL, 3, NULL, '2025-05-10 16:43:22', '2025-05-20 23:06:36', NULL, NULL),
('WG6M3', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 07:02:14', '2025-05-15 07:02:14', NULL, NULL),
('WSHFC', 2093, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-04-29 11:53:09', '2025-04-30 02:23:20', NULL, NULL),
('XRRIH', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:57:24', '2025-05-15 06:57:24', NULL, NULL),
('XTK7G', 2452, 'sublimation', 400.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, NULL, '2025-05-14 18:46:33', '2025-05-14 18:47:20', NULL, NULL),
('Y26QN', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 05:43:36', '2025-05-15 05:43:36', NULL, NULL),
('Y6GX5', 2093, 'tailoring', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-08 13:51:10', '2025-05-08 13:51:10', NULL, NULL),
('YHH83', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Tetstubg', '2025-05-18 09:28:03', '2025-05-18 09:28:03', NULL, NULL),
('ZBUI0', 2093, 'tailoring', 150.00, 0.00, 'cash', 'downpayment_paid', 'approved', NULL, NULL, NULL, '2025-05-14 04:57:25', '2025-05-20 23:50:34', NULL, NULL),
('ZEDWM', 2452, 'tailoring', 300.00, 0.00, 'cash', 'pending', 'approved', NULL, 3, 'Sjh', '2025-05-18 14:18:34', '2025-05-21 00:08:24', NULL, NULL),
('ZOR5I', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Nobe\n', '2025-05-15 07:41:36', '2025-05-15 07:41:36', NULL, NULL),
('ZPTXC', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Gegehw', '2025-05-18 10:28:12', '2025-05-18 10:28:12', NULL, NULL),
('ZRQ32', 2452, 'sublimation', 400.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, NULL, '2025-05-14 18:43:43', '2025-05-14 18:51:38', NULL, NULL);

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `trg_order_to_completed` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
  IF OLD.order_status = 'ready_for_pickup' AND NEW.order_status = 'ready_for_pickup' AND NEW.manager_id IS NOT NULL THEN
    SET NEW.order_status = 'completed';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_order_to_in_process` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
  IF OLD.order_status = 'approved' AND NEW.order_status = 'approved' AND NEW.staff_id IS NOT NULL THEN
    SET NEW.order_status = 'in_process';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_order_to_ready_for_pickup` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
  IF OLD.order_status = 'in_process' AND NEW.order_status = 'in_process' AND NEW.notes LIKE '%finished%' THEN
    SET NEW.order_status = 'ready_for_pickup';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `status` enum('pending_approval','declined','approved','in_process','ready_for_pickup','completed') NOT NULL,
  `updated_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`history_id`, `order_id`, `status`, `updated_by`, `notes`, `created_at`) VALUES
(4, '17F3B', 'in_process', 18, 'Order moved to production.', '2025-05-02 00:14:20'),
(5, '7LWIC', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 22:48:16'),
(6, 'NEWT7', 'declined', 3, 'asdsad', '2025-05-20 22:48:34'),
(7, '03MEJ', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 22:54:11'),
(8, '648XY', 'declined', 3, 'sa', '2025-05-20 22:54:17'),
(9, '6EWNK', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 22:58:08'),
(10, 'SRXNM', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:01:44'),
(11, 'VV4VO', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:06:36'),
(12, 'KC5S9', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:06:43'),
(13, 'IZOIP', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:07:22'),
(14, 'BGF9D', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:07:50'),
(15, 'M766L', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:53:41'),
(16, 'L1EJL', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:53:43'),
(17, 'TYGNB', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:53:47'),
(18, 'JTU8A', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:53:50'),
(19, 'NFAC6', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:53:53'),
(20, 'HXNMH', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:53:57'),
(21, 'U4JDW', 'approved', 3, 'Order approved by admin/manager', '2025-05-20 23:54:00'),
(22, 'HU9YE', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:13'),
(23, 'HK21X', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:16'),
(24, 'OEO3O', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:19'),
(25, 'L1DI5', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:22'),
(26, 'ZEDWM', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:24'),
(27, '7RI9Q', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:27'),
(28, 'B566A', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:29'),
(29, '89ISO', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:32'),
(30, '684KL', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:35'),
(31, 'DW0OP', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:38'),
(32, '3XCG9', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:41'),
(33, 'QN88R', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 00:08:43'),
(34, 'H34KX', 'approved', 3, 'Order approved by admin/manager', '2025-05-21 01:52:20'),
(35, 'HU9YE', 'in_process', 18, 'Order moved to production.', '2025-05-21 02:09:41'),
(36, 'G9YIL', 'completed', 18, 'Order marked as completed', '2025-05-21 02:09:51'),
(37, 'HK21X', 'in_process', 18, 'Order moved to production.', '2025-05-21 02:19:48'),
(38, 'HK21X', 'ready_for_pickup', 18, 'Order is ready for pickup.', '2025-05-21 02:20:10');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('downpayment','full_payment','balance') NOT NULL,
  `payment_method` enum('cash','gcash') NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','under verification','confirmed','rejected') DEFAULT 'pending',
  `screenshot_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `amount`, `payment_type`, `payment_method`, `transaction_reference`, `received_by`, `payment_date`, `payment_status`, `screenshot_path`) VALUES
(4, 'PJP9N', 0.00, 'downpayment', 'cash', '', 3, '2025-04-29 08:45:26', 'confirmed', NULL),
(5, 'B1PVL', 225.00, 'downpayment', 'cash', '', 3, '2025-04-29 09:10:21', 'confirmed', NULL),
(6, '17F3B', 200.00, 'downpayment', 'cash', '', 3, '2025-04-29 12:12:15', 'confirmed', NULL),
(7, 'DYRHS', 225.00, 'downpayment', 'cash', '', 3, '2025-05-02 09:57:10', 'confirmed', NULL),
(8, '2C4QQ', 50.00, 'downpayment', 'gcash', '09564563456', 9, '2025-05-05 04:18:46', 'rejected', NULL),
(9, '2C4QQ', 50.00, 'downpayment', 'gcash', '09564563456', 9, '2025-05-05 04:35:02', 'confirmed', NULL),
(10, 'AOQWW', 400.00, 'downpayment', 'gcash', '25489582KDKD', 9, '2025-05-05 08:43:53', 'confirmed', NULL),
(13, 'AOQWW', 400.00, '', 'cash', '', 3, '2025-05-09 11:09:33', 'confirmed', NULL),
(16, 'RNSWI', 200.00, 'downpayment', 'gcash', '25489582KDKDDFR', 3, '2025-05-09 11:49:13', 'confirmed', '../uploads/payment_screenshots/payment_RNSWI_1746791353.jfif'),
(17, 'RNSWI', 200.00, 'downpayment', 'gcash', '25489582KDKDDFR', 3, '2025-05-09 12:10:46', 'rejected', '../uploads/payment_screenshots/payment_RNSWI_1746792646.jfif'),
(18, 'IYGVD', 450.00, 'full_payment', 'cash', '', NULL, '2025-05-14 08:15:48', 'confirmed', ''),
(19, 'EOPR0', 500.00, 'full_payment', 'cash', '', 3, '2025-05-14 08:55:18', 'confirmed', ''),
(20, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 09:12:43', 'confirmed', ''),
(21, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 09:16:18', 'confirmed', ''),
(22, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 09:19:21', 'confirmed', ''),
(23, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 09:25:36', 'confirmed', ''),
(24, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 09:25:59', 'confirmed', ''),
(25, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 09:28:19', 'confirmed', ''),
(26, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 3, '2025-05-14 10:09:13', 'confirmed', ''),
(27, 'G9YIL', 1500.00, 'full_payment', 'gcash', '17726271h2h1u', 3, '2025-05-14 10:51:18', 'confirmed', '../uploads/payment_screenshots/payment_G9YIL_1747219878.jpeg'),
(28, 'LI5GM', 1500.00, 'full_payment', 'cash', '', 3, '2025-05-14 10:53:47', 'confirmed', ''),
(29, 'XTK7G', 400.00, 'full_payment', 'cash', '', 3, '2025-05-14 18:47:20', 'confirmed', ''),
(30, 'ZRQ32', 400.00, 'downpayment', 'cash', '', 3, '2025-05-14 18:51:38', 'confirmed', ''),
(31, '88WDD', 400.00, 'full_payment', 'cash', '', 3, '2025-05-17 17:40:53', 'confirmed', ''),
(32, 'HXNMH', 300.00, 'full_payment', 'cash', '', 3, '2025-05-18 16:04:58', 'confirmed', ''),
(33, 'HU9YE', 450.00, 'full_payment', 'gcash', 'Ysgsg', 3, '2025-05-21 00:15:10', 'confirmed', '../uploads/payment_screenshots/payment_HU9YE_1747786510.jpeg'),
(34, 'HK21X', 250.00, 'downpayment', 'cash', '', 9, '2025-05-21 02:16:52', 'confirmed', ''),
(35, 'HK21X', 250.00, 'downpayment', 'cash', '', 9, '2025-05-21 02:17:38', 'confirmed', ''),
(36, 'HK21X', 250.00, '', 'cash', '', NULL, '2025-05-21 02:21:10', 'confirmed', NULL),
(37, 'TYGNB', 750.00, 'downpayment', 'cash', '', 9, '2025-05-21 02:54:22', 'pending', '');

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `history_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `previous_status` varchar(20) NOT NULL,
  `new_status` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_at` datetime NOT NULL,
  `changed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_history`
--

INSERT INTO `payment_history` (`history_id`, `payment_id`, `previous_status`, `new_status`, `notes`, `changed_at`, `changed_by`) VALUES
(0, 32, 'pending', 'confirmed', '', '2025-05-21 07:43:41', 3),
(0, 31, 'pending', 'confirmed', '', '2025-05-21 07:44:27', 3),
(0, 30, 'pending', 'confirmed', '', '2025-05-21 07:44:32', 3),
(0, 29, 'pending', 'confirmed', '', '2025-05-21 07:44:37', 3),
(0, 28, 'pending', 'confirmed', '', '2025-05-21 07:44:42', 3),
(0, 27, 'pending', 'confirmed', '', '2025-05-21 07:44:56', 3),
(0, 26, 'pending', 'confirmed', '', '2025-05-21 07:50:34', 3),
(0, 25, 'pending', 'confirmed', '', '2025-05-21 07:51:20', 3),
(0, 19, 'pending', 'confirmed', '', '2025-05-21 07:51:31', 3),
(0, 24, 'pending', 'confirmed', '', '2025-05-21 07:53:02', 3),
(0, 23, 'pending', 'confirmed', '', '2025-05-21 07:53:06', 3),
(0, 22, 'pending', 'confirmed', '', '2025-05-21 07:53:13', 3),
(0, 21, 'pending', 'confirmed', '', '2025-05-21 07:53:15', 3),
(0, 20, 'pending', 'confirmed', '', '2025-05-21 07:53:18', 3),
(0, 17, 'pending', 'rejected', 'asrde', '2025-05-21 07:53:32', 3),
(0, 16, 'pending', 'confirmed', '', '2025-05-21 08:08:02', 3),
(0, 13, 'pending', 'confirmed', '', '2025-05-21 08:08:04', 3),
(0, 7, 'pending', 'confirmed', '', '2025-05-21 08:08:50', 3),
(0, 6, 'pending', 'confirmed', '', '2025-05-21 08:08:54', 3),
(0, 5, 'pending', 'confirmed', '', '2025-05-21 08:08:57', 3),
(0, 4, 'pending', 'confirmed', '', '2025-05-21 08:08:59', 3),
(0, 33, 'pending', 'confirmed', '', '2025-05-21 09:52:43', 3);

-- --------------------------------------------------------

--
-- Table structure for table `staff_notifications`
--

CREATE TABLE `staff_notifications` (
  `notification_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `order_id` varchar(10) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_notifications`
--

INSERT INTO `staff_notifications` (`notification_id`, `staff_id`, `order_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'IYGVD', 'New payment of ₱450 received for Order #IYGVD', 0, '2025-05-14 08:15:48'),
(2, 9, 'IYGVD', 'New payment of ₱450 received for Order #IYGVD', 0, '2025-05-14 08:15:48'),
(3, 19, 'IYGVD', 'New payment of ₱450 received for Order #IYGVD', 0, '2025-05-14 08:15:48'),
(4, 3, 'EOPR0', 'New payment of ₱500 received for Order #EOPR0', 0, '2025-05-14 08:55:18'),
(5, 9, 'EOPR0', 'New payment of ₱500 received for Order #EOPR0', 0, '2025-05-14 08:55:18'),
(6, 19, 'EOPR0', 'New payment of ₱500 received for Order #EOPR0', 0, '2025-05-14 08:55:18'),
(7, 3, 'G9YIL', 'New payment of ₱1500 received for Order #G9YIL', 0, '2025-05-14 10:51:18'),
(8, 9, 'G9YIL', 'New payment of ₱1500 received for Order #G9YIL', 0, '2025-05-14 10:51:18'),
(9, 19, 'G9YIL', 'New payment of ₱1500 received for Order #G9YIL', 0, '2025-05-14 10:51:18'),
(10, 3, 'LI5GM', 'New payment of ₱1500 received for Order #LI5GM', 0, '2025-05-14 10:53:47'),
(11, 9, 'LI5GM', 'New payment of ₱1500 received for Order #LI5GM', 0, '2025-05-14 10:53:47'),
(12, 19, 'LI5GM', 'New payment of ₱1500 received for Order #LI5GM', 0, '2025-05-14 10:53:47'),
(13, 3, 'XTK7G', 'New payment of ₱400 received for Order #XTK7G', 0, '2025-05-14 18:47:20'),
(14, 9, 'XTK7G', 'New payment of ₱400 received for Order #XTK7G', 0, '2025-05-14 18:47:20'),
(15, 19, 'XTK7G', 'New payment of ₱400 received for Order #XTK7G', 0, '2025-05-14 18:47:20'),
(16, 3, 'ZRQ32', 'New payment of ₱400 received for Order #ZRQ32', 0, '2025-05-14 18:51:38'),
(17, 9, 'ZRQ32', 'New payment of ₱400 received for Order #ZRQ32', 0, '2025-05-14 18:51:38'),
(18, 19, 'ZRQ32', 'New payment of ₱400 received for Order #ZRQ32', 0, '2025-05-14 18:51:38'),
(19, 3, '88WDD', 'New payment of ₱400 received for Order #88WDD', 0, '2025-05-17 17:40:53'),
(20, 9, '88WDD', 'New payment of ₱400 received for Order #88WDD', 0, '2025-05-17 17:40:54'),
(21, 19, '88WDD', 'New payment of ₱400 received for Order #88WDD', 0, '2025-05-17 17:40:54'),
(22, 4, '5DEPC', 'New sublimation order #5DEPC received from kyle cadimas', 0, '2025-05-18 10:45:40'),
(23, 18, '5DEPC', 'New sublimation order #5DEPC received from kyle cadimas', 0, '2025-05-18 10:45:40'),
(24, 4, 'H34KX', 'New sublimation order #H34KX received from kyle cadimas', 0, '2025-05-18 10:54:27'),
(25, 18, 'H34KX', 'New sublimation order #H34KX received from kyle cadimas', 0, '2025-05-18 10:54:27'),
(26, 4, 'QN88R', 'New sublimation order #QN88R received from kyle cadimas', 0, '2025-05-18 10:58:40'),
(27, 18, 'QN88R', 'New sublimation order #QN88R received from kyle cadimas', 0, '2025-05-18 10:58:40'),
(28, 4, '3XCG9', 'New sublimation order #3XCG9 received from kyle cadimas', 0, '2025-05-18 11:06:21'),
(29, 18, '3XCG9', 'New sublimation order #3XCG9 received from kyle cadimas', 0, '2025-05-18 11:06:21'),
(30, 4, 'DW0OP', 'New sublimation order #DW0OP received from kyle cadimas', 0, '2025-05-18 11:13:58'),
(31, 18, 'DW0OP', 'New sublimation order #DW0OP received from kyle cadimas', 0, '2025-05-18 11:13:58'),
(32, 4, '684KL', 'New sublimation order #684KL received from kyle cadimas', 0, '2025-05-18 11:23:44'),
(33, 18, '684KL', 'New sublimation order #684KL received from kyle cadimas', 0, '2025-05-18 11:23:44'),
(34, 4, '89ISO', 'New sublimation order #89ISO received from kyle cadimas', 0, '2025-05-18 11:38:01'),
(35, 18, '89ISO', 'New sublimation order #89ISO received from kyle cadimas', 0, '2025-05-18 11:38:01'),
(36, 4, 'B566A', 'New sublimation order #B566A received from kyle cadimas', 0, '2025-05-18 11:45:44'),
(37, 18, 'B566A', 'New sublimation order #B566A received from kyle cadimas', 0, '2025-05-18 11:45:44'),
(38, 4, '7RI9Q', 'New tailoring order #7RI9Q received from kyle cadimas', 0, '2025-05-18 14:17:58'),
(39, 18, '7RI9Q', 'New tailoring order #7RI9Q received from kyle cadimas', 0, '2025-05-18 14:17:58'),
(40, 4, 'ZEDWM', 'New tailoring order #ZEDWM received from kyle cadimas', 0, '2025-05-18 14:18:34'),
(41, 18, 'ZEDWM', 'New tailoring order #ZEDWM received from kyle cadimas', 0, '2025-05-18 14:18:34'),
(42, 4, 'L1DI5', 'New tailoring order #L1DI5 received from kyle cadimas', 0, '2025-05-18 14:46:51'),
(43, 18, 'L1DI5', 'New tailoring order #L1DI5 received from kyle cadimas', 0, '2025-05-18 14:46:51'),
(44, 4, 'OEO3O', 'New tailoring order #OEO3O received from kyle cadimas', 0, '2025-05-18 15:03:35'),
(45, 18, 'OEO3O', 'New tailoring order #OEO3O received from kyle cadimas', 0, '2025-05-18 15:03:35'),
(46, 4, 'U4JDW', 'New tailoring order #U4JDW received from kyle cadimas', 0, '2025-05-18 15:04:05'),
(47, 18, 'U4JDW', 'New tailoring order #U4JDW received from kyle cadimas', 0, '2025-05-18 15:04:05'),
(48, 4, 'HXNMH', 'New tailoring order #HXNMH received from kyle cadimas', 0, '2025-05-18 16:01:48'),
(49, 18, 'HXNMH', 'New tailoring order #HXNMH received from kyle cadimas', 0, '2025-05-18 16:01:48'),
(50, 3, 'HXNMH', 'New payment of ₱300 received for Order #HXNMH', 0, '2025-05-18 16:04:58'),
(51, 9, 'HXNMH', 'New payment of ₱300 received for Order #HXNMH', 0, '2025-05-18 16:04:58'),
(52, 19, 'HXNMH', 'New payment of ₱300 received for Order #HXNMH', 0, '2025-05-18 16:04:58'),
(53, 4, 'NFAC6', 'New tailoring order #NFAC6 received from kyle cadimas', 0, '2025-05-18 16:44:44'),
(54, 18, 'NFAC6', 'New tailoring order #NFAC6 received from kyle cadimas', 0, '2025-05-18 16:44:44'),
(55, 4, 'BGF9D', 'New tailoring order #BGF9D received from kyle cadimas', 0, '2025-05-18 16:45:48'),
(56, 18, 'BGF9D', 'New tailoring order #BGF9D received from kyle cadimas', 0, '2025-05-18 16:45:48'),
(57, 4, 'IZOIP', 'New tailoring order #IZOIP received from kyle cadimas', 0, '2025-05-19 10:52:16'),
(58, 18, 'IZOIP', 'New tailoring order #IZOIP received from kyle cadimas', 0, '2025-05-19 10:52:16'),
(59, 4, 'SRXNM', 'New sublimation order #SRXNM received from kyle cadimas', 0, '2025-05-20 18:20:01'),
(60, 18, 'SRXNM', 'New sublimation order #SRXNM received from kyle cadimas', 0, '2025-05-20 18:20:01'),
(61, 4, '6EWNK', 'New sublimation order #6EWNK received from kyle cadimas', 0, '2025-05-20 19:08:06'),
(62, 18, '6EWNK', 'New sublimation order #6EWNK received from kyle cadimas', 0, '2025-05-20 19:08:06'),
(63, 4, '648XY', 'New sublimation order #648XY received from kyle cadimas', 0, '2025-05-20 22:37:06'),
(64, 18, '648XY', 'New sublimation order #648XY received from kyle cadimas', 0, '2025-05-20 22:37:06'),
(65, 4, 'NEWT7', 'New sublimation order #NEWT7 received from kyle cadimas', 0, '2025-05-20 22:41:24'),
(66, 18, 'NEWT7', 'New sublimation order #NEWT7 received from kyle cadimas', 0, '2025-05-20 22:41:24'),
(67, 4, '7LWIC', 'New sublimation order #7LWIC received from kyle cadimas', 0, '2025-05-20 22:46:39'),
(68, 18, '7LWIC', 'New sublimation order #7LWIC received from kyle cadimas', 0, '2025-05-20 22:46:39'),
(69, 4, '03MEJ', 'New sublimation order #03MEJ received from kyle cadimas', 0, '2025-05-20 22:52:15'),
(70, 18, '03MEJ', 'New sublimation order #03MEJ received from kyle cadimas', 0, '2025-05-20 22:52:15'),
(71, 4, 'TYGNB', 'New tailoring order #TYGNB received from kyle cadimas', 0, '2025-05-20 23:36:45'),
(72, 18, 'TYGNB', 'New tailoring order #TYGNB received from kyle cadimas', 0, '2025-05-20 23:36:45'),
(73, 4, 'JTU8A', 'New tailoring order #JTU8A received from kyle cadimas', 0, '2025-05-20 23:40:10'),
(74, 18, 'JTU8A', 'New tailoring order #JTU8A received from kyle cadimas', 0, '2025-05-20 23:40:10'),
(75, 4, 'L1EJL', 'New tailoring order #L1EJL received from kyle cadimas', 0, '2025-05-20 23:40:53'),
(76, 18, 'L1EJL', 'New tailoring order #L1EJL received from kyle cadimas', 0, '2025-05-20 23:40:53'),
(77, 4, 'M766L', 'New tailoring order #M766L received from kyle cadimas', 0, '2025-05-20 23:47:29'),
(78, 18, 'M766L', 'New tailoring order #M766L received from kyle cadimas', 0, '2025-05-20 23:47:29'),
(79, 4, 'HK21X', 'New tailoring order #HK21X received from kyle cadimas', 0, '2025-05-20 23:55:55'),
(80, 18, 'HK21X', 'New tailoring order #HK21X received from kyle cadimas', 0, '2025-05-20 23:55:55'),
(81, 4, 'HU9YE', 'New sublimation order #HU9YE received from kyle cadimas', 0, '2025-05-20 23:59:57'),
(82, 18, 'HU9YE', 'New sublimation order #HU9YE received from kyle cadimas', 0, '2025-05-20 23:59:57'),
(83, 3, 'HU9YE', 'New payment of ₱450 received for Order #HU9YE', 0, '2025-05-21 00:15:11'),
(84, 9, 'HU9YE', 'New payment of ₱450 received for Order #HU9YE', 0, '2025-05-21 00:15:11'),
(85, 19, 'HU9YE', 'New payment of ₱450 received for Order #HU9YE', 0, '2025-05-21 00:15:11');

-- --------------------------------------------------------

--
-- Table structure for table `sublimation_orders`
--

CREATE TABLE `sublimation_orders` (
  `sublimation_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `template_id` varchar(5) DEFAULT NULL,
  `custom_design` tinyint(1) DEFAULT 0,
  `design_path` varchar(255) DEFAULT NULL,
  `player_details_file_reference` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `sublimator_id` int(11) DEFAULT NULL,
  `allow_as_template` tinyint(1) DEFAULT 0,
  `completion_date` date DEFAULT NULL,
  `printing_type` enum('sublimation','silkscreen') NOT NULL DEFAULT 'sublimation',
  `customization` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sublimation_players`
--

CREATE TABLE `sublimation_players` (
  `player_id` int(11) NOT NULL,
  `sublimation_id` int(11) NOT NULL,
  `player_name` varchar(100) NOT NULL,
  `jersey_number` int(11) NOT NULL,
  `size` enum('XS','S','M','L','XL','XXL','XXXL') NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `include_lower` enum('Yes','No') NOT NULL,
  `order_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tailoring_orders`
--

CREATE TABLE `tailoring_orders` (
  `tailoring_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `service_type` enum('alterations','repairs','resize','custom made') NOT NULL,
  `completion_date` date DEFAULT NULL,
  `needs_seamstress` tinyint(1) DEFAULT 0,
  `seamstress_appointment` datetime DEFAULT NULL,
  `measurement_img` varchar(255) NOT NULL,
  `chest` int(5) NOT NULL,
  `waist` int(5) NOT NULL,
  `hips` int(5) NOT NULL,
  `shoulder_width` int(5) NOT NULL,
  `sleeve_length` int(5) NOT NULL,
  `garment_length` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `template_id` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `added_by` int(11) NOT NULL,
  `category` enum('Volleyball','Basketball','Football','Esports','Frisbee','Others') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `other_category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`template_id`, `name`, `description`, `image_path`, `price`, `added_by`, `category`, `created_at`, `updated_at`, `other_category`) VALUES
('11017', 'Milwaukee Bucks Jersey', NULL, 'uploads/Bucks.png', 400.00, 15, 'Basketball', '2025-03-22 15:23:14', '2025-03-22 15:23:14', NULL),
('23547', 'Marvel 616 Jersey', NULL, 'uploads/Marvel 616 Esports Jersey.png', 400.00, 15, 'Esports', '2025-03-22 15:10:42', '2025-03-22 15:10:42', NULL),
('27428', 'Techbeast: Green Wolves', NULL, 'uploads/Teachbest Athletics Green Wolves.png', 400.00, 15, 'Basketball', '2025-03-22 17:26:52', '2025-03-22 17:26:52', NULL),
('28016', 'ONIC Jersey', NULL, 'uploads/Onic Esports Jersey.png', 450.00, 15, 'Esports', '2025-03-22 15:27:56', '2025-03-22 15:27:56', NULL),
('28064', 'Black Panthers', NULL, 'uploads/BPP.png', 450.00, 15, 'Basketball', '2025-04-04 08:13:28', '2025-04-04 08:13:28', NULL),
('34833', 'NBA All Stars', NULL, 'uploads/All-Star NBA.png', 400.00, 17, 'Basketball', '2025-03-23 11:10:38', '2025-03-23 11:10:38', NULL),
('59101', 'Burmese Ghouls Jersey', NULL, 'uploads/Burmese Ghouls Jersey.png', 450.00, 15, 'Esports', '2025-03-22 15:13:26', '2025-03-22 15:13:26', NULL),
('86596', 'Nextplay EVOS Jersey', NULL, 'uploads/Nextplay Evos Jersey.png', 400.00, 15, 'Esports', '2025-03-22 15:11:24', '2025-03-22 15:11:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('Staff','Manager','Admin','Sublimator') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `phone_number`, `role`, `created_at`, `updated_at`) VALUES
(3, 'admin', '$2y$10$uj6gVhIjPD1BmZpmTx4Kpe9ptNOaZi/TTo1xD3YJ3SolHJTzwPkHO', 'admin@gmail.com', 'Vieny Lou', 'Vicente', '09551224455', 'Admin', '2025-03-07 17:30:54', '2025-05-20 21:23:10'),
(4, 'hexon', '$2y$10$M7SAttwOLCuSOqwxncx0WOKR2AK8vD.Y4QcN9jw2t6L7Bi3yyUjWW', 'hexon@gmail.com', 'Hexon Marc', 'Rendal', '09561234567', 'Staff', '2025-03-11 06:31:24', '2025-03-11 07:03:44'),
(9, 'wrymy', '$2y$10$jl2k/pOKFczFlwDo2UsLfOdhRbXsbdo0ucSmopIajBnmkKDX.8lyy', 'wrymyr@gmail.com', 'Wrymyr', 'Cadimas', '09652343456', 'Manager', '2025-03-11 06:46:02', '2025-03-11 06:46:02'),
(10, 'anjelie', '$2y$10$EdkeGx7JHab6Py87RWCzquxED2HQWI/P2qDWn3lbq3cK0C9V7S5cG', 'binads@gmail.com', 'Anjelie', 'Binaday', '09783456543', '', '2025-03-11 07:19:14', '2025-03-11 07:21:28'),
(14, 'earl', '$2y$10$sFG9zZryL4AioLZvpkZmpuoonhKFaeM2IJccM911FC9.gEx.U7/AW', 'earl@gmail.com', 'Vincene Earl', 'Vicente', '09652343456', 'Sublimator', '2025-03-11 07:57:42', '2025-03-11 07:57:42'),
(15, 'sublimator', '$2y$10$nYasjZ/PcRD.MHml9Nwy3.gpCl3qdh3t3ivkYEv9GZuGSwCjJzwpq', 'sub@gmail.com', 'Jane', 'Doe', '09652343456', 'Sublimator', '2025-03-16 16:23:03', '2025-03-16 16:23:03'),
(16, 'vieyah', '$2y$10$nGG3RBCrr0/2VmU9gkrfKuEOqa8GiBztwnwVlmzYnpqsJmOTBk27C', 'vieyahangelav@gmail.com', 'Vieyah Angela', 'Vicente', '09675675654', 'Sublimator', '2025-03-23 09:45:25', '2025-03-23 09:45:25'),
(17, 'dizzemar', '$2y$10$UP8iVcOMZ8170L.lnvbKV.cd6wd2Lgj1eSEJ53xq3txsP2L/eYfN6', 'markd@gmail.com', 'Mark Dizzemar', 'Bais', '09876789876', 'Sublimator', '2025-03-23 09:49:54', '2025-03-23 09:49:54'),
(18, 'staff', '$2y$10$mmdLQ3H/3bCHJtqdB3kG9eYIegvFfke3CtWKLRTBVNxGB25MXzCWa', 'rod@gmail.com', 'Rodielyn', 'Boncales', '097867856434', 'Staff', '2025-03-26 04:24:19', '2025-03-26 04:24:19'),
(19, 'manager', '$2y$10$q9bG..tbxB57Z6SqW408BOsAlYjiMbhFWcSAycYE.NOCUm20Ykogy', 'esnyr@gmail.com', 'Esnyr', 'Ranollo', '09551224455', 'Manager', '2025-04-11 04:31:11', '2025-04-11 04:31:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`session_id`, `customer_id`, `token`, `expiry`, `created_at`, `updated_at`) VALUES
(1, 2093, '0329e387063003963e60a54b9205d5de36f8cdfa12ee1ca73f6d30e0c8eae29e', '2025-06-12 18:19:58', '2025-05-14 00:19:58', '2025-05-14 00:19:58'),
(2, 2452, 'e3027dab0dd8126e099a00042d7778f8875649ee5a47a04b492db0f1188820f2', '2025-06-12 18:59:37', '2025-05-14 00:59:37', '2025-05-14 00:59:37'),
(3, 2452, '3157406b4abbd0750d20ceb5ad35b4f2f8169cba254717ed2d3902f1e23b8aaf', '2025-06-13 10:57:23', '2025-05-14 16:57:23', '2025-05-14 16:57:23'),
(4, 2452, '666aed074bcafb40c688d6df0c52dcbe8c5ca827262f1edca0115be7916e41fc', '2025-06-13 11:49:46', '2025-05-14 17:49:46', '2025-05-14 17:49:46'),
(5, 2452, 'dd7b387be94d9d0e2ea0e6717b270a17a69241acf549d6541363fdc88d7205ff', '2025-06-13 11:50:17', '2025-05-14 17:50:17', '2025-05-14 17:50:17'),
(6, 2452, 'f516a2951dc8f1a64c69d826bd6658efb9d5a5b4ce211705c2e46e1cda225259', '2025-06-13 12:27:53', '2025-05-14 18:27:53', '2025-05-14 18:27:53'),
(7, 2452, 'e29674649dedd6fb0f6c544956c17017e17a78c9e0a4312bb6889ccb749e2111', '2025-06-13 12:33:09', '2025-05-14 18:33:09', '2025-05-14 18:33:09'),
(8, 2452, '7fe073dae5a30e2b38197b113105d7acca16482d6fe1b24b0a9e853a7cf21b4e', '2025-06-13 12:46:02', '2025-05-14 18:46:02', '2025-05-14 18:46:02'),
(9, 2452, '80f901a6165016fc51d9cda460cc6bef8fb7e4e4863a07b1e02137ecb53ff34c', '2025-06-14 07:29:45', '2025-05-15 13:29:45', '2025-05-15 13:29:45'),
(10, 2452, '86d5d70d8822022e9564c6b7bf2fabfe86c8a82dc60039ab2d403ef3de21484b', '2025-06-16 18:04:21', '2025-05-18 00:04:21', '2025-05-18 00:04:21'),
(11, 2452, 'e60af9672f08d349165a92ab6ba180e6213b3386bdef0ffb4ea668ebdd2dfcb4', '2025-06-16 18:25:09', '2025-05-18 00:25:09', '2025-05-18 00:25:09'),
(12, 2452, 'e39014bea319b66dc8d0ee92c106387fb087d4316d095df650af40b0582c1b5f', '2025-06-18 12:51:51', '2025-05-19 18:51:51', '2025-05-19 18:51:51'),
(13, 2452, '6718bd9582912cf0c255141f24dae6ff71fb74c43392dc6c37144a38f746981b', '2025-06-19 23:03:33', '2025-05-21 05:03:33', '2025-05-21 05:03:33'),
(14, 2452, 'd3597104cf8663727578ada2013f10431d9b1ef3a43f41a52ef956b1d8ad0179', '2025-06-19 23:27:02', '2025-05-21 05:27:02', '2025-05-21 05:27:02'),
(15, 2452, '8da95052de701dc41aae4f6393768368572cc97bf1a07d75b38651edeaaf1db8', '2025-06-19 23:33:53', '2025-05-21 05:33:53', '2025-05-21 05:33:53'),
(16, 2452, '4ecf0cbb170b12c0b38e6cce428095976bc98e657c0b5e964a296790f332d0a9', '2025-06-19 23:38:25', '2025-05-21 05:38:25', '2025-05-21 05:38:25'),
(17, 2452, '620f06198c30c77ac1cf8891a1b8916a1993522598fb08e8162ab508e5059628', '2025-06-20 03:51:08', '2025-05-21 09:51:08', '2025-05-21 09:51:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `alterations`
--
ALTER TABLE `alterations`
  ADD PRIMARY KEY (`alteration_id`),
  ADD KEY `alterations_fk_order` (`order_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `custom_made`
--
ALTER TABLE `custom_made`
  ADD PRIMARY KEY (`custom_id`),
  ADD KEY `custom_made_fk_order` (`order_id`);

--
-- Indexes for table `declined_orders`
--
ALTER TABLE `declined_orders`
  ADD PRIMARY KEY (`decline_id`),
  ADD KEY `declined_by` (`declined_by`),
  ADD KEY `declined_orders_ibfk_1` (`order_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `notifications_ibfk_2` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `order_status_history_ibfk_1` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `payments_ibfk_1` (`order_id`);

--
-- Indexes for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_staff_notification_user` (`staff_id`),
  ADD KEY `fk_staff_notification_order` (`order_id`);

--
-- Indexes for table `sublimation_orders`
--
ALTER TABLE `sublimation_orders`
  ADD PRIMARY KEY (`sublimation_id`),
  ADD KEY `sublimator_id` (`sublimator_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `sublimation_orders_ibfk_1` (`order_id`);

--
-- Indexes for table `sublimation_players`
--
ALTER TABLE `sublimation_players`
  ADD PRIMARY KEY (`player_id`),
  ADD KEY `sublimation_id` (`sublimation_id`),
  ADD KEY `fk_order_id` (`order_id`);

--
-- Indexes for table `tailoring_orders`
--
ALTER TABLE `tailoring_orders`
  ADD PRIMARY KEY (`tailoring_id`),
  ADD KEY `tailoring_orders_ibfk_1` (`order_id`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `customer_sessions` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `alterations`
--
ALTER TABLE `alterations`
  MODIFY `alteration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8745;

--
-- AUTO_INCREMENT for table `custom_made`
--
ALTER TABLE `custom_made`
  MODIFY `custom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `declined_orders`
--
ALTER TABLE `declined_orders`
  MODIFY `decline_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `sublimation_orders`
--
ALTER TABLE `sublimation_orders`
  MODIFY `sublimation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sublimation_players`
--
ALTER TABLE `sublimation_players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tailoring_orders`
--
ALTER TABLE `tailoring_orders`
  MODIFY `tailoring_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alterations`
--
ALTER TABLE `alterations`
  ADD CONSTRAINT `alterations_fk_order` FOREIGN KEY (`order_id`) REFERENCES `tailoring_orders` (`order_id`);

--
-- Constraints for table `custom_made`
--
ALTER TABLE `custom_made`
  ADD CONSTRAINT `custom_made_fk_order` FOREIGN KEY (`order_id`) REFERENCES `tailoring_orders` (`order_id`);

--
-- Constraints for table `declined_orders`
--
ALTER TABLE `declined_orders`
  ADD CONSTRAINT `declined_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `declined_orders_ibfk_2` FOREIGN KEY (`declined_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `fk_notes_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`manager_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  ADD CONSTRAINT `fk_staff_notification_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_staff_notification_user` FOREIGN KEY (`staff_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sublimation_orders`
--
ALTER TABLE `sublimation_orders`
  ADD CONSTRAINT `sublimation_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `sublimation_orders_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `templates` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sublimation_orders_ibfk_3` FOREIGN KEY (`sublimator_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `sublimation_orders_ibfk_4` FOREIGN KEY (`template_id`) REFERENCES `templates` (`template_id`) ON DELETE SET NULL;

--
-- Constraints for table `sublimation_players`
--
ALTER TABLE `sublimation_players`
  ADD CONSTRAINT `fk_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sublimation_players_ibfk_1` FOREIGN KEY (`sublimation_id`) REFERENCES `sublimation_orders` (`sublimation_id`) ON DELETE CASCADE;

--
-- Constraints for table `tailoring_orders`
--
ALTER TABLE `tailoring_orders`
  ADD CONSTRAINT `tailoring_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_customer_sessions` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
