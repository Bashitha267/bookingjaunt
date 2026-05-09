-- SQL Queries for New Tables and Admin User
-- Copy and Paste this into your phpMyAdmin SQL tab

-- 1. Create property_requests table for Admin Approvals
CREATE TABLE IF NOT EXISTS `property_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('edit','delete') NOT NULL,
  `old_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add Admin User (Username: admin, Password: admin123)
-- Only run this if you haven't added the admin user yet
INSERT INTO `users` (`first_name`, `last_name`, `email`, `username`, `password`, `role`) 
VALUES (
    'System', 
    'Admin', 
    'admin@bookingjaunt.com', 
    'admin', 
    '$2y$12$wNrLdQANrufyENG7OQpE.eV3ozt/4dtpeifAv6jATw6hnyFaIItBW', 
    'admin'
) ON DUPLICATE KEY UPDATE `role` = 'admin';

-- 3. (Optional) If you haven't updated your users table for contact numbers manually
-- ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone_number` varchar(20) DEFAULT NULL AFTER `email`;
-- ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `whatsapp_number` varchar(20) DEFAULT NULL AFTER `phone_number`;

-- 4. Create hero_images table for hero section slideshow images
CREATE TABLE IF NOT EXISTS `hero_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
