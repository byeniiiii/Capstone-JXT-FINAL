-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2025 at 03:06 AM
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
(2, 18, 'customer', 'order_decline', 'Order #TOHUXNP declined: way klaro', NULL, '2025-04-22 16:12:07');

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
(8739, 'biyah', '$2y$10$by4vg6mmHatpuVWbRcOCoexzJgYPBQiqr/y5Bo1L5YWA0FhceEclO', 'biyah@gmail.com', 'Vieyah', 'Vicente', '09365743627', 'Purok 4, Balugo, Valencia, Negros Oriental', '2025-04-19 06:52:25', '2025-04-19 06:52:25');

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
(1, 'Order Declined', 8739, 'TOHUXNP', 'Your order #TOHUXNP was declined: way klaro', 1, '2025-04-22 16:12:07');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `order_type`, `total_amount`, `downpayment_amount`, `payment_method`, `payment_status`, `order_status`, `staff_id`, `manager_id`, `notes`, `created_at`, `updated_at`) VALUES
('', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-06 07:22:39', '2025-04-06 07:22:39'),
('6JYAB', 2093, 'tailoring', 1500.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-11 07:17:48', '2025-04-25 16:41:02'),
('AFRPA', 8739, 'tailoring', 200.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:43:54', '2025-04-22 15:58:38'),
('B1PVL', 2093, 'sublimation', 450.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-06 07:27:24', '2025-04-06 07:27:24'),
('GNOJL', 2093, 'sublimation', 500.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-17 16:46:47', '2025-04-25 17:01:10'),
('N77QE', 8739, 'tailoring', 150.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-19 06:53:20', '2025-04-19 06:53:20'),
('OP9SC', 8739, 'sublimation', 800.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 09:26:05', '2025-04-22 16:29:34'),
('OQIL6', 2093, 'tailoring', 1000.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-17 17:35:06', '2025-04-23 15:34:20'),
('PJP9N', 2093, 'sublimation', 400.00, 0.00, 'cash', 'pending', 'pending_approval', NULL, NULL, NULL, '2025-04-28 14:50:43', '2025-04-28 14:50:43'),
('TF3LG', 8739, 'tailoring', 100.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 07:23:57', '2025-04-22 16:34:22'),
('TO04658692', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', '', NULL, NULL, NULL, '2025-04-19 07:10:19', '2025-04-23 16:23:24'),
('TO59SLN', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:41:23', '2025-04-22 15:52:46'),
('TOHUXNP', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 18, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:24:39', '2025-04-22 16:12:07'),
('TOK8AVY', 8739, 'tailoring', 0.00, 0.00, 'cash', 'pending', 'declined', 15, NULL, '\nDeclined Reason: way klaro', '2025-04-19 07:45:01', '2025-04-22 15:36:07');

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
  `received_by` int(11) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `include_lower` enum('Yes','No') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tailoring_orders`
--

CREATE TABLE `tailoring_orders` (
  `tailoring_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `service_type` enum('alterations','repairs','resize','custom made') NOT NULL,
  `description` text DEFAULT NULL,
  `measurements` text NOT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `fabric_color` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `measurements_file` varchar(255) DEFAULT NULL,
  `needs_seamstress` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tailoring_orders`
--

INSERT INTO `tailoring_orders` (`tailoring_id`, `order_id`, `service_type`, `description`, `measurements`, `fabric_type`, `fabric_color`, `instructions`, `completion_date`, `quantity`, `measurements_file`, `needs_seamstress`) VALUES
(1, 'TOHUXNP', 'alterations', 'Shorten sleeves', 'no', '', '', 'no', '2025-04-30', 1, '', 0),
(2, 'TO59SLN', 'custom made', '', 'unrye wsg ', 'Other', '#832a2a', '', '2025-04-30', 1, 'uploads/measurements/TO59SLN_measurements_1745048483.jpg', 1),
(3, 'TOK8AVY', 'custom made', '', 'sdfsdfdsf', 'Polyester', '#5a2020', 'dsadas', '2025-04-30', 1, 'uploads/measurements/TOK8AVY_measurements_1745048701.jpg', 1);

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
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `declined_orders`
--
ALTER TABLE `declined_orders`
  ADD PRIMARY KEY (`decline_id`),
  ADD KEY `declined_by` (`declined_by`),
  ADD KEY `declined_orders_ibfk_1` (`order_id`);

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
  ADD KEY `sublimation_id` (`sublimation_id`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8740;

--
-- AUTO_INCREMENT for table `declined_orders`
--
ALTER TABLE `declined_orders`
  MODIFY `decline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `declined_orders`
--
ALTER TABLE `declined_orders`
  ADD CONSTRAINT `declined_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `declined_orders_ibfk_2` FOREIGN KEY (`declined_by`) REFERENCES `users` (`user_id`);

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
