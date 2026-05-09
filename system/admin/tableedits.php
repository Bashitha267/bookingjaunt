-- SQL edits for advertisement packages

-- 1) Create advertisement packages table
CREATE TABLE IF NOT EXISTS `advertisement_packages` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`package_name` varchar(255) NOT NULL,
	`package_type` varchar(100) NOT NULL,
	`price` decimal(10,2) NOT NULL DEFAULT 0.00,
	`duration_days` int(11) NOT NULL DEFAULT 1,
	`is_active` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp NOT NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Add package reference to advertisements
ALTER TABLE `advertisements`
	ADD COLUMN `package_id` int(11) DEFAULT NULL AFTER `owner_name`,
	ADD CONSTRAINT `fk_advertisements_package`
		FOREIGN KEY (`package_id`) REFERENCES `advertisement_packages` (`id`) ON DELETE SET NULL;

-- 3) (Optional) Store package snapshot info on advertisements
ALTER TABLE `advertisements`
	ADD COLUMN `package_name` varchar(255) DEFAULT NULL AFTER `package_id`,
	ADD COLUMN `package_type` varchar(100) DEFAULT NULL AFTER `package_name`,
	ADD COLUMN `package_price` decimal(10,2) DEFAULT NULL AFTER `package_type`,
	ADD COLUMN `package_duration_days` int(11) DEFAULT NULL AFTER `package_price`,
	ADD COLUMN `package_is_active` tinyint(1) DEFAULT NULL AFTER `package_duration_days`;

-- 4) Insert default advertisement packages based on UI placements
-- Dimensions are approximate references for the admin UI
INSERT INTO `advertisement_packages` (`package_name`, `package_type`, `price`, `duration_days`, `is_active`) VALUES
('Sidebar Vertical Unit (280x180px)', 'sidebar_ad', 5000.00, 30, 1),
('Results Horizontal Strip (500x120px)', 'horizontal_strip_ad', 8000.00, 30, 1),
('Mobile Scroll Unit (260x130px)', 'mobile_scroll_ad', 4000.00, 30, 1);
