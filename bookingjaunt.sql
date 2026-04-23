-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 02:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookingjaunt`
--

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
(15, 'Services', 'Security', 'fa-user-shield', 0),
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
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `business_type` enum('hotel','reception_hall','hostel','rest_hall') NOT NULL,
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
  `commission_rate` int(11) DEFAULT 80
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `business_type`, `hotel_category`, `property_name`, `description`, `street_address`, `city`, `district`, `province`, `country`, `google_map_location`, `fixed_telephone`, `mobile_telephone`, `closest_police_station`, `closest_hospital`, `airport_distance`, `closest_main_town`, `postal_code`, `logo_image`, `cover_image`, `contact_number`, `whatsapp_number`, `business_email`, `manager_name`, `manager_email`, `manager_phone`, `manager_nic`, `manager_photo`, `payout_percentage`, `commission_percentage`, `allow_payout_requests`, `min_payout_amount`, `currency`, `vat_percentage`, `service_charge_percentage`, `check_in_time`, `check_out_time`, `id_required`, `cancellation_policy`, `created_at`, `smoking_allowed`, `pets_allowed`, `events_allowed`, `bank_name`, `bank_account_number`, `bank_account_name`, `bank_branch`, `commission_rate`) VALUES
(2, 1, 'hotel', 'luxury', 'Grand Beach Hotel', 'Experience comfort by the ocean  Our beachside hotel in Sri Lanka offers cozy rooms, breathtaking sea views, and the perfect escape from busy life.', '2225/2', 'Dankotuwa', 'Gampaha', 'Western', 'Sri Lanka', '', '+94774829123', '+94774829123', '', '', '', 'Negombo', '11260', '', 'uploads/prop_69ea11815095a.jpg', '', NULL, '', 'sunil', NULL, '0766302421', '1455666', '', 80.00, 20.00, 1, 0.00, 'USD', 0.00, 0.00, '14:00:00', '12:00:00', 1, '', '2026-04-23 12:45:56', 0, 0, 0, NULL, NULL, NULL, NULL, 80);

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
(36, 2, 14);

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

-- --------------------------------------------------------

--
-- Table structure for table `property_rooms`
--

CREATE TABLE `property_rooms` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `adults` int(11) DEFAULT 0,
  `children` int(11) DEFAULT 0,
  `room_image` varchar(255) DEFAULT NULL,
  `price_lkr` decimal(10,2) DEFAULT 0.00,
  `price_usd` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_rooms`
--

INSERT INTO `property_rooms` (`id`, `property_id`, `room_name`, `adults`, `children`, `room_image`, `price_lkr`, `price_usd`) VALUES
(1, 2, 'Couple Room', 2, 0, 'uploads/prop_69ea11dfc93bf.jpg', 12000.00, 20.00),
(2, 2, 'Single Room', 1, 0, 'uploads/prop_69ea11e58818f.jpg', 8000.00, 15.00);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `whatsapp_number`, `nic_passport`, `username`, `password`, `address`, `country`, `role`, `created_at`) VALUES
(1, 'Nimesh', 'Bashitha', 'kamala@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$Wwg08TSDs18I2DjkPonVZ.6ELxkHEf5V25xS7P2HIYpfG68sqwdJO', NULL, NULL, 'user', '2026-04-22 18:50:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amenities_master`
--
ALTER TABLE `amenities_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `extra_services`
--
ALTER TABLE `extra_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

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
-- AUTO_INCREMENT for table `amenities_master`
--
ALTER TABLE `amenities_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `extra_services`
--
ALTER TABLE `extra_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `property_amenities`
--
ALTER TABLE `property_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `property_bank_details`
--
ALTER TABLE `property_bank_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_custom_amenities`
--
ALTER TABLE `property_custom_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_media`
--
ALTER TABLE `property_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_rooms`
--
ALTER TABLE `property_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `extra_services`
--
ALTER TABLE `extra_services`
  ADD CONSTRAINT `extra_services_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

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
