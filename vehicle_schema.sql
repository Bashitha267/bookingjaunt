-- ============================================================
-- BookingJaunt Vehicle Rental Module - Schema Changes
-- Run this in phpMyAdmin SQL tab (copy & paste entire file)
-- ============================================================

-- Step 1: Extend properties.business_type enum to include 'vehicle'
ALTER TABLE `properties`
  MODIFY COLUMN `business_type` enum(
    'hotel','reception_hall','hostel','rest_hall','villa',
    'dayouts','safari','resort','apartment','vehicle'
  ) DEFAULT NULL;

-- Step 2: Add vehicle-specific columns to properties table
ALTER TABLE `properties`
  ADD COLUMN `vehicle_category` enum('car','van','suv','jeep','bus','tuk_tuk','motorbike','other') DEFAULT NULL AFTER `business_type`,
  ADD COLUMN `brand` varchar(100) DEFAULT NULL AFTER `vehicle_category`,
  ADD COLUMN `model` varchar(100) DEFAULT NULL AFTER `brand`,
  ADD COLUMN `manufactured_year` int(4) DEFAULT NULL AFTER `model`,
  ADD COLUMN `registration_number` varchar(50) DEFAULT NULL AFTER `manufactured_year`,
  ADD COLUMN `chassis_number` varchar(100) DEFAULT NULL AFTER `registration_number`,
  ADD COLUMN `engine_number` varchar(100) DEFAULT NULL AFTER `chassis_number`,
  ADD COLUMN `vehicle_color` varchar(50) DEFAULT NULL AFTER `engine_number`,
  ADD COLUMN `fuel_type` enum('petrol','diesel','electric','hybrid') DEFAULT NULL AFTER `vehicle_color`,
  ADD COLUMN `transmission_type` enum('automatic','manual') DEFAULT NULL AFTER `fuel_type`,
  ADD COLUMN `vehicle_condition` enum('excellent','good','fair') DEFAULT 'good' AFTER `transmission_type`,
  ADD COLUMN `seat_count` int(11) DEFAULT 4 AFTER `vehicle_condition`,
  ADD COLUMN `luggage_count` int(11) DEFAULT 2 AFTER `seat_count`,
  ADD COLUMN `max_passengers` int(11) DEFAULT 4 AFTER `luggage_count`,
  ADD COLUMN `pricing_type` enum('day_wise','km_wise') DEFAULT 'day_wise' AFTER `max_passengers`,
  ADD COLUMN `price_per_day` decimal(10,2) DEFAULT 0.00 AFTER `pricing_type`,
  ADD COLUMN `included_km_per_day` int(11) DEFAULT 0 AFTER `price_per_day`,
  ADD COLUMN `extra_km_price` decimal(10,2) DEFAULT 0.00 AFTER `included_km_per_day`,
  ADD COLUMN `price_per_km` decimal(10,2) DEFAULT 0.00 AFTER `extra_km_price`,
  ADD COLUMN `hourly_price` decimal(10,2) DEFAULT 0.00 AFTER `price_per_km`,
  ADD COLUMN `weekly_price` decimal(10,2) DEFAULT 0.00 AFTER `hourly_price`,
  ADD COLUMN `monthly_price` decimal(10,2) DEFAULT 0.00 AFTER `weekly_price`,
  ADD COLUMN `discount_daily` decimal(5,2) DEFAULT 0.00 AFTER `monthly_price`,
  ADD COLUMN `discount_weekly` decimal(5,2) DEFAULT 0.00 AFTER `discount_daily`,
  ADD COLUMN `discount_monthly` decimal(5,2) DEFAULT 0.00 AFTER `discount_weekly`,
  ADD COLUMN `discount_seasonal` decimal(5,2) DEFAULT 0.00 AFTER `discount_monthly`,
  ADD COLUMN `discount_extra_km` decimal(5,2) DEFAULT 0.00 AFTER `discount_seasonal`,
  ADD COLUMN `coupon_support` tinyint(1) DEFAULT 0 AFTER `discount_extra_km`,
  ADD COLUMN `driver_option` enum('with_driver','without_driver','both') DEFAULT 'both' AFTER `coupon_support`,
  ADD COLUMN `driver_name` varchar(255) DEFAULT NULL AFTER `driver_option`,
  ADD COLUMN `driver_contact` varchar(20) DEFAULT NULL AFTER `driver_name`,
  ADD COLUMN `driver_whatsapp` varchar(20) DEFAULT NULL AFTER `driver_contact`,
  ADD COLUMN `driver_nic` varchar(50) DEFAULT NULL AFTER `driver_whatsapp`,
  ADD COLUMN `driver_license` varchar(50) DEFAULT NULL AFTER `driver_nic`,
  ADD COLUMN `driver_experience` varchar(100) DEFAULT NULL AFTER `driver_license`,
  ADD COLUMN `driver_languages` varchar(255) DEFAULT NULL AFTER `driver_experience`,
  ADD COLUMN `has_ac` tinyint(1) DEFAULT 0 AFTER `driver_languages`,
  ADD COLUMN `has_gps` tinyint(1) DEFAULT 0 AFTER `has_ac`,
  ADD COLUMN `has_bluetooth` tinyint(1) DEFAULT 0 AFTER `has_gps`,
  ADD COLUMN `has_wifi` tinyint(1) DEFAULT 0 AFTER `has_bluetooth`,
  ADD COLUMN `has_music_system` tinyint(1) DEFAULT 0 AFTER `has_wifi`,
  ADD COLUMN `has_charging_ports` tinyint(1) DEFAULT 0 AFTER `has_music_system`,
  ADD COLUMN `has_baby_seat` tinyint(1) DEFAULT 0 AFTER `has_charging_ports`,
  ADD COLUMN `has_sunroof` tinyint(1) DEFAULT 0 AFTER `has_baby_seat`,
  ADD COLUMN `has_reverse_camera` tinyint(1) DEFAULT 0 AFTER `has_sunroof`,
  ADD COLUMN `has_airbags` tinyint(1) DEFAULT 0 AFTER `has_reverse_camera`,
  ADD COLUMN `insurance_details` text DEFAULT NULL AFTER `has_airbags`,
  ADD COLUMN `insurance_expiry` date DEFAULT NULL AFTER `insurance_details`,
  ADD COLUMN `revenue_license_expiry` date DEFAULT NULL AFTER `insurance_expiry`,
  ADD COLUMN `vehicle_reg_doc` varchar(255) DEFAULT NULL AFTER `revenue_license_expiry`,
  ADD COLUMN `owner_nic_doc` varchar(255) DEFAULT NULL AFTER `vehicle_reg_doc`,
  ADD COLUMN `driver_license_doc` varchar(255) DEFAULT NULL AFTER `owner_nic_doc`,
  ADD COLUMN `min_booking_duration` int(11) DEFAULT 1 AFTER `driver_license_doc`,
  ADD COLUMN `max_booking_duration` int(11) DEFAULT 30 AFTER `min_booking_duration`,
  ADD COLUMN `delivery_available` tinyint(1) DEFAULT 0 AFTER `max_booking_duration`,
  ADD COLUMN `pickup_available` tinyint(1) DEFAULT 1 AFTER `delivery_available`,
  ADD COLUMN `delivery_fee` decimal(10,2) DEFAULT 0.00 AFTER `pickup_available`,
  ADD COLUMN `delivery_towns_json` text DEFAULT NULL AFTER `delivery_fee`,
  ADD COLUMN `airport_delivery` tinyint(1) DEFAULT 0 AFTER `delivery_towns_json`,
  ADD COLUMN `hotel_delivery` tinyint(1) DEFAULT 0 AFTER `airport_delivery`,
  ADD COLUMN `exact_pickup_location` varchar(255) DEFAULT NULL AFTER `hotel_delivery`;

