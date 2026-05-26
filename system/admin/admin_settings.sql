-- SQL script to create the admin settings table for background customization.
-- Run this in your database or copy-paste it into phpMyAdmin.

CREATE TABLE IF NOT EXISTS `admin_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default empty value for background path
INSERT INTO `admin_settings` (`setting_key`, `setting_value`) 
VALUES ('background_path', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
