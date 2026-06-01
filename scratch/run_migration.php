<?php
require_once __DIR__ . '/../config.php';

$sql = "ALTER TABLE `properties`
ADD COLUMN `pay_cash` tinyint(1) DEFAULT 0,
ADD COLUMN `pay_cc` tinyint(1) DEFAULT 0,
ADD COLUMN `pay_debit` tinyint(1) DEFAULT 0,
ADD COLUMN `pay_online` tinyint(1) DEFAULT 0,
ADD COLUMN `pay_bank` tinyint(1) DEFAULT 0,
ADD COLUMN `pay_installments` tinyint(1) DEFAULT 0,
ADD COLUMN `refund_supported` tinyint(1) DEFAULT NULL,
ADD COLUMN `advance_payment_required` tinyint(1) DEFAULT NULL,
ADD COLUMN `payment_notes` text DEFAULT NULL,
ADD COLUMN `custom_payments_json` text DEFAULT NULL,

ADD COLUMN `food_breakfast_included` tinyint(1) DEFAULT 0,
ADD COLUMN `food_breakfast_type` varchar(100) DEFAULT NULL,
ADD COLUMN `food_restaurant_available` tinyint(1) DEFAULT 0,
ADD COLUMN `food_restaurant_count` int(11) DEFAULT NULL,
ADD COLUMN `food_room_service` tinyint(1) DEFAULT 0,
ADD COLUMN `food_room_service_247` tinyint(1) DEFAULT 0,
ADD COLUMN `food_vegetarian` tinyint(1) DEFAULT 0,
ADD COLUMN `food_vegan` tinyint(1) DEFAULT 0,
ADD COLUMN `food_halal` tinyint(1) DEFAULT 0,
ADD COLUMN `food_buffet` tinyint(1) DEFAULT 0,
ADD COLUMN `food_delivery_allowed` tinyint(1) DEFAULT 0,
ADD COLUMN `food_dietary_options` tinyint(1) DEFAULT 0,
ADD COLUMN `food_kitchen_in_room` tinyint(1) DEFAULT 0,
ADD COLUMN `food_minibar` tinyint(1) DEFAULT 0,
ADD COLUMN `food_notes` text DEFAULT NULL,
ADD COLUMN `custom_food_json` text DEFAULT NULL,

ADD COLUMN `sec_staff_247` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_cctv` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_cctv_coverage` text DEFAULT NULL,
ADD COLUMN `sec_smoke_detectors` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_fire_extinguishers` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_fire_alarm` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_emergency_exit_plan` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_emergency_evac_instructions` text DEFAULT NULL,
ADD COLUMN `sec_patrol_frequency` varchar(100) DEFAULT NULL,
ADD COLUMN `sec_key_card_access` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_digital_lock` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_biometric_access` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_safe_box` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_luggage_storage` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_parking_security` varchar(100) DEFAULT NULL,
ADD COLUMN `sec_female_floor` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_panic_button` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_first_aid` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_medical_support` tinyint(1) DEFAULT 0,
ADD COLUMN `sec_hospital_distance` varchar(100) DEFAULT NULL,
ADD COLUMN `sec_notes` text DEFAULT NULL,
ADD COLUMN `custom_security_json` text DEFAULT NULL;";

try {
    // Check if one of the columns already exists to avoid duplicate column errors
    $check = $pdo->query("SHOW COLUMNS FROM `properties` LIKE 'pay_cash'");
    if ($check->rowCount() > 0) {
        echo "Columns already exist. Migration skipped.\n";
        exit;
    }

    $pdo->exec($sql);
    echo "Database columns added successfully!\n";
} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
