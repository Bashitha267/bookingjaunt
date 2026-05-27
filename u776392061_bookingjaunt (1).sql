-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 27, 2026 at 03:52 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u776392061_bookingjaunt`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`setting_key`, `setting_value`) VALUES
('background_path', 'uploads/admin_bg/admin_bg_20260526_031323_cc5b715b.png');

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ad_title` varchar(255) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `package_name` varchar(255) DEFAULT NULL,
  `package_type` varchar(100) DEFAULT NULL,
  `package_price` decimal(10,2) DEFAULT NULL,
  `package_duration_days` int(11) DEFAULT NULL,
  `package_is_active` tinyint(1) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `user_id`, `ad_title`, `owner_name`, `package_id`, `package_name`, `package_type`, `package_price`, `package_duration_days`, `package_is_active`, `price`, `image_path`, `link_url`, `status`, `created_at`) VALUES
(3, 5, 'Test Add', 'Nimesh Bashitha', 2, 'Results Horizontal Strip (500x120px)', 'horizontal_strip_ad', 8000.00, 30, 1, 8000.00, 'uploads/ads/1778309708_ChatGPT Image May 3, 2026, 12_18_06 AM.png', '', 'active', '2026-05-09 06:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_packages`
--

CREATE TABLE `advertisement_packages` (
  `id` int(11) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_type` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duration_days` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `advertisement_packages`
--

INSERT INTO `advertisement_packages` (`id`, `package_name`, `package_type`, `price`, `duration_days`, `is_active`, `created_at`) VALUES
(1, 'Sidebar Vertical Unit (280x180px)', 'sidebar_ad', 5000.00, 30, 1, '2026-05-09 06:13:06'),
(2, 'Results Horizontal Strip (500x120px)', 'horizontal_strip_ad', 8000.00, 30, 1, '2026-05-09 06:13:06'),
(3, 'Mobile Scroll Unit (260x130px)', 'mobile_scroll_ad', 4000.00, 30, 1, '2026-05-09 06:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `amenities_master`
--

CREATE TABLE `amenities_master` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amenity_name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_popular` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `amenities_master`
--

INSERT INTO `amenities_master` (`id`, `category`, `amenity_name`, `icon`, `is_popular`) VALUES
(1, 'Basic', 'Free WiFi', 'fa-wifi', 1),
(3, 'Basic', 'Hot Water', 'fa-hot-tub', 1),
(5, 'Basic', 'Swimming Pool', 'fa-swimming-pool', 1),
(6, 'Nature & Wellness', 'Parking', 'fa-parking', 1),
(7, 'Nature & Wellness', 'Gym', 'fa-dumbbell', 0),
(8, 'Dining', 'Restaurant', 'fa-utensils', 1),
(10, 'Dining', 'Room Service', 'fa-concierge-bell', 0),
(11, 'Nature & Wellness', 'Garden', 'fa-leaf', 0),
(12, 'Nature & Wellness', 'Sea View', 'fa-umbrella-beach', 1),
(13, 'Services', '24-hour Front Desk', 'fa-user-clock', 0),
(14, 'Services', 'Laundry', 'fa-tshirt', 0),
(16, 'Services', 'Airport Shuttle', 'fa-taxi', 1),
(18, 'Adventures', 'Safari', 'fa-car-side', 1),
(19, 'Adventures', 'Surfing', 'fa-water', 1),
(20, 'Adventures', 'Cycling', 'fa-bicycle', 0),
(21, 'Adventures', 'Water Rafting', 'fa-water', 0),
(22, 'Adventures', 'Hiking', 'fa-mountain', 0),
(23, 'Adventures', 'Whale Watching', 'fa-water', 1),
(24, 'Adventures', 'Fishing', 'fa-fish', 0),
(25, 'Adventures', 'Rifle Shooting', 'fa-bullseye', 0),
(26, 'Adventures', 'Boat Rides', 'fa-ship', 0),
(27, 'Cultural', 'Traditional Dance', 'fa-theater-masks', 0),
(28, 'Cultural', 'Temple Tours', 'fa-vihara', 0),
(29, 'Cultural', 'Cooking Classes', 'fa-blender', 0),
(30, 'Nature & Wellness', 'Spa', 'fa-spa', 1),
(32, 'Nature & Wellness', 'Private Beach', 'fa-umbrella-beach', 1),
(33, 'Nature & Wellness', 'Beach Access', 'fa-sun', 0),
(34, 'Nature & Wellness', 'Lakes', 'fa-water', 0),
(35, 'Adventures', 'Elephant Watching', 'fa-binoculars', 1),
(36, 'Adventures', 'Wildlife', 'fa-paw', 1),
(37, 'Entertainment', 'Party Nights', 'fa-music', 0),
(38, 'Dining', 'BBQ Nights', 'fa-fire', 1),
(39, 'Dining', 'Bar', 'fa-glass-martini-alt', 1),
(40, 'Entertainment', 'Casino', 'fa-dice', 0),
(41, 'Entertainment', '8 Ball Pool', 'fa-bowling-ball', 0);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `booking_type` enum('online','inplace') DEFAULT 'online',
  `guest_name` varchar(255) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_nic` varchar(50) DEFAULT NULL,
  `guest_address` text DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `country` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `price_per_room` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `payment_description` text DEFAULT NULL,
  `payment_status` enum('pending','complete') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `property_id`, `room_id`, `user_id`, `booking_type`, `guest_name`, `guest_phone`, `guest_email`, `guest_nic`, `guest_address`, `room_number`, `check_in_date`, `check_out_date`, `adults`, `children`, `country`, `status`, `price_per_room`, `total_price`, `amount_paid`, `payment_description`, `payment_status`, `created_at`, `updated_at`) VALUES
(3, 2, 7, 3, 'online', 'sunil kumar', '1234', NULL, NULL, NULL, NULL, '2026-04-28', '2026-05-02', 1, 0, 'Sri Lanka', 'confirmed', 12000.00, 49000.00, 45000.00, '', 'pending', '2026-04-28 05:35:06', '2026-04-28 05:37:13'),
(4, 2, 7, 3, 'online', 'sunil kumar', '1222', 'asasas@sff', NULL, NULL, NULL, '0000-00-00', '0000-00-00', 1, 0, 'Sri Lanka', 'pending', 12000.00, 12000.00, 0.00, '', 'pending', '2026-04-28 05:54:59', '2026-04-28 05:54:59'),
(5, 2, 7, 4, 'online', 'Dulani Perera', '123456', 'dulani@gmail.com', NULL, NULL, NULL, '0000-00-00', '0000-00-00', 1, 0, 'Sri Lanka', 'pending', 12000.00, 12000.00, 0.00, '', 'pending', '2026-04-28 06:01:56', '2026-04-28 06:01:56'),
(6, 8, 10, 8, 'online', 'Motive Tales', '0711124046', 'talesmotive@gmail.com', NULL, NULL, NULL, '0000-00-00', '0000-00-00', 1, 0, 'Sri Lanka', 'pending', 25000.00, 25000.00, 0.00, '', 'pending', '2026-04-28 11:30:20', '2026-04-28 11:30:20'),
(7, 17, 11, 7, 'online', 'Aluthwawe Nimala Himi', '+94714935454', 'saliyadigitalagency@gmail.com', NULL, NULL, NULL, '0000-00-00', '0000-00-00', 1, 0, 'Sri Lanka', 'confirmed', 50000.00, 50000.00, 0.00, '', 'pending', '2026-04-28 11:52:09', '2026-04-28 11:54:29'),
(8, 8, 10, 1, 'online', 'kamal Fernando', '1222', 'kamala@gmail.com', NULL, NULL, NULL, '0000-00-00', '0000-00-00', 1, 0, 'Sri Lanka', 'pending', 25000.00, 50000.00, 0.00, '', 'pending', '2026-04-28 12:00:38', '2026-04-28 12:00:38'),
(9, 8, 10, 9, 'online', 'bashitha test', '1222', 'bashithaspc@gmail.com', NULL, NULL, NULL, '2026-04-29', '2026-05-02', 1, 0, 'Sri Lanka', 'pending', 25000.00, 75000.00, 0.00, '', 'pending', '2026-04-28 12:03:34', '2026-04-28 12:03:34'),
(10, 8, 10, 10, 'online', 'Sanduljith Jerome', '+94723173372', 'samarasooriya2007@gmail.com', NULL, NULL, NULL, '2026-04-30', '2026-05-01', 1, 0, 'Sri Lanka', 'pending', 25000.00, 25000.00, 0.00, '', 'pending', '2026-04-28 12:04:20', '2026-04-28 12:04:20'),
(11, 2, 7, 10, 'online', 'Sanduljith Jerome', '+94723173372', 'samarasooriya2007@gmail.com', NULL, NULL, NULL, '2026-04-30', '2026-05-01', 1, 0, 'Sri Lanka', 'confirmed', 12000.00, 12000.00, 12000.00, '', 'complete', '2026-04-28 12:09:00', '2026-05-12 07:15:55'),
(12, 17, 11, 11, 'online', 'Dhanushka Lakmal', '0711124046', 'lakmaldhanushka208@gmail.com', NULL, NULL, NULL, '2026-05-02', '2026-05-03', 1, 0, 'Sri Lanka', 'pending', 50000.00, 50000.00, 0.00, '', 'pending', '2026-05-01 01:49:19', '2026-05-01 01:49:19'),
(13, 17, 11, 12, 'online', 'Nimala Thero', '+94714935454', '+94714935454', NULL, NULL, NULL, '2026-05-09', '2026-05-06', 1, 0, 'Sri Lanka', 'pending', 50000.00, 100000.00, 0.00, '', 'pending', '2026-05-01 02:56:46', '2026-05-01 02:56:46'),
(14, 21, 21, 16, 'online', 'Nimala Thero', '0714886677', 'Nimala', NULL, NULL, NULL, '2026-05-16', '2026-05-23', 1, 0, 'Sri Lanka', 'pending', 200000.00, 1400000.00, 0.00, '', 'pending', '2026-05-07 04:32:00', '2026-05-07 04:32:00'),
(15, 23, 10, NULL, 'inplace', 'kamal Fernando', '457899', 'kamala@gmail.com', '5469', 'weewwewe', '102', '2026-05-12', '2026-05-16', 2, 0, 'Sri Lanka', 'checked_in', 15000.00, 16200.00, 16200.00, NULL, 'complete', '2026-05-11 18:05:43', '2026-05-11 18:16:30'),
(16, 2, 8, 5, 'online', 'Nimesh Bashitha', '212', 'nimeshspc2k17@gmail.com', NULL, NULL, NULL, '2026-05-12', '2026-05-15', 1, 0, 'Sri Lanka', 'checked_out', 8000.00, 31400.00, 57800.00, '', 'complete', '2026-05-12 07:50:17', '2026-05-15 09:06:48'),
(17, 2, 7, 2, 'inplace', 'Rev Elapathwawe Thero', '+94788154076', 'nimalathero@gmail.com', NULL, NULL, NULL, '2026-05-12', '2026-05-13', 1, 0, 'Sri Lanka', 'pending', 12000.00, 12000.00, 0.00, '', 'pending', '2026-05-12 11:55:49', '2026-05-12 11:55:49'),
(18, 7, 9, 2, 'inplace', 'Rev Elapathwawe Nimala Thero', '+94788154076', 'nimalathero@gmail.com', NULL, NULL, NULL, '2026-05-12', '2026-05-13', 1, 0, 'Sri Lanka', 'pending', 12000.00, 12000.00, 0.00, '', 'pending', '2026-05-12 11:56:46', '2026-05-12 11:56:46'),
(19, 7, 9, 2, 'online', 'Nimala Thero', '0714886677', 'admin@bookingjaunt.com', NULL, NULL, NULL, '2026-05-14', '2026-05-15', 1, 0, 'Sri Lanka', 'pending', 12000.00, 12000.00, 0.00, '', 'pending', '2026-05-13 09:04:33', '2026-05-13 09:04:33'),
(20, 7, 9, 2, 'online', 'Nimala Thero', '0714886677', 'admin@bookingjaunt.com', NULL, NULL, NULL, '2026-05-14', '2026-05-15', 1, 0, 'Sri Lanka', 'pending', 12000.00, 12000.00, 0.00, '', 'pending', '2026-05-13 09:37:49', '2026-05-13 09:37:49'),
(21, 26, 25, 22, 'online', 'Polwaththe Pansala', '075652478', 'polwaththepansala@gmail.com', NULL, NULL, NULL, '2026-05-14', '2026-05-15', 1, 0, 'Sri Lanka', 'confirmed', 7000.00, 7000.00, 0.00, '', 'pending', '2026-05-13 12:11:56', '2026-05-13 12:12:27'),
(22, 23, 24, 24, 'online', 'dln multimidea', '0711145156', 'dlnmultimidea@gmail.com', NULL, NULL, NULL, '2026-05-27', '2026-05-27', 1, 0, 'Sri Lanka', 'pending', 5000.00, 5000.00, 0.00, '', 'pending', '2026-05-26 03:36:45', '2026-05-26 03:36:45');

-- --------------------------------------------------------

--
-- Table structure for table `booking_expenses`
--

CREATE TABLE `booking_expenses` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_expenses`
--

INSERT INTO `booking_expenses` (`id`, `booking_id`, `description`, `amount`, `created_at`) VALUES
(1, 3, 'Laundry', 1000.00, '2026-04-28 05:36:53'),
(2, 15, 'Bar', 1200.00, '2026-05-11 18:16:15'),
(3, 16, 'Bar', 2400.00, '2026-05-14 14:32:21'),
(4, 16, 'lunch', 5000.00, '2026-05-15 09:05:44');

-- --------------------------------------------------------

--
-- Table structure for table `booking_payments`
--

CREATE TABLE `booking_payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_payments`
--

INSERT INTO `booking_payments` (`id`, `booking_id`, `amount`, `created_at`) VALUES
(1, 15, 10000.00, '2026-05-11 18:16:01'),
(2, 15, 1200.00, '2026-05-11 18:16:30'),
(3, 11, 12000.00, '2026-05-12 07:15:55'),
(4, 16, 24000.00, '2026-05-12 07:52:01'),
(5, 16, 2400.00, '2026-05-13 04:33:10'),
(6, 16, 31400.00, '2026-05-15 09:06:48');

-- --------------------------------------------------------

--
-- Table structure for table `boost_packages`
--

CREATE TABLE `boost_packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price_lkr` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boost_packages`
--

INSERT INTO `boost_packages` (`id`, `name`, `duration_days`, `price_lkr`, `is_active`, `created_at`) VALUES
(1, 'Basic Boost (7 Days)', 7, 5000.00, 1, '2026-05-09 05:22:43'),
(2, 'Premium Boost (30 Days)', 30, 15000.00, 1, '2026-05-09 05:22:55');

-- --------------------------------------------------------

--
-- Table structure for table `extra_services`
--

CREATE TABLE `extra_services` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `pricing_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_slides`
--

INSERT INTO `hero_slides` (`id`, `media_path`, `media_type`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'uploads/hero/hero_20260512_055340_de60b663.png', 'image', 1, 1, '2026-05-12 05:53:40'),
(2, 'uploads/hero/hero_20260512_055349_04608162.png', 'image', 2, 1, '2026-05-12 05:53:49'),
(3, 'uploads/hero/hero_20260512_055354_5eb9b8e8.png', 'image', 3, 1, '2026-05-12 05:53:54');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_service_payments`
--

CREATE TABLE `hotel_service_payments` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hotel_service_payments`
--

INSERT INTO `hotel_service_payments` (`id`, `property_id`, `amount`, `payment_date`, `proof_image`, `status`, `created_at`) VALUES
(1, 2, 12000.00, '2026-05-12', 'uploads/payments/proof_20260512_074520_8f40b7c0.jpeg', 'approved', '2026-05-12 07:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `popular_destinations`
--

CREATE TABLE `popular_destinations` (
  `id` int(11) NOT NULL,
  `destination_name` varchar(100) NOT NULL,
  `district_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `popular_destinations`
--

INSERT INTO `popular_destinations` (`id`, `destination_name`, `district_name`, `description`, `media_path`, `media_type`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Anuradapura', 'Anuradhapura', 'Ancient Ruins and Sacred Temples', 'uploads/destinations/dest_20260512_055412_dfb37904.png', 'image', 1, 1, '2026-05-12 05:54:12'),
(2, 'Colombo', 'Colombo', 'Hotels,Shopping,Night Life', 'uploads/destinations/dest_20260512_055447_94ab36c9.png', 'image', 2, 1, '2026-05-12 05:54:47'),
(3, 'Kandy', 'Kandy', 'Sri Dalada Maligawa', 'uploads/destinations/dest_20260512_055542_940646c0.png', 'image', 3, 1, '2026-05-12 05:55:42');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `business_type` enum('hotel','reception_hall','hostel','rest_hall','villa','dayouts','safari','resort','apartment') DEFAULT NULL,
  `hotel_category` enum('budget_friendly','luxury','super_luxury') DEFAULT NULL,
  `property_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `street_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `google_map_location` text DEFAULT NULL,
  `fixed_telephone` varchar(20) DEFAULT NULL,
  `mobile_telephone` varchar(20) DEFAULT NULL,
  `closest_police_station` varchar(255) DEFAULT NULL,
  `closest_hospital` varchar(255) DEFAULT NULL,
  `airport_distance` varchar(100) DEFAULT NULL,
  `closest_main_town` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `logo_image` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `business_email` varchar(100) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `manager_email` varchar(100) DEFAULT NULL,
  `manager_phone` varchar(20) DEFAULT NULL,
  `manager_nic` varchar(50) DEFAULT NULL,
  `manager_photo` varchar(255) DEFAULT NULL,
  `payout_percentage` decimal(5,2) DEFAULT 80.00,
  `commission_percentage` decimal(5,2) DEFAULT 20.00,
  `allow_payout_requests` tinyint(1) DEFAULT 1,
  `min_payout_amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `vat_percentage` decimal(5,2) DEFAULT 0.00,
  `service_charge_percentage` decimal(5,2) DEFAULT 0.00,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `id_required` tinyint(1) DEFAULT 1,
  `cancellation_policy` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `smoking_allowed` tinyint(1) DEFAULT 0,
  `pets_allowed` tinyint(1) DEFAULT 0,
  `events_allowed` tinyint(1) DEFAULT 0,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `commission_rate` int(11) DEFAULT 80,
  `rules_json` text DEFAULT NULL,
  `popular_amenities_json` text DEFAULT NULL,
  `custom_rules_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `business_type`, `hotel_category`, `property_name`, `description`, `street_address`, `city`, `district`, `province`, `country`, `google_map_location`, `fixed_telephone`, `mobile_telephone`, `closest_police_station`, `closest_hospital`, `airport_distance`, `closest_main_town`, `postal_code`, `logo_image`, `cover_image`, `contact_number`, `whatsapp_number`, `business_email`, `manager_name`, `manager_email`, `manager_phone`, `manager_nic`, `manager_photo`, `payout_percentage`, `commission_percentage`, `allow_payout_requests`, `min_payout_amount`, `currency`, `vat_percentage`, `service_charge_percentage`, `check_in_time`, `check_out_time`, `id_required`, `cancellation_policy`, `created_at`, `smoking_allowed`, `pets_allowed`, `events_allowed`, `bank_name`, `bank_account_number`, `bank_account_name`, `bank_branch`, `commission_rate`, `rules_json`, `popular_amenities_json`, `custom_rules_json`) VALUES
(2, 1, 'hotel', 'luxury', 'Grand Beach Hotel', 'Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.', '2225/2', 'Dankotuwa', 'Gampaha', 'Western', 'Sri Lanka', '', '+94774829123', '+94774829123', '', '', '', 'Negombo', '11260', '', 'uploads/prop_69f0432783b32.jpg', '', NULL, '', 'sunil', NULL, '0766302421', '1455666', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-23 12:45:56', 0, 0, 0, 'Commercial Branch', '21211212', 'sunil perera', 'Negombo', 80, '[]', '[]', '[]'),
(7, 5, 'hotel', 'luxury', 'testing hotel ', 'dsdsdsd', ' Henpitagedara ', 'Negombo', 'Gampaha', 'Western', 'Sri Lanka', '', '12222', '12222', '', '', '', 'gampaha', '', '', 'uploads/prop_69f0642ba452b.jpg', '', NULL, '', 'dsds', NULL, 'dsd', 'dsdsd', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 07:40:16', 0, 0, 0, 'commercial', '124587', 'sdasd', 'negombo', 80, '{\"no_pets\":\"1\",\"quiet_hours\":\"1\"}', '[\"wifi\",\"pool\",\"parking\"]', '[]'),
(8, 5, 'hotel', 'super_luxury', 'testh22', 'weaswdddddddddddd', 'dsadasd', 'sdfsf', 'Anuradhapura', 'North Central', 'Sri Lanka', '', 'dsds', 'dsdsd', '', '', '', 'sdfsf', '', '', 'uploads/prop_69f065238bc28.jpg', '', NULL, '', 'dsad', NULL, 'dsdsd', 'dsds', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 07:44:35', 0, 0, 0, 'commercial', '124587', 'sdasd', 'negombo', 80, '{\"quiet_hours\":\"1\"}', '[\"pool\",\"parking\"]', '[]'),
(17, 8, 'hotel', 'budget_friendly', 'hotel in samal', 'iughiugiugiu8uij', 'MATHALE ROAD KURUNDANKUKLAMA ', 'KURUNDANKULAMA ', 'Anuradhapura', 'North Central', 'Sri Lanka', '', '0711124046', '0711124046', '', '', '', 'ANURADHAPURA ', '', 'uploads/prop_69f09e82ebe32.jpg', 'uploads/prop_69f09e89a10ca.jpg', '', NULL, '', 'DHANUSHKA LAKMLA', NULL, '0711124046', '140015345V', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 11:50:30', 0, 0, 0, 'BANK OF CEYLOAN ', '000254587458', 'DHANUSHKA', 'ANURADHAPURA', 80, '[]', '[]', '[]'),
(18, 10, 'hotel', 'luxury', 'hotel carl', 'luxary', '225/5 Henpitagedera Road', 'Marandagahamula', 'Galle', 'Eastern', 'Sri Lanka', '', '+94723173372', '+94723173372', '', '', '', 'Marandagahamula', '11870', '', 'uploads/prop_69f0a4fb7dfa3.jpg', '', NULL, '', 'Sanduljith', NULL, 'Samarasooriya', '200722201406', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 12:17:59', 0, 0, 0, 'commercial ', '1234688 19', 'Jeraome', 'yuhh', 80, '{\"no_alcohol\":\"1\"}', '[\"wifi\",\"pool\",\"parking\"]', '[]'),
(19, 6, 'reception_hall', 'budget_friendly', 'Samarasiri Hotel ', 'Located in a convenient and peaceful area, [Hotel Name] offers comfortable and affordable accommodation for travelers who seek value and simplicity. Our rooms are clean, well-maintained, and equipped with essential facilities including free Wi-Fi, air conditioning, and 24-hour service.\r\n\r\nWhether you are traveling for business or leisure, our friendly staff ensures a pleasant and hassle-free stay. Enjoy easy access to nearby attractions, local restaurants, and transport facilities.\r\n\r\nAt [Hotel Name], we believe in providing quality service at an affordable price — making your stay comfortable without breaking your budget', 'No 720/A, Bandaranayaka Mawatha, Maharagama ', 'Maharagama ', 'Colombo', 'Western', 'Sri Lanka', '', '+94714935454', '+94714935454', 'Maharagama ', 'Maharagama', '250', 'Maharagama', '32608', 'uploads/prop_69f41c92e924b.jpg', 'uploads/prop_69f41c9667c6b.jpg', '', NULL, '', 'Sidhuranga Bandara ', NULL, '0711145052', '128452454v', 'uploads/prop_69f06dfc59ce7.png', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-01 03:34:16', 0, 0, 0, 'Bank Of Ceylon ', '000322355484', 'Dhanushaka Lankamakl', 'Anuradhapura', 80, '{\"no_alcohol\":\"1\",\"no_smoking\":\"1\",\"no_parties\":\"1\",\"no_pets\":\"1\",\"quiet_hours\":\"1\",\"no_outside_food\":\"1\"}', '[]', '[]'),
(20, 6, 'hostel', 'budget_friendly', 'Mileniyam Hotel ', 'Experience elegance and comfort at [Hotel Name], where modern luxury meets warm hospitality. Designed to offer a relaxing and stylish stay, our hotel features beautifully furnished rooms, high-speed Wi-Fi, fine dining restaurants, and personalized guest services.\r\n\r\nWake up to stunning views, unwind in our premium facilities, and enjoy a peaceful atmosphere tailored for both leisure and business travelers.\r\n\r\nOur dedicated team is committed to delivering exceptional service, ensuring every guest enjoys a memorable and refined experience.\r\n\r\nAt [Hotel Name], every moment is crafted to offer comfort, sophistication, and unforgettable memories', 'mathale road ', 'Anuradhapura', 'Anuradhapura', 'North Central', 'Sri Lanka', '', '0713855047', '0713855047', 'Anuradhapura', 'Anuradhapura', '250', 'Anuradhapura', '50000', 'uploads/prop_69f42a5c2e561.jpg', 'uploads/prop_69f42a6a5d555.jpg', '', NULL, '', 'Manjula ', NULL, '0752355684', '945084957v', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-01 04:29:24', 0, 0, 0, 'Bank Of Ceylon ', '000322355484', 'Dhanushaka Lankamakl', 'Anuradhapura', 80, '[]', '[]', '[]'),
(21, 13, 'hotel', 'super_luxury', 'hetel with Sha', 'Experience elegance and comfort at [Hotel Name], where modern luxury meets warm hospitality. Designed to offer a relaxing and stylish stay, our hotel features beautifully furnished rooms, high-speed Wi-Fi, fine dining restaurants, and personalized guest services.\r\n\r\nWake up to stunning views, unwind in our premium facilities, and enjoy a peaceful atmosphere tailored for both leisure and business travelers.\r\n\r\nOur dedicated team is committed to delivering exceptional service, ensuring every guest enjoys a memorable and refined experience.\r\n\r\nAt [Hotel Name], every moment is crafted to offer comfort, sophistication, and unforgettable memories', 'No 159/A, Siriwardardana Mawatha wellala, Kandy ', 'Kandy', 'Kandy', 'Central', 'Sri Lanka', '', '0722255854', '0722255854', '', '', '', 'Kandy', '', 'uploads/prop_69f4316644994.jpg', 'uploads/prop_69f4316ae2e73.jpg', '', NULL, '', 'Kalhara', NULL, '0755526547', '780058847v', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-01 04:56:43', 0, 0, 0, 'bgi', '0002215254782', 'sloklk', 'iugiuj', 80, '[]', '[]', '[]'),
(22, 17, 'dayouts', 'budget_friendly', 'sadun', 'yugy8t8yt8t7ygi8yg8t8', 'noi 123 nihy hftryb ', 'kandy', 'Kandy', 'Central', 'Sri Lanka', '', '0714887744', '0714887744', '', '', '', 'kandy', '', 'uploads/prop_69fee2fc56e8a.jpg', 'uploads/prop_69fee2ff92aad.jpg', '', NULL, '', 'sasdun', NULL, 'kumara', '538855987v', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-09 07:34:10', 0, 0, 0, 'i9i9-0o', '6576564764465', '8y90uoi0', 'lojoijoi', 80, '[]', '[]', '[]'),
(23, 5, 'dayouts', 'budget_friendly', 'Day out test', 'dsdsdasdadsd', 'sdasd', 'gampaha', 'Gampaha', 'Western', 'Sri Lanka', '', '4586', '4785', '', '', '', 'gampaha', '', '', 'uploads/prop_69ff11ca61ad2.jpg', '', NULL, '', 'dsdsd', NULL, '456', '4455', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-09 10:53:56', 0, 0, 0, 'commercial', '1221', 'sdasd', 'negombo', 80, '[]', '[\"wifi\",\"pool\",\"parking\"]', '[]'),
(24, 15, 'dayouts', 'budget_friendly', 'Colombo hotel', 'Hagahsssbsbs', 'Mathale Road ,', 'Colombo', 'Colombo', 'Western', 'Sri Lanka', '', '0787565456', '0787565456', '', '', '', 'Colombo ', '50000', '', '', '', NULL, '', 'Sadanu', NULL, 'Kumara', '94776565v', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-09 11:02:11', 0, 0, 0, 'Fggh', '2356754444', '&8=$$$%&&', 'Ggvbb', 80, '[]', '[]', '[]'),
(26, 21, 'hotel', 'budget_friendly', 'Siduranga Hotel In Anuradhapura', 'oeiho0igoijoithroiktjroithroitrjoryoitr', 'MATHALE ROAD KURUNDANKUKLAMA ', 'KURUNDANKULAMA ', 'Anuradhapura', 'North Central', 'Sri Lanka', '', '0711124046', '0711124046', '', '', '', 'ANURADHAPURA ', '', 'uploads/prop_6a0450a4c7e8f.jpg', 'uploads/prop_6a0450a5d176b.jpg', '', NULL, '', 'DHANUSHKA LAKMLA', NULL, '0711124046', '140015345V', 'uploads/prop_6a0450b8c420f.png', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-13 12:06:45', 0, 0, 0, 'BANK OF CEYLOAN ', '000254587458', 'DHANUSHKA', 'ANURADHAPURA', 80, '[]', '[]', '[]'),
(27, 24, 'villa', 'budget_friendly', 'ij\'l\'[pk[pfr', 'wgqhgwe4', 'eqdb rhyjryjyrjyyt', 'Ampara', 'Ampara', 'Eastern', 'Sri Lanka', '', '0711124547', '0711124784', '', '', '', 'Ampara', '', 'uploads/prop_6a1511c103933.jpg', 'uploads/prop_6a1511bc3280f.jpg', '', NULL, '', 'Sameera', NULL, '0712225544', '530054877v', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-26 03:24:40', 0, 0, 0, 'boc', '5654654984654', 'siduranga', 'anuradhapura', 80, '[]', '[]', '[]');

-- --------------------------------------------------------

--
-- Table structure for table `property_amenities`
--

CREATE TABLE `property_amenities` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_amenities`
--

INSERT INTO `property_amenities` (`id`, `property_id`, `amenity_id`) VALUES
(19, 2, 26),
(20, 2, 20),
(21, 2, 19),
(22, 2, 1),
(23, 2, 3),
(24, 2, 5),
(25, 2, 29),
(26, 2, 28),
(27, 2, 39),
(28, 2, 38),
(29, 2, 8),
(30, 2, 37),
(31, 2, 33),
(32, 2, 6),
(33, 2, 32),
(34, 2, 12),
(35, 2, 16),
(36, 2, 14),
(37, 7, 26),
(38, 7, 20),
(39, 8, 20),
(40, 8, 35),
(41, 8, 24),
(42, 8, 34),
(43, 8, 6),
(44, 17, 26),
(45, 17, 20),
(46, 17, 35),
(47, 17, 24),
(48, 17, 22),
(49, 17, 25),
(50, 17, 18),
(51, 17, 19),
(52, 17, 21),
(53, 17, 23),
(54, 17, 36),
(55, 17, 1),
(56, 17, 3),
(57, 17, 5),
(58, 17, 29),
(59, 17, 28),
(60, 17, 27),
(61, 17, 10),
(62, 18, 26),
(63, 18, 20),
(64, 18, 35),
(65, 18, 24),
(66, 18, 41),
(67, 18, 33),
(68, 18, 11),
(69, 19, 26),
(70, 19, 18),
(71, 19, 19),
(72, 19, 23),
(73, 19, 36),
(74, 19, 3),
(75, 19, 27),
(76, 19, 37),
(77, 19, 34),
(78, 19, 16),
(79, 20, 18),
(80, 20, 1),
(81, 20, 3),
(82, 20, 5),
(83, 20, 29),
(84, 20, 28),
(85, 20, 27),
(86, 20, 39),
(87, 20, 38),
(88, 20, 10),
(89, 20, 37),
(90, 20, 7),
(91, 21, 20),
(92, 21, 18),
(93, 21, 36),
(94, 21, 1),
(95, 21, 3),
(96, 21, 5),
(97, 21, 28),
(98, 21, 27),
(99, 21, 39),
(100, 21, 38),
(101, 21, 8),
(102, 21, 10),
(103, 21, 41),
(104, 21, 40),
(105, 21, 37),
(106, 21, 11),
(107, 21, 34),
(108, 21, 32),
(109, 21, 12),
(110, 21, 13),
(111, 21, 16),
(112, 21, 14),
(114, 22, 26),
(115, 22, 20),
(116, 22, 35),
(117, 22, 24),
(118, 22, 22),
(119, 22, 25),
(120, 22, 18),
(121, 22, 19),
(122, 22, 23),
(123, 22, 36),
(124, 22, 1),
(125, 22, 3),
(126, 22, 5),
(127, 22, 29),
(128, 22, 28),
(129, 22, 27),
(130, 22, 39),
(131, 22, 38),
(132, 22, 8),
(133, 22, 10),
(134, 23, 26),
(135, 23, 20),
(136, 23, 35),
(137, 23, 29),
(138, 23, 28),
(139, 23, 11),
(140, 23, 12),
(141, 24, 26),
(142, 24, 20),
(143, 24, 35),
(144, 24, 24),
(145, 24, 22),
(146, 24, 25),
(147, 24, 18),
(148, 24, 19),
(180, 26, 26),
(181, 26, 20),
(182, 26, 35),
(183, 26, 24),
(184, 26, 22),
(185, 26, 25),
(186, 27, 26),
(187, 27, 20),
(188, 27, 35),
(189, 27, 24),
(190, 27, 22),
(191, 27, 25),
(192, 27, 18),
(193, 27, 19),
(194, 27, 21),
(195, 27, 23),
(196, 27, 36),
(197, 27, 1),
(198, 27, 3),
(199, 27, 5),
(200, 27, 29),
(201, 27, 28),
(202, 27, 27),
(203, 27, 39),
(204, 27, 38),
(205, 27, 8),
(206, 27, 10),
(207, 27, 41),
(208, 27, 40),
(209, 27, 37),
(210, 27, 33),
(211, 27, 11),
(212, 27, 7),
(213, 27, 34),
(214, 27, 6),
(215, 27, 32),
(216, 27, 12),
(217, 27, 30),
(218, 27, 13),
(219, 27, 16),
(220, 27, 14);

-- --------------------------------------------------------

--
-- Table structure for table `property_bank_details`
--

CREATE TABLE `property_bank_details` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_boosts`
--

CREATE TABLE `property_boosts` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `duration_days` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `payment_status` varchar(50) DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_boosts`
--

INSERT INTO `property_boosts` (`id`, `property_id`, `package_id`, `start_date`, `duration_days`, `status`, `payment_status`, `amount`, `created_at`) VALUES
(1, 2, 1, '2026-05-09', 7, 'active', 'success', 5000.00, '2026-05-09 06:47:29'),
(2, 7, 1, '2026-05-09', 7, 'active', 'success', 5000.00, '2026-05-09 06:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `property_custom_amenities`
--

CREATE TABLE `property_custom_amenities` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `amenity_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_media`
--

CREATE TABLE `property_media` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `media_type` enum('image','video') DEFAULT 'image',
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_media`
--

INSERT INTO `property_media` (`id`, `property_id`, `media_path`, `media_type`, `is_featured`, `sort_order`, `created_at`) VALUES
(10, 2, 'uploads/prop_69ed1a0587a51.webp', 'image', 0, 0, '2026-04-28 05:19:38'),
(11, 2, 'uploads/prop_69ed1a08f00b1.jpg', 'image', 0, 0, '2026-04-28 05:19:38'),
(12, 2, 'uploads/prop_69ed1a0eae36a.jpg', 'image', 0, 0, '2026-04-28 05:19:38'),
(13, 7, 'uploads/prop_69f0642ba452b.jpg', 'image', 1, 0, '2026-04-28 07:40:16'),
(14, 7, 'uploads/prop_69f06451dda12.jpg', 'image', 0, 1, '2026-04-28 07:40:16'),
(15, 7, 'uploads/prop_69f06454c9a78.jpg', 'image', 0, 2, '2026-04-28 07:40:16'),
(16, 7, 'uploads/prop_69f0645916d86.jpg', 'image', 0, 3, '2026-04-28 07:40:16'),
(17, 8, 'uploads/prop_69f065238bc28.jpg', 'image', 1, 0, '2026-04-28 07:44:35'),
(18, 8, 'uploads/prop_69f065563a701.jpg', 'image', 0, 1, '2026-04-28 07:44:35'),
(19, 8, 'uploads/prop_69f06559ea405.jpg', 'image', 0, 2, '2026-04-28 07:44:35'),
(20, 8, 'uploads/prop_69f0655c9e0a3.jpg', 'image', 0, 3, '2026-04-28 07:44:35'),
(21, 17, 'uploads/prop_69f09e89a10ca.jpg', 'image', 1, 0, '2026-04-28 11:50:30'),
(22, 17, 'uploads/prop_69f09ef73cc1d.jpg', 'image', 0, 1, '2026-04-28 11:50:30'),
(23, 17, 'uploads/prop_69f09ef58b6b3.jpg', 'image', 0, 2, '2026-04-28 11:50:30'),
(24, 17, 'uploads/prop_69f09efd03dc0.jpg', 'image', 0, 3, '2026-04-28 11:50:30'),
(25, 18, 'uploads/prop_69f0a4fb7dfa3.jpg', 'image', 1, 0, '2026-04-28 12:17:59'),
(26, 18, 'uploads/prop_69f0a49912301.jpg', 'image', 0, 1, '2026-04-28 12:17:59'),
(27, 19, 'uploads/prop_69f41c9667c6b.jpg', 'image', 1, 0, '2026-05-01 03:34:16'),
(28, 19, 'uploads/prop_69f06e853ec4a.jpg', 'image', 0, 1, '2026-05-01 03:34:16'),
(29, 19, 'uploads/prop_69f06e9665f5e.jpg', 'image', 0, 2, '2026-05-01 03:34:16'),
(30, 19, 'uploads/prop_69f06e9866127.jpg', 'image', 0, 3, '2026-05-01 03:34:16'),
(31, 19, 'uploads/prop_69f06e8a270a9.jpg', 'image', 0, 4, '2026-05-01 03:34:16'),
(32, 19, 'uploads/prop_69f06e97b557a.jpg', 'image', 0, 5, '2026-05-01 03:34:16'),
(33, 20, 'uploads/prop_69f42a6a5d555.jpg', 'image', 1, 0, '2026-05-01 04:29:24'),
(34, 20, 'uploads/prop_69f42c16daa4e.jpg', 'image', 0, 1, '2026-05-01 04:29:24'),
(35, 20, 'uploads/prop_69f42c1be9d2c.jpg', 'image', 0, 2, '2026-05-01 04:29:24'),
(36, 20, ',', 'video', 0, 1, '2026-05-01 04:29:24'),
(37, 21, 'uploads/prop_69f4316ae2e73.jpg', 'image', 1, 0, '2026-05-01 04:56:43'),
(38, 21, 'uploads/prop_69f43264ab0f6.jpg', 'image', 0, 1, '2026-05-01 04:56:43'),
(39, 21, 'uploads/prop_69f432668af62.jpg', 'image', 0, 2, '2026-05-01 04:56:43'),
(40, 21, 'uploads/prop_69f4326aa6d8a.jpg', 'image', 0, 3, '2026-05-01 04:56:43'),
(41, 21, 'uploads/prop_69f4326b76456.jpg', 'image', 0, 4, '2026-05-01 04:56:43'),
(42, 21, 'uploads/prop_69f4326eccf83.jpg', 'image', 0, 5, '2026-05-01 04:56:43'),
(43, 22, 'uploads/prop_69fee2ff92aad.jpg', 'image', 1, 0, '2026-05-09 07:34:10'),
(44, 23, 'uploads/prop_69ff11ca61ad2.jpg', 'image', 1, 0, '2026-05-09 10:53:56'),
(45, 23, 'uploads/prop_69ff12210f5cc.jpg', 'image', 0, 1, '2026-05-09 10:53:56'),
(46, 23, 'uploads/prop_69ff122710887.jpg', 'image', 0, 2, '2026-05-09 10:53:56'),
(47, 23, 'uploads/prop_69ff122e969d8.webp', 'image', 0, 3, '2026-05-09 10:53:56'),
(48, 24, ',,,,', 'image', 0, 1, '2026-05-09 11:02:11'),
(49, 24, ',', 'video', 0, 1, '2026-05-09 11:02:11'),
(58, 26, 'uploads/prop_6a0450a5d176b.jpg', 'image', 1, 0, '2026-05-13 12:06:45'),
(59, 26, 'uploads/prop_6a0451ed49fba.jpg', 'image', 0, 1, '2026-05-13 12:06:45'),
(60, 26, 'uploads/prop_6a04521045233.jpg', 'image', 0, 2, '2026-05-13 12:06:45'),
(61, 26, 'uploads/prop_6a045212914d1.jpg', 'image', 0, 3, '2026-05-13 12:06:45'),
(62, 26, 'uploads/prop_6a045238ddb73.jpg', 'image', 0, 4, '2026-05-13 12:06:45'),
(63, 26, 'uploads/prop_6a04522ab41a0.jpg', 'image', 0, 5, '2026-05-13 12:06:45'),
(64, 26, ',', 'video', 0, 1, '2026-05-13 12:06:45'),
(65, 27, 'uploads/prop_6a1511bc3280f.jpg', 'image', 1, 0, '2026-05-26 03:24:40'),
(66, 27, 'uploads/prop_6a15125a79693.jpg', 'image', 0, 1, '2026-05-26 03:24:40'),
(67, 27, 'uploads/prop_6a15125d041c2.jpg', 'image', 0, 2, '2026-05-26 03:24:40'),
(68, 27, 'uploads/prop_6a15125fb16f2.jpg', 'image', 0, 3, '2026-05-26 03:24:40'),
(69, 27, 'uploads/prop_6a1512635fb67.jpg', 'image', 0, 4, '2026-05-26 03:24:40'),
(70, 27, 'uploads/prop_6a1512687347c.jpg', 'image', 0, 5, '2026-05-26 03:24:40');

-- --------------------------------------------------------

--
-- Table structure for table `property_requests`
--

CREATE TABLE `property_requests` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('edit','delete') NOT NULL,
  `old_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_requests`
--

INSERT INTO `property_requests` (`id`, `property_id`, `user_id`, `request_type`, `old_data`, `new_data`, `status`, `admin_notes`, `created_at`) VALUES
(1, 2, 1, 'edit', '{\"id\":2,\"owner_id\":1,\"business_type\":\"hotel\",\"hotel_category\":\"luxury\",\"property_name\":\"Grand Beach Hotel\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"city\":\"Dankotuwa\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"closest_main_town\":\"Negombo\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"uploads\\/prop_69ea11815095a.jpg\",\"contact_number\":\"\",\"whatsapp_number\":null,\"business_email\":\"\",\"manager_name\":\"sunil\",\"manager_email\":null,\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"manager_photo\":\"\",\"payout_percentage\":\"80.00\",\"commission_percentage\":\"20.00\",\"allow_payout_requests\":1,\"min_payout_amount\":\"0.00\",\"currency\":\"USD\",\"vat_percentage\":\"0.00\",\"service_charge_percentage\":\"0.00\",\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"id_required\":1,\"cancellation_policy\":\"\",\"created_at\":\"2026-04-23 18:15:56\",\"smoking_allowed\":0,\"pets_allowed\":0,\"events_allowed\":0,\"bank_name\":null,\"bank_account_number\":null,\"bank_account_name\":null,\"bank_branch\":null,\"commission_rate\":80,\"rules_json\":null,\"popular_amenities_json\":null,\"custom_rules_json\":null}', '{\"business_type\":\"hotel\",\"property_name\":\"Grand Beach Hotel\",\"hotel_category\":\"luxury\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"city\":\"Dankotuwa\",\"closest_main_town\":\"Negombo\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"\",\"manager_photo\":\"\",\"manager_name\":\"sunil\",\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"amenities\":[\"26\",\"20\",\"19\",\"1\",\"3\",\"5\",\"29\",\"28\",\"39\",\"38\",\"8\",\"37\",\"33\",\"6\",\"32\",\"12\",\"16\",\"14\"],\"special_amenities\":[\"\"],\"rooms\":[{\"image\":\"uploads\\/prop_69ea11dfc93bf.jpg\",\"name\":\"Couple Room\",\"count\":\"1\",\"is_hall\":\"0\",\"adults\":\"2\",\"children\":\"0\",\"price_lkr\":\"12000.00\",\"price_usd\":\"20.00\"},{\"image\":\"uploads\\/prop_69ea11e58818f.jpg\",\"name\":\"Single Room\",\"count\":\"1\",\"is_hall\":\"0\",\"adults\":\"1\",\"children\":\"0\",\"price_lkr\":\"8000.00\",\"price_usd\":\"15.00\"}],\"bank_name\":\"Commercial Branch\",\"bank_branch\":\"Negombo\",\"bank_account_name\":\"sunil perera\",\"bank_account_number\":\"21211212\",\"commission_rate\":\"80\",\"property_photos\":[\"uploads\\/prop_69ed180fbd7d2.jpg\",\"uploads\\/prop_69ed18133b41f.webp\",\"uploads\\/prop_69ed181a8a0cc.jpg\",\"\",\"\"],\"property_videos\":[\"\",\"\"],\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"cancellation_time\":\"24\",\"cancellation_details\":\"\",\"action\":\"request_edit\",\"property_id\":\"2\"}', 'approved', '', '2026-04-25 19:38:17'),
(2, 2, 1, 'edit', '{\"id\":2,\"owner_id\":1,\"business_type\":\"hotel\",\"hotel_category\":\"luxury\",\"property_name\":\"Grand Beach Hotel\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"city\":\"Dankotuwa\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"closest_main_town\":\"Negombo\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"uploads\\/prop_69ea11815095a.jpg\",\"contact_number\":\"\",\"whatsapp_number\":null,\"business_email\":\"\",\"manager_name\":\"sunil\",\"manager_email\":null,\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"manager_photo\":\"\",\"payout_percentage\":\"80.00\",\"commission_percentage\":\"20.00\",\"allow_payout_requests\":1,\"min_payout_amount\":\"0.00\",\"currency\":\"USD\",\"vat_percentage\":\"0.00\",\"service_charge_percentage\":\"0.00\",\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"id_required\":1,\"cancellation_policy\":\"\",\"created_at\":\"2026-04-23 18:15:56\",\"smoking_allowed\":0,\"pets_allowed\":0,\"events_allowed\":0,\"bank_name\":null,\"bank_account_number\":null,\"bank_account_name\":null,\"bank_branch\":null,\"commission_rate\":80,\"rules_json\":null,\"popular_amenities_json\":null,\"custom_rules_json\":null}', '{\"business_type\":\"hotel\",\"property_name\":\"Grand Beach Hotel\",\"hotel_category\":\"luxury\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"city\":\"Dankotuwa\",\"closest_main_town\":\"Negombo\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"\",\"manager_photo\":\"\",\"manager_name\":\"sunil\",\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"amenities\":[\"26\",\"20\",\"19\",\"1\",\"3\",\"5\",\"29\",\"28\",\"39\",\"38\",\"8\",\"37\",\"33\",\"6\",\"32\",\"12\",\"16\",\"14\"],\"special_amenities\":[\"\"],\"rooms\":[{\"image\":\"uploads\\/prop_69ea11dfc93bf.jpg\",\"name\":\"Couple Room\",\"count\":\"1\",\"is_hall\":\"0\",\"adults\":\"2\",\"children\":\"0\",\"price_lkr\":\"12000.00\",\"price_usd\":\"20.00\"},{\"image\":\"uploads\\/prop_69ea11e58818f.jpg\",\"name\":\"Single Room\",\"count\":\"1\",\"is_hall\":\"0\",\"adults\":\"1\",\"children\":\"0\",\"price_lkr\":\"8000.00\",\"price_usd\":\"15.00\"}],\"bank_name\":\"Commercial Branch\",\"bank_branch\":\"Negombo\",\"bank_account_name\":\"sunil perera\",\"bank_account_number\":\"21211212\",\"commission_rate\":\"80\",\"property_photos\":[\"uploads\\/prop_69ed1a0587a51.webp\",\"uploads\\/prop_69ed1a08f00b1.jpg\",\"uploads\\/prop_69ed1a0eae36a.jpg\",\"\",\"\"],\"property_videos\":[\"\",\"\"],\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"cancellation_time\":\"24\",\"cancellation_details\":\"\",\"action\":\"request_edit\",\"property_id\":\"2\"}', 'approved', '', '2026-04-25 19:46:29'),
(3, 2, 1, 'edit', '{\"id\":2,\"owner_id\":1,\"business_type\":\"hotel\",\"hotel_category\":\"luxury\",\"property_name\":\"Grand Beach Hotel\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"city\":\"Dankotuwa\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"closest_main_town\":\"Negombo\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"\",\"contact_number\":\"\",\"whatsapp_number\":null,\"business_email\":\"\",\"manager_name\":\"sunil\",\"manager_email\":null,\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"manager_photo\":\"\",\"payout_percentage\":\"80.00\",\"commission_percentage\":\"20.00\",\"allow_payout_requests\":1,\"min_payout_amount\":\"0.00\",\"currency\":\"USD\",\"vat_percentage\":\"0.00\",\"service_charge_percentage\":\"0.00\",\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"id_required\":1,\"cancellation_policy\":\"\",\"created_at\":\"2026-04-23 18:15:56\",\"smoking_allowed\":0,\"pets_allowed\":0,\"events_allowed\":0,\"bank_name\":\"Commercial Branch\",\"bank_account_number\":\"21211212\",\"bank_account_name\":\"sunil perera\",\"bank_branch\":\"Negombo\",\"commission_rate\":80,\"rules_json\":\"[]\",\"popular_amenities_json\":\"[]\",\"custom_rules_json\":\"[]\"}', '{\"business_type\":\"hotel\",\"property_name\":\"Grand Beach Hotel\",\"hotel_category\":\"luxury\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"city\":\"Dankotuwa\",\"closest_main_town\":\"Negombo\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"uploads\\/prop_69ed1f5666499.jpg\",\"manager_photo\":\"\",\"manager_name\":\"sunil\",\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"amenities\":[\"26\",\"20\",\"19\",\"1\",\"3\",\"5\",\"29\",\"28\",\"39\",\"38\",\"8\",\"37\",\"33\",\"6\",\"32\",\"12\",\"16\",\"14\"],\"special_amenities\":[\"\"],\"rooms\":[{\"image\":\"uploads\\/prop_69ea11dfc93bf.jpg\",\"name\":\"Couple Room\",\"count\":\"1\",\"is_hall\":\"0\",\"adults\":\"2\",\"children\":\"0\",\"price_lkr\":\"12000.00\",\"price_usd\":\"20.00\"},{\"image\":\"uploads\\/prop_69ed1f6e996dd.jpg\",\"name\":\"Single Room\",\"count\":\"1\",\"is_hall\":\"0\",\"adults\":\"1\",\"children\":\"0\",\"price_lkr\":\"8000.00\",\"price_usd\":\"15.00\"}],\"bank_name\":\"Commercial Branch\",\"bank_branch\":\"Negombo\",\"bank_account_name\":\"sunil perera\",\"bank_account_number\":\"21211212\",\"commission_rate\":\"80\",\"property_photos\":[\"uploads\\/prop_69ed1a0587a51.webp\",\"uploads\\/prop_69ed1a08f00b1.jpg\",\"uploads\\/prop_69ed1a0eae36a.jpg\",\"\",\"\"],\"property_videos\":[\"\",\"\"],\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"free_cancellation\":\"1\",\"cancellation_time\":\"24\",\"cancellation_details\":\"\",\"action\":\"request_edit\",\"property_id\":\"2\"}', 'approved', '', '2026-04-25 20:09:34'),
(4, 2, 1, 'edit', '{\"id\":2,\"owner_id\":1,\"business_type\":\"hotel\",\"hotel_category\":\"luxury\",\"property_name\":\"Grand Beach Hotel\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"city\":\"Dankotuwa\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"closest_main_town\":\"Negombo\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"uploads\\/prop_69ed1f5666499.jpg\",\"contact_number\":\"\",\"whatsapp_number\":null,\"business_email\":\"\",\"manager_name\":\"sunil\",\"manager_email\":null,\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"manager_photo\":\"\",\"payout_percentage\":\"80.00\",\"commission_percentage\":\"20.00\",\"allow_payout_requests\":1,\"min_payout_amount\":\"0.00\",\"currency\":\"USD\",\"vat_percentage\":\"0.00\",\"service_charge_percentage\":\"0.00\",\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"id_required\":1,\"cancellation_policy\":\"\",\"created_at\":\"2026-04-23 18:15:56\",\"smoking_allowed\":0,\"pets_allowed\":0,\"events_allowed\":0,\"bank_name\":\"Commercial Branch\",\"bank_account_number\":\"21211212\",\"bank_account_name\":\"sunil perera\",\"bank_branch\":\"Negombo\",\"commission_rate\":80,\"rules_json\":\"[]\",\"popular_amenities_json\":\"[]\",\"custom_rules_json\":\"[]\"}', '{\"business_type\":\"hotel\",\"property_name\":\"Grand Beach Hotel\",\"hotel_category\":\"luxury\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"city\":\"Dankotuwa\",\"closest_main_town\":\"Negombo\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"\",\"manager_photo\":\"\",\"manager_name\":\"sunil\",\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"amenities\":[\"26\",\"20\",\"19\",\"1\",\"3\",\"5\",\"29\",\"28\",\"39\",\"38\",\"8\",\"37\",\"33\",\"6\",\"32\",\"12\",\"16\",\"14\"],\"special_amenities\":[\"\"],\"rooms\":[{\"image\":\"uploads\\/prop_69ea11dfc93bf.jpg\",\"name\":\"Couple Room\",\"count\":\"1\",\"is_hall\":\"0\",\"room_numbers\":\"101\",\"adults\":\"2\",\"children\":\"0\",\"price_lkr\":\"12000.00\",\"price_usd\":\"20.00\"},{\"image\":\"uploads\\/prop_69ed1f6e996dd.jpg\",\"name\":\"Single Room\",\"count\":\"2\",\"is_hall\":\"0\",\"room_numbers\":\"105,106\",\"adults\":\"1\",\"children\":\"0\",\"price_lkr\":\"8000.00\",\"price_usd\":\"15.00\"}],\"bank_name\":\"Commercial Branch\",\"bank_branch\":\"Negombo\",\"bank_account_name\":\"sunil perera\",\"bank_account_number\":\"21211212\",\"commission_rate\":\"80\",\"property_photos\":[\"uploads\\/prop_69ed1a0587a51.webp\",\"uploads\\/prop_69ed1a08f00b1.jpg\",\"uploads\\/prop_69ed1a0eae36a.jpg\",\"\",\"\"],\"property_videos\":[\"\",\"\"],\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"free_cancellation\":\"1\",\"cancellation_time\":\"24\",\"cancellation_details\":\"\",\"action\":\"request_edit\",\"property_id\":\"2\"}', 'approved', '', '2026-04-28 04:49:47'),
(5, 2, 1, 'edit', '{\"id\":2,\"owner_id\":1,\"business_type\":\"hotel\",\"hotel_category\":\"luxury\",\"property_name\":\"Grand Beach Hotel\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"city\":\"Dankotuwa\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"closest_main_town\":\"Negombo\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"\",\"contact_number\":\"\",\"whatsapp_number\":null,\"business_email\":\"\",\"manager_name\":\"sunil\",\"manager_email\":null,\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"manager_photo\":\"\",\"payout_percentage\":\"80.00\",\"commission_percentage\":\"20.00\",\"allow_payout_requests\":1,\"min_payout_amount\":\"0.00\",\"currency\":\"USD\",\"vat_percentage\":\"0.00\",\"service_charge_percentage\":\"0.00\",\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"id_required\":1,\"cancellation_policy\":\"\",\"created_at\":\"2026-04-23 18:15:56\",\"smoking_allowed\":0,\"pets_allowed\":0,\"events_allowed\":0,\"bank_name\":\"Commercial Branch\",\"bank_account_number\":\"21211212\",\"bank_account_name\":\"sunil perera\",\"bank_branch\":\"Negombo\",\"commission_rate\":80,\"rules_json\":\"[]\",\"popular_amenities_json\":\"[]\",\"custom_rules_json\":\"[]\"}', '{\"business_type\":\"hotel\",\"property_name\":\"Grand Beach Hotel\",\"hotel_category\":\"luxury\",\"description\":\"Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.\",\"street_address\":\"2225\\/2\",\"fixed_telephone\":\"+94774829123\",\"mobile_telephone\":\"+94774829123\",\"city\":\"Dankotuwa\",\"closest_main_town\":\"Negombo\",\"district\":\"Gampaha\",\"province\":\"Western\",\"country\":\"Sri Lanka\",\"google_map_location\":\"\",\"closest_police_station\":\"\",\"closest_hospital\":\"\",\"airport_distance\":\"\",\"postal_code\":\"11260\",\"logo_image\":\"\",\"cover_image\":\"uploads\\/prop_69f0432783b32.jpg\",\"manager_photo\":\"\",\"manager_name\":\"sunil\",\"manager_phone\":\"0766302421\",\"manager_nic\":\"1455666\",\"amenities\":[\"26\",\"20\",\"19\",\"1\",\"3\",\"5\",\"29\",\"28\",\"39\",\"38\",\"8\",\"37\",\"33\",\"6\",\"32\",\"12\",\"16\",\"14\"],\"special_amenities\":[\"\"],\"rooms\":[{\"image\":\"uploads\\/prop_69ea11dfc93bf.jpg\",\"name\":\"Couple Room\",\"count\":\"2\",\"is_hall\":\"0\",\"room_numbers\":\"100,101\",\"adults\":\"2\",\"children\":\"0\",\"price_lkr\":\"12000.00\",\"price_usd\":\"20.00\"},{\"image\":\"uploads\\/prop_69ed1f6e996dd.jpg\",\"name\":\"Single Room\",\"count\":\"3\",\"is_hall\":\"0\",\"room_numbers\":\"150,151,152\",\"adults\":\"1\",\"children\":\"0\",\"price_lkr\":\"8000.00\",\"price_usd\":\"15.00\"}],\"bank_name\":\"Commercial Branch\",\"bank_branch\":\"Negombo\",\"bank_account_name\":\"sunil perera\",\"bank_account_number\":\"21211212\",\"commission_rate\":\"80\",\"property_photos\":[\"uploads\\/prop_69ed1a0587a51.webp\",\"uploads\\/prop_69ed1a08f00b1.jpg\",\"uploads\\/prop_69ed1a0eae36a.jpg\",\"\",\"\"],\"property_videos\":[\"\",\"\"],\"check_in_time\":\"14:00:00\",\"check_out_time\":\"12:00:00\",\"cancellation_time\":\"24\",\"cancellation_details\":\"\",\"action\":\"request_edit\",\"property_id\":\"2\"}', 'approved', '', '2026-04-28 05:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `property_rooms`
--

CREATE TABLE `property_rooms` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `total_rooms` int(11) DEFAULT 1,
  `room_numbers` text DEFAULT NULL,
  `adults` int(11) DEFAULT 0,
  `children` int(11) DEFAULT 0,
  `room_image` varchar(255) DEFAULT NULL,
  `price_lkr` decimal(10,2) DEFAULT 0.00,
  `price_usd` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `things_included` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_rooms`
--

INSERT INTO `property_rooms` (`id`, `property_id`, `room_name`, `total_rooms`, `room_numbers`, `adults`, `children`, `room_image`, `price_lkr`, `price_usd`, `description`, `things_included`) VALUES
(7, 2, 'Couple Room', 2, '100,101', 2, 0, 'uploads/prop_69ea11dfc93bf.jpg', 12000.00, 20.00, NULL, NULL),
(8, 2, 'Single Room', 3, '150,151,152', 1, 0, 'uploads/prop_69ed1f6e996dd.jpg', 8000.00, 15.00, NULL, NULL),
(9, 7, 'Couple Room', 1, '101', 2, 0, '', 12000.00, 30.00, NULL, NULL),
(10, 8, 'Couple Room', 2, '101,102', 2, 0, 'uploads/prop_69f06539721f4.webp', 25000.00, 29.00, NULL, NULL),
(11, 17, 'Couple Room', 11, '', 4, 5, '', 50000.00, 148.00, NULL, NULL),
(12, 18, 'Couple Room', 2, '100,101', 2, 0, 'uploads/prop_69f0a46952814.jpg', 1000000.00, 10000000.00, NULL, NULL),
(13, 18, 'Single Room', 1, '105', 1, 0, 'uploads/prop_69f0a46f4f7d1.jpg', 52000.00, 60.00, NULL, NULL),
(14, 19, 'Couple Room', 4, '', 5, 6, 'uploads/prop_69f41d5eccb5f.jpg', 2500.00, 3.50, NULL, NULL),
(15, 19, 'Single Room', 2, '', 2, 0, 'uploads/prop_69f41d7fb7ec7.jpg', 5000.00, 15.00, NULL, NULL),
(16, 19, 'Couple Room', 2, '', 2, 3, '', 60000.00, 186.00, NULL, NULL),
(17, 19, 'Luxury Suite', 3, 'CC1,CC2,CC3', 3, 5, 'uploads/prop_69f41d90eb5c8.jpg', 150000.00, 469.00, NULL, NULL),
(18, 20, 'Single Room', 1, '', 1, 3, 'uploads/prop_69f42b33af8b1.jpg', 5000.00, 15.00, NULL, NULL),
(19, 20, 'Family Room', 15, '150,151,152,153,154,155,156,157,158,159,160,161,162,163,164', 3, 4, 'uploads/prop_69f42b4591e7d.jpg', 30000.00, 93.00, NULL, NULL),
(20, 20, 'Couple Room', 4, '462,163,465,466', 2, 1, 'uploads/prop_69f42bc34ec11.jpg', 40000.00, 125.00, NULL, NULL),
(21, 21, 'Luxury Suite', 5, '', 5, 8, 'uploads/prop_69f431ff19a6e.jpg', 200000.00, 625.00, NULL, NULL),
(22, 21, 'Family Room', 6, '', 2, 4, 'uploads/prop_69f4320150155.jpg', 0.00, 0.00, NULL, NULL),
(23, 21, 'Couple Room', 1, '', 2, 0, 'uploads/prop_69f432315bd06.jpg', 50000.00, 156.00, NULL, NULL),
(24, 23, 'Standard Package', 1, '', 2, 0, 'uploads/prop_69ff12124906e.jpeg', 5000.00, 25.00, 'Breakfast included', 'Welcome Drink'),
(25, 26, 'Couple Room', 4, '01,002,003,004', 2, 0, '', 7000.00, 21.67, '', ''),
(26, 26, 'Single Room', 7, '', 2, 0, '', 5000.00, 15.48, '', ''),
(27, 27, 'Couple Room', 3, '', 1, 0, 'uploads/prop_6a15121a746a6.jpg', 40000.00, 4000.00, '', ''),
(28, 27, 'Single Room', 10, '', 2, 2, 'uploads/prop_6a151238d9ae0.jpg', 50000.00, 2000.00, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `property_staff`
--

CREATE TABLE `property_staff` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `staff_role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_staff_names`
--

CREATE TABLE `property_staff_names` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `staff_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `feedback_type` enum('positive','negative') NOT NULL DEFAULT 'positive',
  `comment` text DEFAULT NULL,
  `status` enum('pending','published','reported') NOT NULL DEFAULT 'pending',
  `report_note` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `property_id`, `booking_id`, `rating`, `feedback_type`, `comment`, `status`, `report_note`, `published_at`, `reported_at`, `created_at`) VALUES
(1, 5, 2, 16, 4, 'positive', 'woooow nice experience', 'published', NULL, '2026-05-12 07:53:27', NULL, '2026-05-12 07:52:40'),
(2, 2, 2, 16, 4, 'positive', 'woooow nice experience', 'published', '', '2026-05-25 07:19:01', '2026-05-25 07:18:59', '2026-05-12 07:53:37');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `total_rooms` int(11) NOT NULL DEFAULT 1,
  `max_adults` int(11) NOT NULL DEFAULT 2,
  `max_children` int(11) NOT NULL DEFAULT 0,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `is_hall` tinyint(1) DEFAULT 0,
  `room_image` varchar(255) DEFAULT NULL,
  `price_lkr` decimal(15,2) DEFAULT 0.00,
  `price_usd` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `nic_passport` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `role` enum('admin','owner','staff','user') DEFAULT 'user',
  `is_disabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dashboard_bg` varchar(255) DEFAULT NULL COMMENT 'Custom hotel dashboard background image path'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `whatsapp_number`, `nic_passport`, `username`, `password`, `address`, `country`, `role`, `is_disabled`, `created_at`, `dashboard_bg`) VALUES
(1, 'Nimesh', 'Bashitha', 'kamala@gmail.com', NULL, NULL, NULL, NULL, '$2y$12$wNrLdQANrufyENG7OQpE.eV3ozt/4dtpeifAv6jATw6hnyFaIItBW', NULL, NULL, 'user', 0, '2026-04-22 18:50:46', 'uploads/dashboard_bg/bg_1_1779762747.jpg'),
(2, 'System', 'Admin', 'admin@bookingjaunt.com', NULL, NULL, NULL, 'admin', '$2y$12$wNrLdQANrufyENG7OQpE.eV3ozt/4dtpeifAv6jATw6hnyFaIItBW', NULL, NULL, 'admin', 0, '2026-04-25 18:53:03', NULL),
(3, 'sunil', 'kumar', 'asasas@sff', NULL, NULL, NULL, NULL, '$2y$10$YsdPKexKZrRIUgjWa40qxeyMMRStLiqj6RZANt1wrwP2b4XuTzRY6', NULL, NULL, 'user', 0, '2026-04-28 05:35:06', NULL),
(4, 'Dulani', 'Perera', 'dulani@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$PGzr29iiK1uG2aZbDYBhaO.KgEuwzP/5w7g6K2dDw3OwtEPEitvzu', NULL, NULL, 'user', 0, '2026-04-28 06:01:56', NULL),
(5, 'Nimesh', 'Bashitha', 'nimeshspc2k17@gmail.com', '212', '1212', NULL, NULL, '$2y$12$wNrLdQANrufyENG7OQpE.eV3ozt/4dtpeifAv6jATw6hnyFaIItBW', NULL, NULL, 'user', 0, '2026-04-28 07:38:45', NULL),
(6, 'Nimala', 'Thero', 'theronimala7@gmail.com', '+94714935454', '+94714935454', NULL, NULL, '$2y$10$R7aieQDWn0iMP5bmOK/dsufWK8xWZSwgpFnCqKRyYGm/lVsq3ydnO', NULL, NULL, 'user', 0, '2026-04-28 08:18:31', NULL),
(7, 'Aluthwawe', 'Nimala Himi', 'saliyadigitalagency@gmail.com', '+94714935454', '+94714935454', '390056784v', NULL, '$2y$10$Dp2FEVe6oRB4m00dAbIC2e4YHloyeBVmR0lhJTuFOckMwkGN5Hw2W', 'No 190, W A Silva Mawatha, Wallawatta, Colobo  06', 'Sri Lanka', 'user', 0, '2026-04-28 11:25:18', NULL),
(8, 'Motive', 'Tales', 'talesmotive@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$QeziIGitFOC8X252L.m40.0Ghc.2Y0NPNHsqMWfSTN1bDJPq9k31y', NULL, NULL, 'user', 0, '2026-04-28 11:30:20', NULL),
(9, 'bashitha', 'test', 'bashithaspc@gmail.com', '1222', '21212', NULL, NULL, '$2y$10$SNWYBw1/AreKULj1SGsfSOSwFkeuWaS6oaC.X21LnqVgGrUCp4sAy', NULL, NULL, 'user', 0, '2026-04-28 12:02:34', NULL),
(10, 'Sanduljith', 'Jerome', 'samarasooriya2007@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$78MZxJjhnY730fNpHpmqbuj.jVjONqpoHT7sDnEIZA22yMH1dbeWe', NULL, NULL, 'user', 0, '2026-04-28 12:04:20', NULL),
(11, 'Dhanushka', 'Lakmal', 'lakmaldhanushka208@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$NuCCrC8wwL2e/LG0e9Q5b.Feghpeyg79hMmkhfweM/.1V16Cu8bM.', NULL, NULL, 'user', 0, '2026-05-01 01:49:19', NULL),
(12, 'Nimala', 'Thero', '+94714935454', NULL, NULL, NULL, NULL, '$2y$10$jRGfmfP4gW58YgdqQE6iCOUKb9HvcXw0zEfUtwopKO4RxFwXDIBt.', NULL, NULL, 'user', 0, '2026-05-01 02:56:46', NULL),
(13, 'Dhanushka', 'Lakmal', 'dhanushka999@gmail.com', '0751145566', '', NULL, NULL, '$2y$10$Nb/J9uiq.1z61NihlPyasOzgjmZFkcpaBVaFDQuZG8UqJjhQVf0du', NULL, NULL, 'user', 0, '2026-05-01 04:42:39', NULL),
(14, 'kt u', 'Malshan', 'mrdevil66099@gmail.com', '+94740621148', 'mrdevil66099@gmail.c', NULL, NULL, '$2y$10$NhPTVMYlfMNtgM0.mQMgX.Gm/KjzFAEaHQx0gXfPNRjvqh436JJl.', NULL, NULL, 'user', 0, '2026-05-03 03:37:54', NULL),
(15, 'Nimala', 'Thero', 'nimalathro@gmail.com', '8714557733', 'Nimala', NULL, NULL, '$2y$10$muR8egaMoC9H/Hjxs3zN6.fARm9WtXf20KeBojkoSZZLpCmRZFzeC', NULL, NULL, 'user', 0, '2026-05-04 03:09:14', NULL),
(16, 'Nimala', 'Thero', 'Nimala', NULL, NULL, NULL, NULL, '$2y$10$sG2NJTOQxKKcbRlF3K1bkuaZC0nhDCly5HF2GzwXD7Itiys3ymVZC', NULL, NULL, 'user', 0, '2026-05-07 04:32:00', NULL),
(17, 'sadun ', 'kumR', 'saddarmawansa@gmail.com', '0714553258', '0714553258', NULL, NULL, '$2y$10$kuPVmIBvH1laziWlAyxYBeFPLc.Nmqqe3nzashpbufzbv2wmWzNEG', NULL, NULL, 'user', 0, '2026-05-09 07:30:25', NULL),
(20, 'Siduranga ', 'Sanjeewa', 'sadaruwan@gmail.com', '+94722255854', ' +94722255854', NULL, NULL, '$2y$10$sB0nk.hsIqL193RKGIrdwuGQam80TJuWijy6ZaWa.qrcws7qVFHdy', NULL, NULL, 'user', 0, '2026-05-13 10:04:37', NULL),
(21, 'samira', 'kumara', 'srilanka@gmail.com', '0754226677', '0754226677', NULL, NULL, '$2y$10$QHuRf/Q9YNb8sXBeciP8NOYcSkX3uLPojj5EeCDJw0buPAw/8ri6m', NULL, NULL, 'user', 0, '2026-05-13 10:20:13', NULL),
(22, 'Polwaththe', 'Pansala', 'polwaththepansala@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$tHWec3Y6ZsYyMoQQ9FBKG..Pi.0c9JrHBdjucQ1mHwcRAEXoW49lK', NULL, NULL, 'user', 0, '2026-05-13 12:11:56', NULL),
(23, 'Dhanushka', 'Lakmal', 'dd@gmail.com', '0711143366', '0711143366', NULL, NULL, '$2y$10$42W2N0GZdIjcTVuHM2IqF.2mpU3.ROCg7uEctGXb5tVFDdD0/F0MO', NULL, NULL, 'user', 0, '2026-05-14 12:07:34', NULL),
(24, 'dln', 'multimidea', 'dlnmultimidea@gmail.com', '0711145156', '0711124578', NULL, NULL, '$2y$10$TlFNo.AIm1.u65yPiAAGwuCSwzx//KgQ2v90vAu22BbOymHsnjynG', NULL, NULL, 'user', 0, '2026-05-26 03:19:01', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_advertisements_package` (`package_id`);

--
-- Indexes for table `advertisement_packages`
--
ALTER TABLE `advertisement_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `amenities_master`
--
ALTER TABLE `amenities_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `booking_expenses`
--
ALTER TABLE `booking_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `boost_packages`
--
ALTER TABLE `boost_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `extra_services`
--
ALTER TABLE `extra_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotel_service_payments`
--
ALTER TABLE `hotel_service_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `popular_destinations`
--
ALTER TABLE `popular_destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `property_amenities`
--
ALTER TABLE `property_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `property_bank_details`
--
ALTER TABLE `property_bank_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_boosts`
--
ALTER TABLE `property_boosts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `property_custom_amenities`
--
ALTER TABLE `property_custom_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_media`
--
ALTER TABLE `property_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_requests`
--
ALTER TABLE `property_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `property_rooms`
--
ALTER TABLE `property_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_staff`
--
ALTER TABLE `property_staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `property_staff_names`
--
ALTER TABLE `property_staff_names`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `advertisement_packages`
--
ALTER TABLE `advertisement_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `amenities_master`
--
ALTER TABLE `amenities_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `booking_expenses`
--
ALTER TABLE `booking_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking_payments`
--
ALTER TABLE `booking_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `boost_packages`
--
ALTER TABLE `boost_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `extra_services`
--
ALTER TABLE `extra_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hotel_service_payments`
--
ALTER TABLE `hotel_service_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `popular_destinations`
--
ALTER TABLE `popular_destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `property_amenities`
--
ALTER TABLE `property_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT for table `property_bank_details`
--
ALTER TABLE `property_bank_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_boosts`
--
ALTER TABLE `property_boosts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `property_custom_amenities`
--
ALTER TABLE `property_custom_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_media`
--
ALTER TABLE `property_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `property_requests`
--
ALTER TABLE `property_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `property_rooms`
--
ALTER TABLE `property_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `property_staff`
--
ALTER TABLE `property_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_staff_names`
--
ALTER TABLE `property_staff_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD CONSTRAINT `fk_advertisements_package` FOREIGN KEY (`package_id`) REFERENCES `advertisement_packages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `property_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_expenses`
--
ALTER TABLE `booking_expenses`
  ADD CONSTRAINT `booking_expenses_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD CONSTRAINT `fk_booking_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `extra_services`
--
ALTER TABLE `extra_services`
  ADD CONSTRAINT `extra_services_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotel_service_payments`
--
ALTER TABLE `hotel_service_payments`
  ADD CONSTRAINT `fk_hotel_service_payments_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_amenities`
--
ALTER TABLE `property_amenities`
  ADD CONSTRAINT `property_amenities_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities_master` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_bank_details`
--
ALTER TABLE `property_bank_details`
  ADD CONSTRAINT `property_bank_details_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_boosts`
--
ALTER TABLE `property_boosts`
  ADD CONSTRAINT `property_boosts_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_boosts_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `boost_packages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `property_custom_amenities`
--
ALTER TABLE `property_custom_amenities`
  ADD CONSTRAINT `property_custom_amenities_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_media`
--
ALTER TABLE `property_media`
  ADD CONSTRAINT `property_media_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_rooms`
--
ALTER TABLE `property_rooms`
  ADD CONSTRAINT `property_rooms_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_staff`
--
ALTER TABLE `property_staff`
  ADD CONSTRAINT `property_staff_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_staff_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_staff_names`
--
ALTER TABLE `property_staff_names`
  ADD CONSTRAINT `property_staff_names_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_types`
--
ALTER TABLE `room_types`
  ADD CONSTRAINT `room_types_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
