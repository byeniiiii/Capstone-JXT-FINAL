-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 11:08 AM
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

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `generate_template_id` () RETURNS VARCHAR(5) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE random_id VARCHAR(5);
    SET random_id = (
        SELECT CONCAT(
            CHAR(FLOOR(RAND() * 26) + 65),  -- Random uppercase letter A-Z
            CHAR(FLOOR(RAND() * 26) + 65),  
            CHAR(FLOOR(RAND() * 10) + 48),  -- Random number 0-9
            CHAR(FLOOR(RAND() * 26) + 65),
            CHAR(FLOOR(RAND() * 10) + 48)
        )
    );
    RETURN random_id;
END$$

DELIMITER ;

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
(4, 18, 'customer', 'order_decline', 'Order #LSCD8 declined: walay klaro', NULL, '2025-05-05 08:42:11');

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
(7, '05R84', 'custom made', 'manual', '{\"chest\":\"11\",\"waist\":\"11\",\"hips\":\"1111\",\"shoulders\":\"11\",\"sleeves\":\"11\",\"length\":\"11\"}', NULL, NULL);

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
(1, '05R84', '', NULL, 'customer', 1, NULL, NULL);

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
(53, '192.168.1.25', 'kyle', 1, '2025-05-19 04:00:31');

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
(6, 'DYRHS', 18, 'Order marked as ready for pickup', '2025-05-18 23:31:20'),
(7, 'G9YIL', 18, 'Order marked as completed', '2025-05-18 23:35:46'),
(8, 'QWRAU', 18, 'Order moved to production.', '2025-05-19 14:35:03'),
(9, 'AOQWW', 18, 'Order completed', '2025-05-19 16:20:41');

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
(17, 'Payment Verification Required', 2452, 'IYGVD', 'New payment submitted for Order #IYGVD requires verification', 0, '2025-05-14 08:15:48'),
(18, 'Payment Confirmed', 2452, 'IYGVD', 'Your payment of ₱450.00 for Order #IYGVD has been confirmed.', 0, '2025-05-14 08:17:02'),
(19, 'Payment Verification Required', 2452, 'EOPR0', 'New payment submitted for Order #EOPR0 requires verification', 0, '2025-05-14 08:55:18'),
(20, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:12:43'),
(21, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:16:18'),
(22, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:19:21'),
(23, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:25:36'),
(24, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:25:59'),
(25, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 09:28:19'),
(26, 'Payment Verification Required', 2093, 'ZBUI0', 'New payment submitted for Order #ZBUI0 requires verification', 1, '2025-05-14 10:09:13'),
(27, 'Payment Verification Required', 2452, 'G9YIL', 'New payment submitted for Order #G9YIL requires verification', 0, '2025-05-14 10:51:18'),
(28, 'Order In Process', 2452, 'G9YIL', 'Your order #G9YIL is now being processed.', 0, '2025-05-14 10:52:28'),
(29, 'Payment Verification Required', 2452, 'LI5GM', 'New payment submitted for Order #LI5GM requires verification', 0, '2025-05-14 10:53:47'),
(30, 'Order Ready for Pickup', 2452, 'G9YIL', 'Your order #G9YIL is now ready for pickup.', 0, '2025-05-14 10:58:18'),
(31, 'Payment Verification Required', 2452, 'XTK7G', 'New payment submitted for Order #XTK7G requires verification', 0, '2025-05-14 18:47:20'),
(32, 'Payment Verification Required', 2452, 'ZRQ32', 'New payment submitted for Order #ZRQ32 requires verification', 0, '2025-05-14 18:51:38'),
(33, 'Order Ready for Pickup', 2093, '2C4QQ', 'Your order #2C4QQ is now ready for pickup.', 0, '2025-05-14 19:59:24'),
(36, 'Order Completed', 2452, '05R84', 'Your order #05R84 has been completed.', 0, '2025-05-18 14:35:19'),
(40, 'Order Ready for Pickup', 2093, 'DYRHS', 'Your order #DYRHS is now ready for pickup.', 0, '2025-05-18 15:31:20'),
(41, 'Order Completed', 2452, 'G9YIL', 'Your order #G9YIL has been completed.', 0, '2025-05-18 15:35:46'),
(42, 'Payment Verification Required', 2452, '88WDD', 'New payment submitted for Order #88WDD requires verification', 0, '2025-05-19 04:03:10'),
(43, 'Order In Process', 2452, 'QWRAU', 'Your order #QWRAU is now being processed.', 0, '2025-05-19 06:35:03'),
(44, 'Order Completed', 2093, 'AOQWW', 'Your order #AOQWW has been completed.', 0, '2025-05-19 08:20:41');

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
('', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-06 07:22:39', '2025-04-06 07:22:39', NULL, NULL),
('05R84', 2452, 'tailoring', 1500.00, 0.00, 'cash', 'pending', 'completed', NULL, NULL, '', '2025-05-15 05:52:46', '2025-05-18 14:35:19', NULL, NULL),
('08HYW', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:10:28', '2025-05-15 06:10:28', NULL, NULL),
('0UWXJ', 2452, 'tailoring', 300.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-14 17:57:08', '2025-05-14 17:57:08', NULL, NULL),
('15B5T', 2452, 'tailoring', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Test 3', '2025-05-14 07:26:04', '2025-05-14 07:26:04', NULL, NULL),
('17F3B', 2093, 'sublimation', 400.00, 0.00, 'cash', 'downpayment_paid', 'completed', NULL, NULL, NULL, '2025-04-29 12:09:39', '2025-05-14 05:23:42', NULL, NULL),
('2C4QQ', 2093, 'tailoring', 100.00, 50.00, 'cash', 'downpayment_paid', 'ready_for_pickup', NULL, NULL, NULL, '2025-05-05 03:56:18', '2025-05-14 19:59:24', NULL, NULL),
('3SQ5N', 8740, 'tailoring', 100.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 06:25:32', '2025-05-05 06:27:21', NULL, NULL),
('3U739', 2093, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: No amount given', '2025-04-29 11:52:46', '2025-05-02 01:21:38', NULL, NULL),
('3XDSJ', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:53:09', '2025-05-15 06:53:09', NULL, NULL),
('4E9C8', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:43:33', '2025-05-10 16:43:33', NULL, NULL),
('4ERSE', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-10 16:53:20', '2025-05-14 05:02:18', NULL, NULL),
('63W3F', 8742, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 07:21:07', '2025-05-05 07:21:07', NULL, NULL),
('6JYAB', 2093, 'tailoring', 1500.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-11 07:17:48', '2025-04-25 16:41:02', NULL, NULL),
('6XGPL', 2093, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-08 14:37:26', '2025-05-08 14:37:26', NULL, NULL),
('88WDD', 2452, 'sublimation', 400.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, '', '2025-05-15 08:24:20', '2025-05-19 04:03:10', NULL, NULL),
('8P4D9', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:25:57', '2025-05-15 06:25:57', NULL, NULL),
('9AIWF', 2452, 'tailoring', 300.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-14 17:58:00', '2025-05-14 17:58:00', NULL, NULL),
('AFRPA', 8739, 'tailoring', 200.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:43:54', '2025-04-22 15:58:38', NULL, NULL),
('AOQWW', 2093, 'sublimation', 800.00, 400.00, 'cash', 'fully_paid', 'completed', NULL, NULL, NULL, '2025-05-05 08:40:27', '2025-05-19 08:20:41', NULL, NULL),
('B1PVL', 2093, 'sublimation', 450.00, 0.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-04-06 07:27:24', '2025-05-05 05:04:11', NULL, NULL),
('B2V53', 2452, 'tailoring', 300.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Trial 2\n', '2025-05-14 05:27:35', '2025-05-14 05:27:35', NULL, NULL),
('B6HQ0', 2452, 'tailoring', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-14 16:36:08', '2025-05-14 16:36:08', NULL, NULL),
('CNT2I', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 05:48:43', '2025-05-15 05:48:43', NULL, NULL),
('CRQB9', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 07:33:39', '2025-05-15 07:33:39', NULL, NULL),
('D68GY', 2093, 'tailoring', 100.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-14 03:45:06', '2025-05-14 03:45:06', NULL, NULL),
('DYRHS', 2093, 'sublimation', 450.00, 0.00, 'cash', 'downpayment_paid', 'ready_for_pickup', NULL, NULL, NULL, '2025-05-02 01:18:22', '2025-05-18 15:31:20', NULL, NULL),
('E3UTB', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-02 01:17:36', '2025-05-02 01:17:36', NULL, NULL),
('E4J4J', 8742, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 07:14:06', '2025-05-05 07:14:06', NULL, NULL),
('EOPR0', 2452, 'tailoring', 500.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, 'Test 6', '2025-05-14 08:55:08', '2025-05-14 08:55:18', NULL, NULL),
('ETU56', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:49:15', '2025-05-10 16:49:15', NULL, NULL),
('FOH7G', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:43:45', '2025-05-10 16:43:45', NULL, NULL),
('G9YIL', 2452, 'tailoring', 1500.00, 0.00, 'cash', 'fully_paid', 'completed', NULL, NULL, 'W\n', '2025-05-14 10:48:11', '2025-05-18 15:35:46', NULL, NULL),
('GNOJL', 2093, 'sublimation', 500.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-17 16:46:47', '2025-04-25 17:01:10', NULL, NULL),
('H4DT6', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 08:16:15', '2025-05-15 08:16:15', NULL, NULL),
('HGBVJ', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:09:43', '2025-05-15 06:09:43', NULL, NULL),
('HZZ9B', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 05:31:56', '2025-05-15 05:31:56', NULL, NULL),
('IJPNG', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-14 18:05:20', '2025-05-14 18:05:20', NULL, NULL),
('IYGVD', 2452, 'sublimation', 450.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, '', '2025-05-14 07:01:52', '2025-05-14 08:17:02', NULL, NULL),
('KC5S9', 2093, 'sublimation', 5000.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-29 11:50:53', '2025-04-29 11:50:53', NULL, NULL),
('LI5GM', 2452, 'tailoring', 1500.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, '', '2025-05-14 10:46:55', '2025-05-14 10:53:47', NULL, NULL),
('LSCD8', 2093, 'tailoring', 100.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: walay klaro', '2025-05-05 02:33:02', '2025-05-05 08:42:11', NULL, NULL),
('N77QE', 8739, 'tailoring', 150.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-19 06:53:20', '2025-04-19 06:53:20', NULL, NULL),
('NDERG', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:44:15', '2025-05-10 16:44:15', NULL, NULL),
('OP0M0', 2452, 'tailoring', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Trial\n', '2025-05-14 05:27:09', '2025-05-14 05:27:09', NULL, NULL),
('OP9SC', 8739, 'sublimation', 800.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 09:26:05', '2025-04-22 16:29:34', NULL, NULL),
('OQIL6', 2093, 'tailoring', 1000.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-17 17:35:06', '2025-04-23 15:34:20', NULL, NULL),
('P4ZHQ', 2452, 'tailoring', 300.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-14 18:01:26', '2025-05-14 18:01:26', NULL, NULL),
('P7Z7X', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:47:21', '2025-05-10 16:47:21', NULL, NULL),
('PJP9N', 2093, 'sublimation', 400.00, 0.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-04-28 14:50:43', '2025-05-05 05:04:11', NULL, NULL),
('QWRAU', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'in_process', NULL, NULL, NULL, '2025-05-15 06:23:49', '2025-05-19 06:35:03', NULL, NULL),
('RBO3M', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 07:06:21', '2025-05-15 07:06:21', NULL, NULL),
('RNSWI', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 08:47:08', '2025-05-05 08:47:36', NULL, NULL),
('ROBCE', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 05:30:44', '2025-05-15 05:30:44', NULL, NULL),
('RSFUN', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 07:28:15', '2025-05-15 07:28:15', NULL, NULL),
('SW0LN', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:54:17', '2025-05-15 06:54:17', NULL, NULL),
('TF3LG', 8739, 'tailoring', 100.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 07:23:57', '2025-04-22 16:34:22', NULL, NULL),
('TO04658692', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 07:10:19', '2025-04-23 16:23:24', NULL, NULL),
('TO59SLN', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:41:23', '2025-04-22 15:52:46', NULL, NULL),
('TOHUXNP', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:24:39', '2025-04-22 16:12:07', NULL, NULL),
('TOK8AVY', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 15, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:45:01', '2025-04-22 15:36:07', NULL, NULL),
('TP2BC', 8742, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 06:47:08', '2025-05-05 06:47:08', NULL, NULL),
('TQ6W9', 8739, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-14 05:53:25', '2025-05-14 05:53:25', NULL, NULL),
('TUSK9', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 07:01:40', '2025-05-15 07:01:40', NULL, NULL),
('VGPGX', 2093, 'sublimation', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-29 09:44:27', '2025-04-29 09:44:27', NULL, NULL),
('VV4VO', 2452, 'sublimation', 5000.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:43:22', '2025-05-10 16:43:22', NULL, NULL),
('WG6M3', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, '', '2025-05-15 07:02:14', '2025-05-15 07:02:14', NULL, NULL),
('WJOLN', 2452, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-10 16:48:55', '2025-05-10 16:48:55', NULL, NULL),
('WSHFC', 2093, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-04-29 11:53:09', '2025-04-30 02:23:20', NULL, NULL),
('XRRIH', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 06:57:24', '2025-05-15 06:57:24', NULL, NULL),
('XTK7G', 2452, 'sublimation', 400.00, 0.00, 'cash', 'fully_paid', 'pending_approval', NULL, NULL, NULL, '2025-05-14 18:46:33', '2025-05-14 18:47:20', NULL, NULL),
('Y26QN', 2452, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-15 05:43:36', '2025-05-15 05:43:36', NULL, NULL),
('Y6GX5', 2093, 'tailoring', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-08 13:51:10', '2025-05-08 13:51:10', NULL, NULL),
('ZBUI0', 2093, 'tailoring', 150.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-14 04:57:25', '2025-05-14 04:58:40', NULL, NULL),
('ZOR5I', 2452, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, 'Nobe\n', '2025-05-15 07:41:36', '2025-05-15 07:41:36', NULL, NULL),
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
(4, '17F3B', 'in_process', 18, 'Order moved to production.', '2025-05-02 00:14:20');

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
(4, 'PJP9N', 0.00, 'downpayment', 'cash', '', 4, '2025-04-29 08:45:26', 'pending', NULL),
(5, 'B1PVL', 225.00, 'downpayment', 'cash', '', 3, '2025-04-29 09:10:21', 'pending', NULL),
(6, '17F3B', 200.00, 'downpayment', 'cash', '', 3, '2025-04-29 12:12:15', 'pending', NULL),
(7, 'DYRHS', 225.00, 'downpayment', 'cash', '', 9, '2025-05-02 09:57:10', 'pending', NULL),
(8, '2C4QQ', 50.00, 'downpayment', 'gcash', '09564563456', 9, '2025-05-05 04:18:46', 'rejected', NULL),
(9, '2C4QQ', 50.00, 'downpayment', 'gcash', '09564563456', 9, '2025-05-05 04:35:02', 'confirmed', NULL),
(10, 'AOQWW', 400.00, 'downpayment', 'gcash', '25489582KDKD', 9, '2025-05-05 08:43:53', 'confirmed', NULL),
(13, 'AOQWW', 400.00, '', 'cash', '', NULL, '2025-05-09 11:09:33', 'pending', NULL),
(16, 'RNSWI', 200.00, 'downpayment', 'gcash', '25489582KDKDDFR', 9, '2025-05-09 11:49:13', 'pending', '../uploads/payment_screenshots/payment_RNSWI_1746791353.jfif'),
(17, 'RNSWI', 200.00, 'downpayment', 'gcash', '25489582KDKDDFR', 9, '2025-05-09 12:10:46', 'pending', '../uploads/payment_screenshots/payment_RNSWI_1746792646.jfif'),
(18, 'IYGVD', 450.00, 'full_payment', 'cash', '', NULL, '2025-05-14 08:15:48', 'confirmed', ''),
(19, 'EOPR0', 500.00, 'full_payment', 'cash', '', NULL, '2025-05-14 08:55:18', 'pending', ''),
(20, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 09:12:43', 'pending', ''),
(21, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 09:16:18', 'pending', ''),
(22, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 09:19:21', 'pending', ''),
(23, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 09:25:36', 'pending', ''),
(24, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 09:25:59', 'pending', ''),
(25, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 09:28:19', 'pending', ''),
(26, 'ZBUI0', 75.00, 'downpayment', 'cash', '', 9, '2025-05-14 10:09:13', 'pending', ''),
(27, 'G9YIL', 1500.00, 'full_payment', 'gcash', '17726271h2h1u', NULL, '2025-05-14 10:51:18', 'pending', '../uploads/payment_screenshots/payment_G9YIL_1747219878.jpeg'),
(28, 'LI5GM', 1500.00, 'full_payment', 'cash', '', NULL, '2025-05-14 10:53:47', 'pending', ''),
(29, 'XTK7G', 400.00, 'full_payment', 'cash', '', NULL, '2025-05-14 18:47:20', 'pending', ''),
(30, 'ZRQ32', 400.00, 'downpayment', 'cash', '', NULL, '2025-05-14 18:51:38', 'pending', ''),
(31, '88WDD', 400.00, 'full_payment', 'gcash', 'SKAASJ238328', NULL, '2025-05-19 04:03:10', 'pending', '../uploads/payment_screenshots/payment_88WDD_1747627390.jpeg');

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
(19, 3, '88WDD', 'New payment of ₱400 received for Order #88WDD', 0, '2025-05-19 04:03:10'),
(20, 9, '88WDD', 'New payment of ₱400 received for Order #88WDD', 0, '2025-05-19 04:03:10'),
(21, 19, '88WDD', 'New payment of ₱400 received for Order #88WDD', 0, '2025-05-19 04:03:10');

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
  `quantity` int(11) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `sublimator_id` int(11) DEFAULT NULL,
  `allow_as_template` tinyint(1) DEFAULT 0,
  `completion_date` date DEFAULT NULL,
  `printing_type` enum('sublimation','silkscreen') NOT NULL DEFAULT 'sublimation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sublimation_orders`
--

INSERT INTO `sublimation_orders` (`sublimation_id`, `order_id`, `template_id`, `custom_design`, `design_path`, `quantity`, `size`, `color`, `instructions`, `sublimator_id`, `allow_as_template`, `completion_date`, `printing_type`) VALUES
(2, 'IYGVD', '28064', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(4, 'XTK7G', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(5, 'ROBCE', '28064', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(6, 'HZZ9B', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(7, 'Y26QN', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(8, 'CNT2I', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(9, 'HGBVJ', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(11, 'QWRAU', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(12, '8P4D9', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(13, '3XDSJ', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(14, 'SW0LN', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(15, 'XRRIH', '11017', 0, NULL, 1, NULL, NULL, NULL, NULL, 0, NULL, 'sublimation'),
(16, 'TUSK9', '11017', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(17, 'WG6M3', '11017', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(18, 'RBO3M', '28064', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(19, 'RSFUN', '34833', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(20, 'CRQB9', '34833', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(21, 'ZOR5I', '28064', 0, NULL, 1, NULL, NULL, 'Nobe\n', NULL, 0, NULL, 'sublimation'),
(22, 'H4DT6', '11017', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation'),
(23, '88WDD', '11017', 0, NULL, 1, NULL, NULL, '', NULL, 0, NULL, 'sublimation');

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
  `seamstress_appointment` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tailoring_orders`
--

INSERT INTO `tailoring_orders` (`tailoring_id`, `order_id`, `service_type`, `completion_date`, `needs_seamstress`, `seamstress_appointment`) VALUES
(1, 'TOHUXNP', 'alterations', '2025-04-30', 0, NULL),
(2, 'TO59SLN', 'custom made', '2025-04-30', 1, NULL),
(3, 'TOK8AVY', 'custom made', '2025-04-30', 1, NULL),
(4, 'OP0M0', 'alterations', NULL, 0, NULL),
(5, 'B2V53', 'repairs', NULL, 0, NULL),
(6, '15B5T', 'alterations', NULL, 0, NULL),
(7, 'EOPR0', 'alterations', NULL, 0, NULL),
(8, 'LI5GM', '', NULL, 0, NULL),
(9, 'G9YIL', '', NULL, 0, NULL),
(10, '0UWXJ', 'resize', NULL, 0, NULL),
(11, '9AIWF', 'resize', NULL, 0, NULL),
(12, 'P4ZHQ', 'resize', NULL, 0, NULL),
(13, '05R84', 'custom made', NULL, 0, NULL);

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
(3, 'admin', '$2y$10$uj6gVhIjPD1BmZpmTx4Kpe9ptNOaZi/TTo1xD3YJ3SolHJTzwPkHO', 'admin@gmail.com', 'Vieny Lou', 'Vicente', '09551224455', 'Admin', '2025-03-07 17:30:54', '2025-03-07 17:30:54'),
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
(10, 2452, '2f031686eac482c7b0117023c0e00a38246afd35bcf0a1c632b9c074a3cd37db', '2025-06-18 06:00:31', '2025-05-19 12:00:31', '2025-05-19 12:00:31');

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `alterations`
--
ALTER TABLE `alterations`
  MODIFY `alteration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8745;

--
-- AUTO_INCREMENT for table `custom_made`
--
ALTER TABLE `custom_made`
  MODIFY `custom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `declined_orders`
--
ALTER TABLE `declined_orders`
  MODIFY `decline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sublimation_orders`
--
ALTER TABLE `sublimation_orders`
  MODIFY `sublimation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sublimation_players`
--
ALTER TABLE `sublimation_players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tailoring_orders`
--
ALTER TABLE `tailoring_orders`
  MODIFY `tailoring_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
