-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 27, 2026 at 06:30 AM
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
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `business_type` enum('hotel','reception_hall','hostel','rest_hall','villa','dayouts','safari','resort','apartment','vehicle') DEFAULT NULL,
  `vehicle_category` enum('car','van','suv','jeep','bus','tuk_tuk','motorbike','other') DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `manufactured_year` int(4) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `chassis_number` varchar(100) DEFAULT NULL,
  `engine_number` varchar(100) DEFAULT NULL,
  `vehicle_color` varchar(50) DEFAULT NULL,
  `fuel_type` enum('petrol','diesel','electric','hybrid') DEFAULT NULL,
  `transmission_type` enum('automatic','manual') DEFAULT NULL,
  `vehicle_condition` enum('excellent','good','fair') DEFAULT 'good',
  `seat_count` int(11) DEFAULT 4,
  `luggage_count` int(11) DEFAULT 2,
  `max_passengers` int(11) DEFAULT 4,
  `pricing_type` enum('day_wise','km_wise') DEFAULT 'day_wise',
  `price_per_day` decimal(10,2) DEFAULT 0.00,
  `included_km_per_day` int(11) DEFAULT 0,
  `extra_km_price` decimal(10,2) DEFAULT 0.00,
  `price_per_km` decimal(10,2) DEFAULT 0.00,
  `hourly_price` decimal(10,2) DEFAULT 0.00,
  `weekly_price` decimal(10,2) DEFAULT 0.00,
  `monthly_price` decimal(10,2) DEFAULT 0.00,
  `discount_daily` decimal(5,2) DEFAULT 0.00,
  `discount_weekly` decimal(5,2) DEFAULT 0.00,
  `discount_monthly` decimal(5,2) DEFAULT 0.00,
  `discount_seasonal` decimal(5,2) DEFAULT 0.00,
  `discount_extra_km` decimal(5,2) DEFAULT 0.00,
  `coupon_support` tinyint(1) DEFAULT 0,
  `driver_option` enum('with_driver','without_driver','both') DEFAULT 'both',
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `driver_whatsapp` varchar(20) DEFAULT NULL,
  `driver_nic` varchar(50) DEFAULT NULL,
  `driver_license` varchar(50) DEFAULT NULL,
  `driver_experience` varchar(100) DEFAULT NULL,
  `driver_languages` varchar(255) DEFAULT NULL,
  `has_ac` tinyint(1) DEFAULT 0,
  `has_gps` tinyint(1) DEFAULT 0,
  `has_bluetooth` tinyint(1) DEFAULT 0,
  `has_wifi` tinyint(1) DEFAULT 0,
  `has_music_system` tinyint(1) DEFAULT 0,
  `has_charging_ports` tinyint(1) DEFAULT 0,
  `has_baby_seat` tinyint(1) DEFAULT 0,
  `has_sunroof` tinyint(1) DEFAULT 0,
  `has_reverse_camera` tinyint(1) DEFAULT 0,
  `has_airbags` tinyint(1) DEFAULT 0,
  `insurance_details` text DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `revenue_license_expiry` date DEFAULT NULL,
  `vehicle_reg_doc` varchar(255) DEFAULT NULL,
  `owner_nic_doc` varchar(255) DEFAULT NULL,
  `driver_license_doc` varchar(255) DEFAULT NULL,
  `min_booking_duration` int(11) DEFAULT 1,
  `max_booking_duration` int(11) DEFAULT 30,
  `delivery_available` tinyint(1) DEFAULT 0,
  `pickup_available` tinyint(1) DEFAULT 1,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `delivery_towns_json` text DEFAULT NULL,
  `airport_delivery` tinyint(1) DEFAULT 0,
  `hotel_delivery` tinyint(1) DEFAULT 0,
  `exact_pickup_location` varchar(255) DEFAULT NULL,
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
  `custom_rules_json` text DEFAULT NULL,
  `tourist_attractions` text DEFAULT NULL,
  `closest_fuel_station` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `business_type`, `vehicle_category`, `brand`, `model`, `manufactured_year`, `registration_number`, `chassis_number`, `engine_number`, `vehicle_color`, `fuel_type`, `transmission_type`, `vehicle_condition`, `seat_count`, `luggage_count`, `max_passengers`, `pricing_type`, `price_per_day`, `included_km_per_day`, `extra_km_price`, `price_per_km`, `hourly_price`, `weekly_price`, `monthly_price`, `discount_daily`, `discount_weekly`, `discount_monthly`, `discount_seasonal`, `discount_extra_km`, `coupon_support`, `driver_option`, `driver_name`, `driver_contact`, `driver_whatsapp`, `driver_nic`, `driver_license`, `driver_experience`, `driver_languages`, `has_ac`, `has_gps`, `has_bluetooth`, `has_wifi`, `has_music_system`, `has_charging_ports`, `has_baby_seat`, `has_sunroof`, `has_reverse_camera`, `has_airbags`, `insurance_details`, `insurance_expiry`, `revenue_license_expiry`, `vehicle_reg_doc`, `owner_nic_doc`, `driver_license_doc`, `min_booking_duration`, `max_booking_duration`, `delivery_available`, `pickup_available`, `delivery_fee`, `delivery_towns_json`, `airport_delivery`, `hotel_delivery`, `exact_pickup_location`, `hotel_category`, `property_name`, `description`, `street_address`, `city`, `district`, `province`, `country`, `google_map_location`, `fixed_telephone`, `mobile_telephone`, `closest_police_station`, `closest_hospital`, `airport_distance`, `closest_main_town`, `postal_code`, `logo_image`, `cover_image`, `contact_number`, `whatsapp_number`, `business_email`, `manager_name`, `manager_email`, `manager_phone`, `manager_nic`, `manager_photo`, `payout_percentage`, `commission_percentage`, `allow_payout_requests`, `min_payout_amount`, `currency`, `vat_percentage`, `service_charge_percentage`, `check_in_time`, `check_out_time`, `id_required`, `cancellation_policy`, `created_at`, `smoking_allowed`, `pets_allowed`, `events_allowed`, `bank_name`, `bank_account_number`, `bank_account_name`, `bank_branch`, `commission_rate`, `rules_json`, `popular_amenities_json`, `custom_rules_json`, `tourist_attractions`, `closest_fuel_station`) VALUES
