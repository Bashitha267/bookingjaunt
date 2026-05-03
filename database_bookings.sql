-- SQL Queries for Bookingjaunt Bookings and Room Management
-- Copy and paste this into your phpMyAdmin SQL tab

-- 1. Add total_rooms to property_rooms to track inventory (e.g., 3 Couple Rooms)
ALTER TABLE `property_rooms` 
ADD COLUMN `total_rooms` INT(11) DEFAULT 1 AFTER `room_name`;

-- 2. Create the bookings table with guest, room, and payment details
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `booking_type` enum('online', 'inplace') DEFAULT 'online',
  `guest_name` varchar(255) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `country` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  
  -- Payment Information
  `price_per_room` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `payment_description` text DEFAULT NULL, -- e.g., 'Advance payment', 'Full payment'
  `payment_status` enum('pending', 'complete') DEFAULT 'pending',
  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,

  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `property_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. (Optional) Update existing rooms to have a default inventory count
UPDATE `property_rooms` SET `total_rooms` = 3 WHERE room_name = 'Couple Room';
UPDATE `property_rooms` SET `total_rooms` = 1 WHERE room_name = 'Single Room';

-- 4. ALTER commands for existing databases (if you already have the bookings table)
-- ALTER TABLE `bookings` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `room_id`;
-- ALTER TABLE `bookings` ADD INDEX (`user_id`);
-- ALTER TABLE `bookings` ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 5. Add room_numbers to track specific room IDs (e.g. 101, 102)
ALTER TABLE `property_rooms` 
ADD COLUMN `room_numbers` TEXT AFTER `total_rooms`;

-- 6. Create booking_expenses table for tracking extra costs
CREATE TABLE IF NOT EXISTS `booking_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `booking_expenses_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