-- Step 3: Make room_id nullable in bookings (vehicles have no rooms)
ALTER TABLE `bookings`
  DROP FOREIGN KEY `bookings_ibfk_2`;

ALTER TABLE `bookings`
  MODIFY COLUMN `room_id` int(11) DEFAULT NULL;

ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `property_rooms` (`id`) ON DELETE SET NULL;

-- Step 4: Add vehicle-specific columns to bookings table
ALTER TABLE `bookings`
  ADD COLUMN `booking_category` enum('hotel','vehicle') DEFAULT 'hotel' AFTER `booking_type`,
  ADD COLUMN `vehicle_with_driver` tinyint(1) DEFAULT 0 AFTER `booking_category`,
  ADD COLUMN `delivery_needed` tinyint(1) DEFAULT 0 AFTER `vehicle_with_driver`,
  ADD COLUMN `airport_pickup` tinyint(1) DEFAULT 0 AFTER `delivery_needed`,
  ADD COLUMN `guest_driving_license` varchar(50) DEFAULT NULL AFTER `airport_pickup`,
  ADD COLUMN `pickup_time` time DEFAULT NULL AFTER `guest_driving_license`,
  ADD COLUMN `return_time` time DEFAULT NULL AFTER `pickup_time`,
  ADD COLUMN `num_passengers` int(11) DEFAULT 1 AFTER `return_time`,
  ADD COLUMN `num_luggage` int(11) DEFAULT 0 AFTER `num_passengers`,
  ADD COLUMN `special_requests` text DEFAULT NULL AFTER `num_luggage`,
  ADD COLUMN `emergency_contact` varchar(50) DEFAULT NULL AFTER `special_requests`,
  ADD COLUMN `security_deposit` decimal(10,2) DEFAULT 0.00 AFTER `emergency_contact`,
  ADD COLUMN `advance_payment` decimal(10,2) DEFAULT 0.00 AFTER `security_deposit`;

-- Done! All changes applied successfully.
-- ============================================================