(2, 1, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'luxury', 'Grand Beach Hotel', 'Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.', '2225/2', 'Dankotuwa', 'Gampaha', 'Western', 'Sri Lanka', '', '+94774829123', '+94774829123', '', '', '', 'Negombo', '11260', '', 'uploads/prop_69f0432783b32.jpg', '', NULL, '', 'sunil', NULL, '0766302421', '1455666', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-23 12:45:56', 0, 0, 0, 'Commercial Branch', '21211212', 'sunil perera', 'Negombo', 80, '[]', '[]', '[]', NULL, NULL),
(7, 5, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'luxury', 'testing hotel ', 'dsdsdsd', ' Henpitagedara ', 'Negombo', 'Gampaha', 'Western', 'Sri Lanka', '', '12222', '12222', '', '', '', 'gampaha', '', '', 'uploads/prop_69f0642ba452b.jpg', '', NULL, '', 'dsds', NULL, 'dsd', 'dsdsd', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 07:40:16', 0, 0, 0, 'commercial', '124587', 'sdasd', 'negombo', 80, '{\"no_pets\":\"1\",\"quiet_hours\":\"1\"}', '[\"wifi\",\"pool\",\"parking\"]', '[]', NULL, NULL),
(8, 5, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'super_luxury', 'testh22', 'weaswdddddddddddd', 'dsadasd', 'sdfsf', 'Anuradhapura', 'North Central', 'Sri Lanka', '', 'dsds', 'dsdsd', '', '', '', 'sdfsf', '', '', 'uploads/prop_69f065238bc28.jpg', '', NULL, '', 'dsad', NULL, 'dsdsd', 'dsds', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 07:44:35', 0, 0, 0, 'commercial', '124587', 'sdasd', 'negombo', 80, '{\"quiet_hours\":\"1\"}', '[\"pool\",\"parking\"]', '[]', NULL, NULL),
(17, 8, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'hotel in samal', 'iughiugiugiu8uij', 'MATHALE ROAD KURUNDANKUKLAMA ', 'KURUNDANKULAMA ', 'Anuradhapura', 'North Central', 'Sri Lanka', '', '0711124046', '0711124046', '', '', '', 'ANURADHAPURA ', '', 'uploads/prop_69f09e82ebe32.jpg', 'uploads/prop_69f09e89a10ca.jpg', '', NULL, '', 'DHANUSHKA LAKMLA', NULL, '0711124046', '140015345V', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 11:50:30', 0, 0, 0, 'BANK OF CEYLOAN ', '000254587458', 'DHANUSHKA', 'ANURADHAPURA', 80, '[]', '[]', '[]', NULL, NULL),
(18, 10, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'luxury', 'hotel carl', 'luxary', '225/5 Henpitagedera Road', 'Marandagahamula', 'Galle', 'Eastern', 'Sri Lanka', '', '+94723173372', '+94723173372', '', '', '', 'Marandagahamula', '11870', '', 'uploads/prop_69f0a4fb7dfa3.jpg', '', NULL, '', 'Sanduljith', NULL, 'Samarasooriya', '200722201406', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-28 12:17:59', 0, 0, 0, 'commercial ', '1234688 19', 'Jeraome', 'yuhh', 80, '{\"no_alcohol\":\"1\"}', '[\"wifi\",\"pool\",\"parking\"]', '[]', NULL, NULL),
(19, 6, 'reception_hall', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'Samarasiri Hotel ', 'Located in a convenient and peaceful area, [Hotel Name] offers comfortable and affordable accommodation for travelers who seek value and simplicity. Our rooms are clean, well-maintained, and equipped with essential facilities including free Wi-Fi, air conditioning, and 24-hour service.\r\n\r\nWhether you are traveling for business or leisure, our friendly staff ensures a pleasant and hassle-free stay. Enjoy easy access to nearby attractions, local restaurants, and transport facilities.\r\n\r\nAt [Hotel Name], we believe in providing quality service at an affordable price — making your stay comfortable without breaking your budget', 'No 720/A, Bandaranayaka Mawatha, Maharagama ', 'Maharagama ', 'Colombo', 'Western', 'Sri Lanka', '', '+94714935454', '+94714935454', 'Maharagama ', 'Maharagama', '250', 'Maharagama', '32608', 'uploads/prop_69f41c92e924b.jpg', 'uploads/prop_69f41c9667c6b.jpg', '', NULL, '', 'Sidhuranga Bandara ', NULL, '0711145052', '128452454v', 'uploads/prop_69f06dfc59ce7.png', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-01 03:34:16', 0, 0, 0, 'Bank Of Ceylon ', '000322355484', 'Dhanushaka Lankamakl', 'Anuradhapura', 80, '{\"no_alcohol\":\"1\",\"no_smoking\":\"1\",\"no_parties\":\"1\",\"no_pets\":\"1\",\"quiet_hours\":\"1\",\"no_outside_food\":\"1\"}', '[]', '[]', NULL, NULL),
(20, 6, 'hostel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'Mileniyam Hotel ', 'Experience elegance and comfort at [Hotel Name], where modern luxury meets warm hospitality. Designed to offer a relaxing and stylish stay, our hotel features beautifully furnished rooms, high-speed Wi-Fi, fine dining restaurants, and personalized guest services.\r\n\r\nWake up to stunning views, unwind in our premium facilities, and enjoy a peaceful atmosphere tailored for both leisure and business travelers.\r\n\r\nOur dedicated team is committed to delivering exceptional service, ensuring every guest enjoys a memorable and refined experience.\r\n\r\nAt [Hotel Name], every moment is crafted to offer comfort, sophistication, and unforgettable memories', 'mathale road ', 'Anuradhapura', 'Anuradhapura', 'North Central', 'Sri Lanka', '', '0713855047', '0713855047', 'Anuradhapura', 'Anuradhapura', '250', 'Anuradhapura', '50000', 'uploads/prop_69f42a5c2e561.jpg', 'uploads/prop_69f42a6a5d555.jpg', '', NULL, '', 'Manjula ', NULL, '0752355684', '945084957v', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-01 04:29:24', 0, 0, 0, 'Bank Of Ceylon ', '000322355484', 'Dhanushaka Lankamakl', 'Anuradhapura', 80, '[]', '[]', '[]', NULL, NULL),
(21, 13, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'super_luxury', 'hetel with Sha', 'Experience elegance and comfort at [Hotel Name], where modern luxury meets warm hospitality. Designed to offer a relaxing and stylish stay, our hotel features beautifully furnished rooms, high-speed Wi-Fi, fine dining restaurants, and personalized guest services.\r\n\r\nWake up to stunning views, unwind in our premium facilities, and enjoy a peaceful atmosphere tailored for both leisure and business travelers.\r\n\r\nOur dedicated team is committed to delivering exceptional service, ensuring every guest enjoys a memorable and refined experience.\r\n\r\nAt [Hotel Name], every moment is crafted to offer comfort, sophistication, and unforgettable memories', 'No 159/A, Siriwardardana Mawatha wellala, Kandy ', 'Kandy', 'Kandy', 'Central', 'Sri Lanka', '', '0722255854', '0722255854', '', '', '', 'Kandy', '', 'uploads/prop_69f4316644994.jpg', 'uploads/prop_69f4316ae2e73.jpg', '', NULL, '', 'Kalhara', NULL, '0755526547', '780058847v', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-01 04:56:43', 0, 0, 0, 'bgi', '0002215254782', 'sloklk', 'iugiuj', 80, '[]', '[]', '[]', NULL, NULL),
(22, 17, 'dayouts', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'sadun', 'yugy8t8yt8t7ygi8yg8t8', 'noi 123 nihy hftryb ', 'kandy', 'Kandy', 'Central', 'Sri Lanka', '', '0714887744', '0714887744', '', '', '', 'kandy', '', 'uploads/prop_69fee2fc56e8a.jpg', 'uploads/prop_69fee2ff92aad.jpg', '', NULL, '', 'sasdun', NULL, 'kumara', '538855987v', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-09 07:34:10', 0, 0, 0, 'i9i9-0o', '6576564764465', '8y90uoi0', 'lojoijoi', 80, '[]', '[]', '[]', NULL, NULL),
(23, 5, 'dayouts', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'Day out test', 'dsdsdasdadsd', 'sdasd', 'gampaha', 'Gampaha', 'Western', 'Sri Lanka', '', '4586', '4785', '', '', '', 'gampaha', '', '', 'uploads/prop_69ff11ca61ad2.jpg', '', NULL, '', 'dsdsd', NULL, '456', '4455', '', 80.00, 20.00, 0, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-09 10:53:56', 0, 0, 0, 'commercial', '1221', 'sdasd', 'negombo', 80, '[]', '[\"wifi\",\"pool\",\"parking\"]', '[]', NULL, NULL),
(24, 15, 'dayouts', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'Colombo hotel', 'Hagahsssbsbs', 'Mathale Road ,', 'Colombo', 'Colombo', 'Western', 'Sri Lanka', '', '0787565456', '0787565456', '', '', '', 'Colombo ', '50000', '', '', '', NULL, '', 'Sadanu', NULL, 'Kumara', '94776565v', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-09 11:02:11', 0, 0, 0, 'Fggh', '2356754444', '&8=$$$%&&', 'Ggvbb', 80, '[]', '[]', '[]', NULL, NULL),
(26, 21, 'hotel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'Siduranga Hotel In Anuradhapura', 'oeiho0igoijoithroiktjroithroitrjoryoitr', 'MATHALE ROAD KURUNDANKUKLAMA ', 'KURUNDANKULAMA ', 'Anuradhapura', 'North Central', 'Sri Lanka', '', '0711124046', '0711124046', '', '', '', 'ANURADHAPURA ', '', 'uploads/prop_6a0450a4c7e8f.jpg', 'uploads/prop_6a0450a5d176b.jpg', '', NULL, '', 'DHANUSHKA LAKMLA', NULL, '0711124046', '140015345V', 'uploads/prop_6a0450b8c420f.png', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-13 12:06:45', 0, 0, 0, 'BANK OF CEYLOAN ', '000254587458', 'DHANUSHKA', 'ANURADHAPURA', 80, '[]', '[]', '[]', NULL, NULL),
(27, 24, 'villa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'good', 4, 2, 4, 'day_wise', 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, NULL, 0, 0, NULL, 'budget_friendly', 'ij\'l\'[pk[pfr', 'wgqhgwe4', 'eqdb rhyjryjyrjyyt', 'Ampara', 'Ampara', 'Eastern', 'Sri Lanka', '', '0711124547', '0711124784', '', '', '', 'Ampara', '', 'uploads/prop_6a1511c103933.jpg', 'uploads/prop_6a1511bc3280f.jpg', '', NULL, '', 'Sameera', NULL, '0712225544', '530054877v', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-26 03:24:40', 0, 0, 0, 'boc', '5654654984654', 'siduranga', 'anuradhapura', 80, '[]', '[]', '[]', NULL, NULL),
(29, 1, 'vehicle', 'suv', 'Honda', 'Fit', 2014, '456789', '', '', 'white', 'hybrid', 'automatic', 'excellent', 4, 2, 4, 'day_wise', 15000.00, 150, 150.00, 150.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'without_driver', '', '', '', '', '', '', '', 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 30, 0, 1, 0.00, '[\"Negombo\"]', 0, 0, 'Negombo', NULL, 'Honda  Fit', '', '', 'Negombo', 'Gampaha', '', 'Sri Lanka', '', '', '+94766302421', '', '', '', 'Negombo', '', '', 'uploads/prop_6a167c2e0a3fe.jpg', '', '', '', '', NULL, '', '', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-05-27 05:08:11', 0, 0, 0, 'Commercial Branch', '12323333333', 'Kamal Perera', 'Kandy', 80, '[]', '[]', '[]', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
