ALTER TABLE `properties` 
ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
ADD COLUMN `approved_by` INT(11) NULL DEFAULT NULL,
ADD COLUMN `approval_timestamp` TIMESTAMP NULL DEFAULT NULL;

-- Automatically approve all existing properties
UPDATE `properties` SET `approval_status` = 'approved';
