-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2025 at 01:52 PM
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
(2, 'AOQWW', 19, 'ok nani', '2025-05-09 09:08:36');

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
(9, 'Order Ready for Pickup', 2093, 'AOQWW', 'Your order #AOQWW is now ready for pickup.', 0, '2025-05-08 23:56:03'),
(10, 'Payment Confirmed', 2093, 'AOQWW', 'Your payment of ₱400.00 for Order #AOQWW has been confirmed.', 0, '2025-05-09 01:08:36'),
(11, 'Payment Confirmed', 2093, '2C4QQ', 'Your payment of ₱50.00 for Order #2C4QQ has been confirmed.', 0, '2025-05-09 01:25:49'),
(12, 'Payment Rejected', 2093, '2C4QQ', 'Your payment for Order #2C4QQ was rejected: ayaw rani', 0, '2025-05-09 01:26:04'),
(13, 'Payment Received', 2093, 'AOQWW', 'Your payment of ₱400.00 for order #AOQWW has been received. Thank you!', 0, '2025-05-09 11:09:33'),
(15, 'Payment Verification Required', 2093, 'RNSWI', 'New payment submitted for Order #RNSWI requires verification', 0, '2025-05-09 11:49:13');

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
  `order_status` enum('pending_approval','declined','approved','in_process','ready_for_pickup','completed') DEFAULT 'pending_approval',
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
('17F3B', 2093, 'sublimation', 400.00, 0.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-04-29 12:09:39', '2025-05-02 00:14:20', NULL, NULL),
('2C4QQ', 2093, 'tailoring', 100.00, 50.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-05-05 03:56:18', '2025-05-09 01:25:49', NULL, NULL),
('3SQ5N', 8740, 'tailoring', 100.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 06:25:32', '2025-05-05 06:27:21', NULL, NULL),
('3U739', 2093, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: No amount given', '2025-04-29 11:52:46', '2025-05-02 01:21:38', NULL, NULL),
('63W3F', 8742, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 07:21:07', '2025-05-05 07:21:07', NULL, NULL),
('6JYAB', 2093, 'tailoring', 1500.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-11 07:17:48', '2025-04-25 16:41:02', NULL, NULL),
('6XGPL', 2093, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-08 14:37:26', '2025-05-08 14:37:26', NULL, NULL),
('AFRPA', 8739, 'tailoring', 200.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:43:54', '2025-04-22 15:58:38', NULL, NULL),
('AOQWW', 2093, 'sublimation', 800.00, 400.00, 'cash', 'fully_paid', 'ready_for_pickup', NULL, NULL, NULL, '2025-05-05 08:40:27', '2025-05-09 11:09:33', NULL, NULL),
('B1PVL', 2093, 'sublimation', 450.00, 0.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-04-06 07:27:24', '2025-05-05 05:04:11', NULL, NULL),
('DYRHS', 2093, 'sublimation', 450.00, 0.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-05-02 01:18:22', '2025-05-02 09:57:10', NULL, NULL),
('E3UTB', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-02 01:17:36', '2025-05-02 01:17:36', NULL, NULL),
('E4J4J', 8742, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 07:14:06', '2025-05-05 07:14:06', NULL, NULL),
('GNOJL', 2093, 'sublimation', 500.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-17 16:46:47', '2025-04-25 17:01:10', NULL, NULL),
('KC5S9', 2093, 'sublimation', 5000.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-29 11:50:53', '2025-04-29 11:50:53', NULL, NULL),
('LSCD8', 2093, 'tailoring', 100.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: walay klaro', '2025-05-05 02:33:02', '2025-05-05 08:42:11', NULL, NULL),
('N77QE', 8739, 'tailoring', 150.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-19 06:53:20', '2025-04-19 06:53:20', NULL, NULL),
('OP9SC', 8739, 'sublimation', 800.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 09:26:05', '2025-04-22 16:29:34', NULL, NULL),
('OQIL6', 2093, 'tailoring', 1000.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-17 17:35:06', '2025-04-23 15:34:20', NULL, NULL),
('PJP9N', 2093, 'sublimation', 400.00, 0.00, 'cash', 'downpayment_paid', 'in_process', NULL, NULL, NULL, '2025-04-28 14:50:43', '2025-05-05 05:04:11', NULL, NULL),
('RNSWI', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 08:47:08', '2025-05-05 08:47:36', NULL, NULL),
('TF3LG', 8739, 'tailoring', 100.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 07:23:57', '2025-04-22 16:34:22', NULL, NULL),
('TO04658692', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 07:10:19', '2025-04-23 16:23:24', NULL, NULL),
('TO59SLN', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:41:23', '2025-04-22 15:52:46', NULL, NULL),
('TOHUXNP', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:24:39', '2025-04-22 16:12:07', NULL, NULL),
('TOK8AVY', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 15, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:45:01', '2025-04-22 15:36:07', NULL, NULL),
('TP2BC', 8742, 'sublimation', 0.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-05-05 06:47:08', '2025-05-05 06:47:08', NULL, NULL),
('VGPGX', 2093, 'sublimation', 500.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-29 09:44:27', '2025-04-29 09:44:27', NULL, NULL),
('WSHFC', 2093, 'sublimation', 800.00, 0.00, 'cash', 'pending', 'approved', NULL, NULL, NULL, '2025-04-29 11:53:09', '2025-04-30 02:23:20', NULL, NULL),
('Y6GX5', 2093, 'tailoring', 800.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-05-08 13:51:10', '2025-05-08 13:51:10', NULL, NULL);

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
(16, 'RNSWI', 200.00, 'downpayment', 'gcash', '25489582KDKDDFR', 9, '2025-05-09 11:49:13', 'pending', '../uploads/payment_screenshots/payment_RNSWI_1746791353.jfif');

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
(3, 'TOK8AVY', 'custom made', '2025-04-30', 1, NULL);

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
  MODIFY `alteration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8743;

--
-- AUTO_INCREMENT for table `custom_made`
--
ALTER TABLE `custom_made`
  MODIFY `custom_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `declined_orders`
--
ALTER TABLE `declined_orders`
  MODIFY `decline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sublimation_orders`
--
ALTER TABLE `sublimation_orders`
  MODIFY `sublimation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sublimation_players`
--
ALTER TABLE `sublimation_players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tailoring_orders`
--
ALTER TABLE `tailoring_orders`
  MODIFY `tailoring_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
